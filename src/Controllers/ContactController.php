<?php

namespace GP247\Front\Controllers;

use GP247\Front\Models\FrontContact;
use GP247\Front\Models\FrontPage;
use GP247\Front\Support\ContactMailer;
use GP247\Front\Support\ContactSettings;
use GP247\Front\Support\FrontLayoutPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Public contact form: shows the form (under the CMS page "contact" when there is
 * one) and stores a visitor's message, then mails. While the form is switched off
 * the address keeps serving that CMS page, as before the form existed.
 *
 * @aidlc-unit front-contact
 * @aidlc-story US-front-contact-toggle, US-front-contact-submit, US-front-contact-anti-spam
 * @aidlc-adr front-contact_contact-form-in-front
 */
class ContactController extends RootFrontController
{
    /**
     * Hidden field humans never fill; a value means a bot. WHY a non-semantic name:
     * browsers and password managers autofill fields named like "website"/"url",
     * which would silently drop real messages.
     */
    public const HONEYPOT = 'cf_hp_field';

    /** Fields the visitor may send. */
    private const FIELDS = ['name', 'email', 'phone', 'title', 'content'];

    /**
     * GET {lang?}/contact{suffix}.
     *
     * @param mixed ...$params Route params ([lang] when SEO language URLs are on).
     * @return mixed View or response.
     */
    public function showProcessFront(...$params)
    {
        $lang = $this->switchLanguage($params);

        if (!ContactSettings::enabled()) {
            // WHY delegate: a site may already have a CMS page with alias "contact".
            return (new HomeController())->pageDetailProcessFront(...(GP247_SEO_LANG ? [$lang, 'contact'] : ['contact']));
        }

        $title = gp247_language_render('contact.page_title');

        return view(gp247_front_view('screen.front_contact'), [
            'title' => $title,
            'description' => '',
            'keyword' => '',
            'page' => (new FrontPage())->getDetail('contact', 'alias'),
            'viewCaptcha' => gp247_captcha_processview('contact', gp247_language_render('action.submit')),
            'honeypot' => self::HONEYPOT,
            'layout_page' => FrontLayoutPage::Contact->value,
            'breadcrumbs' => [['url' => '', 'title' => $title]],
        ]);
    }

    /**
     * POST {lang?}/contact{suffix} (throttled in the route).
     *
     * @param Request $request
     * @param mixed   ...$params Route params.
     * @return mixed Redirect or 404 response.
     */
    public function submitProcessFront(Request $request, ...$params)
    {
        $this->switchLanguage($params);

        if (!ContactSettings::enabled()) {
            return $this->pageNotFound();
        }

        // Bots get the same answer as people, so the trap is not revealed.
        if (trim((string) $request->input(self::HONEYPOT, '')) !== '') {
            return $this->thanked();
        }

        $data = $request->only(self::FIELDS);
        $rules = [
            'name' => 'required|string|max:100',
            'email' => 'required|string|email:filter|max:150',
            'phone' => 'nullable|string|max:30',
            'title' => 'required|string|max:200',
            'content' => 'required|string|max:5000',
        ];
        $captcha = gp247_captcha_method();
        if ($captcha && in_array('contact', gp247_captcha_page())) {
            $data['captcha_field'] = $request->input($captcha->getField(), '');
            $rules['captcha_field'] = ['required', 'string', new \GP247\Core\Rules\CaptchaRule()];
        }

        $validator = Validator::make($data, $rules, [], $this->attributeLabels());
        if ($validator->fails()) {
            return redirect(gp247_route_front('front.contact'))
                ->withErrors($validator)
                ->withInput($request->only(self::FIELDS));
        }

        $contact = FrontContact::receive(array_intersect_key($validator->validated(), array_flip(self::FIELDS)), config('app.storeId'));
        (new ContactMailer())->send($contact);

        return $this->thanked();
    }

    /**
     * @param array<int, mixed> $params Route params.
     * @return string Language segment ('' when SEO language URLs are off).
     */
    private function switchLanguage(array $params): string
    {
        if (!GP247_SEO_LANG) {
            return '';
        }
        $lang = (string) ($params[0] ?? '');
        gp247_lang_switch($lang);

        return $lang;
    }

    /**
     * @return RedirectResponse Back to an empty form with the thank-you notice.
     */
    private function thanked(): RedirectResponse
    {
        return redirect(gp247_route_front('front.contact'))
            ->with('contact_sent', gp247_language_render('contact.thank_contact'));
    }

    /**
     * @return array<string, string> Field => translated label for validation messages.
     */
    private function attributeLabels(): array
    {
        return [
            'name' => gp247_language_render('contact.name'),
            'email' => gp247_language_render('contact.email'),
            'phone' => gp247_language_render('contact.phone'),
            'title' => gp247_language_render('contact.subject'),
            'content' => gp247_language_render('contact.content'),
        ];
    }
}
