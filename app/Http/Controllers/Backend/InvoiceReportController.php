<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class InvoiceReportController extends Controller
{
    private const STATUS_FILTERS = ['all', 'paid', 'pending', 'partial'];

    private const INVOICE_TYPE_FILTERS = ['all', 'agreement', 'agreement_deposit', 'agreement_additional_charge', 'manual', 'subscription'];

    public function __construct()
    {
        $this->middleware('role:admin|manager|user');
    }

    public function index(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'status' => ['nullable', Rule::in(self::STATUS_FILTERS)],
            'invoice_type' => ['nullable', Rule::in(self::INVOICE_TYPE_FILTERS)],
        ]);

        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($validated['to'] ?? now()->toDateString())->endOfDay();
        $statusFilter = $validated['status'] ?? 'all';
        $invoiceTypeFilter = $validated['invoice_type'] ?? 'all';

        $invoices = Invoice::query()
            ->whereHas('driver', fn ($query) => $query->where('tenant_id', $tenant->id))
            ->where('status', '!=', 'cancelled')
            ->whereDate('invoice_date', '>=', $from->toDateString())
            ->whereDate('invoice_date', '<=', $to->toDateString())
            ->with([
                'driver',
                'sourceAgreement.car',
                'paymentAllocations.payment',
            ])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get();

        $typeFilteredInvoices = $this->applyInvoiceTypeFilter($invoices, $invoiceTypeFilter);
        $summary = $this->buildSummary($typeFilteredInvoices);
        $filteredInvoices = $this->applyStatusFilter($typeFilteredInvoices, $statusFilter);

        $rows = $filteredInvoices->map(fn (Invoice $invoice) => $this->mapInvoiceRow($invoice));

        return view('backend.payments.invoices', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'statusFilter' => $statusFilter,
            'invoiceTypeFilter' => $invoiceTypeFilter,
            'summary' => $summary,
            'rows' => $rows,
        ]);
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return array{
     *     generated_count: int,
     *     generated_total: float,
     *     paid_count: int,
     *     paid_total: float,
     *     pending_count: int,
     *     pending_total: float,
     *     partial_count: int,
     *     partial_total: float,
     *     outstanding: float
     * }
     */
    private function buildSummary(Collection $invoices): array
    {
        $paid = $invoices->where('status', 'paid');
        $pending = $invoices->filter(fn (Invoice $invoice) => in_array($invoice->status, ['pending', 'overdue'], true));
        $partial = $invoices->where('status', 'partial');

        return [
            'generated_count' => $invoices->count(),
            'generated_total' => round((float) $invoices->sum('total_amount'), 2),
            'paid_count' => $paid->count(),
            'paid_total' => round((float) $paid->sum('total_amount'), 2),
            'pending_count' => $pending->count(),
            'pending_total' => round((float) $pending->sum('total_amount'), 2),
            'partial_count' => $partial->count(),
            'partial_total' => round((float) $partial->sum('total_amount'), 2),
            'outstanding' => round((float) $invoices
                ->filter(fn (Invoice $invoice) => in_array($invoice->status, ['pending', 'overdue', 'partial'], true))
                ->sum('balance_amount'), 2),
        ];
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return Collection<int, Invoice>
     */
    private function applyInvoiceTypeFilter(Collection $invoices, string $invoiceTypeFilter): Collection
    {
        if ($invoiceTypeFilter === 'all') {
            return $invoices->values();
        }

        return $invoices->where('invoice_type', $invoiceTypeFilter)->values();
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return Collection<int, Invoice>
     */
    private function applyStatusFilter(Collection $invoices, string $statusFilter): Collection
    {
        return match ($statusFilter) {
            'paid' => $invoices->where('status', 'paid')->values(),
            'pending' => $invoices->filter(fn (Invoice $invoice) => in_array($invoice->status, ['pending', 'overdue'], true))->values(),
            'partial' => $invoices->where('status', 'partial')->values(),
            default => $invoices->values(),
        };
    }

    /**
     * @return array{
     *     invoice_no: string,
     *     customer: string,
     *     vehicle: string,
     *     invoice_date: string,
     *     amount: string,
     *     status: string,
     *     payment_date: string,
     *     balance: string
     * }
     */
    private function mapInvoiceRow(Invoice $invoice): array
    {
        return [
            'invoice_no' => (string) ($invoice->invoice_no ?: '—'),
            'customer' => $invoice->driver?->selectOptionLabel() ?: '—',
            'vehicle' => $invoice->vehicleRegistrationLabel(),
            'invoice_date' => optional($invoice->invoice_date)->format('d M Y') ?: '—',
            'amount' => '£'.number_format((float) $invoice->total_amount, 2),
            'status' => ucfirst((string) $invoice->status),
            'payment_date' => $this->latestPostedPaymentDate($invoice)?->format('d M Y') ?: '—',
            'balance' => '£'.number_format((float) $invoice->balance_amount, 2),
        ];
    }

    private function latestPostedPaymentDate(Invoice $invoice): ?Carbon
    {
        $dates = $invoice->paymentAllocations
            ->filter(function ($allocation) {
                $payment = $allocation->payment;

                return $payment
                    && $payment->posting_status === Payment::POSTING_STATUS_POSTED
                    && $payment->payment_date;
            })
            ->map(fn ($allocation) => $allocation->payment->payment_date);

        if ($dates->isEmpty()) {
            return null;
        }

        return $dates->sortByDesc(fn (Carbon $date) => $date->timestamp)->first();
    }
}
