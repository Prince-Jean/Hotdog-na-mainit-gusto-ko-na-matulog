<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/app/bootstrap.php';

use App\Models\Client;
use App\Models\Trainer;
use App\Models\PaymentAndMessage;

$contactNotice = null;
$contactError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_contact'])) {
    $fn = trim($_POST['first_name'] ?? '');
    $ln = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $msg = trim($_POST['message_body'] ?? '');

    if ($fn === '' || $ln === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $msg === '') {
        $contactError = 'Please fill in all required fields with a valid email address.';
    } else {
        try {
            $pm = new PaymentAndMessage();
            $ok = $pm->createContactMessage($fn, $ln, $email, $phone, $msg);
            if ($ok) {
                $contactNotice = 'Thank you! Your inquiry has been sent to our Dagupan front desk team.';
            } else {
                $contactError = 'Unable to submit inquiry at this moment.';
            }
        } catch (Throwable $e) {
            $contactError = 'Database connection unavailable. Please import database/gym_database.sql first.';
        }
    }
}

$memberships = [];
$trainers = [];
try {
    $memberships = (new Client())->getAllMemberships();
    $trainers = (new Trainer())->getAllTrainers();
} catch (Throwable $e) {
}

$isLoggedIn = !empty($_SESSION['user_email']);
$dashboardUrl = ((int)($_SESSION['is_admin'] ?? 0) === 1)
    ? 'admin_portal/admin_dashboard.php'
    : 'member_portal/member_dashboard.php';

$monthlyPricesByPlan = [
    'BASIC' => 1000,
    'MID' => 1300,
    'PRO' => 1800,
];

$yearlyPricesByPlan = [
    'BASIC' => 9600,
    'MID' => 13200,
    'PRO' => 19200,
];

$displayPlans = !empty($memberships) ? $memberships : [
    [
        'MembershipType' => 'BASIC',
        'MembershipDesc' => 'Basic Package - Gym Access & Core Coaching',
        'MembershipRateMonthly' => 1000,
        'MembershipRateBiannual' => 900,
        'MembershipRateYearly' => 9600,
    ],
    [
        'MembershipType' => 'MID',
        'MembershipDesc' => 'Mid Package - Full Access & Extended Coaching',
        'MembershipRateMonthly' => 1300,
        'MembershipRateBiannual' => 1150,
        'MembershipRateYearly' => 13200,
    ],
    [
        'MembershipType' => 'PRO',
        'MembershipDesc' => 'Pro Package - Unlimited Access & Priority Coaching',
        'MembershipRateMonthly' => 1800,
        'MembershipRateBiannual' => 1650,
        'MembershipRateYearly' => 19200,
    ],
];

$fallbackTrainers = [
    ['FirstName' => 'Leonard', 'LastName' => 'Ramos', 'SpecializationType' => 'Head Strength Coach', 'RatePerHour' => null, 'img' => 'assets/trainer1.jpeg'],
    ['FirstName' => 'Jean', 'LastName' => 'Cruz', 'SpecializationType' => 'Head Strength Coach', 'RatePerHour' => null, 'img' => 'assets/trainer2.jpeg'],
    ['FirstName' => 'Leon', 'LastName' => 'Dela Cruz', 'SpecializationType' => 'Head Strength Coach', 'RatePerHour' => null, 'img' => 'assets/trainer3.jpeg'],
    ['FirstName' => 'Jin', 'LastName' => 'Jugo', 'SpecializationType' => 'Head Strength Coach', 'RatePerHour' => null, 'img' => 'assets/trainer4.jpeg'],
    ['FirstName' => 'Jillion', 'LastName' => 'Pasana', 'SpecializationType' => 'Head Strength Coach', 'RatePerHour' => null, 'img' => 'assets/trainer5.jpeg'],
    ['FirstName' => 'Lij', 'LastName' => 'Bautista', 'SpecializationType' => 'Head Strength Coach', 'RatePerHour' => null, 'img' => 'assets/trainer6.jpeg'],
    ['FirstName' => 'Jeo', 'LastName' => 'Isla', 'SpecializationType' => 'Head Strength Coach', 'RatePerHour' => null, 'img' => 'assets/trainer7.jpeg'],
    ['FirstName' => 'Prins', 'LastName' => 'Pabuna', 'SpecializationType' => 'Head Strength Coach', 'RatePerHour' => null, 'img' => 'assets/trainer8.jpeg'],
];

$displayTrainers = !empty($trainers) ? array_slice($trainers, 0, 8) : $fallbackTrainers;

$planFeaturesMap = [
    'BASIC' => [
        'Gym Access',
        '2x Fitness Consultant',
        'Nutrition Tracking',
        '3 Days per week',
        'Personal Trainer',
    ],
    'MID' => [
        'Full Gym Access',
        '4x Fitness Consultant',
        'Nutrition Tracking',
        '5 Days per week',
        'Personal Trainer',
    ],
    'PRO' => [
        'Unlimited Gym Access',
        '7x Fitness Consultant',
        'Nutrition Tracking',
        'Gym Card',
        'Personal Trainer',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BIG Gainz Fitness - Dagupan Gym &amp; Membership Portal</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=M+PLUS+1:wght@400;500&family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&family=Orbitron:wght@800;900&family=Oswald:wght@700&family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    .inputSample {
      font-size: 2rem;
    }

    :root {
      --bg-dark: #0a0a0c;
      --bg-card: #2D2D2F;
      --bg-card-hover: #38383a;
      --text-main: #ffffff;
      --text-muted: #a0a5b5;
      --accent-light: #e5e5e5;

      --font-navbar: 'Malgun Gothic', 'Dotum', 'Apple SD Gothic Neo', sans-serif;
      --font-logo: 'Impact', 'Haettenschweiler', 'Arial Narrow Bold', sans-serif;
      --font-hero-title: 'Outfit', sans-serif;
      --font-hero-desc: 'M PLUS 1', sans-serif;
      --font-btn-member: 'Oswald', sans-serif;
      --font-transformation: 'Outfit', sans-serif;
      --font-section-titles: 'Orbitron', sans-serif;
      --font-body-descriptions: 'Montserrat', sans-serif;
      --font-inter: 'Inter', sans-serif;

      --transition-fast: all 0.25s cubic-bezier(0.25, 0.8, 0.25, 1);
      --transition-smooth: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
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
      overflow-x: hidden;
      scroll-behavior: smooth;
    }

    p, span, li, div, button {
      font-family: var(--font-body-descriptions);
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    .site-container {
      width: 100%;
      max-width: 1240px;
      margin: 0 auto;
      padding-left: 20px;
      padding-right: 20px;
    }

    .text-stroke {
      color: transparent;
      -webkit-text-stroke: 1px rgba(255, 255, 255, 0.3);
    }

    .text-center-wrapper {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }

    .centered-desc {
      max-width: 600px;
      margin-top: 15px;
      margin-bottom: 40px;
    }

    .animate-on-scroll {
      opacity: 0;
      transform: translateY(30px);
      transition: opacity 0.8s ease-out, transform 0.8s ease-out;
      will-change: opacity, transform;
    }

    .animate-on-scroll.animated {
      opacity: 1;
      transform: translateY(0);
    }

    .delay-1 { transition-delay: 0.15s; }
    .delay-2 { transition-delay: 0.3s; }
    .delay-3 { transition-delay: 0.45s; }

    .site-header {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1000;
      background: rgba(10, 10, 12, 0.85);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      transition: var(--transition-smooth);
    }

    .site-header.scrolled {
      background: rgba(10, 10, 12, 0.95);
      padding-top: 5px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.6);
    }

    .header-container {
      display: flex;
      align-items: center;
      justify-content: space-between;
      max-width: 1240px;
      margin: 0 auto;
      padding: 18px 20px;
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

    .hamburger-btn {
      display: none;
      background: transparent;
      border: none;
      color: #fff;
      font-size: 1.5rem;
      cursor: pointer;
      padding: 5px;
    }

    .nav-drawer {
      display: flex;
      align-items: center;
      gap: 30px;
    }

    .nav-links-list {
      display: flex;
      list-style: none;
      gap: 10px;
      margin-bottom: 10px;
    }

    .nav-link {
      font-family: var(--font-navbar) !important;
      color: var(--text-muted);
      font-weight: 600;
      font-size: 0.85rem;
      letter-spacing: 1px;
      padding: 8px 16px;
      margin-top: 12px;
      transition: var(--transition-fast);
    }

    .nav-link:hover, .nav-link.active {
      color: var(--text-main);
    }

    .drawer-header {
      display: none;
    }

    .drawer-footer {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .btn-admin {
      font-family: var(--font-navbar) !important;
      background-color: #000;
      color: #fff;
      border: 1px solid #333;
      font-weight: 700;
      font-size: 0.8rem;
      padding: 8px 20px;
      border-radius: 4px;
      letter-spacing: 0.5px;
      transition: var(--transition-fast);
      display: inline-block;
    }

    .btn-admin:hover {
      background-color: #222;
      color: #fff;
    }

    .nav-overlay {
      display: none;
    }

    .hero-section {
      position: relative;
      min-height: 100vh;
      background: url('assets/img1.jpg') center/cover no-repeat;
      display: flex;
      align-items: center;
    }

    .hero-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(180deg, rgba(10, 10, 12, 0.7) 0%, rgba(10, 10, 12, 0.95) 100%);
      z-index: 1;
    }

    .hero-container {
      position: relative;
      z-index: 2;
      padding-top: 100px;
      padding-bottom: 60px;
    }

    .hero-flex {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 40px;
    }

    .hero-left {
      flex: 1 1 500px;
    }

    .hero-right {
      flex: 0 1 380px;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }

    .hero-title {
      font-family: var(--font-hero-title) !important;
      font-weight: 900 !important;
      font-size: clamp(2.8rem, 6vw, 5.5rem);
      line-height: 0.95;
      letter-spacing: -1px;
      text-transform: uppercase;
    }

    .transformation-box {
      display: flex;
      align-items: center;
      margin-top: 25px;
    }

    .transformation-avatars {
      display: flex;
      margin-right: 15px;
    }

    .avatar {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--bg-dark);
      margin-left: -12px;
    }

    .avatar:first-child {
      margin-left: 0;
    }

    .transformation-count {
      font-family: var(--font-transformation) !important;
      font-weight: 800;
      font-size: 1.8rem;
      line-height: 1;
    }

    .transformation-label {
      font-family: var(--font-transformation) !important;
      color: var(--text-muted);
      font-size: 0.85rem;
    }

    .hero-description {
      font-family: var(--font-hero-desc) !important;
      font-weight: 400 !important;
      color: var(--text-muted);
      font-size: 1.3rem;
      line-height: 1.6;
      margin-bottom: 25px;
    }

    .btn-member {
      font-family: var(--font-btn-member) !important;
      font-weight: 700 !important;
      text-transform: uppercase;
      background-color: var(--accent-light);
      color: #000;
      padding: 12px 28px;
      border-radius: 4px;
      letter-spacing: 2.5px;
      transition: var(--transition-fast);
      display: inline-block;
      font-size: 1.3rem;
    }

    .btn-member:hover {
      background-color: #ffffff;
      transform: translateY(-3px);
      color: #949494;
      box-shadow: 0 10px 20px rgba(255, 255, 255, 0.15);
    }

    .studio-section {
      padding: 60px 0;
    }

    .section-header-flex {
      display: flex;
      flex-wrap: wrap;
      align-items: flex-start;
      justify-content: space-between;
      gap: 30px;
    }

    .section-title-col {
      flex: 1 1 450px;
    }

    .section-desc-col {
      flex: 1 1 400px;
    }

    .studio-title,
    .trainers-title,
    .plans-title,
    .connect-title,
    .section-title {
      font-family: var(--font-section-titles) !important;
      font-weight: 900 !important;
      font-size: clamp(2rem, 4vw, 3rem);
      line-height: 1.1;
      text-transform: uppercase;
    }

    .section-description {
      font-family: var(--font-body-descriptions) !important;
      color: var(--text-muted);
      font-size: 1.3rem;
      line-height: 1.6;
    }

    .studio-carousel {
      max-width: 100%;
    }

    .curved-frame {
      position: relative;
      border-top: 1px solid rgba(255, 255, 255, 0.2);
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
      padding: 28px 0;
      border-radius: 50% / 12px;
    }

    .carousel-flex-row {
      display: flex;
      gap: 18px;
      width: 100%;
    }

    .carousel-card {
      flex: 1 1 25%;
    }

    .carousel-img {
      width: 100%;
      height: 300px;
      object-fit: cover;
      border-radius: 8px;
      filter: brightness(0.85);
      transition: var(--transition-fast);
    }

    .carousel-img:hover {
      filter: brightness(1);
      transform: scale(1.02);
    }

    .trainers-section {
      padding: 60px 0;
    }

    .flex-cards-row {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-top: 20px;
    }

    .card-column {
      flex: 1 1 calc(25% - 20px);
      min-width: 240px;
    }

    .trainer-card {
      background-color: var(--bg-card);
      border-radius: 8px;
      overflow: hidden;
      transition: var(--transition-smooth);
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    .trainer-card:hover {
      transform: translateY(-6px);
      background-color: var(--bg-card-hover);
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5);
    }

    .trainer-img-wrapper {
      width: 100%;
      height: 260px;
      overflow: hidden;
    }

    .trainer-img-wrapper img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: var(--transition-smooth);
    }

    .trainer-card:hover .trainer-img-wrapper img {
      transform: scale(1.05);
    }

    .trainer-info {
      padding: 20px 15px;
      text-align: center;
    }

    .trainer-info h5 {
      font-family: var(--font-body-descriptions) !important;
      font-size: 1.1rem;
      font-weight: 700;
      margin-bottom: 4px;
    }

    .trainer-info p {
      font-family: var(--font-body-descriptions) !important;
      color: var(--text-muted);
      font-size: 0.8rem;
      margin-bottom: 10px;
    }

    .trainer-rate {
      font-family: var(--font-inter) !important;
      font-size: 0.85rem;
      font-weight: 600;
      color: #fff;
      margin-bottom: 12px;
    }

    .social-links {
      display: flex;
      justify-content: center;
      gap: 12px;
    }

    .social-links a {
      color: var(--text-muted);
      font-size: 0.85rem;
      transition: var(--transition-fast);
    }

    .social-links a:hover {
      color: #fff;
    }

    .plans-section {
      padding: 60px 0;
    }

    .toggle-switch-wrapper {
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: var(--bg-card);
      padding: 8px 24px;
      border-radius: 30px;
      border: 1px solid rgba(255, 255, 255, 0.08);
      margin-bottom: 40px;
    }

    .toggle-label {
      font-family: var(--font-inter) !important;
      font-weight: 400 !important;
      font-size: 0.9rem;
      color: var(--text-muted);
      margin: 0 12px;
      transition: var(--transition-fast);
    }

    .toggle-label.active {
      color: #fff;
    }

    .custom-toggle {
      position: relative;
      display: inline-block;
      width: 45px;
      height: 24px;
    }

    .custom-toggle input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .toggle-slider {
      position: absolute;
      cursor: pointer;
      top: 0; left: 0; right: 0; bottom: 0;
      background-color: #1a1a1c;
      transition: .3s;
      border-radius: 24px;
    }

    .toggle-slider:before {
      position: absolute;
      content: "";
      height: 18px;
      width: 18px;
      left: 3px;
      bottom: 3px;
      background-color: white;
      transition: .3s;
      border-radius: 50%;
    }

    input:checked + .toggle-slider {
      background-color: #48484a;
    }

    input:checked + .toggle-slider:before {
      transform: translateX(21px);
    }

    .plans-row {
      align-items: stretch;
    }

    .plan-card-column {
      flex: 1 1 calc(33.333% - 20px);
      min-width: 280px;
    }

    .plan-card {
      position: relative;
      background-color: #2D2D2F !important;
      border-radius: 8px;
      padding: 35px 25px;
      display: flex;
      flex-direction: column;
      height: 100%;
      transition: var(--transition-smooth);
      border: 1px solid rgba(255, 255, 255, 0.05);
      overflow: hidden;
    }

    .plan-card:hover {
      transform: translateY(-5px);
      border-color: rgba(255, 255, 255, 0.18);
    }

    .plan-card.featured {
      border: 1px solid rgba(255, 255, 255, 0.25);
    }

    .best-offer-ribbon {
      position: absolute;
      top: 18px;
      right: -30px;
      background-color: #fff;
      color: #000;
      transform: rotate(45deg);
      padding: 4px 35px;
      font-family: var(--font-inter) !important;
      font-weight: 400 !important;
      font-size: 0.65rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    .plan-title {
      font-family: var(--font-body-descriptions) !important;
      font-weight: 900 !important;
      font-size: 1.3rem;
      margin-bottom: 12px;
    }

    .plan-desc {
      font-family: var(--font-inter) !important;
      font-size: 0.85rem;
      color: var(--text-muted);
      margin-bottom: 16px;
    }

    .plan-price {
      font-family: var(--font-body-descriptions) !important;
      font-weight: 800 !important;
      font-size: 2.5rem;
      line-height: 1;
    }

    .plan-price .currency {
      font-family: var(--font-body-descriptions) !important;
      font-weight: 800 !important;
      font-size: 1.5rem;
      vertical-align: super;
      margin-right: 2px;
    }

    .billing-period {
      font-family: var(--font-inter) !important;
      font-weight: 400 !important;
      font-size: 0.8rem;
      color: var(--text-muted);
      margin-bottom: 20px;
      margin-top: 5px;
    }

    .plan-features {
      list-style: none;
      padding: 0;
      margin: 0 0 25px 0;
    }

    .plan-features li {
      font-family: var(--font-inter) !important;
      font-weight: 400 !important;
      font-size: 0.85rem;
      color: #d1d5db;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .plan-features li i {
      color: #fff;
      font-size: 0.9rem;
    }

    .btn-plan {
      font-family: var(--font-body-descriptions) !important;
      font-weight: 600 !important;
      background-color: #000;
      color: #fff;
      font-size: 0.85rem;
      padding: 12px;
      border-radius: 4px;
      border: 1px solid #111;
      width: 100%;
      margin-top: auto;
      cursor: pointer;
      transition: var(--transition-fast);
      display: block;
      text-align: center;
    }

    .btn-plan:hover {
      background-color: #fff;
      color: #000;
    }

    .connect-section {
      position: relative;
      padding: 80px 0 90px 0;
      overflow: hidden;
    }

    .connect-section::after {
      content: "";
      position: absolute;
      right: -120px;
      top: 50%;
      transform: translateY(-50%) rotate(45deg);
      width: 420px;
      height: 420px;
      border: 42px solid rgba(255, 255, 255, 0.04);
      pointer-events: none;
      z-index: 0;
    }

    .connect-grid {
      position: relative;
      z-index: 1;
      display: grid;
      grid-template-columns: 1fr 1.05fr;
      gap: 50px;
      align-items: start;
    }

    .connect-left-heading {
      font-family: var(--font-section-titles) !important;
      font-weight: 900 !important;
      font-size: clamp(2rem, 3.6vw, 2.85rem);
      line-height: 1.12;
      text-transform: uppercase;
      color: #fff;
      margin-bottom: 18px;
    }

    .connect-left-heading .connect-accent-muted {
      font-family: var(--font-section-titles) !important;
      color: #4f5259;
    }

    .connect-left-desc {
      font-family: var(--font-inter) !important;
      font-size: 0.95rem;
      line-height: 1.6;
      color: #b8bcc8;
      max-width: 430px;
      margin-bottom: 28px;
    }

    .contact-channel-list {
      display: flex;
      flex-direction: column;
      gap: 16px;
      max-width: 410px;
    }

    .contact-channel-box {
      display: flex;
      align-items: center;
      gap: 18px;
      background-color: #0c0d10;
      border: 1px solid rgba(255, 255, 255, 0.16);
      border-radius: 14px;
      padding: 15px 22px;
      transition: var(--transition-fast);
    }

    .contact-channel-box:hover {
      border-color: rgba(255, 255, 255, 0.36);
      background-color: #121317;
    }

    .contact-channel-icon {
      font-size: 1.35rem;
      color: #ffffff;
      width: 26px;
      text-align: center;
      flex-shrink: 0;
    }

    .contact-channel-text {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .contact-channel-label {
      font-family: var(--font-inter) !important;
      font-size: 0.78rem;
      color: #9da2b0;
    }

    .contact-channel-value {
      font-family: var(--font-inter) !important;
      font-size: 0.92rem;
      font-weight: 700;
      color: #ffffff;
    }

    .connect-form-shell {
      background: linear-gradient(145deg, #232428 0%, #141518 65%, #191a1e 100%);
      border: 1px solid rgba(255, 255, 255, 0.14);
      border-radius: 22px;
      padding: 36px 34px;
      box-shadow: 0 24px 60px rgba(0, 0, 0, 0.75);
    }

    .connect-form-title {
      font-family: var(--font-inter) !important;
      font-size: 1.45rem;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 6px;
    }

    .connect-form-subtitle {
      font-family: var(--font-inter) !important;
      font-size: 0.85rem;
      color: #a0a5b5;
      margin-bottom: 24px;
    }

    .connect-form-shell .form-label {
      font-family: var(--font-inter) !important;
      font-size: 0.82rem;
      font-weight: 600;
      color: #e5e7eb;
      margin-bottom: 6px;
    }

    .connect-form-shell .form-control {
      background-color: #36373b;
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 8px;
      color: #ffffff;
      font-family: var(--font-inter) !important;
      font-size: 0.88rem;
      padding: 10px 14px;
    }

    .connect-form-shell .form-control::placeholder {
      color: #8e929e;
    }

    .connect-form-shell .form-control:focus {
      background-color: #3e3f44;
      border-color: rgba(255, 255, 255, 0.3);
      color: #ffffff;
      box-shadow: none;
    }

    .btn-send-message {
      font-family: var(--font-inter) !important;
      font-size: 0.88rem;
      font-weight: 700;
      color: #ffffff;
      background-color: #38393d;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      padding: 12px 20px;
      width: 100%;
      cursor: pointer;
      transition: var(--transition-fast);
    }

    .btn-send-message:hover {
      background-color: #ffffff;
      color: #000000;
    }

    .site-footer {
      background-color: #050506;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      padding: 64px 0 28px 0;
      color: var(--text-muted);
    }

    .footer-top-grid {
      display: grid;
      grid-template-columns: 1.35fr 1fr 1.15fr 1.15fr;
      gap: 40px;
      padding-bottom: 52px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    }

    .footer-brand-stack {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 18px;
    }

    .footer-custom-logo {
      display: inline-flex;
      flex-direction: column;
      align-items: center;
      line-height: 1;
      color: #ffffff;
    }

    .footer-logo-top {
      font-family: var(--font-section-titles) !important;
      font-weight: 900;
      font-size: 2rem;
      letter-spacing: 3px;
      display: flex;
      align-items: center;
      gap: 2px;
    }

    .footer-logo-sub {
      font-family: var(--font-section-titles) !important;
      font-weight: 800;
      font-size: 0.62rem;
      letter-spacing: 3.5px;
      color: #d1d5db;
      margin-top: 3px;
    }

    .footer-socials {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-top: 4px;
    }

    .footer-socials a {
      color: #ffffff;
      font-size: 1.25rem;
      transition: var(--transition-fast);
    }

    .footer-socials a:hover {
      color: var(--text-muted);
      transform: translateY(-2px);
    }

    .footer-col-heading {
      font-family: var(--font-inter) !important;
      font-size: 0.92rem;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 18px;
    }

    .footer-links-list {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .footer-links-list a {
      font-family: var(--font-inter) !important;
      font-size: 0.85rem;
      color: #b8bcc8;
      transition: var(--transition-fast);
    }

    .footer-links-list a:hover {
      color: #ffffff;
    }

    .footer-hours-list {
      display: flex;
      flex-direction: column;
      gap: 14px;
      font-family: var(--font-inter) !important;
      font-size: 0.84rem;
      color: #b8bcc8;
      line-height: 1.45;
    }

    .footer-visit-list {
      display: flex;
      flex-direction: column;
      gap: 16px;
      font-family: var(--font-inter) !important;
      font-size: 0.84rem;
      color: #b8bcc8;
      line-height: 1.45;
    }

    .footer-bottom-bar {
      padding-top: 24px;
      text-align: center;
      font-family: var(--font-inter) !important;
      font-size: 0.8rem;
      color: #a0a5b5;
    }

    .footer-bottom-bar a {
      color: #a0a5b5;
      transition: var(--transition-fast);
    }

    .footer-bottom-bar a:hover {
      color: #ffffff;
    }

    @media (max-width: 992px) {
      .hero-section {
        margin-top: 100px;
      }

      .hero-title {
        font-size: 5rem;
      }

      .hero-flex {
        flex-direction: column;
        text-align: center;
      }

      .hero-right {
        align-items: center;
      }

      .transformation-box {
        justify-content: center;
      }

      .carousel-flex-row {
        flex-wrap: wrap;
      }

      .carousel-card {
        flex: 1 1 calc(50% - 10px);
      }

      .carousel-img {
        height: 190px;
      }

      .curved-frame {
        border-radius: 0;
      }

      .connect-grid {
        grid-template-columns: 1fr;
        gap: 36px;
      }

      .contact-channel-list {
        max-width: 100%;
      }

      .footer-top-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .hamburger-btn {
        display: block;
      }

      .nav-drawer {
        position: fixed;
        top: 0;
        right: -300px;
        width: 280px;
        height: 100vh;
        background-color: #121316;
        flex-direction: column;
        align-items: flex-start;
        padding: 30px 25px;
        gap: 30px;
        z-index: 1002;
        transition: right 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        box-shadow: -5px 0 25px rgba(0, 0, 0, 0.8);
      }

      .nav-drawer.open {
        right: 0;
      }

      .drawer-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding-bottom: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      }

      .close-btn {
        background: transparent;
        border: none;
        color: #fff;
        font-size: 1.4rem;
        cursor: pointer;
      }

      .nav-links-list {
        flex-direction: column;
        width: 100%;
        gap: 15px;
      }

      .nav-link {
        display: block;
        padding: 10px 0;
        font-size: 1rem;
      }

      .drawer-footer {
        width: 100%;
        margin-top: auto;
        flex-direction: column;
      }

      .drawer-footer .btn-admin {
        width: 100%;
        text-align: center;
      }

      .nav-overlay {
        display: block;
        position: fixed;
        top: 0; left: 0;
        width: 100vw; height: 100vh;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(4px);
        z-index: 1001;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.4s ease;
      }

      .nav-overlay.active {
        opacity: 1;
        pointer-events: auto;
      }
    }

    @media (max-width: 768px) {
      .hero-flex {
        flex-direction: column;
        text-align: center;
      }

      .hero-right {
        align-items: center;
      }

      .transformation-box {
        justify-content: center;
      }

      .carousel-flex-row {
        flex-wrap: wrap;
      }

      .carousel-card {
        flex: 1 1 calc(50% - 10px);
      }

      .carousel-img {
        height: 175px;
      }

      .curved-frame {
        border-radius: 0;
      }

      .connect-form-shell {
        padding: 26px 20px;
      }

      .footer-top-grid {
        grid-template-columns: 1fr;
        gap: 28px;
      }

      .footer-brand-stack {
        align-items: center;
        text-align: center;
      }
    }
  </style>
</head>
<body>

  <header class="site-header fixed-top">
    <div class="header-container">
      <a class="site-brand" href="#home">
        <span class="logo-icon">B<i class="fa-solid fa-dumbbell"></i>G</span>
        <span class="logo-text">GainzFitness</span>
      </a>

      <button class="hamburger-btn" id="menuToggle" aria-label="Toggle navigation">
        <i class="fa-solid fa-bars"></i>
      </button>

      <div class="nav-overlay" id="navOverlay"></div>

      <div class="nav-drawer" id="navDrawer">
        <div class="drawer-header">
          <span class="logo-text">GainzFitness</span>
          <button class="close-btn" id="menuClose" aria-label="Close navigation">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <ul class="nav-links-list">
          <li class="nav-item"><a class="nav-link" href="#home">HOME</a></li>
          <li class="nav-item"><a class="nav-link" href="#studio">STUDIO</a></li>
          <li class="nav-item"><a class="nav-link" href="#trainers">TRAINERS</a></li>
          <li class="nav-item"><a class="nav-link" href="#plans">WELLNESS PLANS</a></li>
          <li class="nav-item"><a class="nav-link" href="#connect">CONNECT</a></li>
        </ul>

        <div class="drawer-footer">
          <?php if ($isLoggedIn): ?>
            <a href="<?= htmlspecialchars($dashboardUrl) ?>" class="btn btn-admin">PORTAL</a>
            <a href="logout.php" class="btn btn-admin">LOGOUT</a>
          <?php else: ?>
            <a href="login.php" class="btn btn-admin">LOGIN</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <section id="home" class="hero-section">
    <div class="hero-overlay"></div>
    <div class="site-container hero-container">
      <div class="hero-flex">
        <div class="hero-left animate-on-scroll fade-up">
          <h1 class="hero-title">BUILD YOUR<br>ULTIMATE<br>STRENGTH</h1>

          <div class="transformation-box">
            <div class="transformation-avatars">
              <img src="assets/transform1.jpeg" alt="Transformation member" class="avatar">
              <img src="assets/transform2.jpeg" alt="Transformation member" class="avatar">
              <img src="assets/transform3.jpeg" alt="Transformation member" class="avatar">
            </div>
            <div class="transformation-details">
              <div class="transformation-count">200+</div>
              <div class="transformation-label">Transformation</div>
            </div>
          </div>
        </div>

        <div class="hero-right animate-on-scroll fade-up delay-2">
          <p class="hero-description">
            Heavy-duty gym equipment, personalized coaching, and an elite community engineered to unlock your peak athletic performance.
          </p>
          <?php if ($isLoggedIn): ?>
            <a href="<?= htmlspecialchars($dashboardUrl) ?>" class="btn btn-member">OPEN DASHBOARD</a>
          <?php else: ?>
            <a href="#plans" class="btn btn-member">BECOME A MEMBER</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <section id="studio" class="studio-section">
    <div class="site-container py-5">
      <div class="section-header-flex animate-on-scroll fade-up">
        <div class="section-title-col">
          <h2 class="section-title studio-title">BIG GAINZ<br>FITNESS <span class="text-stroke section-title">STUDIO</span></h2>
        </div>
        <div class="section-desc-col">
          <p class="section-description">
            From heavy free-weight training to advanced cardio decks and luxury recovery lounges, every corner of our studio is built to support your daily strength journey.
          </p>
        </div>
      </div>

      <div id="studioCarousel" class="carousel slide studio-carousel mt-5 animate-on-scroll fade-up delay-1" data-bs-ride="carousel" data-bs-interval="3000">
        <div class="carousel-inner curved-frame">
          <div class="carousel-item active">
            <div class="carousel-flex-row">
              <div class="carousel-card"><img src="assets/kar1.jpg" class="carousel-img" alt="Studio Area 1"></div>
              <div class="carousel-card"><img src="assets/kar2.jpg" class="carousel-img" alt="Studio Area 2"></div>
              <div class="carousel-card"><img src="assets/kar3.jpg" class="carousel-img" alt="Studio Area 3"></div>
              <div class="carousel-card"><img src="assets/kar4.jpg" class="carousel-img" alt="Studio Area 4"></div>
            </div>
          </div>
          <div class="carousel-item">
            <div class="carousel-flex-row">
              <div class="carousel-card"><img src="assets/kar5.jpg" class="carousel-img" alt="Studio Area 5"></div>
              <div class="carousel-card"><img src="assets/kar1.jpg" class="carousel-img" alt="Studio Area 6"></div>
              <div class="carousel-card"><img src="assets/kar2.jpg" class="carousel-img" alt="Studio Area 7"></div>
              <div class="carousel-card"><img src="assets/kar3.jpg" class="carousel-img" alt="Studio Area 8"></div>
            </div>
          </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#studioCarousel" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#studioCarousel" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Next</span>
        </button>
      </div>
    </div>
  </section>

  <section id="trainers" class="trainers-section">
    <div class="site-container py-5">
      <div class="text-center-wrapper animate-on-scroll fade-up">
        <h2 class="section-title trainers-title">MEET OUR <span class="text-stroke section-title">TRAINERS</span></h2>
        <p class="section-description centered-desc">
          Certified fitness experts dedicated to guiding your strength, conditioning, and transformation goals.
        </p>
      </div>

      <div class="flex-cards-row">
        <?php foreach ($displayTrainers as $idx => $t):
          $imgSrc = $t['img'] ?? ('assets/trainer' . (($idx % 8) + 1) . '.jpeg');
          $fullName = trim(($t['FirstName'] ?? '') . ' ' . ($t['LastName'] ?? ''));
          $spec = $t['SpecializationType'] ?? 'Head Strength Coach';
          $rate = isset($t['RatePerHour']) && $t['RatePerHour'] !== null ? (float)$t['RatePerHour'] : null;
          $delayClass = $idx === 0 ? '' : ($idx === 1 ? 'delay-1' : ($idx === 2 ? 'delay-2' : 'delay-3'));
        ?>
          <div class="card-column animate-on-scroll fade-up <?= $delayClass ?>">
            <div class="trainer-card">
              <div class="trainer-img-wrapper">
                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($fullName) ?>">
              </div>
              <div class="trainer-info">
                <h5><?= htmlspecialchars($fullName) ?></h5>
                <p><?= htmlspecialchars($spec) ?></p>
                <?php if ($rate !== null): ?>
                  <div class="trainer-rate">&#8369;<?= number_format($rate, 2) ?>/hr</div>
                <?php endif; ?>
                <div class="social-links">
                  <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                  <a href="#"><i class="fa-brands fa-instagram"></i></a>
                  <a href="#"><i class="fa-brands fa-twitter"></i></a>
                  <a href="#"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section id="plans" class="plans-section">
    <div class="site-container py-5">
      <div class="text-center-wrapper animate-on-scroll fade-up">
        <h2 class="section-title plans-title">WELLNESS <span class="text-stroke section-title">PLANS</span></h2>
        <p class="section-description centered-desc">
          Choose the right package engineered for your fitness goals. Upgrade or cancel anytime with zero hidden fees.
        </p>

        <div class="toggle-switch-wrapper">
          <span class="toggle-label" id="label-monthly">Monthly</span>
          <label class="custom-toggle">
            <input type="checkbox" id="billingToggle" checked>
            <span class="toggle-slider"></span>
          </label>
          <span class="toggle-label active" id="label-yearly">Yearly</span>
        </div>
      </div>

      <div class="flex-cards-row plans-row" id="monthlyPlans" style="display: none;">
        <?php foreach ($displayPlans as $idx => $plan):
          $type = strtoupper((string)$plan['MembershipType']);
          $isFeatured = ($type === 'MID' || ($type === 'PRO' && count($displayPlans) === 1));
          $monthlyRate = $monthlyPricesByPlan[$type] ?? (float)$plan['MembershipRateMonthly'];
          $features = $planFeaturesMap[$type] ?? [
              'Gym Access',
              'Fitness Consultant',
              'Nutrition Tracking',
              'Progress Check-ins',
              'Personal Trainer',
          ];
          $delayClass = $idx === 0 ? '' : ($idx === 1 ? 'delay-1' : 'delay-2');
        ?>
          <div class="plan-card-column animate-on-scroll fade-up <?= $delayClass ?>">
            <div class="plan-card <?= $isFeatured ? 'featured' : '' ?>">
              <?php if ($isFeatured): ?>
                <div class="best-offer-ribbon">
                  <span>Best Offer</span>
                </div>
              <?php endif; ?>
              <h3 class="plan-title"><?= htmlspecialchars($type) ?> Package</h3>
              <?php if (!empty($plan['MembershipDesc'])): ?>
                <p class="plan-desc"><?= htmlspecialchars($plan['MembershipDesc']) ?></p>
              <?php endif; ?>
              <div class="plan-price">
                <span class="currency">&#8369;</span>
                <span class="price-value"><?= number_format((float)$monthlyRate, 0) ?></span>
              </div>
              <p class="billing-period">per month, billed monthly</p>
              <ul class="plan-features">
                <?php foreach ($features as $feat): ?>
                  <li><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($feat) ?></li>
                <?php endforeach; ?>
              </ul>
              <a href="member_signup.php?plan=<?= urlencode($type) ?>&amp;billing=monthly" class="btn btn-plan">Register Now</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="flex-cards-row plans-row" id="yearlyPlans" style="display: flex;">
        <?php foreach ($displayPlans as $idx => $plan):
          $type = strtoupper((string)$plan['MembershipType']);
          $isFeatured = ($type === 'MID' || ($type === 'PRO' && count($displayPlans) === 1));
          $yearlyRate = $yearlyPricesByPlan[$type] ?? (float)$plan['MembershipRateYearly'];
          $features = $planFeaturesMap[$type] ?? [
              'Gym Access',
              'Fitness Consultant',
              'Nutrition Tracking',
              'Progress Check-ins',
              'Personal Trainer',
          ];
          $delayClass = $idx === 0 ? '' : ($idx === 1 ? 'delay-1' : 'delay-2');
        ?>
          <div class="plan-card-column animate-on-scroll fade-up <?= $delayClass ?>">
            <div class="plan-card <?= $isFeatured ? 'featured' : '' ?>">
              <?php if ($isFeatured): ?>
                <div class="best-offer-ribbon">
                  <span>Best Offer</span>
                </div>
              <?php endif; ?>
              <h3 class="plan-title"><?= htmlspecialchars($type) ?> Package</h3>
              <?php if (!empty($plan['MembershipDesc'])): ?>
                <p class="plan-desc"><?= htmlspecialchars($plan['MembershipDesc']) ?></p>
              <?php endif; ?>
              <div class="plan-price">
                <span class="currency">&#8369;</span>
                <span class="price-value"><?= number_format((float)$yearlyRate, 0) ?></span>
              </div>
              <p class="billing-period">per year, billed annually</p>
              <ul class="plan-features">
                <?php foreach ($features as $feat): ?>
                  <li><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($feat) ?></li>
                <?php endforeach; ?>
              </ul>
              <a href="member_signup.php?plan=<?= urlencode($type) ?>&amp;billing=yearly" class="btn btn-plan">Register Now</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section id="connect" class="connect-section">
    <div class="site-container">
      <div class="connect-grid">
        <div class="animate-on-scroll fade-up">
          <h2 class="connect-left-heading">CONNECT <span class="connect-accent-muted">TO</span><br>OUR TEAM</h2>
          <p class="connect-left-desc">
            Have questions, feedback, or need assistance? We're here to help. Reach out to us through any of the channels below.
          </p>

          <div class="contact-channel-list">
            <div class="contact-channel-box">
              <div class="contact-channel-icon"><i class="fa-regular fa-envelope"></i></div>
              <div class="contact-channel-text">
                <span class="contact-channel-label">Email Us</span>
                <span class="contact-channel-value">biggainzfitness@gmail.com</span>
              </div>
            </div>

            <div class="contact-channel-box">
              <div class="contact-channel-icon"><i class="fa-solid fa-phone"></i></div>
              <div class="contact-channel-text">
                <span class="contact-channel-label">Call Us</span>
                <span class="contact-channel-value">+63 963 456 4067</span>
              </div>
            </div>

            <div class="contact-channel-box">
              <div class="contact-channel-icon"><i class="fa-solid fa-location-dot"></i></div>
              <div class="contact-channel-text">
                <span class="contact-channel-label">Our Location</span>
                <span class="contact-channel-value">673 Arellano Biringan, Dagupan City</span>
              </div>
            </div>
          </div>
        </div>

        <div class="animate-on-scroll fade-up delay-1">
          <div class="connect-form-shell">
            <h3 class="connect-form-title">Send Us a Message</h3>
            <p class="connect-form-subtitle">Fill out the form below with your details and we'll respond quickly as possible.</p>

            <?php if ($contactNotice): ?>
              <div class="alert alert-success"><?= htmlspecialchars($contactNotice) ?></div>
            <?php endif; ?>
            <?php if ($contactError): ?>
              <div class="alert alert-danger"><?= htmlspecialchars($contactError) ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php#connect" class="row g-3">
              <div class="col-12">
                <label class="form-label">Name</label>
                <div class="row g-2">
                  <div class="col-sm-6">
                    <input type="text" name="first_name" class="form-control" placeholder="Enter your first name" required>
                  </div>
                  <div class="col-sm-6">
                    <input type="text" name="last_name" class="form-control" placeholder="Enter your last name" required>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <label class="form-label">Email address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email address" required>
              </div>
              <div class="col-12">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" placeholder="Enter your phone number">
              </div>
              <div class="col-12">
                <label class="form-label">Message</label>
                <textarea name="message_body" rows="4" class="form-control" placeholder="Leave a message...." required></textarea>
              </div>
              <div class="col-12 pt-1">
                <button type="submit" name="send_contact" class="btn-send-message">Send Message</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>

  <footer class="site-footer">
    <div class="site-container">
      <div class="footer-top-grid">
        <div class="footer-brand-stack">
          <a href="#home" class="footer-custom-logo">
            <span class="footer-logo-top">B<i class="fa-solid fa-dumbbell"></i>G</span>
            <span class="footer-logo-sub">GAINZFITNESS</span>
          </a>
          <div class="footer-socials">
            <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
            <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
            <a href="#" aria-label="Twitter"><i class="fa-brands fa-twitter"></i></a>
          </div>
        </div>

        <div>
          <h4 class="footer-col-heading">Quick Links</h4>
          <ul class="footer-links-list">
            <li><a href="#home">Home</a></li>
            <li><a href="#studio">Studio</a></li>
            <li><a href="#trainers">Trainers</a></li>
            <li><a href="#plans">Wellness Plan</a></li>
            <li><a href="#connect">Contact Us</a></li>
          </ul>
        </div>

        <div>
          <h4 class="footer-col-heading">Operating Hours</h4>
          <div class="footer-hours-list">
            <div>Monday &ndash; Friday:<br>6:00 AM &ndash; 10:00 PM</div>
            <div>Saturday:<br>7:00 AM &ndash; 8:00 PM</div>
            <div>Sunday:<br>Walk-ins Only</div>
          </div>
        </div>

        <div>
          <h4 class="footer-col-heading">Visit Us</h4>
          <div class="footer-visit-list">
            <div>673 Arellano Biringan,<br>Dagupan City</div>
            <div>+63 963 456 4067</div>
            <div>biggainzfitness@gmail.com</div>
          </div>
        </div>
      </div>

      <div class="footer-bottom-bar">
        &copy; <?= date('Y') ?> Big Gainz Fitness. All Rights Reserved. &nbsp;&middot;&nbsp; <a href="#">Terms &amp; Conditions</a> &nbsp;&middot;&nbsp; <a href="#">Data Privacy</a>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const studioCarouselElement = document.getElementById('studioCarousel');
      if (studioCarouselElement && typeof bootstrap !== 'undefined') {
        const carouselInstance = new bootstrap.Carousel(studioCarouselElement, {
          interval: 3000,
          ride: 'carousel',
          pause: 'hover',
          wrap: true
        });
        carouselInstance.cycle();
      }

      const menuToggle = document.getElementById('menuToggle');
      const menuClose = document.getElementById('menuClose');
      const navDrawer = document.getElementById('navDrawer');
      const navOverlay = document.getElementById('navOverlay');

      function openDrawer() {
        navDrawer.classList.add('open');
        navOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
      }

      function closeDrawer() {
        navDrawer.classList.remove('open');
        navOverlay.classList.remove('active');
        document.body.style.overflow = '';
      }

      if (menuToggle) menuToggle.addEventListener('click', openDrawer);
      if (menuClose) menuClose.addEventListener('click', closeDrawer);
      if (navOverlay) navOverlay.addEventListener('click', closeDrawer);

      const siteHeader = document.querySelector('.site-header');

      window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
          siteHeader.classList.add('scrolled');
        } else {
          siteHeader.classList.remove('scrolled');
        }
      });

      const billingToggle = document.getElementById('billingToggle');
      const labelMonthly = document.getElementById('label-monthly');
      const labelYearly = document.getElementById('label-yearly');
      const monthlyPlans = document.getElementById('monthlyPlans');
      const yearlyPlans = document.getElementById('yearlyPlans');

      if (billingToggle && monthlyPlans && yearlyPlans) {
        billingToggle.addEventListener('change', (e) => {
          const isYearly = e.target.checked;

          if (isYearly) {
            labelYearly.classList.add('active');
            labelMonthly.classList.remove('active');
            monthlyPlans.style.display = 'none';
            yearlyPlans.style.display = 'flex';
          } else {
            labelMonthly.classList.add('active');
            labelYearly.classList.remove('active');
            yearlyPlans.style.display = 'none';
            monthlyPlans.style.display = 'flex';
          }

          const visibleCards = (isYearly ? yearlyPlans : monthlyPlans).querySelectorAll('.animate-on-scroll');
          visibleCards.forEach(card => card.classList.add('animated'));
        });
      }

      const animatedElements = document.querySelectorAll('.animate-on-scroll');

      const observerOptions = {
        root: null,
        threshold: 0.15,
        rootMargin: '0px 0px -50px 0px'
      };

      const scrollObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('animated');
            observer.unobserve(entry.target);
          }
        });
      }, observerOptions);

      animatedElements.forEach(el => scrollObserver.observe(el));

      const navLinks = document.querySelectorAll('.nav-link, .btn-admin, .btn-member');

      navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
          const targetId = link.getAttribute('href');
          if (targetId && targetId.startsWith('#')) {
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
              e.preventDefault();

              closeDrawer();

              targetElement.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
              });
            }
          }
        });
      });
    });
  </script>
</body>
</html>