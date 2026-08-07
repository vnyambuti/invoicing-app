# AR Invoice — SAP Business One Clone (Filament)

A Laravel + Filament recreation of SAP Business One's **AR Invoice** screen — matching its layout, field names, and dense spreadsheet-style UI, with Kenya-specific fields (KRA PIN, TIMS, eTIMS) built in.



---

## Overview

This project reproduces SAP B1's AR Invoice document as a web-based Filament resource — same header layout, same tabbed content area (Contents / Logistics / Accounting / Attachments / TIMS / ETIMS), same dense line-items grid, and the same footer totals box. The goal is pixel-close visual parity with the original SAP screen while running entirely on a modern Laravel/Filament stack.

| Original (SAP B1) | Clone (Filament) |
|---|---|
| Desktop client, dense grid UI | Web-based, matching density via custom theme |

---

## Features

- **Header** — Customer (code + name, type-ahead searchable both ways), Contact Person, BP Currency, KRA PIN, auto-generated sequential document number (`IN{YY}000001` format), Posting/Value/Document dates (defaults to today)
- **Line items grid** — Item lookup by code or description with auto-population of description, unit price, and UoM; quantity, discount %, VAT code, and totals columns; supports up to 3 decimal places on quantity/price/discount
- **Approval workflow indicator** — automatically shows an approval notice when the invoice total exceeds KES 10,000
- **Validation**
  - Remarks field is mandatory
  - Line-level discount capped at 50%
  - Numeric-only quantity and price fields
- **Tabs** — Contents, Logistics, Accounting, Attachments, TIMS, ETIMS (Kenya Revenue Authority integration points)
- **Footer totals** — Total Before Discount, Discount %, Total Down Payment, Freight, Rounding, Tax, Total, Applied Amount, Balance Due
- **Sales Employee / Owner** — searchable relationship fields
- Dense, SAP-matching visual theme built via a custom `theme.css` targeting Filament's real rendered classes (not assumed hook classes)

---

## Tech Stack

- **Backend:** Laravel 13
- **Admin/UI framework:** Filament 3.x
- **Line-items grid:** [awcodes/filament-table-repeater](https://github.com/awcodes/filament-table-repeater)
- **Frontend build:** Vite
- **Testing:** Pest (PHPUnit under the hood)
- **Localization:**  KES currency

---

## Requirements

- PHP 8.4+
- Composer
- Node.js + npm
- Microsoft SQL Server
- [sqlsrv PHP extension](https://learn.microsoft.com/en-us/sql/connect/php/microsoft-php-driver-for-sql-server) enabled (`pdo_sqlsrv` + `sqlsrv`)
- Git



---

## Installation

```bash

cd invoice-app

# PHP dependencies
composer install

# JS dependencies
npm install

# Environment
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database credentials and any Kenya-specific integration keys (M-Pesa Daraja, KRA eTIMS) as applicable.

### Database

```bash
php artisan migrate

```

### Build frontend assets

```bash
# Development (with hot reload)
npm run dev

# Production build
npm run build
```

### Filament setup

```bash
# Create an admin user to access the Filament panel
php artisan make:filament-user
```

---

## Running the App

```bash
php artisan serve
```

Then visit the Filament panel (default `/admin` unless reconfigured) and log in with the user created above.

---

## Testing

The project uses **Pest** for its test suite (`tests/Feature/InvoiceResourceTest.php` covers the AR Invoice resource specifically — model relationships, type-ahead search, validation rules, and totals calculation).

```bash
./vendor/bin/pest
```

Run a specific test:

```bash
./vendor/bin/pest --filter="test name fragment"
```



## Project Structure (key paths)

```
app/
  Filament/
    Resources/
      InvoiceResource.php       
      InvoiceResource/Pages/
        CreateInvoice.php
        EditInvoice.php
  Models/
    Invoice.php
    InvoiceLine.php
    Customer.php
    Item.php
    SalesEmployee.php
    Warehouse.php
resources/
  css/
    theme.css                 
tests/
  Feature/
    InvoiceResourceTest.php
```

---

## Styling Notes

The visual theme intentionally targets Filament's **actual rendered CSS classes** rather than assumed ones — the installed Filament version doesn't emit some of the standard hook classes (e.g. `fi-fo-grid`, `fi-fo-repeater`); instead it uses plain Tailwind utilities directly, and the `awcodes/filament-table-repeater` package renders its own class names (`table-repeater-container`, `table-repeater-header`, etc.). If you're extending the theme, inspect the rendered DOM before assuming a class name exists.

The line-items table scrolls internally (`overflow-x: auto` on `.table-repeater-container`) rather than stretching the page width, to accommodate its 13 columns.

---

