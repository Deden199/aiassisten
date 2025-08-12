# Installing on a VPS

> Before starting, review the [installation prerequisites](installation.md) for PHP requirements and environment setup.

1. **Server requirements**
   - Ubuntu 22.04+, Docker (optional), Git, and PHP. Ensure PHP version and extensions meet the [prerequisites](installation.md).
2. **Clone repository**
   - `git clone <repo> && cd <repo>`
3. **Environment configuration**
   - Set up your `.env` file as described in the [installation prerequisites](installation.md).
4. **Dependencies**
   - Run `composer install --optimize-autoloader` and `npm install && npm run build` if serving assets.
5. **Database migration**
   - Run `php artisan migrate --seed` to set up tables and seed sample data.
6. **Queues and scheduler**
   - Start `php artisan queue:work --daemon` under Supervisor or systemd.
   - Add cron job: `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`.
7. **Web server**
   - Configure Nginx or Apache to point the document root to `public/` and enable HTTPS.
8. **Docker alternative**
   - Use `docker compose up -d` to build the containers. Set environment variables in `.env` before running the stack.

The application should now be accessible on the configured domain or IP address.
