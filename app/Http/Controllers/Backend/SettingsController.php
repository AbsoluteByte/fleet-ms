<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    protected $url = 'settings.';
    protected $dir = 'backend.settings.';
    protected $name = 'Settings';

    public function __construct()
    {
        $this->middleware('role:admin');
        view()->share('url', $this->url);
        view()->share('dir', $this->dir);
        view()->share('singular', Str::singular($this->name));
        view()->share('plural', Str::plural($this->name));
    }

    /**
     * Display settings (only one record per tenant)
     */
    public function index()
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        // Get or create settings for tenant
        $setting = Setting::getForTenant($tenant->id);
        $tenantUsers = $tenant->users()->orderBy('name')->orderBy('email')->get();

        return view($this->dir . 'index', compact('setting', 'tenantUsers'));
    }

    /**
     * Show edit form
     */
    public function edit(Setting $setting)
    {
        $tenant = Auth::user()->currentTenant();

        // Check ownership
        if ($setting->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }

        $model = $setting;
        $tenantUsers = $tenant->users()->orderBy('name')->orderBy('email')->get();

        return view($this->dir . 'edit', compact('model', 'tenantUsers'));
    }

    /**
     * Update settings
     */
    public function update(Request $request, Setting $setting)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }

        // Check ownership
        if ($setting->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }

        $validated = $request->validate([
            'esign_provider' => 'required|in:custom,hellosign',
            'payment_reminder_user_ids' => 'nullable|array',
            'payment_reminder_user_ids.*' => [
                'integer',
                Rule::exists('tenant_user', 'user_id')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
        ]);

        try {
            $setting->update([
                'esign_provider' => $validated['esign_provider'],
                'payment_reminder_user_ids' => array_values(array_unique(array_map('intval', $validated['payment_reminder_user_ids'] ?? []))),
            ]);

            return redirect()->route('settings.index')
                ->with('success', 'Settings updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating settings: ' . $e->getMessage());
        }
    }
}
