<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
        }

        .form-wrapper {
            background-color: #ffffff;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            width: 100%;
            max-width: 400px;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #323c9a;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }

        .error {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .success {
            color: green;
            font-size: 14px;
            margin-bottom: 10px;
        }

        @media (max-width: 600px) {
            .form-wrapper {
                padding: 15px;
            }

            input, button {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="form-wrapper">
        <h2>Reset Your Password</h2>

        @if(session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif

        <form action="{{ route('resetPassword') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <label for="email">Email Address:</label>
            <input type="email" name="email" value="{{ request()->get('email') }}" required disabled>

            @error('email')
                <div class="error">{{ $message }}</div>
            @enderror

            <label for="password">New Password:</label>
            <input type="password" name="password" required>

            @error('password')
                <div class="error">{{ $message }}</div>
            @enderror

            <label for="password_confirmation">Confirm Password:</label>
            <input type="password" name="password_confirmation" required>

            @error('password_confirmation')
                <div class="error">{{ $message }}</div>
            @enderror

            <button type="submit">Reset Password</button>
        </form>
    </div>
</div>

</body>
</html>
