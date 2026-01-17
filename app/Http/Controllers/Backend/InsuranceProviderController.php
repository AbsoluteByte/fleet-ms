<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\InsuranceProvider;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InsuranceProviderController extends Controller
{
    protected $url = 'insurance-providers.';
    protected $dir = 'backend.insuranceProviders.';
    protected $name = 'Insurance Providers';

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
                ->with('error', 'No active company found! Please contact administrator.');
        }
        $insuranceProviders = InsuranceProvider::where('tenant_id', $tenant->id)->with(['status', 'company'])->get();
        return view($this->dir.'index', compact('insuranceProviders'));
    }

    public function create()
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }
        $statuses = Status::where('type', 'insurance')->get();
        $companies = Company::where('tenant_id', $tenant->id)->all();
        return view($this->dir.'create', compact('statuses', 'companies'));
    }

    public function store(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'provider_name' => 'required|string|max:255',
            'insurance_type' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'policy_number' => 'required|string|max:255',
            'expiry_date' => 'required|date',
            'status_id' => 'required|exists:statuses,id',
        ]);

        $validated['tenant_id'] = $tenant->id;
        $validated['createdBy'] = Auth::id();
        InsuranceProvider::create($validated);

        return redirect()->route('insurance-providers.index')
            ->with('success', 'Insurance provider created successfully.');
    }

    public function show(InsuranceProvider $insuranceProvider)
    {
        $tenant = Auth::user()->currentTenant();

        // ✅ Check ownership
        if ($insuranceProvider->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this car');
        }
        $insuranceProvider->load(['status', 'company']);
        return view($this->dir.'show', compact('insuranceProvider'));
    }

    public function edit($id)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        $model = InsuranceProvider::where('tenant_id', $tenant->id)->findOrFail($id);
        $statuses = Status::where('type', 'insurance')->get();
        $companies = Company::where('tenant_id', $tenant->id)->all();
        return view($this->dir.'edit', compact('model', 'statuses', 'companies'));
    }

    public function update(Request $request, InsuranceProvider $insuranceProvider)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'provider_name' => 'required|string|max:255',
            'insurance_type' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'policy_number' => 'required|string|max:255',
            'expiry_date' => 'required|date',
            'status_id' => 'required|exists:statuses,id',
        ]);

        $validated['tenant_id'] = $tenant->id;
        $validated['updatedBy'] = Auth::id();
        $insuranceProvider->update($validated);

        return redirect()->route('insurance-providers.index')
            ->with('success', 'Insurance provider updated successfully.');
    }

    public function destroy(InsuranceProvider $insuranceProvider)
    {
        $tenant = Auth::user()->currentTenant();

        // ✅ Check ownership
        if ($insuranceProvider->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }
        $insuranceProvider->delete();

        return redirect()->route('insurance-providers.index')
            ->with('success', 'Insurance provider deleted successfully.');
    }
}
