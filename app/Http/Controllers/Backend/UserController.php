<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    protected $url = 'users.';
    protected $dir = 'backend.users.';
    protected $name = 'Users';

    public function __construct()
    {
        $this->middleware('role:admin');
        view()->share('url', $this->url);
        view()->share('dir', $this->dir);
        view()->share('singular', Str::singular($this->name));
        view()->share('plural', Str::plural($this->name));
    }

    public function index()
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        // Get users who belong to current tenant only
        $users = $tenant->users()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['user', 'driver']);
            })
            ->get();

        return view($this->dir . 'index', compact('users'));
    }

    public function create()
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        $model = new User();
        return view($this->dir . 'create', compact('model'));
    }

    public function store(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }

        $this->validate($request, [
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:50', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed']
        ]);

        try {
            DB::beginTransaction();
            // Create user
            $model = new User();
            $model->name = request('name', null);
            $model->email = request('email', null);
            $model->password = Hash::make(request('password'));
            $model->save();

            // Assign role
            $model->assignRole('user');

            // Attach user to current tenant
            $model->tenants()->attach($tenant->id, [
                'role' => 'user',
                'is_primary' => true,
                'joined_at' => now()
            ]);

            DB::commit();

            return redirect()
                ->route($this->url . 'index')
                ->with('success', Str::singular($this->name) . ' saved successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error creating user: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        //Check if user belongs to current tenant
        $model = $tenant->users()->where('users.id', $id)->firstOrFail();

        return view($this->dir . 'show', compact('model'));
    }

    public function edit($id)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        // Check if user belongs to current tenant
        $model = $tenant->users()->where('users.id', $id)->firstOrFail();

        return view($this->dir . 'edit', compact('model'));
    }

    public function update(Request $request, $id)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }

        // Check if user belongs to current tenant
        $model = $tenant->users()->where('users.id', $id)->firstOrFail();

        $this->validate($request, [
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'unique:users,email,' . $model->id],
        ]);

        $model->name = request('name', null);
        $model->email = request('email', null);
        $model->save();

        return redirect()
            ->route($this->url . 'index')
            ->with('success', Str::singular($this->name) . ' updated successfully!');
    }

    public function destroy($id)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }

        // Check if user belongs to current tenant
        $model = $tenant->users()->where('users.id', $id)->firstOrFail();

        try {
            DB::beginTransaction();

            // Detach from tenant first
            $model->tenants()->detach($tenant->id);

            // If user has no other tenants, delete the user
            if ($model->tenants()->count() === 0) {
                $model->delete();
            }

            DB::commit();

            return redirect()
                ->route($this->url . 'index')
                ->with('success', Str::singular($this->name) . ' deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Error deleting user: ' . $e->getMessage());
        }
    }
}
