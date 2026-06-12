API quick reference — ready for Kotlin mobile app

Auth
- POST /api/register
  - body: { name, email, password }
  - success: 201 Created, returns user JSON

- POST /api/login
  - body: { email, password }
  - success: 200, returns { token, user }
  - Use returned token as Bearer for subsequent requests:
    Authorization: Bearer <token>

Donations
- POST /api/donations
  - body: { campaign_id, donor_name, donor_email, amount, payment_method }
  - returns created donation resource with `receipt_number` and `receipt_url`

- GET /donations/{donation}/receipt
  - authenticated (web) PDF receipt download (requires `auth` middleware)

Campaigns
- GET /api/campaigns
- GET /api/campaigns/{id}
- POST /api/campaigns (auth:sanctum)
- PUT /api/campaigns/{id} (auth:sanctum)

Admin / Financial report
- GET /api/admin/financial-report (auth:sanctum)
  - returns Excel .xlsx file

Stripe
- POST /api/stripe/webhook
  - configured to validate webhook signature (set `services.stripe.webhook_secret` in .env)

Notes for Kotlin integration
- Use `POST /api/login` to retrieve the token, then add header:
  - `Authorization: Bearer <token>`
  - `Accept: application/json`

- Example using OkHttp (Kotlin):

```kotlin
val client = OkHttpClient()
val request = Request.Builder()
    .url("https://your-api.example.com/api/campaigns")
    .header("Authorization", "Bearer $token")
    .header("Accept", "application/json")
    .build()
val resp = client.newCall(request).execute()
```

Environment
- Configure `.env` with `services.stripe.secret` and `services.stripe.webhook_secret` for Stripe integration.
- CORS is permissive by default in `config/cors.php` — tighten in production.

Tests
- Run the test suite with:

```bash
composer install
php artisan key:generate
php artisan migrate --env=testing
./vendor/bin/pest
```
