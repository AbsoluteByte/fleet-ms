<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $url = 'payments.';
    protected $dir = 'backend.payments.';
    protected $name = 'Payments';

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
        $payments = Payment::with('company')->get();
        return view($this->dir.'index', compact('payments'));
    }

    public function create()
    {
        $model = new Payment();
        return view($this->dir.'create', compact('model'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_type' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'sort_code' => 'required|string|max:10',
            'iban_number' => 'nullable|string|max:34',
            'company_id' => 'required|exists:companies,id',
        ]);

        Payment::create($validated);

        return redirect()->route('payments.index')
            ->with('success', 'Payment method created successfully.');
    }

    public function show(Payment $payment)
    {
        return view('payments.show', compact('payment'));
    }

    public function edit($id)
    {
        $model = Payment::findOrFail($id);
        return view($this->dir.'edit', compact('model'));
    }

    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'payment_type' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'sort_code' => 'required|string|max:10',
            'iban_number' => 'nullable|string|max:34',
            'company_id' => 'required|exists:companies,id',
        ]);

        $payment->update($validated);

        return redirect()->route('payments.index')
            ->with('success', 'Payment method updated successfully.');
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();

        return redirect()->route('payments.index')
            ->with('success', 'Payment method deleted successfully.');
    }
}
