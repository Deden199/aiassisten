# Installing on Shared Hosting (cPanel)

1. **Upload files**
   - Upload the application archive to your hosting account and extract it inside the desired directory.
2. **Configure PHP**
   - Ensure PHP 8.2 or higher and required extensions (OpenSSL, PDO, Mbstring, Tokenizer, Fileinfo, JSON, cURL, Zip).
3. **Create database**
   - From cPanel, create a MySQL database and user, then assign privileges.
4. **Set environment values**
   - Copy `.env.example` to `.env` and update database credentials, `APP_URL`, mail settings, and storage driver.
5. **Run installer**
   - Visit your domain and follow the three step installer wizard to generate the app key and run migrations.
6. **Configure queue & cron**
   - In cPanel "Cron Jobs", schedule `php artisan schedule:run` every minute.
   - For queues, use `php artisan queue:work --tries=1` via Supervisor or create another cron job.
7. **Create storage link**
   - Run `php artisan storage:link` or ask hosting support to create the symlink from `public/storage` to `storage/app/public`.

After completing these steps the application should be reachable at your domain.
