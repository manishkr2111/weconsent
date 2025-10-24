<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
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

        .email-body p {
            margin-bottom: 20px;
        }

        .reset-button {
            margin: 25px;
            display: block;
            background-color: #323c9a;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 16px;
            text-align: center;
            margin-top: 20px;
        }

        .reset-button:hover {
            background-color: #45a049;
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
    </style>
</head>
<body>

    <div class="email-container">
        <div class="email-header">
            <h2>Password Reset Request</h2>
        </div>

        <div class="email-body">
            <p>Hello,</p>
            <p>We received a request to reset your password. If you did not make this request, please ignore this email.</p>
            <p>To reset your password, click the button below:</p>

            <a href="{{ $url }}" class="reset-button">Reset Password</a>

            <p>If you have any issues, please contact our support team.</p>
        </div>

        <div class="footer">
            <p>Best regards,<br> The WeConsent Team</p>
            <p><a href="mailto:support@weconsent.app">Contact Support</a></p>
        </div>
    </div>

</body>
</html>
