# Environment Configuration

The application reads configuration from the `.env` file. Important keys:

| Key | Description |
| --- | --- |
| `APP_URL` | Full URL to the application. Used for links and storage URLs. |
| `APP_LOCALE` | Default locale (e.g. `en`). |
| `APP_TIMEZONE` | Time zone identifier. |
| `DB_*` | Database connection details. |
| `QUEUE_CONNECTION` | Usually `redis` or `database`. |
| `CACHE_STORE` | Cache driver, defaults to `file` or `redis`. |
| `AI_OPENAI_KEY` | API key for OpenAI services. Leave blank to disable. |
| `FILESYSTEM_DISK` | Default storage disk (`local`, `s3`, etc.). |
| `MAIL_MAILER` | Mail transport driver (`smtp`, `log`, etc.). |
| `MAIL_FROM_ADDRESS` | Sender email for notifications. |

After editing `.env`, run `php artisan config:clear` to apply changes.
