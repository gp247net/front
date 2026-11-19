/*!
 * GP247 consent loader — runs tracking code that the server printed in dormant form
 * (<script type="text/plain" data-gp247-consent="analytics|marketing">"<json string>"</script>)
 * once its cookie group may run, and shows the cookie banner. No dependencies, no build step.
 *
 * Unit tracking-consent · ADR tracking-consent_deferred-script-activation,
 * tracking-consent_head-injection-via-meta-head · stories US-tracking-consent-preset-services,
 * US-tracking-consent-custom-code, US-tracking-consent-template-hook, US-tracking-consent-banner,
 * US-tracking-consent-consent-gate, US-tracking-consent-google-consent-mode,
 * US-tracking-consent-commerce-events (ADR tracking-consent_commerce-event-contract).
 */
(function (window, doc) {
    'use strict';

    var DORMANT = 'script[type="text/plain"][data-gp247-consent]';
    var COOKIE = 'gp247_consent';
    var MAX_AGE = 15552000; // 180 days (Q11: six months)
    var GROUPS = ['analytics', 'marketing'];
    // Cookies the preset services set, by group; dropped when that group is not accepted (ADR §7).
    var SERVICE_COOKIES = { analytics: /^(_ga|_gid|_gat)/, marketing: /^(_gcl|_fbp|_fbc)/ };
    var bodyOpenMarker = null;
    var config = null;
    var ui = null;
    var googleDefaultSent = false;

    /**
     * Loader settings printed by the server (mode, policy version, labels).
     * @returns {{mode: string, version: number, labels: Object}|null}
     */
    function readConfig() {
        var el = doc.getElementById('gp247-tracking-config');
        if (!el) {
            return null;
        }
        try {
            return JSON.parse(el.textContent || '{}');
        } catch (e) {
            return null;
        }
    }

    /**
     * The visitor's stored choice.
     * @returns {{groups: Array<string>, current: boolean}|null} Accepted groups and whether they
     *     were given for the current policy version; null when the visitor never chose.
     */
    function readChoice() {
        var match = doc.cookie.match(/(?:^|;\s*)gp247_consent=([^;]*)/);
        if (!match) {
            return null;
        }
        try {
            var value = JSON.parse(decodeURIComponent(match[1]));
            if (!value || !Array.isArray(value.c)) {
                return null;
            }
            return {
                groups: value.c.filter(function (group) { return GROUPS.indexOf(group) !== -1; }),
                // WHY: a raised policy version means the owner wants every visitor asked again.
                current: String(value.v) === String(config.version)
            };
        } catch (e) {
            return null;
        }
    }

    /**
     * Whether code of a cookie group may run now.
     * off: everything runs. notice: runs unless the visitor switched the group off (a
     * refusal stays a refusal while the visitor is asked again). optin: runs only after
     * the visitor accepted the group for the current policy version.
     * @param {string} group analytics|marketing
     * @returns {boolean}
     */
    function allowed(group) {
        if (GROUPS.indexOf(group) === -1) {
            return false;
        }
        if (config.mode === 'off') {
            return true;
        }
        var choice = readChoice();
        if (choice && (choice.current || config.mode === 'notice')) {
            return choice.groups.indexOf(group) !== -1;
        }
        return config.mode === 'notice';
    }

    /**
     * @returns {boolean} Whether the visitor still has to choose (never did, or the version was raised).
     */
    function needsChoice() {
        var choice = readChoice();
        return !choice || !choice.current;
    }

    /**
     * Insert a node in its region: <head>, start of <body> (in paste order) or end of <body>.
     * @param {Node} node
     * @param {string} region head|body_open|body_close
     */
    function place(node, region) {
        if (region === 'body_open' && doc.body) {
            if (!bodyOpenMarker) {
                bodyOpenMarker = doc.createComment('gp247-body-open');
                doc.body.insertBefore(bodyOpenMarker, doc.body.firstChild);
            }
            doc.body.insertBefore(node, bodyOpenMarker);
        } else if (region === 'body_close' && doc.body) {
            doc.body.appendChild(node);
        } else {
            doc.head.appendChild(node);
        }
    }

    /**
     * place() that never throws.
     * WHY: the browser can throw from insertBefore/appendChild when pasted code has a
     * syntax error; one broken block must not stop the blocks of other services.
     * @param {Node} node
     * @param {string} region
     * @returns {boolean} Whether the node was inserted without an error.
     */
    function safePlace(node, region) {
        try {
            place(node, region);
            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * A script element the browser runs (scripts parsed from HTML text never run).
     * @param {HTMLScriptElement} source
     * @returns {HTMLScriptElement}
     */
    function liveScript(source) {
        var script = doc.createElement('script');
        for (var i = 0; i < source.attributes.length; i++) {
            script.setAttribute(source.attributes[i].name, source.attributes[i].value);
        }
        if (source.src && !source.hasAttribute('async')) {
            script.async = false;
        }
        script.text = source.text;
        return script;
    }

    /**
     * Whether the next node must wait for this script's load / error event.
     * WHY the checks: `nomodule` or a non-JavaScript type never fires either event, and
     * waiting for it would stop every later block.
     * @param {HTMLScriptElement} source
     * @returns {boolean}
     */
    function waitsForLoad(source) {
        var type = (source.getAttribute('type') || '').toLowerCase();
        var runs = type === '' || type === 'module' || type.indexOf('javascript') !== -1 || type.indexOf('ecmascript') !== -1;
        return !!source.src && runs && !source.hasAttribute('async') && !source.hasAttribute('nomodule');
    }

    /**
     * Insert the nodes of one block one after another; an external script without
     * `async` is awaited first, so code that depends on it runs in paste order.
     * @param {Array<Node>} nodes
     * @param {string} region
     * @param {Function} done
     */
    function insertNodes(nodes, region, done) {
        if (!nodes.length) {
            done();
            return;
        }
        var node = nodes.shift();
        var next = function () { insertNodes(nodes, region, done); };

        // WHY: tracking code never shows text; loose text outside any tag (JavaScript
        // pasted without <script>, a stray word) would appear at the top or bottom of the page.
        if (node.nodeType !== 1) {
            next();
            return;
        }

        if (node.tagName === 'SCRIPT') {
            var script = liveScript(node);
            var wait = waitsForLoad(node);
            if (wait) {
                script.onload = script.onerror = next;
            }
            if (!safePlace(script, region) || !wait) {
                next();
            }
            return;
        }

        var copy = doc.importNode(node, true);
        var inner = copy.querySelectorAll('script');
        for (var i = 0; i < inner.length; i++) {
            inner[i].parentNode.replaceChild(liveScript(inner[i]), inner[i]);
        }
        safePlace(copy, region);
        next();
    }

    /**
     * Turn one dormant element into live code.
     * @param {HTMLScriptElement} el
     * @param {Function} done
     */
    function activate(el, done) {
        var html;
        try {
            html = JSON.parse(el.textContent || '""');
        } catch (e) {
            done();
            return;
        }
        var template = doc.createElement('template');
        template.innerHTML = String(html);
        // WHY: <template> is parsed as if scripting were off, so <noscript> content becomes
        // real elements — the no-JS pixel / iframe of a pasted snippet would load as well.
        var fallbacks = template.content.querySelectorAll('noscript');
        for (var i = 0; i < fallbacks.length; i++) {
            fallbacks[i].parentNode.removeChild(fallbacks[i]);
        }
        insertNodes(Array.prototype.slice.call(template.content.childNodes), el.getAttribute('data-gp247-region') || 'head', done);
    }

    /**
     * Google Consent Mode v2, basic: everything denied by default, then the visitor's
     * groups granted. Sent before any Google tag runs and again on every change (ADR §5).
     */
    function sendGoogleConsent() {
        // WHY any block, not only the GA4 / GTM fields: Google tags pasted as custom code need
        // the denied default as well before they run.
        if (!doc.querySelector(DORMANT)) {
            return;
        }
        window.dataLayer = window.dataLayer || [];
        var gtag = function () { window.dataLayer.push(arguments); };
        var state = function (group) { return allowed(group) ? 'granted' : 'denied'; };
        if (!googleDefaultSent) {
            googleDefaultSent = true;
            gtag('consent', 'default', { ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', analytics_storage: 'denied' });
        }
        var marketing = state('marketing');
        gtag('consent', 'update', { analytics_storage: state('analytics'), ad_storage: marketing, ad_user_data: marketing, ad_personalization: marketing });
    }

    /**
     * Activate, in page order, every dormant block whose group may run.
     */
    function run() {
        config = config || readConfig();
        if (!config) {
            return;
        }
        var pending = Array.prototype.filter.call(doc.querySelectorAll(DORMANT), function (el) {
            return !el.hasAttribute('data-gp247-done') && allowed(el.getAttribute('data-gp247-consent'));
        });
        sendGoogleConsent();
        // WHY mark now: a second run() while this chain waits for a script must not pick
        // the same blocks again (they would run twice).
        pending.forEach(function (el) { el.setAttribute('data-gp247-done', '1'); });
        var step = function () {
            if (pending.length) {
                activate(pending.shift(), step);
            } else {
                flushCommerce();
            }
        };
        step();
    }

    // ---- E-commerce events (TC-3) -------------------------------------------------

    var META_EVENTS = { view_item: 'ViewContent', add_to_cart: 'AddToCart', begin_checkout: 'InitiateCheckout', purchase: 'Purchase' };
    var PURCHASE_KEY = 'gp247_purchase_sent';
    var CHECKOUT_KEY = 'gp247_checkout_sent';
    var MAX_PURCHASES = 20;
    var PAYLOAD_TEXT = ['transaction_id', 'currency'];
    var PAYLOAD_NUMBERS = ['value', 'tax', 'shipping'];
    var ITEM_TEXT = ['item_id', 'item_name', 'item_category', 'item_brand'];
    var ITEM_NUMBERS = ['price', 'quantity'];
    var commerceQueue = [];

    /**
     * Copy only the allowed fields, as text or numbers (NFR-SEC-tracking-no-personal-data).
     * WHY here too: browser events come from any plugin or template, not through the server.
     * @param {Object} payload
     * @returns {Object}
     */
    function cleanPayload(payload) {
        var copy = function (source, texts, numbers) {
            var out = {};
            texts.forEach(function (key) {
                if (typeof source[key] === 'string' || typeof source[key] === 'number') { out[key] = String(source[key]); }
            });
            numbers.forEach(function (key) {
                if (source[key] !== undefined && source[key] !== null && source[key] !== '' && !isNaN(Number(source[key]))) { out[key] = Number(source[key]); }
            });
            return out;
        };
        var clean = copy(payload, PAYLOAD_TEXT, PAYLOAD_NUMBERS);
        clean.items = (Array.isArray(payload.items) ? payload.items : []).slice(0, 100).filter(function (item) {
            return item && typeof item === 'object';
        }).map(function (item) { return copy(item, ITEM_TEXT, ITEM_NUMBERS); });
        return clean;
    }

    /**
     * A checkout start identical to the last one sent in this browser tab session.
     * WHY: reloading the checkout page, or coming back from a cancelled payment, is not a
     * new start; sending it again would inflate the funnel.
     * @param {Object} payload
     * @returns {boolean}
     */
    function repeatedCheckout(payload) {
        var signature = JSON.stringify([payload.currency, payload.value, payload.items.map(function (item) { return [item.item_id, item.quantity]; })]);
        try {
            if (window.sessionStorage.getItem(CHECKOUT_KEY) === signature) {
                return true;
            }
            window.sessionStorage.setItem(CHECKOUT_KEY, signature);
        } catch (e) {
            return false;
        }
        return false;
    }

    /**
     * Order ids whose purchase this browser already sent (localStorage may be blocked).
     * @returns {Array<string>}
     */
    function sentPurchases() {
        try {
            var list = JSON.parse(window.localStorage.getItem(PURCHASE_KEY) || '[]');
            return Array.isArray(list) ? list : [];
        } catch (e) {
            return [];
        }
    }

    /**
     * @param {string} id Order id just sent.
     */
    function rememberPurchase(id) {
        try {
            var list = sentPurchases().filter(function (known) { return known !== id; });
            list.push(id);
            window.localStorage.setItem(PURCHASE_KEY, JSON.stringify(list.slice(-MAX_PURCHASES)));
        } catch (e) {
            // WHY ignore: private mode / blocked storage; GA4 still drops duplicates by transaction_id.
        }
    }

    /**
     * Queue one event for every service of the page.
     * WHY drop a known purchase: a thank-you page restored from the browser cache must not
     * count the order twice (ADR §5, RISK-TECH-tracking-purchase-double-count).
     * @param {{type: string, payload: Object}} event
     */
    function queueCommerce(event) {
        if (!event || !META_EVENTS[event.type] || !event.payload || typeof event.payload !== 'object') {
            return;
        }
        var payload = cleanPayload(event.payload);
        var id = payload.transaction_id;
        if (event.type === 'purchase' && (!id || sentPurchases().indexOf(String(id)) !== -1)) {
            return;
        }
        if (event.type === 'begin_checkout' && repeatedCheckout(payload)) {
            return;
        }
        commerceQueue.push({ type: event.type, payload: payload, sent: {}, started: false });
    }

    /**
     * A preset service whose code is running and whose group the visitor accepted.
     * @param {string} service ga4|gtm|pixel
     * @returns {boolean}
     */
    function serviceReady(service) {
        var el = doc.querySelector(DORMANT + '[data-gp247-service="' + service + '"]');
        if (!el || !el.hasAttribute('data-gp247-done') || !allowed(el.getAttribute('data-gp247-consent'))) {
            return false;
        }
        if (service === 'ga4') {
            return typeof window.gtag === 'function';
        }
        if (service === 'pixel') {
            return typeof window.fbq === 'function';
        }
        return Array.isArray(window.dataLayer);
    }

    /**
     * Hand one event to one service, in the format that service reads.
     * @param {string} service
     * @param {string} type
     * @param {Object} p Payload: currency, value, items[], transaction_id.
     */
    function sendCommerce(service, type, p) {
        var items = Array.isArray(p.items) ? p.items : [];
        var params = { currency: p.currency, value: p.value, items: items };
        ['transaction_id', 'tax', 'shipping'].forEach(function (key) {
            if (p[key] !== undefined) {
                params[key] = p[key];
            }
        });
        if (service === 'ga4') {
            // WHY gtag(), not a dataLayer object: gtag.js reads only its own command calls.
            window.gtag('event', type, params);
        } else if (service === 'gtm') {
            // WHY clear first: GTM merges ecommerce objects of consecutive pushes.
            window.dataLayer.push({ ecommerce: null });
            window.dataLayer.push({ event: type, ecommerce: params });
        } else if (service === 'pixel') {
            window.fbq('track', META_EVENTS[type], {
                content_ids: items.map(function (item) { return item.item_id; }),
                contents: items.map(function (item) { return { id: item.item_id, quantity: item.quantity, item_price: item.price }; }),
                content_type: 'product',
                value: p.value,
                currency: p.currency
            });
        }
    }

    /**
     * Send every queued event to every ready service that has not had it yet — at once,
     * or as soon as the visitor accepts the service's group on this page.
     */
    function flushCommerce() {
        ['ga4', 'gtm', 'pixel'].forEach(function (service) {
            if (!serviceReady(service)) {
                return;
            }
            commerceQueue.forEach(function (event) {
                if (event.sent[service]) {
                    return;
                }
                // WHY check again: another tab of the same thank-you page may have sent the
                // order while this one waited for consent.
                if (event.type === 'purchase' && !event.started && sentPurchases().indexOf(String(event.payload.transaction_id)) !== -1) {
                    event.sent = { ga4: true, gtm: true, pixel: true };
                    return;
                }
                event.started = true;
                event.sent[service] = true;
                try {
                    sendCommerce(service, event.type, event.payload);
                } catch (e) {
                    return;
                }
                if (event.type === 'purchase') {
                    rememberPurchase(String(event.payload.transaction_id));
                }
            });
        });
    }

    /**
     * Events printed by the server for this page.
     */
    function readPageEvents() {
        var el = doc.getElementById('gp247-commerce-events');
        if (!el) {
            return;
        }
        try {
            var events = JSON.parse(el.textContent || '[]');
            (Array.isArray(events) ? events : []).forEach(queueCommerce);
        } catch (e) {
            // WHY ignore: a broken block must not stop the tracking code of the page.
        }
    }

    // Events of Livewire actions (add to cart) and of plugins / templates (open contract).
    window.addEventListener('gp247-commerce', function (event) {
        var detail = event.detail || {};
        queueCommerce({ type: detail.type, payload: detail.payload });
        if (config) {
            flushCommerce();
        }
    });

    /**
     * Expire the cookies of the groups the visitor did not accept, on this host and its parents.
     * WHY whatever the earlier state: cookies set while the banner was off (or before the
     * version was raised) must go when the visitor refuses.
     * @param {Array<string>} accepted
     */
    function dropServiceCookies(accepted) {
        var parts = window.location.hostname.split('.');
        doc.cookie.split(';').forEach(function (pair) {
            var name = pair.split('=')[0].trim();
            var refused = GROUPS.some(function (group) {
                return accepted.indexOf(group) === -1 && SERVICE_COOKIES[group].test(name);
            });
            if (!refused) {
                return;
            }
            doc.cookie = name + '=; max-age=0; path=/';
            for (var i = 0; i < parts.length - 1; i++) {
                doc.cookie = name + '=; max-age=0; path=/; domain=.' + parts.slice(i).join('.');
            }
        });
    }

    /**
     * Store the visitor's choice and apply it at once (no reload).
     * @param {Array<string>} groups Accepted groups.
     */
    function save(groups) {
        doc.cookie = COOKIE + '=' + encodeURIComponent(JSON.stringify({ v: config.version, c: groups, t: Math.floor(Date.now() / 1000) }))
            + '; max-age=' + MAX_AGE + '; path=/; SameSite=Lax' + (window.location.protocol === 'https:' ? '; Secure' : '');
        dropServiceCookies(groups);
        var opener = ui ? ui.opener : null;
        hide();
        run();
        if (opener && opener.focus) { opener.focus(); }
    }

    /**
     * Copy the banner out of its inert <template> once.
     * @returns {boolean} Whether the page has a banner.
     */
    function mountBanner() {
        if (ui) {
            return true;
        }
        var source = doc.getElementById('gp247-consent-banner');
        if (!source || !doc.body) {
            return false;
        }
        var root = doc.createElement('div');
        root.appendChild(doc.importNode(source.content, true));
        // WHY first in <body>: keyboard users reach the banner first (it is fixed on screen anyway).
        doc.body.insertBefore(root, doc.body.firstChild);
        ui = {
            bar: root.querySelector('[data-gp247-consent-banner]'),
            panel: root.querySelector('[data-gp247-consent-panel]'),
            opener: null,
            padding: null
        };
        // WHY: a template's banner view may not mark its parts hidden; start() decides.
        if (ui.bar) { ui.bar.hidden = true; }
        if (ui.panel) { ui.panel.hidden = true; }
        root.addEventListener('click', onClick);
        root.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && ui.panel && !ui.panel.hidden) {
                closePanel();
            }
        });
        return true;
    }

    /**
     * Show the bar or the panel; keep the bottom of the page reachable on small screens.
     * @param {Element|null} el
     * @param {boolean} visible
     */
    function toggle(el, visible) {
        if (!el) {
            return;
        }
        el.hidden = !visible;
        if (visible) {
            ui.padding = ui.padding === null ? doc.body.style.paddingBottom : ui.padding;
            doc.body.style.paddingBottom = el.offsetHeight + 'px';
        }
    }

    /**
     * Hide the banner and the panel and give the page its own bottom padding back.
     */
    function hide() {
        if (!ui) {
            return;
        }
        if (ui.bar) { ui.bar.hidden = true; }
        if (ui.panel) { ui.panel.hidden = true; }
        if (ui.padding !== null) {
            doc.body.style.paddingBottom = ui.padding;
            ui.padding = null;
        }
    }

    /**
     * Open the "customise" panel with the current state ticked.
     * @param {Element|null} opener Element to give the focus back to.
     */
    function openPanel(opener) {
        if (!mountBanner()) {
            return;
        }
        ui.opener = opener || null;
        // WHY: a template's banner without the panel still lets the visitor change their mind.
        if (!ui.panel) {
            toggle(ui.bar, true);
            return;
        }
        Array.prototype.forEach.call(ui.panel.querySelectorAll('[data-gp247-consent-category]'), function (box) {
            box.checked = allowed(box.getAttribute('data-gp247-consent-category'));
        });
        if (ui.bar) { ui.bar.hidden = true; }
        toggle(ui.panel, true);
        var first = ui.panel.querySelector('[data-gp247-consent-category]');
        if (first) { first.focus(); }
    }

    /**
     * Close the panel; back to the bar while the visitor has not chosen yet.
     */
    function closePanel() {
        hide();
        if (needsChoice()) {
            toggle(ui.bar, true);
        }
        if (ui.opener && ui.opener.focus) { ui.opener.focus(); }
    }

    /**
     * Buttons of the banner and the panel.
     * @param {MouseEvent} event
     */
    function onClick(event) {
        var target = event.target.closest ? event.target.closest('button') : null;
        if (!target) {
            return;
        }
        if (target.hasAttribute('data-gp247-consent-accept')) {
            save(GROUPS.slice());
        } else if (target.hasAttribute('data-gp247-consent-reject')) {
            save([]);
        } else if (target.hasAttribute('data-gp247-consent-customize')) {
            openPanel(target);
        } else if (target.hasAttribute('data-gp247-consent-save')) {
            save(Array.prototype.filter.call(ui.panel.querySelectorAll('[data-gp247-consent-category]'), function (box) {
                return box.checked;
            }).map(function (box) { return box.getAttribute('data-gp247-consent-category'); }));
        } else if (target.hasAttribute('data-gp247-consent-close')) {
            closePanel();
        }
    }

    /**
     * "Cookie settings": the template's own elements, or a small floating button.
     */
    function setUpReopen() {
        var openers = doc.querySelectorAll('[data-gp247-consent-open]');
        if (!openers.length) {
            var button = doc.createElement('button');
            button.type = 'button';
            button.setAttribute('data-gp247-consent-open', '');
            button.setAttribute('data-testid', 'tracking-consent-floating-settings');
            button.textContent = (config.labels && config.labels.settings) || 'Cookie settings';
            button.style.cssText = 'position:fixed;inset-inline-start:12px;inset-block-end:12px;z-index:2147482999;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;background:#fff;color:#1f2937;font:12px/1.4 inherit;cursor:pointer';
            doc.body.appendChild(button);
            openers = [button];
        }
        Array.prototype.forEach.call(openers, function (el) {
            el.hidden = false;
            el.addEventListener('click', function (event) {
                event.preventDefault();
                openPanel(el);
            });
        });
    }

    /**
     * Banner on first visit (or after the policy version was raised) and the way back to it.
     */
    function start() {
        config = config || readConfig();
        readPageEvents();
        run();
        if (!config || config.mode === 'off' || !mountBanner()) {
            return;
        }
        setUpReopen();
        if (needsChoice()) {
            toggle(ui.bar, true);
        }
    }

    window.gp247Consent = { run: run, open: function () { openPanel(null); } };
    start();
})(window, document);
