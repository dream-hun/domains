# Codebase Security Review

Date: 2026-05-15

Scope: reviewed the Laravel application codebase excluding `public/`. Vendor code was not treated as application code, except where package configuration or middleware behavior affected application exposure.

## Findings

### Critical

1. New users are effectively over-permissioned.

   Role `2` is the registration default, and the seeder grants it every permission except `user_*`, `role_*`, and `permission_*`. That includes settings, pricing, domains, subscriptions, VPS admin actions, audit logs, and other administrative capabilities.

   Evidence:
   - `database/seeders/PermissionRoleSeeder.php:17`
   - `config/panel.php:14`
   - `app/Models/User.php:153`

   Improve:
   - Make the default registration role a minimal customer role.
   - Rebuild the role-permission matrix from least privilege.
   - Add a migration or one-time command to revoke dangerous permissions from existing role `2` users.

2. `/admin` has no real admin boundary.

   The admin route group uses only `auth` and `verified`, then relies on inconsistent controller gates. Some destructive or sensitive actions are missing controller-level gates.

   Evidence:
   - `routes/web.php:61`
   - `app/Http/Controllers/Admin/TldController.php:56`
   - `app/Http/Controllers/Admin/HostingPlanPriceController.php:118`
   - `app/Http/Controllers/Admin/DomainPriceHistoryController.php:18`
   - `app/Http/Controllers/Admin/HostingPlanPriceHistoryController.php:18`
   - `app/Http/Controllers/Admin/DashboardController.php:24`

   Improve:
   - Add explicit admin middleware or `can:admin_access` to the whole `/admin` group.
   - Audit every admin controller action for authorization.
   - Prefer policies/FormRequest authorization that validates the specific model being acted on.

3. PawaPay webhook verification fails open.

   If `PAWAPAY_WEBHOOK_SECRET` is missing, the webhook accepts the request. The route is also CSRF-exempt and can mark payments and orders as successful.

   Evidence:
   - `app/Http/Controllers/PawaPayWebhookController.php:89`
   - `app/Http/Controllers/PawaPayWebhookController.php:106`
   - `bootstrap/app.php:26`

   Improve:
   - Fail closed when the webhook secret is missing.
   - Require valid signatures for every webhook request.
   - Store only redacted webhook payloads.
   - Consider verifying status server-to-server before marking an order paid.

4. Cart and order pricing are client-influenced.

   Livewire public properties and action parameters can set domain price, selected domain price, and discount amounts. Order creation and Stripe line items then calculate from cart item prices. The converter returns the cart price unchanged when the item currency matches checkout currency.

   Evidence:
   - `app/Livewire/DomainCartButton.php:63`
   - `app/Livewire/Hosting/Configuration.php:438`
   - `app/Livewire/Hosting/Configuration.php:631`
   - `app/Services/CartPriceConverter.php:35`
   - `app/Actions/Order/CreateOrderFromCartAction.php:48`
   - `app/Actions/Payment/CreateStripeCheckoutSessionAction.php:93`

   Improve:
   - Treat all Livewire public state and action arguments as untrusted input.
   - Store only stable IDs in cart data.
   - Recalculate domain, hosting, renewal, and coupon prices from database records at checkout.
   - Use Livewire `#[Locked]` only as a defense-in-depth measure, not as the pricing authority.

### High

5. Checkout contact IDs can cross ownership boundaries.

   Contact IDs are public Livewire state and later stored into order metadata. Domain registration jobs retrieve contacts by ID without proving they belong to the order owner in the queued context.

   Evidence:
   - `app/Livewire/Checkout/CheckoutWizard.php:53`
   - `app/Livewire/Checkout/CheckoutWizard.php:415`
   - `app/Actions/Order/CreateOrderFromCartAction.php:79`
   - `app/Actions/RegisterDomainAction.php:454`

   Improve:
   - Validate every selected contact ID belongs to the authenticated user before storing order metadata.
   - Revalidate contact ownership in the order processing path using the order user ID.
   - Avoid relying on auth-scoped global scopes inside queued jobs.

6. Payment cancel and failed routes are IDOR-prone.

   `success()` checks order ownership, but cancel and failed handlers do not. The cancel handler mutates payment/order status via GET.

   Evidence:
   - `routes/web.php:212`
   - `app/Http/Controllers/PaymentController.php:269`
   - `app/Http/Controllers/PaymentController.php:296`

   Improve:
   - Add ownership checks to cancel and failed routes.
   - Make state-changing cancel actions POST-only.
   - Keep provider callback URLs separate from user-initiated state changes.

7. Sensitive data is over-logged, and Log Viewer is enabled by default.

   Namecheap API responses and registration params include contact PII. Audit logging stores most model attributes and request context. Log Viewer is enabled by default and exposes log APIs unless environment/auth is configured correctly.

   Evidence:
   - `app/Services/Domain/NamecheapDomainService.php:249`
   - `app/Services/Domain/NamecheapDomainService.php:1449`
   - `app/Services/Audit/ActivityLogger.php:24`
   - `config/log-viewer.php:21`
   - `vendor/opcodesio/log-viewer/src/Http/Middleware/AuthorizeLogViewer.php:13`

   Improve:
   - Redact contact PII, payment metadata, tokens, auth codes, webhook payloads, and API responses.
   - Disable Log Viewer outside locked local/admin use.
   - Protect Log Viewer with `auth` and an explicit admin gate, not only environment checks.
   - Reduce log retention for sensitive operational logs.

8. EPP TLS peer name validation is disabled.

   Registrar credentials and domain commands should not use a connection that skips peer name verification.

   Evidence:
   - `app/Services/Domain/EppDomainService.php:1934`

   Improve:
   - Enable peer name verification.
   - Configure the CA certificate correctly.
   - Keep EPP debug output disabled in production.

### Medium

9. Public domain search is unauthenticated and not route-throttled.

   The API endpoint calls external availability services across active TLDs. This can be abused for cost, rate-limit exhaustion, and denial of service.

   Evidence:
   - `routes/api.php:8`
   - `app/Http/Controllers/Api/DomainSearchController.php:63`
   - `app/Http/Requests/Api/DomainSearchRequest.php:20`

   Improve:
   - Add route throttling.
   - Add stricter domain-label validation.
   - Cache recent search results.
   - Consider captcha or authenticated-only behavior for high-volume paths.

10. Security headers middleware exists but is disabled.

   The app has a middleware for basic security headers, but it is commented out.

   Evidence:
   - `bootstrap/app.php:18`
   - `app/Http/Middleware/SecurityHeaders.php:21`

   Improve:
   - Enable security headers.
   - Add HSTS in production.
   - Tighten CSP over time, ideally with report-only rollout first.
   - Fix header casing for `X-Content-Type-Options`.

11. Registration password rules are weaker than intended.

   The registration rule enforces complexity but has no minimum length rule. A short password such as `Aa1!` satisfies the regex.

   Evidence:
   - `app/Http/Requests/Auth/RegisterUserRequest.php:33`

   Improve:
   - Use Laravel `Password::defaults()`.
   - Enforce at least `min(8)`.
   - Add `uncompromised()` in production.

12. Environment defaults are unsafe if copied to production.

   `.env.example` defaults to local/debug/log debug/session encryption disabled. Session secure cookie behavior is also environment-only.

   Evidence:
   - `.env.example:2`
   - `.env.example:21`
   - `config/session.php:52`
   - `config/session.php:174`

   Improve:
   - Use production-safe deployment templates.
   - Set `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning` or `error`, `SESSION_SECURE_COOKIE=true`.
   - Consider `SESSION_ENCRYPT=true` for database-backed sessions.

### Low

13. Permission pivot tables lack uniqueness constraints.

   Duplicate role-user or permission-role rows can confuse authorization state and admin UI behavior.

   Evidence:
   - `database/migrations/2025_08_05_095503_create_role_user_table.php:16`
   - `database/migrations/2025_08_05_95057_create_permission_role_table.php:16`

   Improve:
   - Add unique indexes on `(user_id, role_id)` and `(role_id, permission_id)`.
   - Add cascade delete behavior where appropriate.

14. Gate permission mapping is cached for 300 seconds.

   Permission revocation can lag for up to five minutes.

   Evidence:
   - `app/Http/Middleware/AuthGates.php:25`

   Improve:
   - Invalidate the permission cache after role or permission changes.
   - Use a cache key version or event-driven invalidation.

15. Session payload clearing uses raw `unserialize()`.

   This currently requires database session payload control to exploit, but raw `unserialize()` should still be avoided or constrained.

   Evidence:
   - `app/Listeners/ClearUserCarts.php:25`

   Improve:
   - Use Laravel session APIs where possible.
   - If manual decoding remains necessary, pass `['allowed_classes' => false]` to `unserialize()`.

## Verification

- No files were modified during the review.
- `composer audit` reported no known Composer package advisories.
- `npm audit` could not run because there is no lockfile, so frontend dependency auditability is incomplete.
- Current runtime reported Laravel `12.58.0`, PHP `8.5.4`, environment `local`, and debug mode enabled.

