<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/app/bootstrap.php';

use App\Models\Client;

$error = '';
$selectedPlan = strtoupper($_GET['plan'] ?? $_POST['membership_type'] ?? 'BASIC');
if (!in_array($selectedPlan, ['BASIC', 'MID', 'PRO'], true)) {
    $selectedPlan = 'BASIC';
}

$selectedBilling = strtolower($_GET['billing'] ?? $_POST['billing_cycle'] ?? 'yearly');
if (!in_array($selectedBilling, ['monthly', 'yearly'], true)) {
    $selectedBilling = 'yearly';
}

$planRates = [
    'BASIC' => ['monthly' => 1000, 'yearly' => 9600],
    'MID' => ['monthly' => 1300, 'yearly' => 13200],
    'PRO' => ['monthly' => 1800, 'yearly' => 19200],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $age = (int)($_POST['age'] ?? 18);
    $gender = $_POST['gender'] ?? 'M';
    $weight = (float)($_POST['weight'] ?? 65);
    $height = (float)($_POST['height'] ?? 170);
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($firstName === '' || $lastName === '' || $email === '' || $phone === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif ($age < 12 || $age > 100) {
        $error = 'Please enter a valid age between 12 and 100.';
    } elseif ($weight <= 0 || $height <= 0) {
        $error = 'Weight and height must be positive numbers.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $clientModel = new Client();

            if ($clientModel->emailOrPhoneExists($email, $phone)) {
                header('Location: signup_failure.php?reason=exists');
                exit();
            }

            $created = $clientModel->createClient(
                $lastName,
                $firstName,
                $age,
                $gender,
                $weight,
                $height,
                $selectedPlan,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $phone,
                0
            );

            if ($created) {
                header('Location: signup_success.php');
                exit();
            }

            header('Location: signup_failure.php?reason=insert');
            exit();
        } catch (Throwable $e) {
            header('Location: signup_failure.php?reason=error');
            exit();
        }
    }
}

$activeRate = $planRates[$selectedPlan][$selectedBilling];
$activePeriodLabel = $selectedBilling === 'yearly' ? '/yr' : '/mo';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Member Sign Up - BIG Gainz Fitness</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=M+PLUS+1:wght@400;500&family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&family=Orbitron:wght@800;900&family=Oswald:wght@700&family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --bg-dark: #0a0a0c;
      --bg-card: #2D2D2F;
      --text-main: #ffffff;
      --text-muted: #a0a5b5;
      --accent-light: #e5e5e5;
      --font-navbar: 'Malgun Gothic', 'Dotum', 'Apple SD Gothic Neo', sans-serif;
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
      flex-direction: column;
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    .site-header {
      position: sticky;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1000;
      background: rgba(10, 10, 12, 0.9);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .header-container {
      display: flex;
      align-items: center;
      justify-content: space-between;
      max-width: 1240px;
      margin: 0 auto;
      padding: 18px 20px;
      gap: 16px;
      flex-wrap: wrap;
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

    .header-actions {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .btn-nav-action {
      font-family: var(--font-navbar) !important;
      background-color: #000;
      color: #fff;
      border: 1px solid #333;
      font-weight: 700;
      font-size: 0.8rem;
      padding: 8px 18px;
      border-radius: 4px;
      letter-spacing: 0.5px;
      transition: var(--transition-fast);
      display: inline-block;
      margin-top: 12px;
      margin-bottom: 12px;
    }

    .btn-nav-action:hover {
      background-color: #222;
      color: #fff;
    }

    .signup-main {
      flex: 1 0 auto;
      width: 100%;
      max-width: 720px;
      margin: auto;
      padding: 48px 20px;
    }

    .signup-panel {
      background: linear-gradient(145deg, #232428 0%, #141518 65%, #191a1e 100%);
      border: 1px solid rgba(255, 255, 255, 0.14);
      border-radius: 22px;
      padding: 36px 34px;
      box-shadow: 0 24px 60px rgba(0, 0, 0, 0.75);
    }

    .signup-title {
      font-family: var(--font-section-titles) !important;
      font-weight: 900 !important;
      font-size: clamp(1.5rem, 3vw, 2rem);
      text-transform: uppercase;
      margin-bottom: 6px;
    }

    .signup-subtitle {
      font-family: var(--font-inter) !important;
      font-size: 0.88rem;
      color: var(--text-muted);
      margin-bottom: 22px;
    }

    .billing-selector-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
      background-color: #0c0d10;
      border: 1px solid rgba(255, 255, 255, 0.14);
      border-radius: 12px;
      padding: 12px 16px;
      margin-bottom: 22px;
    }

    .billing-pill-group {
      display: inline-flex;
      gap: 8px;
    }

    .billing-pill-btn {
      font-family: var(--font-inter) !important;
      font-size: 0.82rem;
      font-weight: 700;
      padding: 6px 14px;
      border-radius: 8px;
      border: 1px solid rgba(255, 255, 255, 0.18);
      background-color: #1a1b20;
      color: var(--text-muted);
      cursor: pointer;
      transition: var(--transition-fast);
    }

    .billing-pill-btn.active {
      background-color: #ffffff;
      color: #000000;
      border-color: #ffffff;
    }

    .selected-rate-summary {
      font-family: var(--font-inter) !important;
      font-size: 0.88rem;
      color: #e5e7eb;
    }

    .signup-panel .form-label {
      font-family: var(--font-inter) !important;
      font-size: 0.82rem;
      font-weight: 600;
      color: #e5e7eb;
      margin-bottom: 6px;
    }

    .signup-panel .form-control,
    .signup-panel .form-select {
      background-color: #36373b;
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 8px;
      color: #ffffff;
      font-family: var(--font-inter) !important;
      font-size: 0.88rem;
      padding: 10px 14px;
    }

    .signup-panel .form-control::placeholder {
      color: #8e929e;
    }

    .signup-panel .form-control:focus,
    .signup-panel .form-select:focus {
      background-color: #3e3f44;
      border-color: rgba(255, 255, 255, 0.3);
      color: #ffffff;
      box-shadow: none;
    }

    .btn-submit-account {
      font-family: var(--font-btn-member) !important;
      font-weight: 700 !important;
      text-transform: uppercase;
      background-color: var(--accent-light);
      color: #000;
      padding: 12px 24px;
      border-radius: 8px;
      border: none;
      letter-spacing: 2px;
      transition: var(--transition-fast);
      width: 100%;
      font-size: 1.05rem;
      cursor: pointer;
    }

    .btn-submit-account:hover {
      background-color: #ffffff;
      transform: translateY(-2px);
    }

    .signup-footer-note {
      margin-top: 22px;
      padding-top: 16px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      text-align: center;
      font-family: var(--font-inter) !important;
      font-size: 0.84rem;
      color: var(--text-muted);
    }

    @media (max-width: 576px) {
      .signup-panel {
        padding: 24px 18px;
      }
    }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="header-container">
      <a href="index.php" class="site-brand">
        <span class="logo-icon">B<i class="fa-solid fa-dumbbell"></i>G</span>
        <span class="logo-text">GainzFitness</span>
      </a>

      <div class="header-actions">
        <!-- <a href="login.php" class="btn-nav-action">Sign In</a> -->
        <a href="index.php" class="btn-nav-action">Back to Home</a>
      </div>
    </div>
  </header>

  <main class="signup-main">
    <div class="signup-panel">
      <h1 class="signup-title">MEMBER REGISTRATION</h1>
      <p class="signup-subtitle">Create your BIG Gainz Fitness profile to book coaching sessions and track your membership.</p>

      <div class="billing-selector-bar">
        <div class="billing-pill-group">
          <button type="button" class="billing-pill-btn <?= $selectedBilling === 'monthly' ? 'active' : '' ?>" data-billing-btn="monthly">Monthly</button>
          <button type="button" class="billing-pill-btn <?= $selectedBilling === 'yearly' ? 'active' : '' ?>" data-billing-btn="yearly">Yearly</button>
        </div>
        <div class="selected-rate-summary" id="rateSummary">
          Selected Rate: <strong>&#8369;<?= number_format((float)$activeRate, 0) ?><?= htmlspecialchars($activePeriodLabel) ?></strong>
        </div>
      </div>

      <?php if ($error !== ''): ?>
        <div class="alert alert-danger py-2 small" role="alert"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="member_signup.php" class="row g-3" id="signupForm">
        <input type="hidden" name="billing_cycle" id="billingCycleInput" value="<?= htmlspecialchars($selectedBilling) ?>">

        <div class="col-md-6">
          <label class="form-label">First Name *</label>
          <input type="text" name="first_name" class="form-control" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Last Name *</label>
          <input type="text" name="last_name" class="form-control" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Age *</label>
          <input type="number" name="age" min="12" max="100" class="form-control" required value="<?= htmlspecialchars($_POST['age'] ?? '20') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Gender *</label>
          <select name="gender" class="form-select">
            <option value="M" <?= ($_POST['gender'] ?? '') === 'M' ? 'selected' : '' ?>>Male</option>
            <option value="F" <?= ($_POST['gender'] ?? '') === 'F' ? 'selected' : '' ?>>Female</option>
            <option value="O" <?= ($_POST['gender'] ?? '') === 'O' ? 'selected' : '' ?>>Other</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Membership Plan *</label>
          <select name="membership_type" id="membershipSelect" class="form-select">
            <option value="BASIC" data-monthly="1000" data-yearly="9600" <?= $selectedPlan === 'BASIC' ? 'selected' : '' ?>>
              BASIC (&#8369;<?= number_format((float)$planRates['BASIC'][$selectedBilling], 0) ?><?= htmlspecialchars($activePeriodLabel) ?>)
            </option>
            <option value="MID" data-monthly="1300" data-yearly="13200" <?= $selectedPlan === 'MID' ? 'selected' : '' ?>>
              MID (&#8369;<?= number_format((float)$planRates['MID'][$selectedBilling], 0) ?><?= htmlspecialchars($activePeriodLabel) ?>)
            </option>
            <option value="PRO" data-monthly="1800" data-yearly="19200" <?= $selectedPlan === 'PRO' ? 'selected' : '' ?>>
              PRO (&#8369;<?= number_format((float)$planRates['PRO'][$selectedBilling], 0) ?><?= htmlspecialchars($activePeriodLabel) ?>)
            </option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Weight (kg) *</label>
          <input type="number" step="0.1" name="weight" class="form-control" required value="<?= htmlspecialchars($_POST['weight'] ?? '65.0') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Height (cm) *</label>
          <input type="number" step="0.1" name="height" class="form-control" required value="<?= htmlspecialchars($_POST['height'] ?? '170.0') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Phone Number *</label>
          <input type="text" name="phone" class="form-control" placeholder="09XXXXXXXXX" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Password (min 6 chars) *</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Confirm Password *</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <div class="col-12 mt-4">
          <button type="submit" class="btn-submit-account">Create Account</button>
        </div>
      </form>

      <div class="signup-footer-note">
        Already have an account? <a href="login.php" class="text-white fw-bold">Log in here</a>
      </div>
    </div>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const billingInput = document.getElementById('billingCycleInput');
      const membershipSelect = document.getElementById('membershipSelect');
      const rateSummary = document.getElementById('rateSummary');
      const pillButtons = document.querySelectorAll('[data-billing-btn]');

      function syncPlanLabels() {
        const mode = billingInput.value === 'monthly' ? 'monthly' : 'yearly';
        const suffix = mode === 'yearly' ? '/yr' : '/mo';

        Array.from(membershipSelect.options).forEach(opt => {
          const planCode = opt.value;
          const amount = Number(opt.dataset[mode] || 0);
          opt.textContent = `${planCode} (₱${amount.toLocaleString()}${suffix})`;
        });

        const selectedOpt = membershipSelect.options[membershipSelect.selectedIndex];
        const currentAmount = Number(selectedOpt.dataset[mode] || 0);
        rateSummary.innerHTML = `Selected Rate: <strong>₱${currentAmount.toLocaleString()}${suffix}</strong>`;

        pillButtons.forEach(btn => {
          btn.classList.toggle('active', btn.dataset.billingBtn === mode);
        });
      }

      pillButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          billingInput.value = btn.dataset.billingBtn;
          syncPlanLabels();
        });
      });

      membershipSelect.addEventListener('change', syncPlanLabels);
      syncPlanLabels();
    });
  </script>
</body>
</html>