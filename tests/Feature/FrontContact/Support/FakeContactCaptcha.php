<?php

namespace GP247\Front\Tests\Feature\FrontContact\Support;

/**
 * Stand-in captcha plugin for the contact-form tests: the same surface the core
 * captcha helpers call on a real captcha plugin (configKey, appPath, getField,
 * validate, msgError), with a fixed correct answer.
 *
 * @aidlc-unit front-contact
 * @aidlc-story US-front-contact-anti-spam
 */
class FakeContactCaptcha
{
    public const CONFIG_KEY = 'ZzFakeContactCaptcha';

    public const FIELD = 'zz_captcha_answer';

    public const ANSWER = 'right';

    /** @var string Config key whose truthy value means "plugin enabled". */
    public $configKey = self::CONFIG_KEY;

    /** @var string View namespace of the captcha widget (none registered: render is skipped). */
    public $appPath = 'zz-fake-contact-captcha';

    /**
     * @return string Request field carrying the captcha answer.
     */
    public function getField(): string
    {
        return self::FIELD;
    }

    /**
     * @param mixed $value Answer typed by the visitor.
     * @return bool Whether it is the expected answer.
     */
    public function validate($value): bool
    {
        return $value === self::ANSWER;
    }

    /**
     * @return string Validation message.
     */
    public function msgError(): string
    {
        return 'ZZ captcha is wrong';
    }
}
