<?php
// app/Http/Controllers/Backend/CustomerController.php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Package;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    protected $url = 'customers.';
    protected $dir = 'backend.customers.';
    protected $name = 'Customers';

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
        $tenants = Tenant::with([
            'users' => function($query) {
                $query->wherePivot('role', 'admin');
            },
            'subscription.package'
        ])->latest()->get();

        return view($this->dir . 'index', compact('tenants'));
    }

    public function create()
    {
        $model = new User();
        $tenant = new Tenant(); // ✅ Add this
        $packages = Package::where('is_active', true)->get();
        return view($this->dir . 'create', compact('model', 'tenant', 'packages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_name' => ['required', 'string', 'max:100', 'unique:tenants,company_name'],
            'name'         => ['required', 'string', 'max:50'],
            'email'        => ['required', 'email', 'max:50', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'package_id'   => ['required', 'exists:packages,id'],
        ]);

        try {
            DB::beginTransaction();

            // 1️⃣ Create Tenant
            $tenant = Tenant::create([
                'company_name' => $request->company_name,
                'status' => Tenant::STATUS_ACTIVE,
            ]);

            // 2️⃣ Create Admin User
            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
            ]);

            // 3️⃣ Assign Spatie Role
            $user->assignRole('admin');

            // 4️⃣ Attach User to Tenant
            $tenant->users()->attach($user->id, [
                'role' => 'admin',
                'is_primary' => true,
                'joined_at' => now()
            ]);

            // 5️⃣ Create Subscription with Trial
            $package = Package::findOrFail($request->package_id);

            Subscription::create([
                'tenant_id' => $tenant->id,
                'package_id' => $package->id,
                'status' => 'trialing',
                'trial_ends_at' => now()->addDays($package->trial_days),
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);

            DB::commit();

            return redirect()
                ->route($this->url . 'index')
                ->with('success', 'Customer created successfully with ' . $package->trial_days . ' days trial!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error creating customer: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        // Get tenant with all relationships
        $tenant = Tenant::with([
            'users' => function($query) {
                $query->wherePivot('role', 'admin');
            },
            'subscription.package',
            'paymentMethods',
            'invoices' => function($query) {
                $query->get();
            },
            'cars',
            'drivers',
        ])->findOrFail($id);

        // Get subscription details
        $subscription = $tenant->subscription;

        // Get usage statistics
        $stats = [
            'total_users' => $tenant->users()->count(),
            'total_vehicles' => $tenant->cars()->count(),
            'total_drivers' => $tenant->drivers()->count(),
            'total_invoices' => $tenant->invoices()->count(),
            'total_paid' => $tenant->invoices()->where('status', 'paid')->sum('total'),
        ];

        // Get package limits if subscription exists
        if ($subscription && $subscription->package) {
            $package = $subscription->package;
            $stats['users_limit'] = $package->max_users;
            $stats['vehicles_limit'] = $package->max_vehicles;
            $stats['drivers_limit'] = $package->max_drivers;

            // Calculate usage percentages
            $stats['users_percentage'] = $package->max_users > 0
                ? ($stats['total_users'] / $package->max_users * 100)
                : 0;
            $stats['vehicles_percentage'] = $package->max_vehicles > 0
                ? ($stats['total_vehicles'] / $package->max_vehicles * 100)
                : 0;
            $stats['drivers_percentage'] = $package->max_drivers > 0
                ? ($stats['total_drivers'] / $package->max_drivers * 100)
                : 0;
        }

        return view($this->dir . 'show', compact('tenant', 'subscription', 'stats'));
    }

    public function edit($id)
    {
        // ✅ Load tenant with admin user
        $tenant = Tenant::with([
            'users' => function($query) {
                $query->wherePivot('role', 'admin');
            },
            'subscription.package'
        ])->findOrFail($id);

        // ✅ Get admin user
        $model = $tenant->users->first();

        // ✅ Check if admin exists
        if (!$model) {
            return redirect()
                ->route($this->url . 'index')
                ->with('error', 'Admin user not found for this tenant!');
        }

        $packages = Package::where('is_active', true)->get();

        return view($this->dir . 'edit', compact('model', 'tenant', 'packages'));
    }

    public function update(Request $request, $id)
    {
        // ✅ Find tenant
        $tenant = Tenant::findOrFail($id);

        // ✅ Get admin user
        $user = $tenant->users()->wherePivot('role', 'admin')->first();

        // ✅ Check if admin user exists
        if (!$user) {
            return redirect()
                ->back()
                ->with('error', 'Admin user not found for this tenant!');
        }

        // ✅ Validation with proper exclusion
        $request->validate([
            'company_name' => [
                'required',
                'string',
                'max:100',
                'unique:tenants,company_name,' . $tenant->id  // ✅ Exclude current tenant
            ],
            'name' => [
                'required',
                'string',
                'max:50'
            ],
            'email' => [
                'required',
                'email',
                'max:50',
                'unique:users,email,' . $user->id  // ✅ Exclude current user
            ],
            'package_id' => [
                'nullable',
                'exists:packages,id'
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed'
            ],
        ]);

        try {
            DB::beginTransaction();

            // ✅ Update Tenant
            $tenant->update([
                'company_name' => $request->company_name,
            ]);

            // ✅ Update Admin User
            $userData = [
                'name'  => $request->name,
                'email' => $request->email,
            ];

            // ✅ Update Password if provided
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            $user->update($userData);

            // ✅ Update Package if changed
            if ($request->filled('package_id') && $tenant->subscription) {
                $tenant->subscription->update([
                    'package_id' => $request->package_id
                ]);
            }

            DB::commit();

            return redirect()
                ->route($this->url . 'index')
                ->with('success', 'Customer updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error updating customer: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $tenant = Tenant::findOrFail($id);
            $tenant->delete(); // Cascade delete will handle everything

            return redirect()
                ->route($this->url . 'index')
                ->with('success', 'Customer deleted successfully!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error deleting customer: ' . $e->getMessage());
        }
    }

    public function suspend(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);

        $tenant->suspend($request->reason ?? 'Suspended by admin');

        return redirect()
            ->route($this->url . 'show', $id)
            ->with('success', 'Customer suspended successfully!');
    }

    public function activate($id)
    {
        $tenant = Tenant::findOrFail($id);

        $tenant->activate();

        return redirect()
            ->route($this->url . 'show', $id)
            ->with('success', 'Customer activated successfully!');
    }
}
