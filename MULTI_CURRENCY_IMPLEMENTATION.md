# Multi-Currency Implementation for Domain Pricing

This document explains the multi-currency system implemented for the domain registration platform.

## Overview

The system supports multiple currencies with automatic conversion and proper formatting. It follows Laravel best practices and provides a clean, maintainable architecture.

## Key Components

### 1. Currency Model (`app/Models/Currency.php`)
- Stores currency information (code, name, symbol, exchange rate)
- Manages base currency relationships
- Provides conversion methods between currencies

### 2. CurrencyService (`app/Services/CurrencyService.php`)
- Handles currency operations and conversions
- Updates exchange rates from external APIs
- Manages user currency preferences
- Provides formatting utilities

### 3. DomainPrice Model Updates (`app/Models/DomainPrice.php`)
- Enhanced with multi-currency support
- Automatic price conversion based on domain type
- Local domains (.rw) use RWF as base currency
- International domains use USD as base currency

### 4. Currency Middleware (`app/Http/Middleware/SetCurrency.php`)
- Handles currency switching requests
- Updates session and user preferences

### 5. Livewire Component (`app/Livewire/CurrencySwitcher.php`)
- Provides UI for currency selection
- Real-time currency switching

## Database Schema

### Currencies Table
```sql
- id: Primary key
- code: 3-letter currency code (USD, EUR, RWF, etc.)
- name: Full currency name
- symbol: Currency symbol ($, €, FRw, etc.)
- exchange_rate: Rate against base currency (USD)
- is_base: Boolean indicating base currency
- is_active: Boolean for active currencies
- rate_updated_at: Timestamp of last rate update
```

## Usage Examples

### Getting Formatted Prices
```php
$domainPrice = DomainPrice::where('tld', '.com')->first();

// Get price in user's preferred currency
$price = $domainPrice->getFormattedPrice('register_price');

// Get price in specific currency
$priceInEUR = $domainPrice->getFormattedPrice('register_price', 'EUR');
```

### Currency Conversion
```php
$currencyService = app(CurrencyService::class);

// Convert between currencies
$convertedAmount = $currencyService->convert(15.00, 'USD', 'RWF');

// Format with currency symbol
$formatted = $currencyService->format(15.00, 'USD'); // Returns "$15.00"
```

### Using the Trait
```php
class MyController extends Controller
{
    use HasCurrency;

    public function index()
    {
        $userCurrency = $this->getUserCurrency();
        $converted = $this->convertCurrency(100, 'USD', 'EUR');
        $formatted = $this->formatCurrency(100, 'USD');
    }
}
```

## Currency Management

### Updating Exchange Rates
```bash
php artisan currency:update-rates
```

### Adding New Currencies
1. Add currency to the `CurrencySeeder`
2. Run the seeder: `php artisan db:seed --class=CurrencySeeder`

### Currency Switching
Users can switch currencies via:
- Livewire component: `<livewire:currency-switcher />`
- API endpoint: `POST /api/currencies/switch`
- URL parameter: `?currency=EUR`

## Configuration

### Supported Currencies
- USD (Base currency)
- RWF (Rwandan Franc)
- EUR (Euro)
- GBP (British Pound)
- KES (Kenyan Shilling)
- UGX (Ugandan Shilling)
- TZS (Tanzanian Shilling)

### Exchange Rate API
The system uses exchangerate-api.com for real-time exchange rates. Rates are cached for 1 hour to improve performance.

## Domain Pricing Logic

### Local Domains (.rw, .co.rw, etc.)
- Base currency: RWF
- Prices stored in RWF cents
- Converted to other currencies when requested

### International Domains (.com, .net, etc.)
- Base currency: USD
- Prices stored in USD cents
- Converted to other currencies when requested

## Best Practices

1. **Always use the service layer** for currency operations
2. **Cache exchange rates** to reduce API calls
3. **Handle conversion failures** gracefully with fallbacks
4. **Store prices in cents** to avoid floating-point precision issues
5. **Use proper currency symbols** for better UX

## Testing

The system includes comprehensive tests for:
- Currency conversion accuracy
- Price formatting
- Domain pricing in different currencies
- User preference handling

## Future Enhancements

1. **Multiple exchange rate providers** for redundancy
2. **Historical exchange rate tracking**
3. **Currency-specific pricing** (different prices per currency)
4. **Automatic rate update scheduling**
5. **Currency conversion fees** handling
