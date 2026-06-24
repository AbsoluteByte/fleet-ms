<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarPhv;
use App\Models\CarPhvlProgress;
use App\Models\Counsel;
use App\Services\PhvlArchiveService;
use App\Services\PhvlProgressEventLogger;
use App\Support\PhvlMotHelper;
use App\Support\PhvlWorkflow;
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

        $appointmentFrom = $request->query('appointment_from');
        $appointmentTo = $request->query('appointment_to');
        if ($appointmentFrom || $appointmentTo) {
            $request->validate([
                'appointment_from' => 'nullable|date',
                'appointment_to' => 'nullable|date',
            ]);
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

            if ($appointmentFrom || $appointmentTo) {
                $appt = $car->phvlProgress?->appointment_at;
                if (! $appt) {
                    continue;
                }
                if ($appointmentFrom && $appt->lt(Carbon::parse($appointmentFrom)->startOfDay())) {
                    continue;
                }
                if ($appointmentTo && $appt->gt(Carbon::parse($appointmentTo)->endOfDay())) {
                    continue;
                }
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
            'appointment_confirmation' => 'sometimes|in:pending,additional_documents,approved',
            'appointment_at' => 'nullable|date',
            'appointment_notes' => 'nullable|string|max:65535',
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

        $payload = [];
        foreach ($validated as $key => $value) {
            if ($key === 'applied_date' && $value === '') {
                $payload[$key] = null;

                continue;
            }
            if ($key === 'appointment_at' && $value === '') {
                $payload[$key] = null;

                continue;
            }
            $payload[$key] = $value;
        }

        if (array_key_exists('applied_date', $payload) && $payload['applied_date'] !== null) {
            $payload['applied_date'] = $payload['applied_date'];
        }

        PhvlWorkflow::validateTransition($progress->exists ? $progress : null, $payload);

        $original = $progress->exists ? $progress->getOriginal() : [];

        foreach ($payload as $key => $value) {
            $progress->{$key} = $value;
        }

        if (isset($payload['phvl_result_status']) && $payload['phvl_result_status'] !== 'fail') {
            $progress->fail_notes = null;
        }

        if (isset($payload['appointment_confirmation']) && $payload['appointment_confirmation'] !== 'additional_documents') {
            $progress->appointment_notes = null;
        }

        $progress->tenant_id = $tenant->id;
        $progress->updated_by = Auth::id();
        $progress->save();

        PhvlProgressEventLogger::logChanges($progress, $payload, $original);

        return response()->json(['ok' => true]);
    }

    public function addMot(Request $request, Car $car)
    {
        $this->authorizeTenantCar($car);

        $tenant = Auth::user()->currentTenant();

        $validated = $request->validate([
            'test_date' => 'required|date',
            'expiry_date' => 'required|date',
            'amount' => 'nullable|numeric|min:0',
            'term' => 'nullable|string',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $motData = [
            'tenant_id' => $tenant->id,
            'test_date' => $validated['test_date'],
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
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
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

        $newPhv = null;

        DB::transaction(function () use ($car, $phvData, $request, &$newPhv) {
            $newPhv = $car->phvs()->create($phvData);
            $car->refresh();
            $newFuturePhvAdded = $this->hasFuturePhvExpiry($phvData);
            $this->syncCarPhvStatusAfterPhvl($car, $request, $newFuturePhvAdded);
        });

        $car->load(['phvlProgress', 'phvs.counsel']);
        PhvlArchiveService::tryArchiveAfterNewPhv($car, $newPhv);

        return response()->json(['ok' => true]);
    }

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
        $appliedDateVal = $p?->applied_date?->format('Y-m-d') ?? '';

        return [
            'car_id' => $cid,
            'make_model' => e($car->carModel?->name ?? '—'),
            'registration' => e($car->registration),
            'company' => e($car->company?->name ?? '—'),
            'council' => e($latestPhv?->counsel?->name ?? '—'),
            'expiry_detail' => e($expiryDetail),
            'expiry_sort' => $expirySort,
            'mot_status' => $this->statusBtnHtml(PhvlWorkflow::FIELD_MOT_STATUS, $cid, ['pending' => 'Pending', 'done' => 'Done'], $p?->mot_status ?? 'pending', $p, 'mot_status'),
            'mot_date' => e($motDateStr),
            'mot_days_old' => $motDaysHtml,
            'application_status' => $this->statusBtnHtml(PhvlWorkflow::FIELD_APPLICATION_STATUS, $cid, ['pending' => 'Pending', 'applied' => 'Applied'], $p?->application_status ?? 'pending', $p),
            'applied_date' => $this->dateBtnHtml(PhvlWorkflow::FIELD_APPLIED_DATE, $cid, $appliedDateVal, 'date', $p),
            'appointment_confirmation' => $this->appointmentConfirmationHtml($cid, $p),
            'appointment_at' => $this->dateBtnHtml(PhvlWorkflow::FIELD_APPOINTMENT_AT, $cid, $p?->appointment_at ? $p->appointment_at->format('Y-m-d\TH:i') : '', 'datetime-local', $p),
            'phvl_actions' => $this->phvlActionsHtml($car, $p),
        ];
    }

    private function statusBtnHtml(string $field, int $carId, array $options, string $current, ?CarPhvlProgress $progress, ?string $extraType = null): string
    {
        $label = $options[$current] ?? ucfirst(str_replace('_', ' ', $current));
        $cls = PhvlWorkflow::statusBtnClass($field, $current);
        $unlocked = PhvlWorkflow::stepUnlocked($progress, $field);
        $optionsJson = htmlspecialchars(json_encode($options), ENT_QUOTES, 'UTF-8');
        $extra = $extraType === 'mot_status' ? ' data-has-mot-form="1"' : '';
        $disabled = $unlocked ? '' : ' disabled';

        return '<button type="button" class="btn btn-sm '.$cls.' phvl-status-btn" '
            .'data-field="'.e($field).'" '
            .'data-car-id="'.$carId.'" '
            .'data-current="'.e($current).'" '
            .'data-options="'.$optionsJson.'"'
            .$extra
            .$disabled
            .'>'.e($label).'</button>';
    }

    private function dateBtnHtml(string $field, int $carId, string $value, string $inputType, ?CarPhvlProgress $progress): string
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

        $cls = PhvlWorkflow::statusBtnClass($field, '', $value);
        $unlocked = PhvlWorkflow::stepUnlocked($progress, $field);
        $disabled = $unlocked ? '' : ' disabled';

        return '<button type="button" class="btn btn-sm '.$cls.' phvl-status-btn" '
            .'data-field="'.e($field).'" '
            .'data-car-id="'.$carId.'" '
            .'data-current="'.e($value).'" '
            .'data-input-type="'.e($inputType).'" '
            .'data-options=""'
            .$disabled
            .'>'.e($display).'</button>';
    }

    private function appointmentConfirmationHtml(int $carId, ?CarPhvlProgress $p): string
    {
        $options = [
            'pending' => 'Pending',
            'additional_documents' => 'Additional Documents required',
            'approved' => 'Approved',
        ];
        $current = $p?->appointment_confirmation ?? 'pending';
        if ($current === 'confirmed') {
            $current = 'approved';
        }

        $btn = $this->statusBtnHtml(PhvlWorkflow::FIELD_APPOINTMENT_CONFIRMATION, $carId, $options, $current, $p);

        $notesBtn = '';
        if ($current === 'additional_documents') {
            $notes = $p?->appointment_notes ?? '';
            $notesAttr = htmlspecialchars($notes, ENT_QUOTES, 'UTF-8');
            $notesBtn = ' <button type="button" class="btn btn-sm btn-outline-primary phvl-appointment-notes-btn ml-50" data-car-id="'.$carId.'" data-notes="'.$notesAttr.'">'.($notes !== '' ? 'View notes' : 'Add notes').'</button>';
        }

        return '<div class="d-flex flex-nowrap align-items-center gap-1">'.$btn.$notesBtn.'</div>';
    }

    private function phvlActionsHtml(Car $car, ?CarPhvlProgress $p): string
    {
        $cid = $car->id;
        $status = $p?->phvl_result_status ?? '';
        $notes = $p?->fail_notes ?? '';
        $unlocked = PhvlWorkflow::stepUnlocked($p, PhvlWorkflow::FIELD_PHVL_RESULT);

        $options = ['' => '—', 'pass' => 'Pass', 'fail' => 'Fail'];
        $label = $options[$status] ?? '—';
        $cls = match ($status) {
            'pass' => 'btn-outline-success',
            'fail' => 'btn-outline-danger',
            default => 'btn-outline-secondary',
        };

        $disabled = $unlocked ? '' : ' disabled';

        $btn = '<button type="button" class="btn btn-sm '.$cls.' phvl-result-btn" '
            .'data-car-id="'.$cid.'" '
            .'data-current="'.e($status).'" '
            .'data-notes="'.htmlspecialchars($notes, ENT_QUOTES, 'UTF-8').'"'
            .$disabled
            .'>'.e($label).'</button>';

        $addPhv = '';
        if ($status === 'pass' && ! PhvlWorkflow::hasNewPhvForCurrentCycle($p, $car)) {
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
