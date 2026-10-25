{{--
    Alert to the store about a new contact message. Visitor text is escaped; line
    breaks kept with nl2br(e()).
    Variables (pre-rendered by ContactMailer): $storeTitle, $intro, $labels,
    $contact = [name, email, phone, title, content]

    @aidlc-unit front-contact
    @aidlc-story US-front-contact-notify-mail
--}}
@extends('gp247-front::email.front_layout')

@section('main')
<p style="margin:0 0 16px;">{{ $intro }}</p>
<table cellpadding="6" cellspacing="0" style="width:100%;border-collapse:collapse;font-size:14px;">
    <tr><td style="color:#74787e;width:30%;">{{ $labels['name'] }}</td><td>{{ $contact['name'] }}</td></tr>
    <tr><td style="color:#74787e;">{{ $labels['email'] }}</td><td>{{ $contact['email'] }}</td></tr>
    @if (!empty($contact['phone']))
        <tr><td style="color:#74787e;">{{ $labels['phone'] }}</td><td>{{ $contact['phone'] }}</td></tr>
    @endif
    <tr><td style="color:#74787e;">{{ $labels['title'] }}</td><td>{{ $contact['title'] }}</td></tr>
</table>
<div style="margin-top:16px;padding:12px;background-color:#f5f8fa;border-radius:4px;">{!! nl2br(e($contact['content'])) !!}</div>
@endsection
