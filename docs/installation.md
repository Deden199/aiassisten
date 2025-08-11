# Installation Guide

1. **Clone the repository**
   - `git clone <repo> && cd <repo>`
2. **Install dependencies**
   - `composer install --optimize-autoloader`
   - `npm install && npm run build` (or `npm run dev` during development)
3. **Environment setup**
   - Copy `.env.example` to `.env` and update database, cache, mail, and `APP_URL` settings.
   - Run `php artisan key:generate` to set the application key.
4. **Database migration**
   - Run `php artisan migrate` to create the database tables.
5. **Interactive installer**
   - Execute `php artisan aiassisten:install` and follow the prompts to create the first tenant, admin user, and license information.
6. **Queue worker**
   - Start a worker with `php artisan queue:work` or use Supervisor/systemd. See [queues.md](queues.md) for detailed instructions.
7. **Billing configuration**
   - Configure Stripe or PayPal credentials in `.env` and set up webhooks. See [billing.md](billing.md) for more information.
8. **Serve the application**
   - `php artisan serve` (or configure Nginx/Apache pointing to `public/`).

The application should now be accessible on the configured domain.
