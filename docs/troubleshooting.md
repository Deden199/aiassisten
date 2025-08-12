# Troubleshooting

## Queue not processing
- Ensure the queue worker is running: `php artisan queue:work`.
- Check Redis or database connection based on `QUEUE_CONNECTION`.

## Permission errors
- The web server must be able to write to `storage/` and `bootstrap/cache/`.
- Run `chmod -R 775 storage bootstrap/cache` and ensure the correct group ownership.

## High memory usage
- Large PDF or DOCX uploads are parsed asynchronously. Confirm Horizon or queue worker is active.
- Increase PHP memory limit in `php.ini` if necessary.

## Cannot connect to AI provider
- Verify API keys in `.env` and that outbound HTTPS traffic is allowed from the server.
- Check the application logs for detailed error messages.

For additional help, consult the community forum or open a support ticket with your purchase code.
