# Goali Tours Management System

A responsive PHP/MySQL back office for building, customizing and exporting polished tour itineraries.

## Requirements

- PHP 8.1 or newer
- MySQL 8 / MariaDB 10.5 or newer
- Composer (for one-click PDF downloads)
- Apache with `mod_rewrite`, or PHP's built-in server for development

## Quick setup with XAMPP

1. Copy `goali-tour-management` into `C:\xampp\htdocs\`.
2. Start Apache and MySQL.
3. Import `database/schema.sql` in phpMyAdmin.
4. Open a terminal in the project folder and run `composer install`.
5. Visit `http://localhost/goali-tour-management/`.

Initial local login:

- Email: `admin@goalitours.com`
- Password: `admin123`

Change the initial password immediately after signing in.

## PDF export

The itinerary screen always supports browser preview and Print / Save PDF. Running `composer install` adds Dompdf and enables the one-click Download PDF button.

The redesigned itinerary includes:

- Full-bleed destination cover
- Journey overview, route and highlight cards
- Image-led day-by-day itinerary
- Inclusions, exclusions and package pricing
- Important notes and additional information
- Curated gallery and branded closing page
- A4 print rules and page-safe content blocks

## Database configuration

Defaults are suitable for a standard local XAMPP installation. Production credentials can be supplied without editing source code:

```text
GOALI_DB_HOST
GOALI_DB_NAME
GOALI_DB_USER
GOALI_DB_PASS
```

## Main workflows

- Role-based admin access and approval
- Tour categories and duration filtering
- New, duplicated and customer-specific packages
- Day plans, route, pricing, highlights, inclusions and gallery uploads
- Soft deletion and recycle-bin restore
- Company name and logo settings
- Branded HTML/PDF itinerary export

## Production checklist

- Change the initial administrator password.
- Use a dedicated database user with a strong password.
- Serve the site over HTTPS.
- Restrict uploads by MIME type and file size at the server level.
- Back up the database and `assets/uploads` directory.
- Disable PHP error display and enable server-side logging.
