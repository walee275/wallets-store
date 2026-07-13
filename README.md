# Commerce

A Laravel + Inertia storefront for the Pakistani market, with admin panel, checkout, inventory, discounts, and payment gateway integrations (Stripe, JazzCash, Easypaisa, COD).

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+
- SQLite (default) or MySQL

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate

# SQLite (default)
touch database/database.sqlite

npm install
npm run build

php artisan migrate:fresh --seed
```

### Local development

If you use [Laravel Herd](https://herd.laravel.com), the app is served automatically at your configured domain (e.g. `commerce.test`).

Otherwise:

```bash
php artisan serve
npm run dev
```

Visit the storefront at `/` and the admin panel at `/admin`.

## Default accounts

| Role     | Email                  | Password  |
|----------|------------------------|-----------|
| Admin    | admin@commerce.test    | password  |
| Customer | customer@commerce.test | password  |

The admin user has the Spatie `owner` role.

## Payment methods

Payment gateways are toggled in the database (`payment_methods` table) and via admin **Payment Methods**. By default:

- **COD** — enabled
- **Stripe**, **JazzCash**, **Easypaisa** — disabled

Configure credentials in `.env`:

```env
STORE_CURRENCY=PKR

STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

JAZZCASH_MERCHANT_ID=
JAZZCASH_PASSWORD=
JAZZCASH_INTEGRITY_SALT=

EASYPAISA_STORE_ID=
EASYPAISA_HASH_KEY=
```

In local/testing environments, JazzCash and Easypaisa redirect to simulate routes under `/webhooks/*/simulate/{payment}` when credentials are missing.

## Architecture overview

```
app/
├── Actions/          # Domain actions (cart, checkout, inventory)
├── Enums/            # Order, payment, discount, product status enums
├── Http/
│   ├── Controllers/
│   │   ├── Admin/    # Back-office CRUD and settings
│   │   ├── Storefront/  # Catalog, cart, checkout, account
│   │   └── Webhooks/    # Payment provider webhooks
│   └── Requests/     # Form request validation
├── Jobs/             # Queued jobs (order email, sitemap, metrics)
├── Models/           # Eloquent models
├── Payments/         # Gateway drivers (Stripe, JazzCash, Easypaisa, COD)
└── Services/         # CheckoutCalculator, DashboardMetrics, etc.

database/
├── migrations/
├── seeders/          # Roles, catalog, commerce settings
└── factories/

resources/js/
├── Pages/
│   ├── Admin/        # Inertia admin pages
│   └── Storefront/   # Inertia storefront pages
└── Components/

routes/
├── storefront.php    # Public shop routes (cart, checkout, catalog)
├── admin.php         # Admin routes (prefix /admin)
└── webhooks.php      # Payment webhooks (prefix /webhooks)
```

## Testing

Tests use SQLite in-memory (`phpunit.xml`).

```bash
php artisan test
```

Included coverage:

- **Unit** — `CheckoutCalculator` (percent, fixed, free shipping, min order)
- **Feature** — cart, checkout (COD), payment webhooks, admin access, discount coupons

## Seeded data

Running `php artisan migrate:fresh --seed` creates:

- Spatie roles: `owner`, `staff`, `support`
- Categories: Apparel, Accessories
- Featured collection: Bestsellers (8 products, including sized tee)
- Shipping zone: Pakistan with Standard (250 PKR) and Express (500 PKR)
- Discounts: `SAVE10`, `WELCOME`, `FREESHIP`
- CMS pages: About, Returns Policy
