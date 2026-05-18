<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $accounts = [
            // Asset Accounts
            [
                'code' => 'CASH',
                'name' => 'Cash',
                'type' => 'asset',
                'category' => 'current_asset',
                'balance' => 0,
                'description' => 'Cash on hand and petty cash',
            ],
            [
                'code' => 'BANK',
                'name' => 'Bank Account',
                'type' => 'asset',
                'category' => 'current_asset',
                'balance' => 0,
                'description' => 'Bank account balances',
            ],
            [
                'code' => 'ACCOUNTS_RECEIVABLE',
                'name' => 'Accounts Receivable',
                'type' => 'asset',
                'category' => 'current_asset',
                'balance' => 0,
                'description' => 'Money owed by customers',
            ],
            [
                'code' => 'INVENTORY',
                'name' => 'Inventory',
                'type' => 'asset',
                'category' => 'current_asset',
                'balance' => 0,
                'description' => 'Fuel and product inventory',
            ],
            [
                'code' => 'EQUIPMENT',
                'name' => 'Equipment',
                'type' => 'asset',
                'category' => 'fixed_asset',
                'balance' => 0,
                'description' => 'Pumps, tanks, and other equipment',
            ],

            // Liability Accounts
            [
                'code' => 'ACCOUNTS_PAYABLE',
                'name' => 'Accounts Payable',
                'type' => 'liability',
                'category' => 'current_liability',
                'balance' => 0,
                'description' => 'Money owed to suppliers',
            ],
            [
                'code' => 'ACCRUED_EXPENSES',
                'name' => 'Accrued Expenses',
                'type' => 'liability',
                'category' => 'current_liability',
                'balance' => 0,
                'description' => 'Expenses incurred but not yet paid',
            ],

            // Equity Accounts
            [
                'code' => 'OWNERS_EQUITY',
                'name' => 'Owner\'s Equity',
                'type' => 'equity',
                'category' => 'equity',
                'balance' => 0,
                'description' => 'Owner\'s investment in the business',
            ],
            [
                'code' => 'RETAINED_EARNINGS',
                'name' => 'Retained Earnings',
                'type' => 'equity',
                'category' => 'equity',
                'balance' => 0,
                'description' => 'Accumulated profits retained in business',
            ],

            // Revenue Accounts
            [
                'code' => 'SALES',
                'name' => 'Sales Revenue',
                'type' => 'revenue',
                'category' => 'operating_revenue',
                'balance' => 0,
                'description' => 'Revenue from fuel and product sales',
            ],
            [
                'code' => 'SERVICE_REVENUE',
                'name' => 'Service Revenue',
                'type' => 'revenue',
                'category' => 'operating_revenue',
                'balance' => 0,
                'description' => 'Revenue from services provided',
            ],

            // Expense Accounts
            [
                'code' => 'PURCHASES',
                'name' => 'Purchases',
                'type' => 'expense',
                'category' => 'cost_of_goods_sold',
                'balance' => 0,
                'description' => 'Cost of fuel and products purchased',
            ],
            [
                'code' => 'SALARY_WAGES',
                'name' => 'Salary and Wages',
                'type' => 'expense',
                'category' => 'operating_expense',
                'balance' => 0,
                'description' => 'Employee salaries and wages',
            ],
            [
                'code' => 'UTILITIES',
                'name' => 'Utilities',
                'type' => 'expense',
                'category' => 'operating_expense',
                'balance' => 0,
                'description' => 'Electricity, water, and other utilities',
            ],
            [
                'code' => 'RENT_EXPENSE',
                'name' => 'Rent Expense',
                'type' => 'expense',
                'category' => 'operating_expense',
                'balance' => 0,
                'description' => 'Rent for premises and equipment',
            ],
            [
                'code' => 'MAINTENANCE',
                'name' => 'Maintenance and Repairs',
                'type' => 'expense',
                'category' => 'operating_expense',
                'balance' => 0,
                'description' => 'Equipment maintenance and repairs',
            ],
            [
                'code' => 'LOYALTY_EXPENSE',
                'name' => 'Loyalty Program Expense',
                'type' => 'expense',
                'category' => 'operating_expense',
                'balance' => 0,
                'description' => 'Costs related to customer loyalty programs',
            ],
            [
                'code' => 'OFFICE_SUPPLIES',
                'name' => 'Office Supplies',
                'type' => 'expense',
                'category' => 'operating_expense',
                'balance' => 0,
                'description' => 'Office supplies and materials',
            ],
            [
                'code' => 'INSURANCE',
                'name' => 'Insurance',
                'type' => 'expense',
                'category' => 'operating_expense',
                'balance' => 0,
                'description' => 'Insurance premiums and coverage',
            ],
            [
                'code' => 'DEPRECIATION',
                'name' => 'Depreciation Expense',
                'type' => 'expense',
                'category' => 'operating_expense',
                'balance' => 0,
                'description' => 'Depreciation of fixed assets',
            ],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(
                ['code' => $account['code']],
                $account
            );
        }
    }
}