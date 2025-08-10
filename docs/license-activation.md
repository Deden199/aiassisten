# License Activation

1. Purchase the script from CodeCanyon and obtain the purchase code from your downloads page.
2. After installing the application, visit `/admin/license`.
3. Enter the purchase code and submit. The server will verify the code with the Envato API.
4. On success, the license is bound to the current domain and stored hashed in the database.
5. If verification fails, ensure the server can reach api.envato.com and the code has not been used on another domain.
6. For development, set `LICENSE_BYPASS=true` in `.env` to skip verification.

A valid license is required for premium features. Unverified installs enter a 7‑day grace period before restrictions apply.
