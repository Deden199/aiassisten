# Billing Setup

## Environment Variables

Set the following variables in your `.env`:

```
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
PAYPAL_CLIENT_ID=
PAYPAL_SECRET=
PAYPAL_WEBHOOK_ID=
BILLING_SUCCESS_URL="https://your.app/billing/return"
BILLING_CANCEL_URL="https://your.app/billing/return?cancel=1"
```

## Webhook Configuration

- **Stripe:** configure a webhook endpoint at `https://your.app/webhooks/stripe` and send subscription and invoice events.
- **PayPal:** configure `https://your.app/webhooks/paypal` with the webhook ID stored in `PAYPAL_WEBHOOK_ID`.

