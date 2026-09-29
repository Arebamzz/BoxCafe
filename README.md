# BoxCafe

PHP and MySQL cafe application with customer accounts, menu and merchandise ordering, events, delivery, and administration.

## Requirements

- PHP 8.2+ with mysqli and PDO MySQL
- MySQL / MariaDB and Apache (for example, XAMPP)
- Composer

## Local setup

1. Place the project inside XAMPP's htdocs directory.
2. Run composer install in the project directory.
3. Create database boxkl in phpMyAdmin and import your private SQL export. Database exports are excluded because they may contain customer and account data.
4. Copy config/local.example.php to config/local.php and enter local settings. Keep an existing local.php when upgrading.
5. Start Apache and MySQL.
6. Open http://localhost:8080/BoxCafe/ and adjust the folder path and Apache port to your installation.

MySQL defaults to port 3306. Apache port 8080 belongs in the browser URL.

Stripe checkout requires a Stripe secret key; email requires SMTP credentials. Enter them in config/local.php. Never commit that file. Environment variables such as BOXCAFE_DB_NAME and BOXCAFE_STRIPE_SECRET_KEY override local settings.

## Structure

| Path | Purpose |
| --- | --- |
| Root PHP files | Customer pages and order/payment handlers |
| admin/ | Administration pages and PDO adapter |
| config/ | Shared configuration and local settings template |
| includes/database.php | Shared mysqli connection factory |
| includes/mail.config.php | Email configuration and helpers |
| assets/, images/ | Site assets |
| uploads/receipts/ | Receipt handlers and private uploaded receipts |
| composer.json, composer.lock | Dependency definitions |

Existing page paths are preserved. PDO and mysqli both use the shared configuration.

## Sharing

Keep local credentials, SQL exports and customer receipts private. Review other assets for private content and redistribution rights before publishing. Revoke any credentials previously published. This cleanup is not a production security audit.
