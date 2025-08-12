# License Activation

1. Set `ENVATO_API_TOKEN` in `.env` with your Envato personal token.
2. Go to **Admin → License** and enter your purchase code and your domain (e.g. `example.com`).
3. On success, the license is bound (hash stored) and status becomes `valid`.
4. For local development or CI builds, set `LICENSE_BYPASS=true`.

### Deactivate / Move Domain
Use the **Deactivate** button in Admin → License to unbind, then re-activate on the new domain.

### Grace Period
Unlicensed installs have a grace period (default 7 days) configured by `LICENSE_GRACE_DAYS`. Premium features are blocked after the period.


**Domain format:** use only host (e.g. `example.com`, `sub.example.co`), no scheme or path.
Note: License verification is rate limited (5 requests/minute) to protect the API.
