<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PackageController extends Controller
{
    protected $url = 'packages.';
    protected $dir = 'backend.packages.';
    protected $name = 'Packages';

    public function __construct()
    {
        $this->middleware('role:superuser');

        view()->share('url', $this->url);
        view()->share('dir', $this->dir);
        view()->share('singular', Str::singular($this->name));
        view()->share('plural', Str::plural($this->name));
    }

    public function index()
    {
        $packages = Package::withCount('subscriptions')->get();
        return view($this->dir . 'index', compact('packages'));
    }

    public function create()
    {
        $model = new Package();
        return view($this->dir . 'create', compact('model'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:packages,name'],
            'description' => ['nullable', 'string'],
            'billing_period' => ['required', 'in:monthly,quarterly,yearly'],
            'price' => ['required', 'numeric', 'min:0'],
            'max_users' => ['required', 'integer', 'min:-1'],
            'max_vehicles' => ['required', 'integer', 'min:-1'],
            'max_drivers' => ['required', 'integer', 'min:-1'],
            'has_notifications' => ['boolean'],
            'has_reports' => ['boolean'],
            'has_api_access' => ['boolean'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'is_active' => ['boolean'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255']
        ]);

        // Convert features array to JSON
        if ($request->has('features')) {
            $validated['features'] = array_filter($request->features);
        }

        // Handle checkboxes
        $validated['has_notifications'] = $request->has('has_notifications');
        $validated['has_reports'] = $request->has('has_reports');
        $validated['has_api_access'] = $request->has('has_api_access');
        $validated['is_active'] = $request->has('is_active');

        Package::create($validated);

        return redirect()
            ->route($this->url . 'index')
            ->with('success', 'Package created successfully!');
    }

    public function show(Package $package)
    {
        $package->loadCount('subscriptions');
        return view($this->dir . 'show', compact('package'));
    }

    public function edit(Package $package)
    {
        $model = $package;
        return view($this->dir . 'edit', compact('model'));
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:packages,name,' . $package->id],
            'description' => ['nullable', 'string'],
            'billing_period' => ['required', 'in:monthly,quarterly,yearly'],
            'price' => ['required', 'numeric', 'min:0'],
            'max_users' => ['required', 'integer', 'min:-1'],
            'max_vehicles' => ['required', 'integer', 'min:-1'],
            'max_drivers' => ['required', 'integer', 'min:-1'],
            'has_notifications' => ['boolean'],
            'has_reports' => ['boolean'],
            'has_api_access' => ['boolean'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'is_active' => ['boolean'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255']
        ]);

        // Convert features array to JSON
        if ($request->has('features')) {
            $validated['features'] = array_filter($request->features);
        }

        // Handle checkboxes
        $validated['has_notifications'] = $request->has('has_notifications');
        $validated['has_reports'] = $request->has('has_reports');
        $validated['has_api_access'] = $request->has('has_api_access');
        $validated['is_active'] = $request->has('is_active');

        $package->update($validated);

        return redirect()
            ->route($this->url . 'index')
            ->with('success', 'Package updated successfully!');
    }

    public function destroy(Package $package)
    {
        // Check if package has active subscriptions
        if ($package->subscriptions()->whereIn('status', ['active', 'trialing'])->exists()) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete package with active subscriptions!');
        }

        $package->delete();

        return redirect()
            ->route($this->url . 'index')
            ->with('success', 'Package deleted successfully!');
    }

    // Toggle active status
    public function toggleStatus(Package $package)
    {
        $package->update(['is_active' => !$package->is_active]);

        $status = $package->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Package {$status} successfully!");
    }
}
