<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment Successful</title>
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
               
                <p style="margin:0 0 18px; padding:0 40px; font-weight:700; font-size:24px; color:#2B2F32;">
                    Payment Successful 🎉
                </p>

                <p style="margin:0 0 25px; font-weight:400; font-size:18px; color:#2B2F32;">
                    Dear {{ $user_name }},<br><br>
                    We’ve successfully received your payment of
			<strong>{{ $plan_name }}</strong> plan.
                </p>

                <p style="margin:0 0 30px; font-weight:400; font-size:18px; color:#2B2F32;">
                    Your plan is now active and will be valid until
                    <strong>{{ $expiry_date }}</strong>.
                </p>

                <a href="{{ $acceptUrl }}"
                    style="color:#ffffff; background-color:#202DD4; padding:15px 30px; min-width: 260px; font-size:18px; font-weight:500; text-decoration:none; border-radius:12px; display:inline-block; box-sizing:border-box;">
                    Go to Dashboard
                </a>

                <p style="margin-top:40px; font-size:14px; color:#7A7A7A;">
                    Date: {{ $payment_date }}
                </p>
            </td>
        </tr>
    </table>
</body>

</html>
