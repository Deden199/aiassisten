# Installing on Shared Hosting (cPanel)

1. **Upload files**
   - Upload the application archive to your hosting account and extract it inside the desired directory.
   - Set the `public` directory as the document root for your domain or subdomain.
2. **Configure PHP**
   - Ensure PHP 8.2 or higher and required extensions (OpenSSL, PDO, Mbstring, Tokenizer, Fileinfo, JSON, cURL, Zip).
3. **Create database**
   - From cPanel, create a MySQL database and user, then assign privileges.
   - _Screenshot: database creation (placeholder)_
4. **Set environment values**
   - Copy `.env.example` to `.env` and update database credentials, `APP_URL`, mail settings, and storage driver.
   - _Screenshot: editing environment variables (placeholder)_
5. **Run installer**
   - Visit your domain and follow the three step installer wizard to generate the app key and run migrations.
6. **Configure queue & cron**
   - In cPanel "Cron Jobs", schedule `php artisan schedule:run` every minute.
   - For queues, use `php artisan queue:work --tries=1` via Supervisor or create another cron job.
   - _Screenshot: cron job setup (placeholder)_
7. **Create storage link**
   - Run `php artisan storage:link` or ask hosting support to create the symlink from `public/storage` to `storage/app/public`.

8. **Set permissions**
   - Ensure `storage/` and `bootstrap/cache/` are writable:
     ```bash
     chmod -R 775 storage bootstrap/cache
     chown -R <cpanel_user>:<cpanel_user> storage bootstrap/cache
     ```

After completing these steps the application should be reachable at your domain.

Next, finish [License Activation](installation.md#license-activation) by setting `ENVATO_API_TOKEN` and, for development, `LICENSE_BYPASS=true` in `.env`.

## Troubleshooting

- **Missing PHP extensions**: If the installer reports missing extensions, enable them via cPanel's "Select PHP Version" or contact hosting support.
- **Permission denied errors**: Re-run the permission commands above and verify the correct user/group ownership.
- **500 errors after upload**: Confirm the document root points to the `public` directory and that `.env` values are correct.
