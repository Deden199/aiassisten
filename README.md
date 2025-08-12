# AI Assistant

AI Assistant is a Laravel-based platform offering AI-driven tools to streamline content creation and management.

## Features
- **Chatbot** for conversational interactions
- **Mindmap** generation from text
- **Summary** creation for long-form content
- **Slides** generation for presentations

## Setup
1. Clone the repository and install dependencies:
   ```bash
   git clone https://github.com/aiassisten/aiassisten.git && cd aiassisten
   composer install --optimize-autoloader
   npm install && npm run build
   ```
2. Configure environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Update database, cache, mail, and `APP_URL` settings in `.env`.
3. Run migrations and interactive installer:
   ```bash
   php artisan migrate
   php artisan aiassisten:install
   ```
4. Serve the application:
   ```bash
   php artisan serve
   ```

## cPanel Installation

For shared hosting environments using cPanel:

1. Upload the release archive to your hosting account and extract it.
2. Create a MySQL database and user in cPanel.
3. Visit your domain to run the web-based installer.

See the [shared hosting installation guide](docs/install-shared-hosting.md) for detailed steps.

## License Activation
1. Purchase the script and obtain your Envato purchase code.
2. Set `ENVATO_API_TOKEN` in `.env`.
3. After installation, visit `/admin/license` and submit the purchase code and domain.
4. For development, set `LICENSE_BYPASS=true` in `.env` to skip verification.

A valid license is required for premium features.

## Navigation
After logging in, the navigation menu provides access to:
- **Dashboard**
- **Projects** (run summary, mindmap, or slide tasks)
- **Billing**
- **Slide Templates** (admin only)
- **Chatbot**

## Contributing
1. Fork the repository and create a feature branch.
2. Make your changes and run the tests:
   ```bash
   php artisan test
   ```
3. Submit a pull request with a clear description.

## Contact
For questions or support, open an issue or contact the team at [support@aiassisten.com](mailto:support@aiassisten.com).

## License
This project is open-sourced under the [MIT license](https://opensource.org/licenses/MIT).
