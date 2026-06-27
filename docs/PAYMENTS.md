# Payments

Rokhdad charges for paid registrations through Iranian payment gateways. The payment layer lives in `backend/app/Payments/` and is driven by `PaymentController`. All amounts are integers in **Rials (IRR)**.

## Gateway Abstraction

Every gateway implements the `App\Payments\PaymentGateway` interface:

```php
interface PaymentGateway {
    public function key(): string;                                  // 'zarinpal' | 'zibal'
    public function createPayment(Payment $payment): PaymentGatewayRedirect;
    public function verifyPayment(Payment $payment, array $payload): PaymentGatewayVerification;
}
```

- `PaymentGatewayRedirect` carries `authority`, `redirectUrl`, and the raw gateway response.
- `PaymentGatewayVerification` carries `paid` (bool), optional `refId`, and the raw response.
- `PaymentGatewayRegistry` holds the registered gateways and resolves one by key, throwing `InvalidArgumentException` for an unknown key.

### Supported Gateways

| Key | Class | Create endpoint | Notes |
|---|---|---|---|
| `zarinpal` | `Gateways/ZarinpalGateway` | `…/pg/v4/payment/request.json` | Sandbox toggle via `ZARINPAL_SANDBOX`; verify accepts codes `100` (verified) and `101` (already verified). |
| `zibal` | `Gateways/ZibalGateway` | `https://gateway.zibal.ir/v1/request` | `result/status == 1` means success; redirect is `https://gateway.zibal.ir/start/{trackId}`. |

Configuration (`config/services.php`): `ZARINPAL_MERCHANT_ID`, `ZARINPAL_SANDBOX`, `ZIBAL_MERCHANT_ID`.

## Payment Flow

1. **Initiate** — `POST /api/v1/registrations/{id}/pay` (auth). Body: `gateway` (`zarinpal`|`zibal`). The registration must belong to the user, have `total_amount > 0`, and not already be `paid`. A `Payment` row is created (`status=pending`), the gateway's `createPayment` is called, the returned `authority` is stored, and `redirect_url` + `authority` are returned to the client.
2. **Redirect** — the client sends the user to `redirect_url` (the gateway's hosted page).
3. **Callback** — `GET /api/v1/payments/callback/{gateway}` (public; the gateway redirects the user back here). The controller resolves the authority/trackId, finds the matching `pending` payment, and calls `verifyPayment`.
   - On success: `markPaid()`, the registration's `payment_status` becomes `paid` and it is `confirm()`-ed, a `payment.paid` webhook is dispatched to the organizer, and the user is redirected to `{frontend}/payment/success?ref=…`.
   - On failure/cancel: `markFailed()`, a `payment.failed` webhook is dispatched, and the user is redirected to `{frontend}/payment/failed?payment_id=…`.
4. **Status** — `GET /api/v1/payments/{id}` (auth, owner only) returns gateway, status, amount, currency, `gateway_ref_id`, and `paid_at`.

The frontend base for redirects is `APP_FRONTEND_URL` (falls back to `APP_URL`).

## `payments` Table

| Column | Notes |
|---|---|
| `registration_id`, `user_id` | Owning registration and payer. |
| `gateway` | `zarinpal` or `zibal`. |
| `gateway_authority` | Transaction reference issued at creation. |
| `gateway_ref_id` | Verified reference returned on success. |
| `status` | `pending`, `paid`, `failed`, `refunded`. |
| `amount`, `currency` | Integer Rials; currency defaults to `IRR`. |
| `callback_url` | Gateway return URL. |
| `gateway_response` | Raw JSON from the gateway (audit trail). |
| `paid_at` | Set when verified. |

## Settlement Linkage

Successful payments feed organizer balances through the settlement ledger (platform fee, payout eligibility). See [`SETTLEMENTS.md`](SETTLEMENTS.md). Organizer-facing payment events also fan out via [`WEBHOOKS.md`](WEBHOOKS.md).
