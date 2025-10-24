<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $actionType === 'created' ? 'Consent Request' : 'Consent Accepted' }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .email-container {
            width: 100%;
            max-width: 640px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .email-logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .email-logo img {
            max-height: 70px;
        }

        .email-header {
            text-align: center;
            color: #2e368f;
            border-bottom: 2px solid #e5e5e5;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .email-header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }

        .email-body {
            font-size: 15px;
            line-height: 1.7;
            color: #444;
        }

        .highlight {
            color: #2e368f;
            font-weight: 600;
        }

        .note {
            font-size: 13px;
            color: #555;
            margin-top: 18px;
            padding: 12px;
            background: #f8f9fb;
            border-left: 4px solid #2e368f;
            border-radius: 4px;
        }

        .footer {
            text-align: center;
            margin-top: 35px;
            font-size: 13px;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .footer p {
            margin: 6px 0;
        }

        .footer a {
            color: #2e368f;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>

@php
    $author = $consentRequest->sender ?? (object)['name' => 'Someone'];
    $receiver = $consentRequest->receiver ?? (object)['name' => 'Someone'];
    $typeRaw = strtolower($consentRequest->type ?? 'request');
    $typeLabel = ucfirst($typeRaw);

    $authorName = e($author->name ?? 'Someone');
    $receiverName = e($receiver->name ?? 'Someone');
    $logoPath = 'https://dev.weconsent.app/storage/website/logo.jfif';
@endphp

<div class="email-container">

    <!-- Logo -->
    <div class="email-logo">
        <img src="{{ $logoPath }}" alt="WeConsent Logo">
    </div>

    <!-- Header -->
    <div class="email-header">
        <h1>
            @if($actionType === 'created')
                New {{ $typeLabel }} Request
            @else
                {{ $typeLabel }} Request Accepted
            @endif
        </h1>
    </div>

    <!-- Body -->
    <div class="email-body">
        @if($actionType === 'created')
            @if($recipientType === 'author')
                <p>Hello <span class="highlight">{{ $authorName }}</span>,</p>
                <p>
                    Your <span class="highlight">{{ $typeLabel }}</span> request has been successfully sent to 
                    <strong>{{ $receiverName }}</strong>. We will notify you once a response is provided.
                </p>
                <div class="note">
                    If you did not initiate this request, please reach out to our support team immediately at 
                    <a href="mailto:support@weconsent.app">support@weconsent.app</a>.
                </div>
            @else
                <p>Hello <span class="highlight">{{ $receiverName }}</span>,</p>
                <p>
                    You have received a new <span class="highlight">{{ $typeLabel }}</span> request from 
                    <strong>{{ $authorName }}</strong>. Please review it at your earliest convenience.
                </p>
            @endif

        @elseif($actionType === 'accepted')
            @if($recipientType === 'author')
                <p>Hello <span class="highlight">{{ $authorName }}</span>,</p>
                <p>
                    Good news! <strong>{{ $receiverName }}</strong> has accepted your 
                    <span class="highlight">{{ $typeLabel }}</span> request.
                </p>
            @else
                <p>Hello <span class="highlight">{{ $receiverName }}</span>,</p>
                <p>
                    You have successfully accepted the <span class="highlight">{{ $typeLabel }}</span> request 
                    from <strong>{{ $authorName }}</strong>.
                </p>
            @endif

        @elseif($actionType === 'cancelled')
            @if($recipientType === 'author')
                <p>Hello <span class="highlight">{{ $authorName }}</span>,</p>
                <p>
                    We wanted to inform you that <strong>{{ $receiverName }}</strong> has <strong>cancelled</strong> 
                    the <span class="highlight">{{ $typeLabel }}</span> request you sent.
                </p>
                <div class="note">
                    If you have any questions or believe this was an error, please contact our support team at 
                    <a href="mailto:support@weconsent.app">support@weconsent.app</a>.
                </div>
            @else
                <p>Hello <span class="highlight">{{ $receiverName }}</span>,</p>
                <p>
                    You have successfully <strong>cancelled</strong> the <span class="highlight">{{ $typeLabel }}</span> request 
                    from <strong>{{ $authorName }}</strong>.
                </p>
                <div class="note">
                    If you did not intend to cancel this request, please contact our support team at 
                    <a href="mailto:support@weconsent.app">support@weconsent.app</a>.
                </div>
            @endif
            
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Best regards,<br><strong>The WeConsent Team</strong></p>
        <p><a href="mailto:support@weconsent.app">support@weconsent.app</a></p>
        <p style="font-size: 12px; color: #aaa; margin-top: 12px;">
            This is an automated message. Please do not reply directly to this email.
        </p>
    </div>

</div>

</body>
</html>
