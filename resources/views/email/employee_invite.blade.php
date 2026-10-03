<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Thank you Email</title>
</head>

<body style="margin:0; padding:50px 0; background-color:#f6f6f6; font-family:Arial, Helvetica, sans-serif;">
    <table width="600" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="margin: 0 auto">
        <tr>
            <td align="center"
                style="padding:16px 16px; background-image: linear-gradient(to right, #202DD4, #13105D);">
                <img src="{{ $site_url }}/public/images/logo.png" alt="X-US Logo" width="223" style="display:block; max-width:100%;">
            </td>
        </tr>
        <tr>
            <td align="center" style="padding:40px 15px 70px 15px; border: 1px solid #EAEAEA;">
                <img src="{{ $site_url }}/public/images/AcceptImg.png" alt="Invitation" width="461"
                    style="display:block; max-width:100%; margin-bottom: 30px;">
                <h2 style="color:#333;margin-bottom:10px;">You are invited by <b>{{ $companyName }}</b></h2>
                
                <p style="margin:0 0 40px; font-weight:400; font-size:24px; color:#2B2F32;">
                    to manage their Invoice System.<br>
                    Please accept the invitation by clicking below link.
                </p>
                <a href="{{ $acceptUrl }}"
                    style="color:#202DD4; padding:15px 30px; min-width: 260px; font-size:18px; font-weight:500; text-decoration:none; border: 2px solid #202DD4; border-radius:12px; display:inline-block; box-sizing:border-box;">
                    Accept Invitation
                </a>
            </td>
        </tr>
    </table>
</body>

</html>