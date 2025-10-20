<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Car;
use App\Models\InsuranceProvider;
use App\Models\Status;
use Illuminate\Http\Request;
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
        $claims = Claim::with(['car', 'insuranceProvider', 'status'])
            ->latest()
            ->paginate(10);
        return view($this->dir.'index', compact('claims'));
    }

    public function create()
    {
        $cars = Car::with(['carModel', 'company'])->get();
        $insuranceProviders = InsuranceProvider::all();
        $statuses = Status::where('type', 'claim')->get();
        return view($this->dir.'create', compact('cars', 'insuranceProviders', 'statuses'));
    }

    public function store(Request $request)
    {
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

        Claim::create($validated);

        return redirect()->route('claims.index')
            ->with('success', 'Claim created successfully.');
    }

    public function show(Claim $claim)
    {
        $claim->load(['car', 'insuranceProvider', 'status']);
        return view($this->dir.'show', compact('claim'));
    }

    public function edit($id)
    {
        $model = Claim::findOrFail($id);
        $cars = Car::with(['carModel', 'company'])->get();
        $insuranceProviders = InsuranceProvider::all();
        $statuses = Status::where('type', 'claim')->get();

        return view($this->dir.'edit', compact('model', 'cars', 'insuranceProviders', 'statuses'));
    }

    public function update(Request $request, Claim $claim)
    {
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

        $claim->update($validated);

        return redirect()->route('claims.index')
            ->with('success', 'Claim updated successfully.');
    }

    public function destroy(Claim $claim)
    {
        $claim->delete();

        return redirect()->route('claims.index')
            ->with('success', 'Claim deleted successfully.');
    }
}
