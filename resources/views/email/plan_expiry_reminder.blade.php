<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Plan Expiry Reminder</title>
</head>

<body style="margin:0; padding:50px 0; background-color:#f6f6f6; font-family:Arial, Helvetica, sans-serif;">
    <table width="600" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="margin: 0 auto">
        <tr>
            <td align="center"
                style="padding:16px 16px; background-image: linear-gradient(to right, #202DD4, #13105D);">
                <img src="{{ $site_url }}/public/images/logo.png" alt="X-US Logo" width="223"
                    style="display:block; max-width:100%;">
            </td>
        </tr>
        <tr>
            <td align="center" style="padding:40px 15px 70px 15px; border: 1px solid #EAEAEA;">
                <img src="{{ $site_url }}/public/images/ReminderImg.png" alt="Reminder" width="461"
                    style="display:block; max-width:100%; margin-bottom: 30px;">
                <p style="margin:0 0 18px; padding:0 50px; font-weight:700; font-size:24px; color:#2B2F32;">
                    Your {{ $plan_name }} plan is about to expire.
                </p>
                <p style="margin:0 0 40px; font-weight:400; font-size:20px; color:#2B2F32;">
                    It will expire on <strong>{{ $expiry_date }}</strong>.<br>
                    Renew automatically.
                </p>
            </td>
        </tr>
    </table>
</body>

</html>
