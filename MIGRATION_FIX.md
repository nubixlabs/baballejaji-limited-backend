# Migration Fix - Foreign Key Constraint Error

## Problem
When running `php artisan migrate`, the migration failed with:
```
SQLSTATE[HY000]: General error: 1005 Can't create table `purchases` (errno: 150 "Foreign key constraint is incorrectly formed")
```

## Root Cause
The filling station migrations had dates of `2025_01_15`, which came **before** the `suppliers` table migration date of `2025_10_28`. Since Laravel runs migrations in chronological order, it tried to create the `purchases` table (which references `suppliers`) before the `suppliers` table existed.

## Solution Applied

### 1. Renamed Migration Files
All filling station migrations were renamed from `2025_01_15_*` to `2025_10_29_*` to ensure they run after the suppliers table is created.

**Files renamed:**
- `2025_01_15_000001_create_products_table.php` → `2025_10_29_000001_create_products_table.php`
- `2025_01_15_000002_create_tanks_table.php` → `2025_10_29_000002_create_tanks_table.php`
- `2025_01_15_000003_create_customers_table.php` → `2025_10_29_000003_create_customers_table.php`
- `2025_01_15_000004_create_shifts_table.php` → `2025_10_29_000004_create_shifts_table.php`
- `2025_01_15_000005_create_stock_levels_table.php` → `2025_10_29_000005_create_stock_levels_table.php`
- `2025_01_15_000006_create_daily_sales_table.php` → `2025_10_29_000006_create_daily_sales_table.php`
- `2025_01_15_000007_create_bulk_sales_table.php` → `2025_10_29_000007_create_bulk_sales_table.php`
- `2025_01_15_000008_create_bulk_sale_items_table.php` → `2025_10_29_000008_create_bulk_sale_items_table.php`
- `2025_01_15_000009_create_retail_sales_table.php` → `2025_10_29_000009_create_retail_sales_table.php`
- `2025_01_15_000010_create_retail_sale_items_table.php` → `2025_10_29_000010_create_retail_sale_items_table.php`
- `2025_01_15_000011_create_purchases_table.php` → `2025_10_29_000011_create_purchases_table.php`
- `2025_01_15_000012_create_purchase_items_table.php` → `2025_10_29_000012_create_purchase_items_table.php`
- `2025_01_15_000013_create_price_adjustments_table.php` → `2025_10_29_000013_create_price_adjustments_table.php`
- `2025_01_15_000014_create_inventory_reconciliations_table.php` → `2025_10_29_000014_create_inventory_reconciliations_table.php`
- `2025_01_15_000015_create_tank_dippings_table.php` → `2025_10_29_000015_create_tank_dippings_table.php`
- `2025_01_15_000016_create_tank_transfers_table.php` → `2025_10_29_000016_create_tank_transfers_table.php`

### 2. Updated Foreign Key Constraint
Changed the `purchases` table migration to explicitly specify the `suppliers` table name:
```php
// Before
$table->foreignId('supplier_id')->constrained()->onDelete('cascade');

// After
$table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
```

## Migration Order (Correct)

1. `2025_10_28_000005_create_suppliers_table.php` - Creates suppliers table
2. `2025_10_29_000001_create_products_table.php` - Creates products table
3. `2025_10_29_000002_create_tanks_table.php` - Creates tanks table (depends on products)
4. `2025_10_29_000003_create_customers_table.php` - Creates customers table
5. `2025_10_29_000004_create_shifts_table.php` - Creates shifts table
6. `2025_10_29_000005_create_stock_levels_table.php` - Creates stock_levels table (depends on shifts, products)
7. `2025_10_29_000006_create_daily_sales_table.php` - Creates daily_sales table (depends on shifts, products)
8. `2025_10_29_000007_create_bulk_sales_table.php` - Creates bulk_sales table (depends on customers)
9. `2025_10_29_000008_create_bulk_sale_items_table.php` - Creates bulk_sale_items table (depends on bulk_sales, products)
10. `2025_10_29_000009_create_retail_sales_table.php` - Creates retail_sales table (depends on customers, shifts)
11. `2025_10_29_000010_create_retail_sale_items_table.php` - Creates retail_sale_items table (depends on retail_sales, products)
12. `2025_10_29_000011_create_purchases_table.php` - Creates purchases table (depends on suppliers) ✅
13. `2025_10_29_000012_create_purchase_items_table.php` - Creates purchase_items table (depends on purchases, products)
14. `2025_10_29_000013_create_price_adjustments_table.php` - Creates price_adjustments table (depends on products)
15. `2025_10_29_000014_create_inventory_reconciliations_table.php` - Creates inventory_reconciliations table (depends on products)
16. `2025_10_29_000015_create_tank_dippings_table.php` - Creates tank_dippings table (depends on tanks)
17. `2025_10_29_000016_create_tank_transfers_table.php` - Creates tank_transfers table (depends on tanks)

## Next Steps

### If Migrations Haven't Run Yet
Simply run:
```bash
php artisan migrate
```

### If Migrations Partially Failed
If the migration partially ran and created some tables, you may need to:

1. **Check migration status:**
   ```bash
   php artisan migrate:status
   ```

2. **If needed, rollback the failed migrations:**
   ```bash
   php artisan migrate:rollback --step=1
   ```

3. **Or manually drop the failed table (if it exists):**
   ```sql
   DROP TABLE IF EXISTS purchases;
   ```

4. **Then run migrations again:**
   ```bash
   php artisan migrate
   ```

## Verification

After running migrations, verify the tables were created:
```bash
php artisan migrate:status
```

All migrations should show as "Ran" with a checkmark.

## Notes

- The migration order is critical for foreign key constraints
- All foreign key constraints now explicitly reference the correct tables
- The `suppliers` table must exist before `purchases` table can be created
- The `products` table must exist before tables that reference it (tanks, sales, etc.)



