# Billing Configuration

The application supports subscriptions through Stripe or PayPal.

## Stripe
1. Set the following environment variables in `.env`:
   - `STRIPE_SECRET`
   - `STRIPE_WEBHOOK_SECRET`
2. In the Stripe dashboard, create a webhook endpoint pointing to `/webhooks/stripe` and subscribe to subscription events.

## PayPal
1. Set the following environment variables in `.env`:
   - `PAYPAL_CLIENT_ID`
   - `PAYPAL_SECRET`
   - `PAYPAL_WEBHOOK_ID`
2. In the PayPal dashboard, create a webhook pointing to `/webhooks/paypal` and enable subscription events.

After configuring the credentials and webhooks, tenants can purchase plans through the billing page.
