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

## Portfolio deployment

The deployment files target a free Render web service and TiDB Cloud Starter's free MySQL-compatible quota. The application container runs database migrations on startup and seeds the default warehouse. TiDB Cloud Starter requires a TLS connection; the Render blueprint sets the system CA bundle path.

This setup is for a portfolio demo, not production use. Render's free service can sleep when idle and its local filesystem is temporary. TiDB Cloud Starter has a monthly free quota; requests may be throttled when the quota is reached. TiDB is MySQL-compatible rather than MySQL itself, so verify the inventory locking and transaction flows against the deployed database before relying on it.

Set these secret or environment values in the hosting dashboard; never commit them:

- `APP_KEY` (generate with `php artisan key:generate --show`)
- `APP_URL`
- `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`

After the first deployment, create the Owner through the `stockflow:create-initial-owner` Artisan command from a trusted local environment connected to the hosted database. Do not publish Owner or Staff passwords in this repository. Seed sample data only after the Owner exists.

## Security notes

- `.env`, SQLite databases, dependency folders, and built assets are excluded from source control or the Docker build context.
- Keep `APP_DEBUG=false` on the hosted service.
- Use a unique strong password for every demo account and rotate it if it was shared publicly.
- Do not connect a public demo to a business database or real inventory.
