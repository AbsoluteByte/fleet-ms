<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\DailyFinancialSheet;
use App\Services\DailyFinancialSheetService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DailyFinancialSheetController extends Controller
{
    private const APPROVER_EMAIL = 'jawad@samoretraders.com';

    protected $url = 'daily-financial-sheet.';

    protected $dir = 'backend.daily-financial-sheet.';

    protected $name = 'Daily Financial Sheet';

    public function __construct()
    {
        $this->middleware('role:admin|manager|user');
        view()->share('url', $this->url);
        view()->share('dir', $this->dir);
        view()->share('singular', Str::singular($this->name));
        view()->share('plural', Str::plural($this->name));
    }

    public function index(DailyFinancialSheetService $service)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        $openDates = $service->openSheetDates($tenant->id);
        $approvedSheets = DailyFinancialSheet::query()
            ->with('approvedByUser')
            ->where('tenant_id', $tenant->id)
            ->where('status', DailyFinancialSheet::STATUS_APPROVED)
            ->orderByDesc('sheet_date')
            ->paginate(15);

        return view($this->dir.'index', compact('openDates', 'approvedSheets'));
    }

    public function show(Request $request, string $date, DailyFinancialSheetService $service)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        $viewData = $this->resolveSheetViewData($request, $date, $service, $tenant->id);
        $canApprove = $this->canApprove() && $viewData['hasPending'];

        return view($this->dir.'show', array_merge($viewData, [
            'canApprove' => $canApprove,
        ]));
    }

    public function pdf(Request $request, string $date, DailyFinancialSheetService $service)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        $viewData = $this->resolveSheetViewData($request, $date, $service, $tenant->id);
        if ($viewData['sheet']) {
            $viewData['sheet']->loadMissing('approvedByUser');
        }

        $pdf = \PDF::loadView($this->dir.'pdf', $viewData);
        $pdf->setPaper('A4', 'landscape');

        $filename = 'Daily_Financial_Sheet_'.$viewData['sheetDate'].'.pdf';

        return $pdf->download($filename);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveSheetViewData(
        Request $request,
        string $date,
        DailyFinancialSheetService $service,
        int $tenantId
    ): array {
        $sheetDate = Carbon::parse($date)->toDateString();
        $sheet = $service->sheetForDate($tenantId, $sheetDate);
        $allEntries = $service->entriesForDate($tenantId, $sheetDate);
        $filterOptions = $service->filterOptionsForEntries($allEntries);

        $activeFilters = $this->resolveSheetFilters($request, $tenantId);
        $isFiltered = $activeFilters['payment_method'] !== null || $activeFilters['bank_account_id'] !== null;

        $entries = $isFiltered
            ? $service->filterEntries(
                $allEntries,
                $activeFilters['payment_method'],
                $activeFilters['bank_account_id']
            )
            : $allEntries;

        $isApproved = $sheet?->isApproved() ?? false;
        $hasPending = $allEntries->where('posting_status', 'pending')->isNotEmpty();
        $pendingTotals = $hasPending
            ? $service->computeTotals($allEntries, pendingOnly: true)
            : null;

        $fullTotals = $isApproved && $sheet
            ? [
                'cash_in' => (float) $sheet->cash_in,
                'cash_out' => (float) $sheet->cash_out,
                'net_cash' => round((float) $sheet->cash_in - (float) $sheet->cash_out, 2),
                'bank_in' => $sheet->bank_in_json ?? [],
                'bank_out' => $sheet->bank_out_json ?? [],
            ]
            : $service->computeTotals($allEntries, pendingOnly: ! $isApproved);

        $totals = $isFiltered
            ? $service->computeTotals($entries, pendingOnly: false)
            : $fullTotals;

        $filterLabels = $this->sheetFilterLabels($activeFilters, $filterOptions);

        return compact(
            'sheetDate',
            'sheet',
            'entries',
            'allEntries',
            'totals',
            'fullTotals',
            'pendingTotals',
            'isApproved',
            'hasPending',
            'filterOptions',
            'activeFilters',
            'isFiltered',
            'filterLabels'
        );
    }

    /**
     * @return array{payment_method: ?string, bank_account_id: ?int}
     */
    private function resolveSheetFilters(Request $request, int $tenantId): array
    {
        $validated = $request->validate([
            'payment_method' => 'nullable|string|max:255',
            'bank_account_id' => [
                'nullable',
                'integer',
                Rule::exists('bank_accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
        ]);

        $paymentMethod = isset($validated['payment_method']) ? trim((string) $validated['payment_method']) : null;
        if ($paymentMethod === '') {
            $paymentMethod = null;
        }

        $bankAccountId = isset($validated['bank_account_id']) ? (int) $validated['bank_account_id'] : null;
        if ($bankAccountId === 0) {
            $bankAccountId = null;
        }

        return [
            'payment_method' => $paymentMethod,
            'bank_account_id' => $bankAccountId,
        ];
    }

    /**
     * @param  array{payment_method: ?string, bank_account_id: ?int}  $activeFilters
     * @param  array{payment_methods: list<string>, bank_accounts: list<array{id: int, label: string}>}  $filterOptions
     * @return array{payment_method: ?string, bank_account: ?string}
     */
    private function sheetFilterLabels(array $activeFilters, array $filterOptions): array
    {
        $bankLabel = null;
        if ($activeFilters['bank_account_id'] !== null) {
            foreach ($filterOptions['bank_accounts'] as $bankAccount) {
                if ((int) $bankAccount['id'] === (int) $activeFilters['bank_account_id']) {
                    $bankLabel = $bankAccount['label'];
                    break;
                }
            }
        }

        return [
            'payment_method' => $activeFilters['payment_method'],
            'bank_account' => $bankLabel,
        ];
    }

    public function approve(Request $request, string $date, DailyFinancialSheetService $service)
    {
        abort_unless($this->canApprove(), 403);

        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        $validated = $request->validate([
            'approval_notes' => 'nullable|string|max:5000',
            'entry_ids' => 'nullable|array',
            'entry_ids.*' => [
                'string',
                'regex:/^(payment|expense|other-payment|reservation-payment|deposit-refund|driver-credit)-\d+$/',
            ],
            'approve_mode' => ['nullable', Rule::in(['all', 'selected'])],
        ]);

        $sheetDate = Carbon::parse($date)->toDateString();
        $alreadyHadSheet = $service->isDateApproved($tenant->id, $sheetDate);

        $entryIds = null;
        if (($validated['approve_mode'] ?? 'all') === 'selected') {
            $entryIds = $validated['entry_ids'] ?? [];
            if ($entryIds === []) {
                return redirect()->route('daily-financial-sheet.show', $sheetDate)
                    ->withErrors(['entry_ids' => 'Select at least one pending entry to approve.'])
                    ->withInput();
            }
        }

        $service->approveSheet(
            $tenant->id,
            $sheetDate,
            (int) Auth::id(),
            $validated['approval_notes'] ?? null,
            $entryIds
        );

        $message = $alreadyHadSheet
            ? 'Selected entries approved and merged into the daily financial sheet.'
            : 'Daily financial sheet approved. Payments have been posted to invoices.';

        return redirect()->route('daily-financial-sheet.show', $sheetDate)
            ->with('success', $message);
    }

    public function reject(Request $request, string $date, DailyFinancialSheetService $service)
    {
        abort_unless($this->canApprove(), 403);

        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        $validated = $request->validate([
            'entry_ids' => 'nullable|array',
            'entry_ids.*' => [
                'string',
                'regex:/^(payment|expense|other-payment|reservation-payment|deposit-refund|driver-credit)-\d+$/',
            ],
            'reject_mode' => ['nullable', Rule::in(['all', 'selected'])],
        ]);

        $sheetDate = Carbon::parse($date)->toDateString();

        $entryIds = null;
        if (($validated['reject_mode'] ?? 'all') === 'selected') {
            $entryIds = $validated['entry_ids'] ?? [];
            if ($entryIds === []) {
                return redirect()->route('daily-financial-sheet.show', $sheetDate)
                    ->withErrors(['entry_ids' => 'Select at least one pending entry to reject.'])
                    ->withInput();
            }
        }

        $count = $service->rejectEntries(
            $tenant->id,
            $sheetDate,
            (int) Auth::id(),
            $entryIds
        );

        return redirect()->route('daily-financial-sheet.show', $sheetDate)
            ->with('success', $count === 1
                ? '1 pending entry rejected and removed from the sheet.'
                : "{$count} pending entries rejected and removed from the sheet.");
    }

    private function canApprove(): bool
    {
        return strtolower((string) Auth::user()?->email) === self::APPROVER_EMAIL;
    }
}
