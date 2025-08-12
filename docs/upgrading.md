# Upgrading

1. **Backup**
   - Create a backup of your `.env` file and database before running the updater.
2. **Upload new files**
   - Replace the application files with the contents of the new release, excluding the `storage` directory and `vendor` if desired.
3. **Run updater**
   - Execute `php artisan app:update` from the project root. The command backs up `.env`, runs new migrations, and clears caches.
4. **Review changelog**
   - After updating, consult `CHANGELOG.md` for notable changes and new environment variables.
5. **Rebuild assets**
   - If the release contains frontend changes, run `npm install && npm run build`.

Once complete, the application is upgraded to the latest version.
