<?php
declare(strict_types=1);
$reason =$_GET['reason'] ?? 'default';

$messages = [
    'exists' => 'An account with that email address or phone number already exists.',
    'insert' => 'We could not save your registration details. Please verify your inputs.',
    'error' => 'A database error occurred while creating your account.',
    'default' => 'Registration could not be completed.',
];

$message =$messages[$reason] ?? $messages['default'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registration Failed - BIG Gainz Fitness</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&family=Orbitron:wght@800;900&family=Oswald:wght@700&family=Outfit:wght@400;600;800;900&display=swap" rel="stylesheet">
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
      --font-btn-member: 'Oswald', sans-serif;
      --font-section-titles: 'Orbitron', sans-serif;
      --font-body-descriptions: 'Montserrat', sans-serif;
      --font-inter: 'Inter', sans-serif;
      --transition-fast: all 0.25s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      background-color: var(--bg-dark);
      color: var(--text-main);
      font-family: var(--font-body-descriptions);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      overflow-x: hidden;
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    .status-main {
      width: 100%;
      max-width: 500px;
      text-align: center;
    }

    .status-panel {
      background: linear-gradient(145deg, #232428 0%, #141518 65%, #191a1e 100%);
      border: 1px solid rgba(255, 255, 255, 0.14);
      border-radius: 22px;
      padding: 38px 32px;
      box-shadow: 0 24px 60px rgba(0, 0, 0, 0.75);
    }

    .status-brand {
      display: inline-flex;
      align-items: center;
      font-family: var(--font-logo);
      font-size: 1.35rem;
      color: #fff;
      margin-bottom: 18px;
    }

    .logo-icon {
      font-family: var(--font-logo);
      margin-right: 8px;
    }

    .logo-text {
      font-family: var(--font-logo);
    }

    .status-icon-fail {
      font-size: 2.5rem;
      color: #ff6b6b;
      margin-bottom: 12px;
      line-height: 1;
    }

    .status-title {
      font-family: var(--font-section-titles) !important;
      font-weight: 900 !important;
      font-size: clamp(1.4rem, 3vw, 1.85rem);
      text-transform: uppercase;
      margin-bottom: 10px;
    }

    .status-desc {
      font-family: var(--font-inter) !important;
      font-size: 0.9rem;
      color: var(--text-muted);
      line-height: 1.55;
      margin-bottom: 26px;
    }

    .status-actions {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 12px;
    }

    .btn-status-primary {
      font-family: var(--font-btn-member) !important;
      font-weight: 700 !important;
      text-transform: uppercase;
      background-color: var(--accent-light);
      color: #000;
      padding: 11px 24px;
      border-radius: 8px;
      border: none;
      letter-spacing: 1.5px;
      font-size: 0.95rem;
      transition: var(--transition-fast);
      display: inline-block;
    }

    .btn-status-primary:hover {
      background-color: #ffffff;
      color: #000;
      transform: translateY(-2px);
    }

    .btn-status-secondary {
      font-family: var(--font-inter) !important;
      font-weight: 600;
      background-color: #161619;
      color: #fff;
      padding: 11px 22px;
      border-radius: 8px;
      border: 1px solid rgba(255, 255, 255, 0.16);
      font-size: 0.88rem;
      transition: var(--transition-fast);
      display: inline-block;
    }

    .btn-status-secondary:hover {
      background-color: #26272c;
      color: #fff;
    }

    @media (max-width: 576px) {
      .status-panel {
        padding: 28px 20px;
      }
    }
  </style>
</head>
<body>
  <main class="status-main">
    <div class="status-panel">
      <a href="index.php" class="status-brand">
        <span class="logo-icon">B<i class="fa-solid fa-dumbbell"></i>G</span>
        <span class="logo-text">GainzFitness</span>
      </a>
      <div class="status-icon-fail">&#10007;</div>
      <h1 class="status-title">REGISTRATION FAILED</h1>
      <p class="status-desc"><?= htmlspecialchars($message) ?></p>
      <div class="status-actions">
        <a href="member_signup.php" class="btn-status-primary">Try Again</a>
        <a href="login.php" class="btn-status-secondary">Back to Login</a>
      </div>
    </div>
  </main>
</body>
</html>