<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    protected $url = 'companies.';
    protected $dir = 'backend.companies.';
    protected $name = 'Companies';

    public function __construct()
    {
        //$this->middleware('role:admin');
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
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $companies = Company::where('tenant_id', $tenant->id)->get();
        return view($this->dir . 'index', compact('companies'));
    }

    public function create()
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        $model = new Company();
        return view($this->dir . 'create', compact('model'));
    }

    public function store(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'director_name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'postcode' => 'required|string|max:20',
            'town' => 'required|string|max:100',
            'county' => 'required|string|max:100',
            'country_id' => 'required|numeric|exists:countries,id',
            'phone' => 'required|numeric',
            'email' => 'required|email|max:255',
        ]);

        if ($request->hasFile('logo')) {
            $dims = getimagesize($request->logo);
            $width = $dims[0];
            $height = $dims[1];
            $name = time() . '-' . $width . '-' . $height . '.' . $request->file('logo')->extension();
            $path = public_path('uploads/companies/');
            $file = $request->file('logo');
            if ($file->move($path, $name)) {
                $validated['logo'] = $name;
            }
        }

        $validated['tenant_id'] = $tenant->id;
        $validated['createdBy'] = Auth::id();
        Company::create($validated);

        return redirect()->route('companies.index')
            ->with('success', 'Company created successfully.');
    }

    public function show(Company $company)
    {
        $tenant = Auth::user()->currentTenant();

        // ✅ Check ownership
        if ($company->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this car');
        }
        $company->load('country');
        return view($this->dir.'show', compact('company'));
    }

    public function edit($id)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }
        $model = Company::where('tenant_id', $tenant->id)->findOrFail($id);
        return view($this->dir . 'edit', compact('model'));
    }

    public function update(Request $request, Company $company)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'director_name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'postcode' => 'required|string|max:20',
            'town' => 'required|string|max:100',
            'county' => 'required|string|max:100',
            'country_id' => 'required|numeric|exists:countries,id',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
        ]);


        if ($request->hasFile('logo')) {
            $dims = getimagesize($request->logo);
            $width = $dims[0];
            $height = $dims[1];
            $name = time() . '-' . $width . '-' . $height . '.' . $request->file('logo')->extension();
            $path = public_path('uploads/companies/');
            $file = $request->file('logo');
            $oldImage = $company->logo;
            if ($file->move($path, $name)) {
                if ($oldImage) {
                    $image_path = public_path('uploads/companies/' . $oldImage);
                    if (File::exists($image_path)) {
                        File::delete($image_path);
                    }
                }
                $validated['logo'] = $name;
            }
        }
        $validated['tenant_id'] = $tenant->id;
        $validated['updatedBy'] = Auth::id();
        $company->update($validated);

        return redirect()->route($this->url.'index')
            ->with('success', 'Company updated successfully.');
    }

    public function destroy(Company $company)
    {
        $tenant = Auth::user()->currentTenant();

        // ✅ Check ownership
        if ($company->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }
        if ($company) {
            $image_path = public_path('uploads/companies/' . $company->logo);
            if (File::exists($image_path)) {
                File::delete($image_path);
            }
        }
        $company->delete();
        return redirect()->route($this->url.'index')
            ->with('success', 'Company deleted successfully.');
    }
}
