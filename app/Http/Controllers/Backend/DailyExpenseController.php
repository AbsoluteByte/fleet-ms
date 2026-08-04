<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Car;
use App\Models\Expense;
use App\Models\Payment;
use App\Support\BatchPaymentInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            ->with(['bankAccount', 'car.carModel'])
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
        $cars = Car::query()
            ->where('tenant_id', $tenant->id)
            ->with(['carModel', 'company'])
            ->orderBy('registration')
            ->get();

        $model = new Expense;

        return view($this->dir.'create', compact('bankAccounts', 'cars', 'model'));
    }

    public function store(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }

        $validated = $request->validate(array_merge([
            'daily_expense_type' => ['required', Rule::in([
                Expense::DAILY_TYPE_OFFICE,
                Expense::DAILY_TYPE_VEHICLE,
            ])],
            'car_id' => [
                'nullable',
                Rule::requiredIf(fn () => $request->input('daily_expense_type') === Expense::DAILY_TYPE_VEHICLE),
                Rule::exists('cars', 'id')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'document' => 'nullable|file',
            'notes' => 'nullable|string',
        ], BatchPaymentInput::validationRules($request, $tenant->id)));

        $paymentRows = BatchPaymentInput::normalizeRows($validated);

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

        DB::transaction(function () use ($tenant, $validated, $paymentRows, $documentName) {
            foreach ($paymentRows as $paymentRow) {
                Expense::query()->create([
                    'tenant_id' => $tenant->id,
                    'car_id' => $validated['daily_expense_type'] === Expense::DAILY_TYPE_VEHICLE
                        ? $validated['car_id']
                        : null,
                    'type' => Expense::TYPE_DAILY,
                    'daily_expense_type' => $validated['daily_expense_type'],
                    'title' => $validated['title'],
                    'date' => $paymentRow['payment_date'] ?? $validated['date'],
                    'description' => $validated['title'],
                    'amount' => $paymentRow['amount'],
                    'payment_method' => $paymentRow['payment_method'],
                    'bank_account_id' => $paymentRow['bank_account_id'],
                    'document' => $documentName,
                    'notes' => $validated['notes'] ?? null,
                    'posting_status' => Expense::POSTING_STATUS_PENDING,
                    'created_by' => Auth::id(),
                ]);
            }
        });

        $message = count($paymentRows) > 1
            ? count($paymentRows).' daily expenses recorded. They will appear on the daily financial sheet until approval.'
            : 'Daily expense recorded. It will appear on the daily financial sheet until approval.';

        return redirect()->route('daily-expenses.index')
            ->with('success', $message);
    }
}
