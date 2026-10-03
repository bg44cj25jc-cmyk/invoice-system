<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0;
            color: #555555;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
            padding: 0 20px;
        }
        .header {
            background-color: #4CAF50;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 26px;
        }
        .content {
            padding: 30px 20px;
            line-height: 1.6;
        }
        .content h2 {
            font-size: 24px;
            color: #333333;
            margin-bottom: 20px;
        }
        .content p {
            margin: 0 0 20px;
        }
        .content a.button {
            display: inline-block;
            padding: 5px 10px 5px 10px;
            margin: 20px 0;
            border-radius: 5px;
            background-color: #4CAF50;
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            text-align: center;
        }
        .footer {
            background-color: #f7f7f7;
            color: #888888;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            border-top: 1px solid #dddddd;
        }
        @media (max-width: 600px) {
            .container {
                width: 95%;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Welcome to {{$site_name}}</h1>
    </div>
    <div class="content">
        <h2>Hello {{$name}},</h2>
        <p>Your request has been approved! Please log in using the information below:</p>
        <p>Email Address/UserId: {{$email}}<br/>
            Password: {{$password}}<br/>
            <a href="{{$site_url}}/doctor/login" class="button" target="_blank">Visit {{$site_name}}</a>
        </p>
        <p>Thank you,</p>
        <p>The {{$site_name}} Team</p>
    </div>
    <div class="footer">
        <p>&copy; 2024 {{$site_name}}. All rights reserved.</p>
    </div>
</div>
</body>
</html>
