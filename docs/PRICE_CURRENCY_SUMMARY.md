# Price Storage & Currency Exchange - Executive Summary

## Why Prices Are Stored in Cents

### Primary Reasons

1. **Precision & Accuracy** ✅
   - Eliminates floating-point errors (e.g., `0.1 + 0.2 ≠ 0.3`)
   - Integer arithmetic ensures exact financial calculations
   - Database `integer` type is more reliable than `decimal`/`float`

2. **Payment Processor Compatibility** 💳
   - Stripe requires amounts in minor units (cents)
   - Payment APIs expect integers, not floats
   - Direct compatibility: `1299` cents → Stripe API

3. **Currency-Specific Rules** 🌍
   - **USD**: Stored in cents (`1299` = `$12.99`)
   - **RWF**: Stored as whole units (`5000` = `5000 RWF`) - zero-decimal currency
   - **Hosting Plans**: Always USD cents

### Code Location
- **DomainPrice**: `app/Models/DomainPrice.php` (lines 83-99)
- **HostingPlanPrice**: `app/Models/HostingPlanPrice.php` (lines 99-114)
- **Database Schema**: `database/migrations/2025_07_28_122309_create_domain_prices_table.php`

## How Currency Exchange Works

### Architecture: Dual-Strategy Approach

```
┌─────────────────────────────────────────────────┐
│  USD/RWF Pairs → ExchangeRate-API (Real-time)  │
│  Other Pairs → Database Rates (Periodic Update) │
└─────────────────────────────────────────────────┘
```

### Flow Steps

1. **Price Retrieval**
   - Database stores: `1299` (integer cents)
   - Model converts: `1299 / 100 = 12.99 USD`

2. **Currency Detection**
   - Check user's preferred currency (session)
   - If different from base currency → convert

3. **Rate Lookup**
   - **USD/RWF**: Fetch from ExchangeRate-API (cached 1 hour)
   - **Other**: Lookup in `currencies` table

4. **Conversion**
   - Formula: `amount × exchange_rate`
   - Example: `12.99 USD × 1350.0 = 17,536.5 RWF`

5. **Formatting**
   - Add currency symbol
   - Handle decimals (RWF = 0 decimals)
   - Display: `FRW 17,537`

### Key Components

| Component | Purpose | Location |
|-----------|---------|----------|
| **CurrencyService** | Main conversion service | `app/Services/CurrencyService.php` |
| **CurrencyConverter** | New implementation | `app/Services/Currency/CurrencyConverter.php` |
| **ExchangeRateProvider** | USD/RWF API handler | `app/Services/Currency/ExchangeRateProvider.php` |
| **CurrencyExchangeHelper** | Legacy helper (deprecated) | `app/Helpers/CurrencyExchangeHelper.php` |
| **CartPriceConverter** | Cart item conversion | `app/Services/CartPriceConverter.php` |
| **PriceFormatter** | Display formatting | `app/Services/PriceFormatter.php` |

### Caching Strategy

1. **Request-Level Cache**: Prevents duplicate queries in same request
2. **Persistent Cache**: Redis/file cache (1 hour TTL)
3. **Cache Keys**:
   - Rates: `exchange_rate:USD:RWF`
   - Currencies: `currency:USD`
   - Active currencies: `active_currencies`

### Exchange Rate Update

- **Frequency**: Every 24 hours (configurable)
- **Source**: ExchangeRate-API for USD/RWF, manual/API for others
- **Storage**: `currencies` table with `rate_updated_at` timestamp
- **Event**: `ExchangeRatesUpdated` event clears user carts

### Example: Complete User Journey

```
User Views Domain Price
    ↓
Database: register_price = 1299 (cents)
    ↓
Model: getPriceInBaseCurrency() → 12.99 USD
    ↓
User Currency: RWF (from session)
    ↓
CurrencyService: convert(12.99, USD, RWF)
    ↓
Rate Lookup: Check cache → 1350.0
    ↓
Calculation: 12.99 × 1350.0 = 17,536.5 RWF
    ↓
Formatting: PriceFormatter → "FRW 17,537"
    ↓
Display: User sees "FRW 17,537"
```

### Special Cases

- **Zero-Decimal Currencies**: RWF, JPY, KRW stored as whole units
- **Hosting Plans**: Always USD, converted for display
- **Cart Items**: Each item converted individually, original preserved
- **Fallback**: Config rates used if API fails

### Benefits

✅ **Accuracy**: Integer storage prevents calculation errors  
✅ **Performance**: Multi-level caching reduces API calls  
✅ **Flexibility**: Supports multiple currencies with different rules  
✅ **Reliability**: Fallback mechanisms ensure continuity  
✅ **Audit Trail**: Original prices/currencies preserved  

## Files Created

1. **`docs/price-storage-and-currency-exchange.md`** - Detailed technical documentation
2. **`docs/price-currency-data-lineage.mmd`** - Mermaid diagram for visualization
3. **`docs/PRICE_CURRENCY_SUMMARY.md`** - This summary document

## Visual Diagram

See `docs/price-currency-data-lineage.mmd` for a complete data lineage diagram showing:
- Database storage layer
- Price retrieval layer
- Currency conversion layer
- Formatting layer
- Cart & checkout flow
