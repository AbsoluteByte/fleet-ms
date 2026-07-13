<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\DailyFinancialSheet;
use App\Services\DailyFinancialSheetService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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

    public function show(string $date, DailyFinancialSheetService $service)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        $sheetDate = Carbon::parse($date)->toDateString();
        $sheet = $service->sheetForDate($tenant->id, $sheetDate);
        $entries = $service->entriesForDate($tenant->id, $sheetDate);
        $isApproved = $sheet?->isApproved() ?? false;
        $totals = $isApproved && $sheet
            ? [
                'cash_in' => (float) $sheet->cash_in,
                'cash_out' => (float) $sheet->cash_out,
                'net_cash' => round((float) $sheet->cash_in - (float) $sheet->cash_out, 2),
                'bank_in' => $sheet->bank_in_json ?? [],
                'bank_out' => $sheet->bank_out_json ?? [],
            ]
            : $service->computeTotals($entries, pendingOnly: true);
        $canApprove = $this->canApprove() && ! $isApproved && $entries->where('posting_status', 'pending')->isNotEmpty();

        return view($this->dir.'show', compact('sheetDate', 'sheet', 'entries', 'totals', 'isApproved', 'canApprove'));
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
        ]);

        $sheetDate = Carbon::parse($date)->toDateString();

        $service->approveSheet(
            $tenant->id,
            $sheetDate,
            (int) Auth::id(),
            $validated['approval_notes'] ?? null
        );

        return redirect()->route('daily-financial-sheet.show', $sheetDate)
            ->with('success', 'Daily financial sheet approved. Payments have been posted to invoices.');
    }

    private function canApprove(): bool
    {
        return strtolower((string) Auth::user()?->email) === self::APPROVER_EMAIL;
    }
}
