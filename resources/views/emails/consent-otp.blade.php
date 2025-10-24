<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .email-container {
            width: 100%;
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .email-header {
            text-align: center;
            color: #323c9a;
        }

        .email-body {
            font-size: 16px;
            color: #333;
            line-height: 1.6;
        }

        .otp {
            font-size: 24px;
            font-weight: bold;
            color: #323c9a;
            padding: 10px;
            border: 2px solid #323c9a;
            border-radius: 4px;
            display: inline-block;
            background-color: #f4f4f4;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #888;
        }

        .footer a {
            color: #323c9a;
            text-decoration: none;
        }

        .button {
            display: inline-block;
            background-color: #323c9a;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 16px;
            text-align: center;
            margin-top: 20px;
        }

        .button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

    <div class="email-container">
        <div class="email-header">
            <h1>Hello, {{ $userName }}!</h1>
        </div>

        <div class="email-body">
            <p>We are sending you an OTP (One-Time Password) to confirm your action:</p>

            <p class="otp">{{ $otp }}</p>

            <p>This OTP will expire in 10 minutes. Please use it within the time limit.</p>

            <p>If you did not request this, please ignore this email. Your account is safe.</p>
        </div>

        <div class="footer">
            <p>Thank you!</p>
            <p>Best regards,<br> The WeConsent Team</p>
            <p><a href="mailto:support@weconsent.app">Contact Support</a></p>
        </div>
    </div>

</body>
</html>
