<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
        // sirf tenant admins
        $users = User::role('admin')->with('tenant')->get();
        return view($this->dir . 'index', compact('users'));
    }

    public function create()
    {
        $model = new User();
        return view($this->dir . 'create', compact('model'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_name' => ['required', 'string', 'max:100'],
            'name'         => ['required', 'string', 'max:50'],
            'email'        => ['required', 'email', 'max:50', 'unique:users'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** -------------------------
         * 1️⃣ Create Tenant
         * ------------------------- */
        $tenant = Tenant::create([
            'company_name'   => $request->company_name,
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        /** -------------------------
         * 2️⃣ Create Admin User
         * ------------------------- */
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
        ]);

        /** -------------------------
         * 3️⃣ Assign Role
         * ------------------------- */
        $user->assignRole('admin');

        return redirect()
            ->route($this->url . 'index')
            ->with('success', 'Customer created successfully!');
    }

    public function show($id)
    {
        $model = User::with('tenant')->findOrFail($id);
        return view($this->dir . 'show', compact('model'));
    }

    public function edit($id)
    {
        $model = User::with('tenant')->findOrFail($id);
        return view($this->dir . 'edit', compact('model'));
    }

    public function update(Request $request, $id)
    {
        $model = User::findOrFail($id);

        $request->validate([
            'company_name' => ['required', 'string', 'max:100'],
            'name'         => ['required', 'string', 'max:50'],
            'email'        => ['required', 'email', 'unique:users,email,' . $model->id],
        ]);

        /** Update Tenant */
        $model->tenant->update([
            'name' => $request->company_name,
        ]);

        /** Update User */
        $model->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        return redirect()
            ->route($this->url . 'index')
            ->with('success', 'Customer updated successfully!');
    }

    public function destroy($id)
    {
        $model = User::findOrFail($id);
        // Tenant delete → cascade users (FK cascade)
        $model->tenant()->delete();

        return redirect()
            ->route($this->url . 'index')
            ->with('success', 'Customer deleted successfully!');
    }
}
