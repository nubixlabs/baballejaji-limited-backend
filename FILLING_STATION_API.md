# Filling Station API Documentation

This document describes the API endpoints for the Filling Station module.

## Base URL
All endpoints are prefixed with `/api/filling` and require authentication via Sanctum bearer token.

## Authentication
All endpoints require the `Authorization: Bearer {token}` header.

## Endpoints

### Products
- `GET /api/filling/products` - List all products
- `GET /api/filling/products/{id}` - Get product by ID
- `POST /api/filling/products` - Create new product
- `PUT /api/filling/products/{id}` - Update product
- `DELETE /api/filling/products/{id}` - Delete product
- `GET /api/filling/products/inventory/summary` - Get inventory summary

### Tanks
- `GET /api/filling/tanks` - List all tanks
- `GET /api/filling/tanks/{id}` - Get tank by ID
- `POST /api/filling/tanks` - Create new tank
- `PUT /api/filling/tanks/{id}` - Update tank
- `DELETE /api/filling/tanks/{id}` - Delete tank

### Customers
- `GET /api/filling/customers` - List all customers
- `GET /api/filling/customers/{id}` - Get customer by ID
- `POST /api/filling/customers` - Create new customer
- `PUT /api/filling/customers/{id}` - Update customer
- `DELETE /api/filling/customers/{id}` - Delete customer

### Shifts
- `GET /api/filling/shifts` - List all shifts
- `GET /api/filling/shifts/{id}` - Get shift by ID
- `POST /api/filling/shifts` - Create new shift
- `PUT /api/filling/shifts/{id}` - Update shift
- `DELETE /api/filling/shifts/{id}` - Delete shift
- `POST /api/filling/shifts/{id}/close` - Close shift
- `POST /api/filling/shifts/{id}/approve` - Approve shift

### Stock Levels
- `GET /api/filling/stock-levels` - List all stock levels
- `POST /api/filling/stock-levels` - Create new stock level
- `PUT /api/filling/stock-levels/{id}` - Update stock level
- `DELETE /api/filling/stock-levels/{id}` - Delete stock level

### Daily Sales
- `GET /api/filling/daily-sales` - List all daily sales
- `POST /api/filling/daily-sales` - Create new daily sale
- `PUT /api/filling/daily-sales/{id}` - Update daily sale
- `DELETE /api/filling/daily-sales/{id}` - Delete daily sale

### Bulk Sales
- `GET /api/filling/bulk-sales` - List all bulk sales
- `GET /api/filling/bulk-sales/{id}` - Get bulk sale by ID
- `POST /api/filling/bulk-sales` - Create new bulk sale
- `PUT /api/filling/bulk-sales/{id}` - Update bulk sale
- `DELETE /api/filling/bulk-sales/{id}` - Delete bulk sale

### Retail Sales
- `GET /api/filling/retail-sales` - List all retail sales
- `GET /api/filling/retail-sales/{id}` - Get retail sale by ID
- `POST /api/filling/retail-sales` - Create new retail sale
- `PUT /api/filling/retail-sales/{id}` - Update retail sale
- `DELETE /api/filling/retail-sales/{id}` - Delete retail sale

### Purchases
- `GET /api/filling/purchases` - List all purchases
- `GET /api/filling/purchases/{id}` - Get purchase by ID
- `POST /api/filling/purchases` - Create new purchase
- `PUT /api/filling/purchases/{id}` - Update purchase
- `DELETE /api/filling/purchases/{id}` - Delete purchase
- `POST /api/filling/purchases/{id}/receive` - Receive purchase items

### Price Adjustments
- `GET /api/filling/price-adjustments` - List all price adjustments
- `POST /api/filling/price-adjustments` - Create new price adjustment

### Inventory Reconciliations
- `GET /api/filling/inventory-reconciliations` - List all inventory reconciliations
- `POST /api/filling/inventory-reconciliations` - Create new inventory reconciliation

## Database Migrations

Run the following command to create all database tables:

```bash
php artisan migrate
```

## Models Created

- Product
- Tank
- Customer
- Shift
- StockLevel
- DailySale
- BulkSale
- BulkSaleItem
- RetailSale
- RetailSaleItem
- Purchase
- PurchaseItem
- PriceAdjustment
- InventoryReconciliation
- TankDipping
- TankTransfer

## Notes

- All endpoints are protected with `auth:sanctum` middleware
- Product quantities are automatically updated when sales are created
- Bulk and retail sales update product inventory
- Purchases update product quantities when received
- Price adjustments automatically update product prices
- Inventory reconciliations update product quantities to match physical count

