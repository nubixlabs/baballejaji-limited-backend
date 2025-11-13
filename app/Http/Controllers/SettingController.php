<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * Get all settings grouped by category.
     */
    public function index(): JsonResponse
    {
        $generalSettings = Setting::getGroup('general');
        $optionSettings = Setting::getGroup('options');
        $hrSettings = Setting::getGroup('hr');

        return response()->json([
            'success' => true,
            'data' => [
                'general' => $generalSettings,
                'options' => $optionSettings,
                'hr' => $hrSettings,
            ]
        ]);
    }

    /**
     * Get settings by group.
     */
    public function getByGroup(string $group): JsonResponse
    {
        $settings = Setting::getGroup($group);

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Update general settings.
     */
    public function updateGeneral(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_code' => 'nullable|string|max:10',
            'station_id' => 'nullable|string|max:10',
            'hash_key' => 'nullable|string',
            'currency_symbol' => 'nullable|string|max:5',
            'currency_name' => 'nullable|string|max:50',
            'cash_account' => 'nullable|string|max:100',
            'sales_account' => 'nullable|string|max:100',
            'purchase_account' => 'nullable|string|max:100',
            'station_name' => 'nullable|string|max:255',
            'manager' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'email_address' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
            Setting::setValue('logo', $logoPath, 'string', 'general');
        }

        // Update other settings
        foreach ($validated as $key => $value) {
            if ($key !== 'logo' && $value !== null) {
                Setting::setValue($key, $value, 'string', 'general');
            }
        }

        // Update last modified info
        Setting::setValue('last_modified_on', now()->format('M d, Y g:ia'), 'string', 'general');
        Setting::setValue('last_modified_by', auth()->user()->name, 'string', 'general');

        // Log activity
        if (auth()->user()) {
            auth()->user()->logActivity('settings_updated', 'Updated general settings');
        }

        return response()->json([
            'success' => true,
            'message' => 'General settings updated successfully'
        ]);
    }

    /**
     * Update HR settings.
     */
    public function updateHr(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'working_days_from' => 'nullable|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'working_days_to' => 'nullable|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'working_hours_from' => 'nullable|string',
            'working_hours_to' => 'nullable|string',
            'enable_overtime' => 'nullable|boolean',
            'salary_wages_account' => 'nullable|string|max:100',
        ]);

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                $type = $key === 'enable_overtime' ? 'boolean' : 'string';
                Setting::setValue($key, $value, $type, 'hr');
            }
        }

        // Log activity
        if (auth()->user()) {
            auth()->user()->logActivity('settings_updated', 'Updated HR settings');
        }

        return response()->json([
            'success' => true,
            'message' => 'HR settings updated successfully'
        ]);
    }

    /**
     * Update options settings.
     */
    public function updateOptions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'print_duplicate_receipt' => 'nullable|boolean',
            'disable_sales_receipt_reprinting' => 'nullable|boolean',
            'allow_changing_rate_during_sales' => 'nullable|boolean',
            'default_remittance_account' => 'nullable|string|max:100',
            'loyalty_expense_account' => 'nullable|string|max:100',
            'fillup_device_id' => 'nullable|string|max:100',
            'note_on_sale_pos_receipt' => 'nullable|string',
            'note_on_sale_a4_receipt' => 'nullable|string',
            'note_on_retail_pos_receipt' => 'nullable|string',
            'note_on_retail_a4_receipt' => 'nullable|string',
        ]);

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                $type = in_array($key, [
                    'print_duplicate_receipt',
                    'disable_sales_receipt_reprinting',
                    'allow_changing_rate_during_sales'
                ]) ? 'boolean' : 'string';
                
                Setting::setValue($key, $value, $type, 'options');
            }
        }

        // Log activity
        if (auth()->user()) {
            auth()->user()->logActivity('settings_updated', 'Updated options settings');
        }

        return response()->json([
            'success' => true,
            'message' => 'Options settings updated successfully'
        ]);
    }

    /**
     * Get all accounts for dropdowns.
     */
    public function getAccounts(): JsonResponse
    {
        $accounts = Account::active()->orderBy('name')->get(['id', 'code', 'name', 'type']);

        return response()->json([
            'success' => true,
            'data' => $accounts
        ]);
    }

    /**
     * Get a specific setting value.
     */
    public function getSetting(string $key): JsonResponse
    {
        $value = Setting::getValue($key);

        return response()->json([
            'success' => true,
            'data' => [
                'key' => $key,
                'value' => $value
            ]
        ]);
    }

    /**
     * Set a specific setting value.
     */
    public function setSetting(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required',
            'type' => 'nullable|string|in:string,boolean,integer,float,json,array',
            'group' => 'nullable|string|max:50',
        ]);

        $type = $validated['type'] ?? 'string';
        $group = $validated['group'] ?? 'general';

        Setting::setValue($validated['key'], $validated['value'], $type, $group);

        // Log activity
        if (auth()->user()) {
            auth()->user()->logActivity('setting_updated', "Updated setting: {$validated['key']}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully'
        ]);
    }
}