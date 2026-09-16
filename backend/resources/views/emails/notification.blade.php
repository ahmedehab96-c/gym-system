<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5; padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:12px; overflow:hidden;">
                    <tr>
                        <td style="background-color:#111827; padding:24px 32px;">
                            <span style="color:#ffffff; font-size:16px; font-weight:600;">{{ $tenantName }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <h1 style="margin:0 0 16px; font-size:18px; color:#111827;">{{ $subjectLine }}</h1>
                            <p style="margin:0; font-size:14px; line-height:1.6; color:#374151;">{{ $bodyText }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px; border-top:1px solid #e5e7eb;">
                            <p style="margin:0; font-size:11px; color:#9ca3af;">
                                This is an automated message from {{ $tenantName }}. You are receiving it based on your notification preferences.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
