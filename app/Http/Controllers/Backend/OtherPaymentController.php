<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Car;
use App\Models\OtherPayment;
use App\Models\Payment;
use App\Support\BatchPaymentInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OtherPaymentController extends Controller
{
    protected $url = 'other-payments.';

    protected $dir = 'backend.other-payments.';

    protected $name = 'Other Payments';

    public function __construct()
    {
        $this->middleware('role:admin|manager|user');
        view()->share('url', $this->url);
        view()->share('dir', $this->dir);
        view()->share('singular', 'Other Payment');
        view()->share('plural', $this->name);
    }

    public function index()
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $otherPayments = OtherPayment::query()
            ->where('tenant_id', $tenant->id)
            ->with(['bankAccount', 'car.carModel', 'createdByUser'])
            ->latest('payment_date')
            ->latest('id')
            ->paginate(15);

        return view($this->dir.'index', compact('otherPayments'));
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

        $model = new OtherPayment;

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
            'other_payment_type' => ['required', Rule::in([
                OtherPayment::TYPE_OFFICE,
                OtherPayment::TYPE_VEHICLE,
            ])],
            'car_id' => [
                'nullable',
                Rule::requiredIf(fn () => $request->input('other_payment_type') === OtherPayment::TYPE_VEHICLE),
                Rule::exists('cars', 'id')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'title' => 'required|string|max:255',
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
            $path = public_path('uploads/other_payment_documents/');
            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }
            $file->move($path, $documentName);
        }

        DB::transaction(function () use ($tenant, $validated, $paymentRows, $documentName) {
            foreach ($paymentRows as $paymentRow) {
                OtherPayment::query()->create([
                    'tenant_id' => $tenant->id,
                    'other_payment_type' => $validated['other_payment_type'],
                    'car_id' => $validated['other_payment_type'] === OtherPayment::TYPE_VEHICLE
                        ? $validated['car_id']
                        : null,
                    'title' => $validated['title'],
                    'amount' => $paymentRow['amount'],
                    'payment_method' => $paymentRow['payment_method'],
                    'bank_account_id' => $paymentRow['bank_account_id'],
                    'payment_date' => $paymentRow['payment_date'] ?? now()->toDateString(),
                    'document' => $documentName,
                    'notes' => $validated['notes'] ?? null,
                    'posting_status' => OtherPayment::POSTING_STATUS_PENDING,
                    'created_by' => Auth::id(),
                ]);
            }
        });

        $message = count($paymentRows) > 1
            ? count($paymentRows).' other payments recorded. They will appear on the daily financial sheet until approval.'
            : 'Other payment recorded. It will appear on the daily financial sheet until approval.';

        return redirect()->route('other-payments.index')
            ->with('success', $message);
    }
}
