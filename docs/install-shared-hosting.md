# Install on Shared Hosting (cPanel)

## Requirements
- PHP 8.2+ with extensions: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, Fileinfo
- MySQL 5.7+ / MariaDB 10.4+
- Writable: `storage/` and `bootstrap/cache`

## Steps
1. Create a subdomain or set the document root to the `/public` directory of the app.
2. Upload the release ZIP and extract it in your home folder (e.g. `/home/USER/app`).
3. Ensure `storage` and `bootstrap/cache` are writable (Permissions 755 or 775).
4. Visit `https://your-domain.com/install` and complete:
   - **Step 1**: Fill App URL and database credentials (cPanel → MySQL® Databases).
   - **Step 2**: Run migrations (one click).
   - **Step 3**: Create Tenant + Admin, optionally enter Envato purchase code to activate.
5. After finishing, the installer will create `storage/installed` and block `/install`.

### Cron (recommended)
Create a Cron job to run schedules:
```
* * * * * php /home/USER/app/artisan schedule:run >> /dev/null 2>&1
```

### Queue (optional, shared-host friendly)
If your host supports it, run the queue via Cron every minute:
```
* * * * * php /home/USER/app/artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

### Troubleshooting
- **500 error after upload**: Ensure your subdomain points to `/public`. On some hosts you must move the `public/*` files to your webroot and adjust `index.php` paths.
- **Blank installer**: Check PHP version and enable required extensions in cPanel → Select PHP Version.
- **Envato token**: Optional during install. You can set it later in `.env` as `ENVATO_API_TOKEN`.
