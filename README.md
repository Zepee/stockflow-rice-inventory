# StockFlow

StockFlow is a responsive inventory management web application for rice wholesalers and warehouse teams. It tracks products, suppliers, customers, purchases, sales, stock adjustments, and inventory movements.

This repository is a portfolio project. Use sample data only; do not store real customer, supplier, or financial information in the public demo.

## Main capabilities

- Owner and Staff sign-in with server-side role checks.
- Product, supplier, and customer records.
- Purchase and sales workflows with review before confirmation.
- Inventory ledger, current stock balances, and owner-authorized adjustments.
- Transaction cancellation with reversal movements and audit history.
- Dashboard, movement history, reports, and CSV export.
- Responsive Blade interface for desktop and mobile screens.

## Technology

- Laravel 13 and PHP 8.3+
- MySQL-compatible database
- Blade, Tailwind CSS, Alpine.js, and Vite
- Brick\Math for exact quantity and money calculations

## Run locally

1. Install PHP 8.3+, Composer, Node.js, npm, and MySQL.
2. Copy `.env.example` to `.env` and set local database values.
3. Install dependencies and create the application key:

   ```sh
   composer install
   npm install
   php artisan key:generate
   ```

4. Create the local database, then run migrations and the warehouse seeder:

   ```sh
   php artisan migrate --seed
   ```

5. Create an Owner account interactively and start the application:

   ```sh
   php artisan stockflow:create-initial-owner
   php artisan serve
   ```

6. Build frontend assets in another terminal:

   ```sh
   npm run build
   ```

Optional sample catalog and transactions can be added after creating an Owner:

```sh
php artisan db:seed --class=DemoDataSeeder
```

## Public portfolio preview

The Vercel configuration publishes a static, interactive preview from `public/demo.html`. It does not run Laravel, require a database, or expose login credentials. Product and transaction records are clearly marked sample data. Purchase prices are not shown.

Visitors can move between the dashboard, products, purchases, sales, stock history, and reports. Stock-in and stock-out forms are simulations held only in the current page session. Nothing is sent to a database or saved in the browser; reloading the page resets the sample data.

Vercel builds the preview using `node scripts/build-static-demo.mjs` and publishes `dist/stockflow-demo`. The deployment needs no database settings, `APP_KEY`, or other secret environment variables. Select this repository in Vercel and keep the project root at the repository root; `vercel.json` contains the build configuration.

The full Laravel application remains available for local use following the steps above. Its authenticated workflows still require MySQL and are not part of this static public preview.

## Security notes

- `.env`, SQLite databases, dependency folders, generated static output, and built assets are excluded from source control.
- Never publish local Owner or Staff passwords in this repository.
- Do not connect a public demo to a business database or real inventory.
