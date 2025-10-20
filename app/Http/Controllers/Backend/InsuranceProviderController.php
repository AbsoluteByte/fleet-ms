<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\InsuranceProvider;
use App\Models\Status;
use Illuminate\Http\Request;
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
        $insuranceProviders = InsuranceProvider::with(['status', 'company'])->get();
        return view($this->dir.'index', compact('insuranceProviders'));
    }

    public function create()
    {
        $statuses = Status::where('type', 'insurance')->get();
        $companies = Company::all();
        return view($this->dir.'create', compact('statuses', 'companies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'provider_name' => 'required|string|max:255',
            'insurance_type' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'policy_number' => 'required|string|max:255',
            'expiry_date' => 'required|date',
            'status_id' => 'required|exists:statuses,id',
        ]);

        InsuranceProvider::create($validated);

        return redirect()->route('insurance-providers.index')
            ->with('success', 'Insurance provider created successfully.');
    }

    public function show(InsuranceProvider $insuranceProvider)
    {
        $insuranceProvider->load(['status', 'company']);
        return view($this->dir.'show', compact('insuranceProvider'));
    }

    public function edit($id)
    {
        $model = InsuranceProvider::findOrFail($id);
        $statuses = Status::where('type', 'insurance')->get();
        $companies = Company::all();
        return view($this->dir.'edit', compact('model', 'statuses', 'companies'));
    }

    public function update(Request $request, InsuranceProvider $insuranceProvider)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'provider_name' => 'required|string|max:255',
            'insurance_type' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'policy_number' => 'required|string|max:255',
            'expiry_date' => 'required|date',
            'status_id' => 'required|exists:statuses,id',
        ]);

        $insuranceProvider->update($validated);

        return redirect()->route('insurance-providers.index')
            ->with('success', 'Insurance provider updated successfully.');
    }

    public function destroy(InsuranceProvider $insuranceProvider)
    {
        $insuranceProvider->delete();

        return redirect()->route('insurance-providers.index')
            ->with('success', 'Insurance provider deleted successfully.');
    }
}
