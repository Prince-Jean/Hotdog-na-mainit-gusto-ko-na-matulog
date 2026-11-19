# BIG Gainz Fitness — Gym Membership Management System

## Quick Setup (XAMPP / MySQL 8.x + PHP 8+)
1. Import `database/gym_database.sql` into MySQL (`mysql -u root -p < database/gym_database.sql` or via phpMyAdmin / VS Code MySQL extension).
2. Place `gainz_project` inside your web root (e.g. `/opt/lampp/htdocs/gainz_project`) or run `php -S localhost:8000` inside `gainz_project`.
3. Optional: Run `composer dump-autoload` if Composer is installed (already handled automatically by `app/bootstrap.php` even without `vendor/`).

## Default Seed Accounts
- **Admin:** `cristoibarra@gmail.com` / `admin123` (or register a new account and promote via admin panel)
- **Member:** `juandelacruz@gmail.com` / `member123`
