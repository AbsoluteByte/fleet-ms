<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Car;
use App\Models\InsuranceProvider;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ClaimController extends Controller
{
    protected $url = 'claims.';
    protected $dir = 'backend.claims.';
    protected $name = 'Claims';

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
        $claims = Claim::where('tenant_id', $tenant->id)->with(['car', 'insuranceProvider', 'status'])->get();
        return view($this->dir.'index', compact('claims'));
    }

    public function create()
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }
        $cars = Car::with(['carModel', 'company'])->where('tenant_id', $tenant->id)->get();
        $insuranceProviders = InsuranceProvider::where('tenant_id', $tenant->id)->get();
        $statuses = Status::where('type', 'claim')->get();
        return view($this->dir.'create', compact('cars', 'insuranceProviders', 'statuses'));
    }

    public function store(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }
        $validated = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'insurance_provider_id' => 'required|exists:insurance_providers,id',
            'case_date' => 'required|date',
            'incident_date' => 'required|date',
            'our_reference' => 'required|string|max:255',
            'case_reference' => 'required|string|max:255',
            'courtesy_type' => 'required|string|max:255',
            'follow_up' => 'nullable|string',
            'notes' => 'nullable|string',
            'status_id' => 'required|exists:statuses,id',
        ]);
        // ✅ Add tenant_id automatically
        $validated['tenant_id'] = $tenant->id;
        Claim::create($validated);

        return redirect()->route('claims.index')
            ->with('success', 'Claim created successfully.');
    }

    public function show(Claim $claim)
    {
        $tenant = Auth::user()->currentTenant();

        // ✅ Check ownership
        if ($claim->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this car');
        }
        $claim->load(['car', 'insuranceProvider', 'status']);
        return view($this->dir.'show', compact('claim'));
    }

    public function edit($id)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        $model = Claim::where('tenant_id', $tenant->id)->findOrFail($id);
        $cars = Car::where('tenant_id', $tenant->id)->with(['carModel', 'company'])->get();
        $insuranceProviders = InsuranceProvider::where('tenant_id', $tenant->id)->get();
        $statuses = Status::where('type', 'claim')->get();

        return view($this->dir.'edit', compact('model', 'cars', 'insuranceProviders', 'statuses'));
    }

    public function update(Request $request, Claim $claim)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }
        $validated = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'insurance_provider_id' => 'required|exists:insurance_providers,id',
            'case_date' => 'required|date',
            'incident_date' => 'required|date',
            'our_reference' => 'required|string|max:255',
            'case_reference' => 'required|string|max:255',
            'courtesy_type' => 'required|string|max:255',
            'follow_up' => 'nullable|string',
            'notes' => 'nullable|string',
            'status_id' => 'required|exists:statuses,id',
        ]);

        // ✅ Ensure tenant_id stays the same
        $validated['tenant_id'] = $tenant->id;
        $claim->update($validated);

        return redirect()->route('claims.index')
            ->with('success', 'Claim updated successfully.');
    }

    public function destroy(Claim $claim)
    {
        $tenant = Auth::user()->currentTenant();

        // ✅ Check ownership
        if ($claim->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }
        $claim->delete();

        return redirect()->route('claims.index')
            ->with('success', 'Claim deleted successfully.');
    }
}
