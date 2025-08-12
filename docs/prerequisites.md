# Installation Prerequisites

## PHP Requirements

Ensure your server runs **PHP 8.2 or higher** with the following extensions enabled:

- OpenSSL
- PDO
- Mbstring
- Tokenizer
- Fileinfo
- JSON
- cURL
- Zip

## Environment Setup

1. Copy `.env.example` to `.env`.
2. Update database, cache, mail, `APP_URL`, and other necessary settings in the `.env` file.
3. Generate the application key:

   ```bash
   php artisan key:generate
   ```

   The browser installer will handle this automatically if you're using it.
