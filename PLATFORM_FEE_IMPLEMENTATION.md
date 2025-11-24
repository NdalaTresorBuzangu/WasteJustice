# Platform Fee Implementation - 1% Transaction Fee

## Overview
WasteJustice now automatically deducts a 1% platform fee from all payment transactions. This fee is separate from subscription fees and provides revenue for the platform.

## Implementation Details

### Database Changes
1. **Payment Table Updates** (`db/wastejustice_complete.sql`):
   - Added `platformFee` column: Stores the 1% fee amount (DECIMAL 10,2)
   - Added `grossAmount` column: Stores the original transaction amount before fee deduction
   - Updated `amount` column: Now stores the net amount received by the recipient (99% of gross)

### Payment Processing Changes

#### 1. Collector → Aggregator Payments (`acceptDelivery`)
- **Location**: `classes/aggregator_class.php`
- **Process**:
  - Calculates gross amount: `weight × pricePerKg`
  - Calculates 1% platform fee: `grossAmount × 0.01`
  - Net amount to collector: `grossAmount - platformFee` (99%)
  - Stores all three values in Payment table

#### 2. Company → Aggregator Payments (`sellBatchToCompany`)
- **Location**: `classes/aggregator_class.php`
- **Process**:
  - Calculates gross sale price: `totalWeight × pricePerKg`
  - Calculates 1% platform fee: `grossSalePrice × 0.01`
  - Net amount to aggregator: `grossSalePrice - platformFee` (99%)
  - Stores all three values in Payment table

### Admin Dashboard Updates
- **Location**: `views/admin/dashboard.php` and `classes/admin_class.php`
- **New Metrics**:
  - **Platform Fees Collected**: Total 1% fees collected from all completed transactions
  - **Gross Transaction Volume**: Total transaction value before fee deduction
  - **Total Payments (Net)**: Total amount actually paid to users (after fees)

### Migration Script
- **File**: `db/migration_add_platform_fee.sql`
- **Purpose**: Adds new columns to existing Payment tables
- **Usage**: Run this script if you have an existing database

## Fee Calculation Example

### Example 1: Collector Payment
- Weight: 100 kg
- Price per kg: GH₵ 5.00
- **Gross Amount**: GH₵ 500.00
- **Platform Fee (1%)**: GH₵ 5.00
- **Net Amount to Collector**: GH₵ 495.00

### Example 2: Batch Sale
- Total Weight: 500 kg
- Price per kg: GH₵ 7.00
- **Gross Sale Price**: GH₵ 3,500.00
- **Platform Fee (1%)**: GH₵ 35.00
- **Net Amount to Aggregator**: GH₵ 3,465.00

## User Experience
- Users see the **net amount** they receive (after fee deduction)
- Platform fee is transparently tracked in the database
- Admin dashboard shows total fees collected
- All notifications and displays show the actual amount users receive

## Important Notes
1. **Subscription fees are separate** - This 1% fee is only on transaction payments, not subscriptions
2. **Fee is automatic** - No manual intervention required
3. **Fee is transparent** - All amounts are tracked and visible in admin dashboard
4. **Existing payments** - Migration script handles existing records (sets fee to 0 for historical data)

## Testing Checklist
- [ ] Test collector payment acceptance (verify fee calculation)
- [ ] Test batch sale to company (verify fee calculation)
- [ ] Verify admin dashboard shows platform fees
- [ ] Verify users see correct net amounts
- [ ] Run migration script on existing database (if applicable)

