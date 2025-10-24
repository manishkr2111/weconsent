<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <style>
    :root {
      --purple-text: rgb(60, 29, 113);
      --teal: rgb(42, 157, 175);
      --green: rgb(39, 168, 68);
      --orange: rgb(247, 148, 30);
      --yellow: rgb(255, 195, 0);
      --white: rgb(255, 255, 255);
      --light-gray: rgb(244, 244, 244);
      --charcoal: rgb(34, 34, 34);
    }

    /* Global styles */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background-color: var(--light-gray);
      padding: 1rem;
    }

    /* Card */
    .login-card {
      background-color: var(--white);
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.1);
      padding: 2rem;
      width: 100%;
      max-width: 400px;
    }

    .login-card h1 {
      color: var(--purple-text);
      text-align: center;
      margin-bottom: 2rem;
      font-weight: 700;
    }

    /* Form styles */
    .login-card form {
      display: flex;
      flex-direction: column;
    }

    label {
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: var(--charcoal);
    }

    input[type="email"],
    input[type="password"] {
      padding: 0.75rem 1rem;
      border-radius: 8px;
      border: 1px solid #ccc;
      margin-bottom: 1.5rem;
      font-size: 1rem;
      transition: border-color 0.3s, box-shadow 0.3s;
    }

    input[type="email"]:focus,
    input[type="password"]:focus {
      outline: none;
      border-color: var(--teal);
      box-shadow: 0 0 0 3px rgba(42, 157, 175, 0.2);
    }

    .form-check {
      display: flex;
      align-items: center;
      margin-bottom: 1.5rem;
      font-size: 0.95rem;
      color: var(--charcoal);
    }

    .form-check input {
      margin-right: 0.5rem;
    }

    button {
      padding: 0.75rem 1rem;
      border: none;
      border-radius: 8px;
      background-color: var(--teal);
      color: var(--white);
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    button:hover {
      background-color: var(--purple-text);
    }

    .text-center {
      text-align: center;
      margin-top: 1.5rem;
    }

    .text-center a {
      color: var(--orange);
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s;
    }

    .text-center a:hover {
      color: var(--purple-text);
      text-decoration: underline;
    }

    /* Error alert */
    .alert {
      background-color: #f8d7da;
      color: #721c24;
      border-radius: 8px;
      padding: 1rem;
      margin-bottom: 1.5rem;
    }

    .alert ul {
      margin: 0;
      padding-left: 1.2rem;
    }

    /* Responsive */
    @media (max-width: 500px) {
      .login-card {
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>

  <div class="login-card">

    <h1>Login</h1>

    <!-- Display errors if any -->
    @if ($errors->any())
      <div class="alert">
        <ul>
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <!-- Login Form -->
    <form action="{{ route('login') }}" method="POST">
      @csrf
      <label for="email">Email</label>
      <input type="email" name="email" id="email" value="{{ old('email') }}" required>

      <label for="password">Password</label>
      <input type="password" name="password" id="password" required>

      <div class="form-check">
        <input type="checkbox" name="remember" id="remember">
        <label for="remember">Remember me</label>
      </div>

      <button type="submit">Login</button>
    </form>

    <div class="text-center">
      <a href="{{ route('register') }}">Don't have an account? Register here</a>
    </div>

  </div>

</body>
</html>
