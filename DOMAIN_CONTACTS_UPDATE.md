# Domain Contact Management Update

## Overview

The domain contact management section has been updated to provide a more user-friendly interface for managing domain contacts. Instead of a single form with dropdowns, each contact type now has its own card with detailed information and individual edit functionality.

## Features

### Individual Contact Cards
- **Registrant Contact** (Primary color)
- **Admin Contact** (Success color)
- **Technical Contact** (Info color)
- **Billing Contact** (Warning color)

Each card displays:
- Contact's full name
- Email address
- Phone number
- Organization (if available)
- Full address
- Edit button for that specific contact type

### Modal-Based Editing
- Click "Edit [Contact Type]" to open a modal
- Select from available contacts in a dropdown
- Live preview of selected contact information
- Updates only the specific contact type being edited

### Backend Integration
- Uses existing `UpdateDomainContactsAction` with enhanced logic
- Maintains existing contacts when updating a single contact type
- Syncs changes with Namecheap API via `NamecheapDomainService`
- Updates local database relationships

## Technical Implementation

### Files Modified

1. **resources/views/admin/domains/nameservers.blade.php**
   - Replaced single contact form with individual contact cards
   - Added modal for contact editing
   - Added JavaScript for modal functionality and form handling

2. **app/Actions/Domains/UpdateDomainContactsAction.php**
   - Enhanced to handle partial contact updates
   - Added methods to get current contacts and merge with new data
   - Maintains all existing contacts when updating a single type

3. **app/Http/Requests/Admin/UpdateDomainContactsRequest.php**
   - Updated validation rules to use 'sometimes' for partial updates
   - Allows updating individual contact types without requiring all

### API Integration

The system integrates with Namecheap's domain service:
- Fetches current contacts from registry
- Updates contacts at registrar level
- Syncs changes back to local database
- Maintains contact type mappings (registrant, admin, technical, billing)

### Contact Type Mapping

The system handles different contact type naming conventions:
- `tech` → `technical`
- `auxbilling` → `billing`
- Direct mapping for `registrant` and `admin`

## Usage

1. Navigate to domain management page
2. View current contacts in individual cards
3. Click "Edit [Contact Type]" for the contact you want to update
4. Select new contact from dropdown
5. Preview contact information
6. Submit to update both registrar and local database

## Testing

A feature test has been added (`tests/Feature/DomainContactUpdateTest.php`) to verify:
- Contact updates work correctly
- Contact cards display properly
- Database relationships are maintained

## Benefits

- **Better UX**: Clear visual separation of contact types
- **Easier Management**: Edit individual contacts without affecting others
- **Better Information Display**: Full contact details visible at a glance
- **Consistent API Integration**: Maintains sync with Namecheap registry
- **Flexible Updates**: Update one contact type at a time
