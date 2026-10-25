{{--
    Minimal mail layout owned by gp247/front (the shop mail layout is not available
    on a site without gp247/shop). Inline styles only — mail clients ignore CSS files.

    @aidlc-unit front-contact
    @aidlc-story US-front-contact-notify-mail
--}}
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body style="margin:0;padding:0;background-color:#f5f8fa;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f8fa;padding:24px 12px;font-family:Arial,Helvetica,sans-serif;">
    <tr>
        <td align="center">
            <table width="570" cellpadding="0" cellspacing="0" style="max-width:570px;width:100%;background-color:#ffffff;border-radius:6px;">
                <tr>
                    <td style="padding:24px 32px;color:#2f3133;font-size:14px;line-height:1.6;">
                        <p style="margin:0 0 16px;font-size:16px;font-weight:bold;">{{ $storeTitle ?? '' }}</p>
                        @yield('main')
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
