<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'company_code',
                'value' => '33',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Company identification code',
            ],
            [
                'key' => 'station_id',
                'value' => '470',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Station identification number',
            ],
            [
                'key' => 'hash_key',
                'value' => 'W8tTzdSx7YfzDNUJunkM3CbMn0XB97rx00NwlQxlOhvfesM3a1kf6klgRIO VQn5my0JzikT8YSJEyOeY',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Security hash key for API communications',
            ],
            [
                'key' => 'currency_symbol',
                'value' => 'N',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Currency symbol for display',
            ],
            [
                'key' => 'currency_name',
                'value' => 'NAIRA',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Full currency name',
            ],
            [
                'key' => 'cash_account',
                'value' => 'CASH',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Default cash account',
            ],
            [
                'key' => 'sales_account',
                'value' => 'SALES',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Default sales account',
            ],
            [
                'key' => 'purchase_account',
                'value' => 'PURCHASES',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Default purchase account',
            ],
            [
                'key' => 'station_name',
                'value' => 'BABALLE OLORU STATION',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Name of the filling station',
            ],
            [
                'key' => 'manager',
                'value' => 'MUHAMMAD MUHAMMAD',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Station manager name',
            ],
            [
                'key' => 'phone_number',
                'value' => '08121285689',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Station contact phone number',
            ],
            [
                'key' => 'email_address',
                'value' => 'info@baballestation.com',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Station contact email address',
            ],
            [
                'key' => 'address',
                'value' => 'Kambi New Oloru Jeba Express Road, Moroh Local Government Kwara State.',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Station physical address',
            ],
            [
                'key' => 'city',
                'value' => 'KWARA',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Station city',
            ],
            [
                'key' => 'state',
                'value' => 'KWARA',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Station state',
            ],
            [
                'key' => 'last_modified_on',
                'value' => now()->format('M d, Y g:ia'),
                'type' => 'string',
                'group' => 'general',
                'description' => 'Last modification timestamp',
            ],
            [
                'key' => 'last_modified_by',
                'value' => 'System',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Last modification user',
            ],

            // HR Settings
            [
                'key' => 'working_days_from',
                'value' => 'Monday',
                'type' => 'string',
                'group' => 'hr',
                'description' => 'Working week start day',
            ],
            [
                'key' => 'working_days_to',
                'value' => 'Friday',
                'type' => 'string',
                'group' => 'hr',
                'description' => 'Working week end day',
            ],
            [
                'key' => 'working_hours_from',
                'value' => '08:00',
                'type' => 'string',
                'group' => 'hr',
                'description' => 'Daily work start time',
            ],
            [
                'key' => 'working_hours_to',
                'value' => '17:00',
                'type' => 'string',
                'group' => 'hr',
                'description' => 'Daily work end time',
            ],
            [
                'key' => 'enable_overtime',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'hr',
                'description' => 'Enable overtime calculations',
            ],
            [
                'key' => 'salary_wages_account',
                'value' => 'SALARY_WAGES',
                'type' => 'string',
                'group' => 'hr',
                'description' => 'Account for salary and wages',
            ],

            // Options Settings
            [
                'key' => 'print_duplicate_receipt',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'options',
                'description' => 'Print duplicate receipts for sales',
            ],
            [
                'key' => 'disable_sales_receipt_reprinting',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'options',
                'description' => 'Disable sales receipt reprinting',
            ],
            [
                'key' => 'allow_changing_rate_during_sales',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'options',
                'description' => 'Allow changing rates during sales',
            ],
            [
                'key' => 'default_remittance_account',
                'value' => 'CASH',
                'type' => 'string',
                'group' => 'options',
                'description' => 'Default account for remittances',
            ],
            [
                'key' => 'loyalty_expense_account',
                'value' => 'LOYALTY_EXPENSE',
                'type' => 'string',
                'group' => 'options',
                'description' => 'Account for loyalty program expenses',
            ],
            [
                'key' => 'fillup_device_id',
                'value' => '',
                'type' => 'string',
                'group' => 'options',
                'description' => 'Fillup device identification',
            ],
            [
                'key' => 'note_on_sale_pos_receipt',
                'value' => 'Thank you for your business!',
                'type' => 'string',
                'group' => 'options',
                'description' => 'Note to print on POS sale receipts',
            ],
            [
                'key' => 'note_on_sale_a4_receipt',
                'value' => 'Thank you for choosing our services.',
                'type' => 'string',
                'group' => 'options',
                'description' => 'Note to print on A4 sale receipts',
            ],
            [
                'key' => 'note_on_retail_pos_receipt',
                'value' => 'Visit us again!',
                'type' => 'string',
                'group' => 'options',
                'description' => 'Note to print on retail POS receipts',
            ],
            [
                'key' => 'note_on_retail_a4_receipt',
                'value' => 'We appreciate your patronage.',
                'type' => 'string',
                'group' => 'options',
                'description' => 'Note to print on retail A4 receipts',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}