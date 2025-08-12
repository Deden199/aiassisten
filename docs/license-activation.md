# License Activation

1. Purchase the script from CodeCanyon and obtain the purchase code from your downloads page.
2. Set your Envato personal token in `.env` as `ENVATO_API_TOKEN`.
3. After installing the application, visit `/admin/license`.
4. Enter the purchase code and domain then submit. The server will verify the code with the Envato API.
5. On success, the license is bound to the current domain and both values are stored hashed in the database.
6. If verification fails, the admin panel will display the error returned by the API. Ensure the server can reach api.envato.com and the code has not been used on another domain.
7. For development, set `LICENSE_BYPASS=true` in `.env` to skip verification.

A valid license is required for premium features. Unverified installs enter a 7‑day grace period before restrictions apply.
