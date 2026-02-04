<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    /**
     * Get all settings or settings by group
     */
    public function index(Request $request)
    {
        $group = $request->get('group');
        
        if ($group) {
            $settings = Setting::getByGroup($group);
        } else {
            $settings = Setting::all()->mapWithKeys(function ($setting) {
                return [$setting->key => Setting::get($setting->key)];
            });
        }

        return response()->json([
            'settings' => $settings
        ]);
    }

    /**
     * Get shipping settings
     */
    public function getShippingSettings()
    {
        return response()->json([
            'shipping_rate_per_kg' => Setting::get('shipping_rate_per_kg', 500),
            'free_shipping_threshold' => Setting::get('free_shipping_threshold', 0),
            'default_weight' => Setting::get('default_weight', 0.5),
        ]);
    }

    /**
     * Update shipping settings
     */
    public function updateShippingSettings(Request $request)
    {
        $validated = $request->validate([
            'shipping_rate_per_kg' => 'required|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'default_weight' => 'nullable|numeric|min:0',
        ]);

        Setting::set('shipping_rate_per_kg', $validated['shipping_rate_per_kg'], 'decimal', 'shipping');
        
        if (isset($validated['free_shipping_threshold'])) {
            Setting::set('free_shipping_threshold', $validated['free_shipping_threshold'], 'decimal', 'shipping');
        }
        
        if (isset($validated['default_weight'])) {
            Setting::set('default_weight', $validated['default_weight'], 'decimal', 'shipping');
        }

        return response()->json([
            'message' => 'Shipping settings updated successfully',
            'settings' => [
                'shipping_rate_per_kg' => Setting::get('shipping_rate_per_kg', 500),
                'free_shipping_threshold' => Setting::get('free_shipping_threshold', 0),
                'default_weight' => Setting::get('default_weight', 0.5),
            ]
        ]);
    }

    /**
     * Get bank account settings
     */
    public function getBankSettings()
    {
        return response()->json([
            'bank_name' => Setting::get('bank_name', 'Bank of Ceylon'),
            'account_number' => Setting::get('bank_account_number', ''),
            'account_name' => Setting::get('bank_account_name', 'SH Womens Fashion (Pvt) Ltd'),
            'branch' => Setting::get('bank_branch', ''),
            'branch_code' => Setting::get('bank_branch_code', ''),
            'swift_code' => Setting::get('bank_swift_code', ''),
        ]);
    }

    /**
     * Update bank account settings
     */
    public function updateBankSettings(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'branch_code' => 'nullable|string|max:20',
            'swift_code' => 'nullable|string|max:20',
        ]);

        Setting::set('bank_name', $validated['bank_name'], 'string', 'payment');
        Setting::set('bank_account_number', $validated['account_number'], 'string', 'payment');
        Setting::set('bank_account_name', $validated['account_name'], 'string', 'payment');
        
        if (isset($validated['branch'])) {
            Setting::set('bank_branch', $validated['branch'], 'string', 'payment');
        }
        if (isset($validated['branch_code'])) {
            Setting::set('bank_branch_code', $validated['branch_code'], 'string', 'payment');
        }
        if (isset($validated['swift_code'])) {
            Setting::set('bank_swift_code', $validated['swift_code'], 'string', 'payment');
        }

        return response()->json([
            'message' => 'Bank settings updated successfully',
            'settings' => [
                'bank_name' => Setting::get('bank_name'),
                'account_number' => Setting::get('bank_account_number'),
                'account_name' => Setting::get('bank_account_name'),
                'branch' => Setting::get('bank_branch'),
                'branch_code' => Setting::get('bank_branch_code'),
                'swift_code' => Setting::get('bank_swift_code'),
            ]
        ]);
    }

    /**
     * Update a single setting
     */
    public function update(Request $request, $key)
    {
        $validated = $request->validate([
            'value' => 'required',
            'type' => 'nullable|string|in:string,integer,boolean,json,array,float,decimal',
            'group' => 'nullable|string',
        ]);

        $type = $validated['type'] ?? 'string';
        $group = $validated['group'] ?? 'general';

        Setting::set($key, $validated['value'], $type, $group);

        return response()->json([
            'message' => 'Setting updated successfully',
            'key' => $key,
            'value' => Setting::get($key)
        ]);
    }
}
