<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BankAccountController extends Controller
{
    protected $url = 'bank-accounts.';

    protected $dir = 'backend.bankAccounts.';

    protected $name = 'Bank Accounts';

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

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $bankAccounts = BankAccount::query()
            ->where('tenant_id', $tenant->id)
            ->with('company')
            ->orderBy('bank_name')
            ->get();

        return view($this->dir.'index', compact('bankAccounts'));
    }

    public function create()
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        $companies = Company::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        return view($this->dir.'create', compact('companies'));
    }

    public function store(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }

        $validated = $request->validate($this->validationRules($tenant->id));

        $validated['tenant_id'] = $tenant->id;
        $validated['createdBy'] = Auth::id();

        BankAccount::create($validated);

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Bank account created successfully.');
    }

    public function edit($id)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        $model = BankAccount::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($id);

        $companies = Company::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        return view($this->dir.'edit', compact('model', 'companies'));
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }

        if ($bankAccount->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }

        $validated = $request->validate($this->validationRules($tenant->id, $bankAccount->id));

        $validated['tenant_id'] = $tenant->id;
        $validated['updatedBy'] = Auth::id();

        $bankAccount->update($validated);

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Bank account updated successfully.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }

        if ($bankAccount->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }

        $bankAccount->delete();

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Bank account deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validationRules(int $tenantId, ?int $ignoreId = null): array
    {
        $uniqueAccount = Rule::unique('bank_accounts', 'account_number')
            ->where(fn ($query) => $query->where('company_id', request()->input('company_id')));

        if ($ignoreId !== null) {
            $uniqueAccount->ignore($ignoreId);
        }

        return [
            'company_id' => [
                'required',
                Rule::exists('companies', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'bank_name' => 'required|string|max:255',
            'account_number' => ['required', 'string', 'max:50', $uniqueAccount],
        ];
    }
}
