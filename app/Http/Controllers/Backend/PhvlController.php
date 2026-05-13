<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarPhv;
use App\Models\CarPhvlProgress;
use App\Models\Counsel;
use App\Support\PhvlMotHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PhvlController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin|manager|user');
    }

    public function index()
    {
        $tenant = Auth::user()->currentTenant();
        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $counsels = Counsel::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('backend.phvl.index', compact('counsels'));
    }

    public function data(Request $request)
    {
        $tenant = Auth::user()->currentTenant();
        if (! $tenant) {
            return response()->json(['data' => []], 403);
        }

        $type = $request->query('type', 'all');
        if (! in_array($type, ['all', 'need_to_apply', 'renewal'], true)) {
            $type = 'all';
        }

        $cars = Car::query()
            ->forCurrentTenant()
            ->with(['company', 'carModel', 'mots', 'phvs.counsel', 'phvlProgress'])
            ->orderBy('registration')
            ->get();

        $rows = [];
        foreach ($cars as $car) {
            $renewal = $this->renewalEligible($car);
            $need = $this->needApplyEligible($car);

            if ($type === 'need_to_apply' && (! $need || $renewal)) {
                continue;
            }
            if ($type === 'renewal' && ! $renewal) {
                continue;
            }
            if ($type === 'all' && ! $renewal && ! $need) {
                continue;
            }

            $rows[] = $this->formatRow($car);
        }

        return response()->json(['data' => $rows]);
    }

    public function updateProgress(Request $request, Car $car)
    {
        $this->authorizeTenantCar($car);

        $tenant = Auth::user()->currentTenant();

        $validated = $request->validate([
            'mot_status' => 'sometimes|in:pending,done',
            'application_status' => 'sometimes|in:pending,applied',
            'applied_date' => 'nullable|date',
            'appointment_confirmation' => 'sometimes|in:pending,confirmed',
            'appointment_at' => 'nullable|date',
            'phvl_result_status' => 'nullable|in:pass,fail',
            'fail_notes' => 'nullable|string|max:65535',
        ]);

        $progress = CarPhvlProgress::firstOrNew(
            ['car_id' => $car->id],
            [
                'tenant_id' => $tenant->id,
                'car_id' => $car->id,
                'mot_status' => 'pending',
                'application_status' => 'pending',
                'appointment_confirmation' => 'pending',
            ]
        );

        foreach ($validated as $key => $value) {
            if ($key === 'applied_date' && $value === '') {
                $progress->applied_date = null;

                continue;
            }
            if ($key === 'appointment_at' && $value === '') {
                $progress->appointment_at = null;

                continue;
            }
            $progress->{$key} = $value;
        }

        $progress->tenant_id = $tenant->id;
        $progress->updated_by = Auth::id();
        $progress->save();

        return response()->json(['ok' => true]);
    }

    public function addMot(Request $request, Car $car)
    {
        $this->authorizeTenantCar($car);

        $tenant = Auth::user()->currentTenant();

        $validated = $request->validate([
            'expiry_date' => 'required|date',
            'amount' => 'nullable|numeric|min:0',
            'term' => 'nullable|string',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $motData = [
            'tenant_id' => $tenant->id,
            'expiry_date' => $validated['expiry_date'],
            'amount' => $validated['amount'] ?? null,
            'term' => $validated['term'] ?? null,
        ];

        if ($request->hasFile('document')) {
            $motData['document'] = $this->uploadFile(
                $request->file('document'),
                'uploads/cars/mot_documents'
            );
        }

        $car->mots()->create($motData);

        return response()->json(['ok' => true]);
    }

    public function completePass(Request $request, Car $car)
    {
        $this->authorizeTenantCar($car);

        $tenant = Auth::user()->currentTenant();

        $validated = $request->validate([
            'counsel_id' => 'required|exists:counsels,id',
            'amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'expiry_date' => 'required|date',
            'notify_before_expiry' => 'required|integer|min:1',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $counselTenant = Counsel::query()->whereKey($validated['counsel_id'])->value('tenant_id');
        if ((int) $counselTenant !== (int) $tenant->id) {
            abort(403);
        }

        $phvData = [
            'tenant_id' => $tenant->id,
            'counsel_id' => $validated['counsel_id'],
            'amount' => $validated['amount'],
            'start_date' => $validated['start_date'],
            'expiry_date' => $validated['expiry_date'],
            'notify_before_expiry' => $validated['notify_before_expiry'],
            'phv_applied' => false,
            'phv_applied_date' => null,
        ];

        if ($request->hasFile('document')) {
            $phvData['document'] = $this->uploadFile(
                $request->file('document'),
                'uploads/cars/phv_documents'
            );
        }

        $phvData = $this->mergePhvAppliedDataForCreate($phvData);

        DB::transaction(function () use ($car, $phvData, $request) {
            $car->phvs()->create($phvData);
            $car->refresh();
            $newFuturePhvAdded = $this->hasFuturePhvExpiry($phvData);
            $this->syncCarPhvStatusAfterPhvl($car, $request, $newFuturePhvAdded);
        });

        return response()->json(['ok' => true]);
    }

    // ==================== Private helpers ====================

    private function authorizeTenantCar(Car $car): void
    {
        $tenant = Auth::user()->currentTenant();
        abort_unless($tenant && (int) $car->tenant_id === (int) $tenant->id, 403);
    }

    private function needApplyEligible(Car $car): bool
    {
        return $car->phvs->isEmpty() || $car->phv_status === 'need_to_apply';
    }

    private function renewalEligible(Car $car): bool
    {
        if ($car->phvs->isEmpty()) {
            return false;
        }

        $latest = $car->phvs
            ->sortByDesc(fn (CarPhv $p) => [optional($p->expiry_date)->timestamp ?? 0, $p->id])
            ->first();

        if (! $latest?->expiry_date) {
            return false;
        }

        $today = now()->startOfDay();
        $exp = $latest->expiry_date->copy()->startOfDay();

        if ($exp->lt($today)) {
            return true;
        }

        $n = $latest->notify_before_expiry;
        if ($n === null) {
            return false;
        }

        $daysUntil = (int) $today->diffInDays($exp);

        return $daysUntil <= (int) $n;
    }

    private function formatRow(Car $car): array
    {
        $p = $car->phvlProgress;
        $latestPhv = $car->phvs
            ->sortByDesc(fn (CarPhv $phv) => [optional($phv->expiry_date)->timestamp ?? 0, $phv->id])
            ->first();
        $latestMot = PhvlMotHelper::latestMot($car);
        $motDate = PhvlMotHelper::estimatedMotDate($latestMot);
        $motDaysOld = PhvlMotHelper::motDaysOld($latestMot);
        $motStale = PhvlMotHelper::motDaysOldWithStale($latestMot);

        $expiryDetail = '—';
        $expirySort = 999999;
        if ($latestPhv?->expiry_date) {
            $today = now()->startOfDay();
            $exp = $latestPhv->expiry_date->copy()->startOfDay();
            if ($exp->lt($today)) {
                $daysAgo = (int) $exp->diffInDays($today);
                $expiryDetail = 'Expired '.$daysAgo.' days ago';
                $expirySort = -$daysAgo;
            } else {
                $daysUntil = (int) $today->diffInDays($exp);
                $expiryDetail = 'Expires in '.$daysUntil.' days';
                $expirySort = $daysUntil;
            }
        }

        $motDaysDisplay = $motDaysOld === null ? '—' : (string) $motDaysOld;
        $motDaysHtml = $motStale
            ? '<span class="insurance-status"><span class="insurance-status-dot insurance-status-dot--inactive" aria-hidden="true"></span><span>'.e($motDaysDisplay).'</span></span>'
            : e($motDaysDisplay);

        $motDateStr = $motDate ? $motDate->format('d M, Y') : '—';

        $cid = $car->id;

        return [
            'car_id' => $cid,
            'make_model' => e($car->carModel?->name ?? '—'),
            'registration' => e($car->registration),
            'company' => e($car->company?->name ?? '—'),
            'council' => e($latestPhv?->counsel?->name ?? '—'),
            'expiry_detail' => e($expiryDetail),
            'expiry_sort' => $expirySort,
            'mot_days_old' => $motDaysHtml,
            'mot_status' => $this->statusBtnHtml('mot_status', $cid, ['pending' => 'Pending', 'done' => 'Done'], $p?->mot_status ?? 'pending', 'mot_status'),
            'mot_date' => e($motDateStr),
            'application_status' => $this->statusBtnHtml('application_status', $cid, ['pending' => 'Pending', 'applied' => 'Applied'], $p?->application_status ?? 'pending'),
            'applied_date' => $this->dateBtnHtml('applied_date', $cid, $p?->applied_date?->format('Y-m-d') ?? '', 'date'),
            'appointment_confirmation' => $this->statusBtnHtml('appointment_confirmation', $cid, ['pending' => 'Pending', 'confirmed' => 'Confirmed'], $p?->appointment_confirmation ?? 'pending'),
            'appointment_at' => $this->dateBtnHtml('appointment_at', $cid, $p?->appointment_at ? $p->appointment_at->format('Y-m-d\TH:i') : '', 'datetime-local'),
            'phvl_actions' => $this->phvlActionsHtml($car, $p),
        ];
    }

    private function statusBtnHtml(string $field, int $carId, array $options, string $current, ?string $extraType = null): string
    {
        $label = $options[$current] ?? ucfirst($current);
        $cls = $current === 'done' || $current === 'applied' || $current === 'confirmed'
            ? 'btn-outline-success'
            : 'btn-outline-secondary';
        $optionsJson = htmlspecialchars(json_encode($options), ENT_QUOTES, 'UTF-8');
        $extra = $extraType === 'mot_status' ? ' data-has-mot-form="1"' : '';

        return '<button type="button" class="btn btn-sm '.$cls.' phvl-status-btn" '
            .'data-field="'.e($field).'" '
            .'data-car-id="'.$carId.'" '
            .'data-current="'.e($current).'" '
            .'data-options="'.$optionsJson.'"'
            .$extra
            .'>'.e($label).'</button>';
    }

    private function dateBtnHtml(string $field, int $carId, string $value, string $inputType): string
    {
        if ($value !== '') {
            if ($inputType === 'datetime-local' && strlen($value) >= 16) {
                $display = Carbon::parse($value)->format('d M, Y H:i');
            } else {
                $display = Carbon::parse($value)->format('d M, Y');
            }
        } else {
            $display = '—';
        }

        return '<button type="button" class="btn btn-sm btn-outline-secondary phvl-status-btn" '
            .'data-field="'.e($field).'" '
            .'data-car-id="'.$carId.'" '
            .'data-current="'.e($value).'" '
            .'data-input-type="'.e($inputType).'" '
            .'data-options=""'
            .'>'.e($display).'</button>';
    }

    private function phvlActionsHtml(Car $car, ?CarPhvlProgress $p): string
    {
        $cid = $car->id;
        $status = $p?->phvl_result_status ?? '';
        $notes = $p?->fail_notes ?? '';

        $options = ['' => '—', 'pass' => 'Pass', 'fail' => 'Fail'];
        $label = $options[$status] ?? '—';
        $cls = match ($status) {
            'pass' => 'btn-outline-success',
            'fail' => 'btn-outline-danger',
            default => 'btn-outline-secondary',
        };

        $btn = '<button type="button" class="btn btn-sm '.$cls.' phvl-result-btn" '
            .'data-car-id="'.$cid.'" '
            .'data-current="'.e($status).'" '
            .'data-notes="'.htmlspecialchars($notes, ENT_QUOTES, 'UTF-8').'"'
            .'>'.e($label).'</button>';

        $addPhv = '';
        if ($status === 'pass') {
            $addPhv = ' <button type="button" class="btn btn-sm btn-success phvl-add-phv-btn ml-50" data-car-id="'.$cid.'">Add PHV</button>';
        }

        $failBtns = '';
        if ($status === 'fail') {
            $notesAttr = htmlspecialchars($notes, ENT_QUOTES, 'UTF-8');
            $failBtns = ' <button type="button" class="btn btn-sm btn-outline-primary phvl-fail-notes-btn ml-50" data-car-id="'.$cid.'" data-notes="'.$notesAttr.'">'.($notes !== '' ? 'View notes' : 'Add notes').'</button>';
        }

        return '<div class="d-flex flex-nowrap align-items-center gap-1 phvl-actions-cell">'.$btn.$addPhv.$failBtns.'</div>';
    }

    private function mergePhvAppliedDataForCreate(array $phvData): array
    {
        $isApplied = (bool) ($phvData['phv_applied'] ?? false);
        $phvData['phv_applied'] = $isApplied;
        $phvData['phv_applied_date'] = $isApplied ? ($phvData['phv_applied_date'] ?? null) : null;
        $phvData['phv_applied_by'] = $isApplied ? Auth::id() : null;

        return $phvData;
    }

    private function hasFuturePhvExpiry(array $phvData): bool
    {
        if (empty($phvData['expiry_date'])) {
            return false;
        }

        return Carbon::parse($phvData['expiry_date'])->startOfDay()->gte(now()->startOfDay());
    }

    private function syncCarPhvStatusAfterPhvl(Car $car, Request $request, bool $newFuturePhvAdded): void
    {
        if ($newFuturePhvAdded) {
            $car->update([
                'phv_status' => 'phv_active',
                'phv_applied_date' => null,
                'phv_applied_by' => null,
                'updatedBy' => Auth::id(),
            ]);

            return;
        }

        $status = $request->input('phv_status', $car->phv_status ?: 'need_to_apply');
        $appliedDate = $status === 'applied' ? ($request->input('phv_applied_date') ?: null) : null;

        $car->update([
            'phv_status' => $status,
            'phv_applied_date' => $appliedDate,
            'phv_applied_by' => $status === 'applied'
                ? ($car->phv_status === 'applied' && $car->phv_applied_by ? $car->phv_applied_by : Auth::id())
                : null,
            'updatedBy' => Auth::id(),
        ]);
    }

    private function uploadFile($file, $directory)
    {
        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'image/')) {
            $dims = getimagesize($file);
            $width = $dims[0];
            $height = $dims[1];
            $name = time().'-'.uniqid().'-'.$width.'-'.$height.'.'.$file->extension();
        } else {
            $name = time().'-'.uniqid().'.'.$file->extension();
        }

        $path = public_path($directory);

        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }

        if ($file->move($path, $name)) {
            return $name;
        }

        throw new \Exception('Failed to upload file');
    }
}
