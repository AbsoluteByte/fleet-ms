<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\InsurancePolicy;
use App\Models\Car;
use App\Models\InsuranceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InsurancePolicyController extends Controller
{
    protected $url = 'insurance-policies.';
    protected $dir = 'backend.insurancePolicies.';
    protected $name = 'Insurance Policies';

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
        $policies = InsurancePolicy::with(['car', 'insuranceProvider'])
            ->latest()
            ->paginate(10);

        return view($this->dir . 'index', compact('policies'));
    }

    public function create()
    {
        $cars = Car::all();
        $providers = InsuranceProvider::all();
        $model = new InsurancePolicy();

        return view($this->dir . 'create', compact('model', 'cars', 'providers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'insurance_provider_id' => 'required|exists:insurance_providers,id',
            'policy_number' => 'required|string|unique:insurance_policies',
            'policy_type' => 'required|in:comprehensive,third_party,fire_theft,collision',
            'premium_amount' => 'required|numeric|min:0',
            'excess_amount' => 'nullable|numeric|min:0',
            'policy_start_date' => 'required|date',
            'policy_end_date' => 'required|date|after:policy_start_date',
            'coverage_details' => 'nullable|array',
            'payment_frequency' => 'required|in:monthly,quarterly,half_yearly,yearly',
            'monthly_premium' => 'nullable|numeric|min:0',
            'auto_renewal' => 'boolean',
            'notify_days_before_expiry' => 'required|integer|min:1|max:365',
            'policy_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'notes' => 'nullable|string'
        ]);

        try {
            if ($request->hasFile('policy_document')) {
                $validated['policy_document'] = $request->file('policy_document')->store('policy_documents', 'public');
            }

            InsurancePolicy::create($validated);

            return redirect()->route('insurance-policies.index')
                ->with('success', 'Insurance policy created successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating policy: ' . $e->getMessage());
        }
    }

    public function show(InsurancePolicy $insurancePolicy)
    {
        $insurancePolicy->load(['car', 'insuranceProvider']);
        return view($this->dir . 'show', compact('insurancePolicy'));
    }

    public function edit(InsurancePolicy $insurancePolicy)
    {
        $model = $insurancePolicy;
        $cars = Car::all();
        $providers = InsuranceProvider::all();

        return view($this->dir . 'edit', compact('model', 'cars', 'providers'));
    }

    public function update(Request $request, InsurancePolicy $insurancePolicy)
    {
        $validated = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'insurance_provider_id' => 'required|exists:insurance_providers,id',
            'policy_number' => 'required|string|unique:insurance_policies,policy_number,' . $insurancePolicy->id,
            'policy_type' => 'required|in:comprehensive,third_party,fire_theft,collision',
            'premium_amount' => 'required|numeric|min:0',
            'excess_amount' => 'nullable|numeric|min:0',
            'policy_start_date' => 'required|date',
            'policy_end_date' => 'required|date|after:policy_start_date',
            'coverage_details' => 'nullable|array',
            'payment_frequency' => 'required|in:monthly,quarterly,half_yearly,yearly',
            'monthly_premium' => 'nullable|numeric|min:0',
            'auto_renewal' => 'boolean',
            'notify_days_before_expiry' => 'required|integer|min:1|max:365',
            'policy_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,expired,cancelled,pending'
        ]);

        try {
            if ($request->hasFile('policy_document')) {
                $validated['policy_document'] = $request->file('policy_document')->store('policy_documents', 'public');
            }

            $insurancePolicy->update($validated);

            return redirect()->route('insurance-policies.index')
                ->with('success', 'Insurance policy updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating policy: ' . $e->getMessage());
        }
    }

    public function destroy(InsurancePolicy $insurancePolicy)
    {
        try {
            $insurancePolicy->delete();

            return redirect()->route('insurance-policies.index')
                ->with('success', 'Insurance policy deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting policy: ' . $e->getMessage());
        }
    }

    public function expiring()
    {
        $expiringPolicies = InsurancePolicy::expiring(30)
            ->with(['car', 'insuranceProvider'])
            ->orderBy('policy_end_date')
            ->paginate(10);

        return view($this->dir . 'expiring', compact('expiringPolicies'));
    }
}
