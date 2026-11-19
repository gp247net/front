/*!
 * GP247 consent loader — runs tracking code that the server printed in dormant form
 * (<script type="text/plain" data-gp247-consent="analytics|marketing">"<json string>"</script>)
 * once its cookie group may run, and shows the cookie banner. No dependencies, no build step.
 *
 * Unit tracking-consent · ADR tracking-consent_deferred-script-activation,
 * tracking-consent_head-injection-via-meta-head · stories US-tracking-consent-preset-services,
 * US-tracking-consent-custom-code, US-tracking-consent-template-hook, US-tracking-consent-banner,
 * US-tracking-consent-consent-gate, US-tracking-consent-google-consent-mode.
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
            }
        };
        step();
    }

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
