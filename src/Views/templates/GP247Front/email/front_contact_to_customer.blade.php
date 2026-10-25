{{--
    Confirmation to the visitor. Deliberately generic: the address was typed by an
    anonymous visitor, so nothing they wrote (name, subject, body) is echoed — the
    form must not become a way to send chosen text to anyone (RISK-SEC-contact-spam-relay).
    Variables (pre-rendered by ContactMailer): $storeTitle, $intro

    @aidlc-unit front-contact
    @aidlc-story US-front-contact-notify-mail
    @aidlc-adr front-contact_contact-form-in-front
--}}
@extends('gp247-front::email.front_layout')

@section('main')
<p style="margin:0;">{{ $intro }}</p>
@endsection
