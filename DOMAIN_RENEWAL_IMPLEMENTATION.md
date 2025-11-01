# Domain Renewal Implementation Guide

This guide explains how the domain renewal system has been implemented and how to use it.

## Overview

The domain renewal system allows users to:
1. Add domain renewals to their cart
2. Select renewal duration (1-10 years)
3. Checkout and pay for renewals
4. Automatically renew domains at the registry (EPP or Namecheap)

## Architecture

### Core Components

1. **RenewalService** (`app/Services/RenewalService.php`)
   - Processes domain renewals after payment
   - Validates domain ownership and renewal eligibility
   - Calculates renewal pricing
   - Calls the appropriate domain service (EPP/Namecheap)

2. **OrderService** (Updated)
   - Detects order type (registration/renewal/transfer)
   - Routes renewals to RenewalService
   - Handles mixed cart items

3. **CartComponent** (Updated)
   - `addRenewalToCart()` method for adding renewals
   - Validates ownership and eligibility
   - Creates cart items with renewal metadata

4. **CheckoutWizard** (Updated)
   - Skips contact selection for renewal-only orders
   - Handles navigation between steps automatically
   - Supports mixed registration and renewal carts

5. **CartController** (Updated)
   - New `addRenewalToCart()` method for AJAX requests
   - Validates ownership and eligibility
   - Returns JSON responses for success/error states

## Usage

### Adding Renewal Button to Domain Management Page

The renewal button is already integrated in `resources/views/admin/domains/index.blade.php`. It works like Namecheap - just click "Renew" and it adds the domain to cart with 1 year renewal by default:

```blade
@can('domain_renew')
    @if ($domain->status !== 'expired')
        <button onclick="addRenewalToCart({{ $domain->id }}, '{{ $domain->name }}')"
            class="btn btn-sm btn-success">
            <i class="bi bi-cart-plus"></i> Renew
        </button>
    @endif
@endcan
```

**How it works:**
1. User clicks "Renew" button
2. JavaScript sends AJAX request to add domain to cart (1 year default)
3. Shows "Adding..." spinner on button
4. On success: Alert message + redirect to cart
5. User can change years in the cart page

### Programmatic Usage

To add a renewal to cart programmatically:

```php
use Darryldecode\Cart\Facades\CartFacade as Cart;
use App\Services\RenewalService;

$renewalService = app(RenewalService::class);
$domain = Domain::find($domainId);

// Validate
$canRenew = $renewalService->canRenewDomain($domain, auth()->id());
if (!$canRenew['can_renew']) {
    // Handle error
    return back()->withErrors($canRenew['reason']);
}

// Get price
$priceData = $renewalService->getRenewalPrice($domain, $years);

// Add to cart
Cart::add([
    'id' => 'renewal-' . $domain->id,
    'name' => $domain->name . ' (Renewal)',
    'price' => $priceData['price'],
    'quantity' => $years,
    'attributes' => [
        'type' => 'renewal',
        'domain_id' => $domain->id,
        'domain_name' => $domain->name,
        'current_expiry' => $domain->expires_at->format('Y-m-d'),
        'tld' => $domain->domainPrice->tld,
        'currency' => $priceData['currency'],
        'added_at' => now()->timestamp,
    ],
]);
```

## Database Structure

### domain_renewals Table
```php
- id
- domain_id (foreign key)
- order_id (foreign key)
- years (int)
- amount (decimal)
- currency (string)
- old_expiry_date (date)
- new_expiry_date (date)
- status (pending|completed|failed)
- timestamps
```

### orders Table Updates
```php
- type (registration|renewal|transfer) // Added
- subtotal (decimal) // Added
- tax (decimal) // Added
- items (json) // Added - cart snapshot
```

## Flow Diagram

```
User's Domain List
    ↓
Select Years (1-10)
    ↓
Click "Renew Domain"
    ↓
Add to Cart (with renewal metadata)
    ↓
Cart Display (shows badge + expiry)
    ↓
Checkout Wizard
    ├─ Review Step
    ├─ Payment Step (skip contacts for renewals)
    └─ Confirmation
    ↓
Payment Processing
    ↓
RenewalService.processDomainRenewals()
    ├─ Validate ownership
    ├─ Call domain service (EPP/Namecheap)
    ├─ Update domain expiry_date
    └─ Create DomainRenewal record
    ↓
Success / Failure Notification
```

## Cart Display

Renewal items in cart show:
- Green "Renewal" badge
- Current expiry date
- Domain name
- Years to renew
- Total price

Regular registrations show:
- Blue "Registration" badge
- Domain name
- Years to register
- Total price

## Checkout Process

### For Renewal-Only Carts:
1. Review Step (shows cart items)
2. Payment Step (select payment method)
3. Confirmation (order complete)

**Note:** Contact selection is automatically skipped!

### For Mixed Carts (Registrations + Renewals):
1. Review Step
2. Contact Step (for registrations)
3. Payment Step
4. Confirmation

## Domain Service Integration

### EPP Service
The `renewDomainRegistration()` method:
1. Gets current domain info from registry
2. Extracts exact expiry date (`exDate`)
3. Creates renewal frame
4. Sends to EPP registry
5. Returns success/failure

### Namecheap Service
The `renewDomainRegistration()` method:
1. Calls `namecheap.domains.renew` API
2. Passes domain name and years
3. Parses XML response
4. Returns new expiry date

## Validation Rules

### Renewal Eligibility:
- User must own the domain
- Domain cannot be expired for more than 30 days
- Domain cannot be in transfer status
- Pricing information must be available

### Cart Validation:
- No duplicate renewals for same domain
- Years must be between 1-10
- Domain must have valid pricing

## Error Handling

All renewal operations have comprehensive error handling:
- Ownership validation errors
- Registry communication errors
- Payment processing errors
- Database transaction rollbacks

Failed renewals create a `DomainRenewal` record with status='failed' for audit purposes.

## Testing

To test the renewal flow:

1. **Add to Cart:**
   ```php
   // Visit domain management page
   // Click renewal button
   // Select years
   // Verify cart shows renewal with badge
   ```

2. **Checkout:**
   ```php
   // Navigate to checkout
   // Verify contact step is skipped
   // Select payment method
   // Complete order
   ```

3. **Verify Renewal:**
   ```php
   // Check domain.expires_at updated
   // Check domain_renewals record created
   // Check order.type = 'renewal'
   ```

## API Endpoints

### Add Renewal to Cart
```
POST /domains/{domain}/renew/add-to-cart
```

## Configuration

Renewal pricing is managed in the `domain_prices` table:
- `renewal_price` field (stored in cents)
- Separate from registration price
- Can differ by TLD

## Future Enhancements

Potential improvements:
- Auto-renewal functionality
- Renewal reminders (email notifications)
- Bulk renewal (multiple domains at once)
- Renewal history dashboard
- Grace period warnings

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Review domain_renewals table for failed renewals
3. Check order status and notes field
4. Verify registry API credentials

## Summary

The renewal system is now fully integrated into your domain management platform. Users can:
- ✅ Add domains to cart for renewal
- ✅ Select renewal duration (1-10 years)
- ✅ Skip contact selection for renewals
- ✅ Pay and automatically renew at registry
- ✅ View renewal history and status

The system handles both EPP and Namecheap registries seamlessly.

