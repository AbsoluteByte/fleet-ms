<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Package;
use App\Models\Subscription;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = '/admin/dashboard';

    public function __construct()
    {
        $this->middleware('guest');
    }

    // ✅ Simple Registration Form
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    // ✅ Validate
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'company_name' => ['required', 'string', 'max:100', 'unique:tenants,company_name'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted']
        ]);
    }

    // ✅ Register User
    protected function register(Request $request)
    {
        $this->validator($request->all())->validate();

        try {
            DB::beginTransaction();

            // 1️⃣ Create Tenant
            $tenant = Tenant::create([
                'company_name' => $request->company_name,
                'status' => Tenant::STATUS_ACTIVE,
            ]);

            // 2️⃣ Create Admin User
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // 3️⃣ Assign Admin Role
            $user->assignRole('admin');

            // 4️⃣ Attach to Tenant
            $tenant->users()->attach($user->id, [
                'role' => 'admin',
                'is_primary' => true,
                'joined_at' => now()
            ]);

            // 5️⃣ Auto-assign Trial Package
            $trialPackage = Package::where('name', 'Free Trial')->first();

            if (!$trialPackage) {
                throw new \Exception('Trial package not found. Please run seeder.');
            }

            Subscription::create([
                'tenant_id' => $tenant->id,
                'package_id' => $trialPackage->id,
                'status' => 'trialing',
                'trial_ends_at' => now()->addDays($trialPackage->trial_days),
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);

            DB::commit();

            // 6️⃣ Login User
            auth()->login($user);

            return redirect()
                ->route('admin.dashboard')
                ->with('success', 'Welcome! Your 30-day free trial has started. Explore all features!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Registration Failed:', ['error' => $e->getMessage()]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Registration failed: ' . $e->getMessage());
        }
    }
}
