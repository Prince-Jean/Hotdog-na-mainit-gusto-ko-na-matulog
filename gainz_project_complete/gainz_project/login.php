<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/app/bootstrap.php';

use App\Controllers\Authentication;

if (!empty($_SESSION['user_email'])) {
    if ((int)($_SESSION['is_admin'] ?? 0) === 1) {
        header('Location: admin_portal/admin_dashboard.php');
        exit();
    }
    header('Location: member_portal/member_dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $auth = new Authentication();
        $result = $auth->authenticateUser($email, $password);

        if ($result['status'] === 'ADMIN' || $result['status'] === 'CLIENT') {
            $user = $result['user'];
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['UserID'];
            $_SESSION['user_email'] = $user['UserEmail'];
            $_SESSION['user_name'] = trim($user['FirstName'] . ' ' . $user['LastName']);
            $_SESSION['is_admin'] = (int)$user['isAdmin'];

            if ((int)$user['isAdmin'] === 1) {
                header('Location: admin_portal/admin_dashboard.php');
                exit();
            }
            header('Location: member_portal/member_dashboard.php');
            exit();
        } elseif ($result['status'] === 'ERROR') {
            $error = 'Database error: ensure gym_database is imported in MySQL.';
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - BIG Gainz Fitness</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&family=Orbitron:wght@800;900&family=Outfit:wght@400;600;800;900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    :root {
      --bg-dark: #0a0a0c;
      --bg-card: #2D2D2F;
      --bg-card-hover: #38383a;
      --text-main: #ffffff;
      --text-muted: #a0a5b5;
      --accent-light: #e5e5e5;
      --font-logo: 'Impact', 'Haettenschweiler', 'Arial Narrow Bold', sans-serif;
      --font-hero-title: 'Outfit', sans-serif;
      --font-body-descriptions: 'Montserrat', sans-serif;
      --font-inter: 'Inter', sans-serif;
      --transition-fast: all 0.25s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body, html {
      margin: 0;
      padding: 0;
      width: 100%;
      height: 100%;
      background-color: var(--bg-dark);
      color: var(--text-main);
      font-family: var(--font-body-descriptions);
      overflow-x: hidden;
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    .top-right-home-btn {
      position: fixed;
      top: 24px;
      right: 28px;
      z-index: 20;
      background-color: rgba(22, 22, 25, 0.88);
      color: #fff;
      border: 1px solid rgba(255, 255, 255, 0.16);
      border-radius: 50px;
      padding: 10px 20px;
      font-family: var(--font-inter) !important;
      font-size: 0.82rem;
      font-weight: 600;
      letter-spacing: 0.4px;
      backdrop-filter: blur(8px);
      transition: var(--transition-fast);
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .top-right-home-btn:hover {
      background-color: #fff;
      color: #000;
      border-color: #fff;
      transform: translateY(-2px);
    }

    .site-brand {
      display: flex;
      align-items: center;
      font-family: var(--font-logo);
      font-size: 1.4rem;
      color: #fff;
    }

    .logo-icon {
      font-family: var(--font-logo);
      margin-right: 8px;
    }

    .logo-text {
      font-family: var(--font-logo);
    }

    .login-page {
      display: flex;
      min-height: 100vh;
      width: 100%;
    }

    .left-box {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 40px 60px;
      background-color: var(--bg-dark);
      z-index: 2;
      max-width: 650px;
      min-height: 100vh;
    }

    .form-wrapper {
      max-width: 440px;
      width: 100%;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
    }

    .brand-logo {
      margin-bottom: 30px;
      font-size: 1.3rem;
    }

    .top-text {
      margin-bottom: 24px;
    }

    .page-title {
      font-family: var(--font-hero-title) !important;
      font-weight: 800 !important;
      font-size: 2.4rem;
      margin-bottom: 8px;
      letter-spacing: -0.5px;
      text-transform: uppercase;
    }

    .page-sub {
      color: var(--text-muted);
      font-size: 0.95rem;
    }

    .login-error-alert {
      background-color: rgba(220, 53, 69, 0.16);
      border: 1px solid rgba(220, 53, 69, 0.45);
      color: #ffb3ba;
      border-radius: 12px;
      padding: 10px 14px;
      font-family: var(--font-inter) !important;
      font-size: 0.85rem;
      margin-bottom: 14px;
    }

    .user-form {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .input-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .field-label {
      font-size: 0.85rem;
      font-weight: 600;
      color: #d1d5db;
    }

    .input-box {
      position: relative;
      display: flex;
      align-items: center;
    }

    .field-icon {
      position: absolute;
      left: 18px;
      color: var(--text-muted);
      font-size: 0.9rem;
    }

    .hide-icon {
      position: absolute;
      right: 18px;
      color: var(--text-muted);
      font-size: 0.9rem;
      cursor: pointer;
    }

    .user-input {
      width: 100%;
      background-color: #161619;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 50px;
      padding: 14px 44px 14px 48px;
      color: #fff;
      font-size: 0.9rem;
      font-family: var(--font-body-descriptions);
      transition: var(--transition-fast);
    }

    .input-box:not(:has(.hide-icon)) .user-input {
      padding-right: 20px;
    }

    .user-input:focus {
      outline: none;
      border-color: rgba(255, 255, 255, 0.4);
      background-color: #1c1c20;
    }

    .user-input::placeholder {
      color: #555;
    }

    .extra-row {
      display: flex;
      justify-content: flex-end;
      margin-top: -2px;
    }

    .forgot-btn, .forgot-link {
      font-size: 0.8rem;
      color: var(--text-muted);
      transition: var(--transition-fast);
    }

    .forgot-btn:hover, .forgot-link:hover {
      color: #fff;
      text-decoration: underline;
    }

    .submit-btn {
      width: 100%;
      background-color: #fff;
      color: #000;
      border: none;
      border-radius: 50px;
      padding: 14px;
      font-weight: 700;
      font-size: 1rem;
      cursor: pointer;
      transition: var(--transition-fast);
      margin-top: 8px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .submit-btn:hover {
      background-color: #e5e5e5;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(255, 255, 255, 0.1);
    }

    .bottom-box {
      margin-top: 30px;
    }

    .change-page {
      text-align: center;
      font-size: 0.85rem;
      color: var(--text-muted);
      margin-bottom: 15px;
    }

    .link-btn {
      color: #fff;
      font-weight: 600;
      margin-left: 4px;
    }

    .link-btn:hover {
      text-decoration: underline;
    }

    .copy-text {
      font-size: 0.8rem;
      color: #555;
      margin-top: 100px;
    }

    .right-box {
      flex: 1;
      position: relative;
      background: url('assets/loginbg.png') center/cover no-repeat;
      min-height: 100vh;
    }

    .bg-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(270deg, rgba(10, 10, 12, 0.1) 0%, rgba(10, 10, 12, 0.95) 100%);
    }

    @media (max-width: 992px) {
      .top-right-home-btn {
        top: 16px;
        right: 16px;
        padding: 8px 14px;
        font-size: 0.75rem;
      }

      .login-page {
        flex-direction: column;
      }

      .left-box {
        max-width: 100%;
        padding: 64px 20px 30px 20px;
        min-height: 100vh;
      }

      .right-box {
        display: none;
      }

      .copy-text {
        text-align: center;
        font-size: 0.8rem;
        color: #555;
        margin-top: 100px;
      }
    }
  </style>
</head>
<body>

  <a href="index.php" class="top-right-home-btn"><i class="fa-solid fa-arrow-left"></i>Back to Home</a>

  <div class="login-page">
    <div class="left-box">
      <div class="form-wrapper">

        <a href="index.php" class="site-brand brand-logo">
          <span class="logo-icon">B<i class="fa-solid fa-dumbbell"></i>G</span>
          <span class="logo-text">GainzFitness</span>
        </a>

        <div class="top-text">
          <h1 class="page-title">Welcome back</h1>
          <p class="page-sub">Log in to continue your fitness journey.</p>
        </div>

        <?php if ($error !== ''): ?>
          <div class="login-error-alert" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form class="user-form" action="login.php" method="POST">
          <div class="input-group">
            <label for="user-email" class="field-label">Email Address</label>
            <div class="input-box">
              <i class="fa-regular fa-envelope field-icon"></i>
              <input type="email" id="user-email" name="email" class="user-input" placeholder="Enter your email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
          </div>

          <div class="input-group">
            <label for="user-pass" class="field-label">Password</label>
            <div class="input-box">
              <i class="fa-solid fa-lock field-icon"></i>
              <input type="password" id="user-pass" name="password" class="user-input" placeholder="Enter your password" required>
              <i class="fa-regular fa-eye-slash hide-icon" onclick="
                const box = document.getElementById('user-pass');
                if (box.type === 'password') {
                  box.type = 'text';
                  this.classList.remove('fa-eye-slash');
                  this.classList.add('fa-eye');
                } else {
                  box.type = 'password';
                  this.classList.remove('fa-eye');
                  this.classList.add('fa-eye-slash');
                }
              "></i>
            </div>
          </div>

          <div class="extra-row">
            <!-- <a href="#" class="forgot-btn">Forgot Password?</a> -->
          </div>

          <button type="submit" class="submit-btn">Log In</button>
        </form>

        <div class="bottom-box">
          <p class="change-page">
            Don't have an account? <a href="member_signup.php" class="link-btn">Sign Up</a>
          </p>
          <p class="copy-text">&copy; <?= date('Y') ?> BigGainzFitness</p>
        </div>
      </div>
    </div>

    <div class="right-box">
      <div class="bg-overlay"></div>
    </div>
  </div>

</body>
</html>
