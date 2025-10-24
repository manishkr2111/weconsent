<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeConsent Waitlist Confirmation</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f8f9fb;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .email-wrapper {
            width: 100%;
            padding: 20px 0;
            background-color: #f8f9fb;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }

        .email-header {
            text-align: center;
            padding: 30px 20px 10px 20px;
            background-color: #323c9a;
            color: #ffffff;
        }

        .email-header img {
            max-width: 100px;
            margin-bottom: 15px;
            border-radius: 8px;
        }

        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }

        .email-body {
            padding: 30px 25px;
            font-size: 16px;
            line-height: 1.6;
            color: #4b4b4b;
        }

        .highlight-email {
            display: block;
            margin: 20px 0;
            font-size: 18px;
            font-weight: bold;
            color: #323c9a;
            background-color: #f0f4ff;
            padding: 12px 15px;
            border-radius: 6px;
            text-align: center;
        }

        .teaser {
            font-size: 16px;
            margin-bottom: 25px;
            color: #555;
        }

        .teaser li {
            margin-bottom: 10px;
        }

        .button {
            display: inline-block;
            background-color: #323c9a;
            color: #ffffff;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 10px 0;
        }

        .footer {
            text-align: center;
            font-size: 14px;
            color: #888;
            padding: 25px 20px;
        }

        .footer a {
            color: #323c9a;
            text-decoration: none;
        }

        /* Mobile Responsiveness */
        @media only screen and (max-width: 600px) {
            .email-body, .footer {
                padding: 20px 15px;
            }

            .email-header h1 {
                font-size: 20px;
            }

            .highlight-email {
                font-size: 16px;
                padding: 10px 12px;
            }

            .button {
                padding: 12px 20px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <!-- Header -->
            <div class="email-header">
                <img src="{{ $logoPath }}" alt="WeConsent Logo">
                <h1>You're on the WeConsent Waitlist!</h1>
            </div>

            <!-- Body -->
            <div class="email-body">
                <p>Hello,</p>
                <p>Thank you for joining the WeConsent waitlist with the email:</p>

                <span class="highlight-email">{{ $email }}</span>

                <p class="teaser">Here’s what you can expect from WeConsent:</p>
                <ul class="teaser" style="list-style: none; padding-left: 0;">
                    <li>🔒 <strong>Privacy First:</strong> Your conversations stay fully private.</li>
                    <li>💬 <strong>Clear Communication:</strong> Set boundaries and confirm mutual agreement respectfully.</li>
                    <li>❤️ <strong>Mutual Consent:</strong> Build trust, respect, and connection in your relationships.</li>
                </ul>

                <!-- Read More Button -->
                <a href="https://weconsent.app" class="button" target="_blank">Read More</a>

                <p style="margin-top: 25px;">We’ll notify you as soon as WeConsent launches. No spam, just meaningful updates!</p>

                <p>If you did not sign up for this, please ignore this email. Your inbox is safe.</p>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p>Best regards,<br>The WeConsent Team</p>
                <p><a href="mailto:support@weconsent.app">Contact Support</a></p>
            </div>
        </div>
    </div>
</body>
</html>
