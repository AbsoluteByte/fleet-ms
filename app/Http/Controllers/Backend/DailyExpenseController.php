<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DailyExpenseController extends Controller
{
    protected $url = 'daily-expenses.';

    protected $dir = 'backend.daily-expenses.';

    protected $name = 'Daily Expenses';

    public function __construct()
    {
        $this->middleware('role:admin|manager|user');
        view()->share('url', $this->url);
        view()->share('dir', $this->dir);
        view()->share('singular', 'Daily Expense');
        view()->share('plural', $this->name);
    }

    public function index()
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $expenses = Expense::query()
            ->where('tenant_id', $tenant->id)
            ->daily()
            ->with('bankAccount')
            ->latest('date')
            ->latest('id')
            ->paginate(15);

        return view($this->dir.'index', compact('expenses'));
    }

    public function create()
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        $bankAccounts = BankAccount::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('bank_name')
            ->get();

        $model = new Expense;

        return view($this->dir.'create', compact('bankAccounts', 'model'));
    }

    public function store(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:Bank Transfer,Cash,Cheque,Card Payment,Direct Debit',
            'bank_account_id' => [
                'nullable',
                Rule::requiredIf(fn () => Payment::requiresBankAccount($request->input('payment_method'))),
                Rule::exists('bank_accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'date' => 'required|date',
            'document' => 'nullable|file',
            'notes' => 'nullable|string',
        ]);

        $documentName = null;
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $mimeType = $file->getMimeType();
            if (str_starts_with((string) $mimeType, 'image/')) {
                $dims = getimagesize($file);
                $width = $dims[0] ?? 0;
                $height = $dims[1] ?? 0;
                $documentName = time().'-'.$width.'-'.$height.'.'.$file->extension();
            } else {
                $documentName = time().'.'.$file->extension();
            }
            $path = public_path('uploads/expense_documents/');
            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }
            $file->move($path, $documentName);
        }

        Expense::query()->create([
            'tenant_id' => $tenant->id,
            'car_id' => null,
            'type' => Expense::TYPE_DAILY,
            'title' => $validated['title'],
            'date' => $validated['date'],
            'description' => $validated['title'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'bank_account_id' => Payment::bankAccountIdForMethod(
                $validated['payment_method'],
                $validated['bank_account_id'] ?? null
            ),
            'document' => $documentName,
            'notes' => $validated['notes'] ?? null,
            'posting_status' => Expense::POSTING_STATUS_PENDING,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('daily-expenses.index')
            ->with('success', 'Daily expense recorded. It will appear on the daily financial sheet until approval.');
    }
}
