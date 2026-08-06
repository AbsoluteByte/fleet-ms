<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarReservation;
use App\Models\OtherPayment;
use App\Support\BatchPaymentInput;
use App\Models\CarStatusHistory;
use App\Models\Tenant;
use App\Models\VehicleSwap;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CarStatusChangeService
{
    public const WRITTEN_OFF_DISPOSAL_OUTCOMES = [
        'disposed_by_insurer' => 'Disposed of by the insurer',
        'retained_for_parts' => 'Retained for parts',
    ];

    /** @var list<string> */
    public const TARGET_STATUSES = [
        Car::FLEET_STATUS_PREPARATION_FOR_PHVL,
        'available_for_rent',
        Car::FLEET_STATUS_NON_COMPLIANT,
        'reserved',
        'damaged',
        'written_off',
        'stolen',
        'for_sale',
        'sold',
    ];

    private const BLOCKED_FLEET_STATUSES = [
        Car::FLEET_STATUS_PREPARATION_FOR_PHVL,
        'damaged',
        'written_off',
        'stolen',
        'for_sale',
        'sold',
        'sorn',
    ];

    public function apply(Request $request, Tenant $tenant, Car $car, string $target): void
    {
        if (! in_array($target, self::TARGET_STATUSES, true)) {
            abort(422, 'Unsupported status.');
        }

        $previousStatus = $car->fleet_status;

        DB::transaction(function () use ($request, $tenant, $car, $target, $previousStatus): void {

            $reservationId = null;
            $vehicleSwapId = null;
            $statusData = [];

            switch ($target) {
                case Car::FLEET_STATUS_PREPARATION_FOR_PHVL:
                    $statusData = $this->applyPreparationForPhvl($car, $previousStatus);
                    break;

                case 'available_for_rent':
                    $statusData = $this->applyAvailableForRentWithCleanup($car, $previousStatus);
                    break;

                case Car::FLEET_STATUS_NON_COMPLIANT:
                    $statusData = $this->applyNonCompliant($car, $previousStatus);
                    break;

                case 'reserved':
                    [$reservationId, $statusData] = $this->applyReserved($request, $tenant, $car);
                    break;

                case 'damaged':
                    $statusData = $this->applyDamaged($request, $tenant, $car);
                    break;

                case 'written_off':
                    $statusData = $this->applyWrittenOff($request, $tenant, $car);
                    break;

                case 'stolen':
                    $statusData = $this->applyStolen($request, $tenant, $car);
                    break;

                case 'for_sale':
                    $statusData = $this->applyForSale($request, $car);
                    break;

                case 'sold':
                    $statusData = $this->applySoldCarUpdate($request, $car);
                    break;

                default:
                    abort(422, 'Unsupported status.');
            }

            $history = CarStatusHistory::create([
                'tenant_id' => $car->tenant_id,
                'car_id' => $car->id,
                'previous_status' => $previousStatus,
                'new_status' => $target,
                'reservation_id' => $reservationId,
                'vehicle_swap_id' => $vehicleSwapId,
                'status_data' => $statusData,
                'changed_by' => Auth::id(),
            ]);

            if ($target === 'sold') {
                $documents = $this->mergeSoldDocumentUploads($request, $history->id, []);
                $history->update([
                    'status_data' => array_merge($statusData, ['documents' => $documents]),
                ]);
            }

            if ($target === 'damaged' && ($statusData['fault_type'] ?? '') === 'non_fault') {
                $phvlStatus = $statusData['phvl_suspension_status'] ?? PhvlSuspensionService::STATUS_ACTIVE;
                $eventDate = ! empty($statusData['phvl_suspension_status_date'])
                    ? Carbon::parse($statusData['phvl_suspension_status_date'])
                    : null;

                app(PhvlSuspensionService::class)->applyStatus(
                    $car->fresh(),
                    $phvlStatus,
                    $eventDate,
                    $statusData['phvl_suspension_notes'] ?? null,
                    $history->id,
                    Auth::id()
                );
            }
        });
    }

    /**
     * Cancels active reservations and removes active swaps involving this car, then marks it available.
     *
     * @return array<string, mixed>
     */
    private function applyAvailableForRentWithCleanup(Car $car, ?string $previousStatus): array
    {
        $hadActiveReservation = CarReservation::query()
            ->where('car_id', $car->id)
            ->where('status', 'active')
            ->exists();

        $hadActiveSwap = VehicleSwap::carHasActiveSwap($car->id);

        $this->cancelActiveReservationsAndSwapsForCar($car);

        $this->applyAvailableForRent($car);

        return [
            'previous_snapshot' => ['fleet_status' => $previousStatus],
            'cancelled_active_reservation' => $hadActiveReservation,
            'removed_active_vehicle_swap' => $hadActiveSwap,
        ];
    }

    private function applyAvailableForRent(Car $car): void
    {
        $car->update([
            'fleet_status' => 'available_for_rent',
            'available_from_date' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function applyPreparationForPhvl(Car $car, ?string $previousStatus): array
    {
        $this->cancelActiveReservationsAndSwapsForCar($car);
        $car->loadMissing(['mots', 'roadTaxes', 'phvs']);

        $car->update([
            'fleet_status' => Car::FLEET_STATUS_PREPARATION_FOR_PHVL,
            'available_from_date' => null,
        ]);

        $reasons = $car->hasPhvRecord() ? $car->complianceFailureReasons() : ['phv_missing'];

        return [
            'source' => 'manual',
            'previous_snapshot' => ['fleet_status' => $previousStatus],
            'reasons' => $reasons,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function applyNonCompliant(Car $car, ?string $previousStatus): array
    {
        $this->cancelActiveReservationsAndSwapsForCar($car);
        $car->loadMissing(['mots', 'roadTaxes', 'phvs']);

        $car->update([
            'fleet_status' => Car::FLEET_STATUS_NON_COMPLIANT,
            'available_from_date' => null,
        ]);

        return [
            'source' => 'manual',
            'previous_snapshot' => ['fleet_status' => $previousStatus],
            'reasons' => $car->complianceFailureReasons(),
        ];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function applyReserved(Request $request, Tenant $tenant, Car $car): array
    {
        $validated = $request->validate(array_merge([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'reservation_date' => 'required|date',
            'pick_up_date' => 'required|date',
            'agreed_rent' => 'required|numeric|min:0',
            'agreed_advance' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
        ], BatchPaymentInput::optionalValidationRules($request, $tenant->id, 'reservation_payments')));

        $paymentRows = BatchPaymentInput::normalizeRows($validated, 'reservation_payments', allowEmpty: true);
        $amountPaid = round(array_sum(array_column($paymentRows, 'amount')), 2);
        if ($amountPaid !== round((float) $validated['amount_paid'], 2)) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Amount paid must match the total of deposit payment rows.',
            ]);
        }

        BatchPaymentInput::assertDepositWithinAgreedAdvance(
            $amountPaid,
            (float) ($validated['agreed_advance'] ?? 0)
        );

        $this->assertCarAssignableForReservation($car, null);

        $balance = $this->computeBalance(
            (float) $validated['agreed_rent'],
            (float) $validated['agreed_advance'],
            (float) $validated['amount_paid']
        );

        $reservation = CarReservation::create([
            'tenant_id' => $tenant->id,
            'car_id' => $car->id,
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'customer_email' => $validated['customer_email'],
            'reservation_date' => $validated['reservation_date'],
            'pick_up_date' => $validated['pick_up_date'],
            'available_from_date' => $validated['pick_up_date'],
            'agreed_rent' => $validated['agreed_rent'],
            'agreed_advance' => $validated['agreed_advance'],
            'amount_paid' => $validated['amount_paid'],
            'payment_method' => null,
            'bank_account_id' => null,
            'balance_payable_on_pickup' => $balance,
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

        $reservation->syncReservationPayments(array_map(fn (array $row) => [
            'payment_method' => $row['payment_method'],
            'bank_account_id' => $row['bank_account_id'],
            'amount' => $row['amount'],
        ], $paymentRows));

        $reservation->syncFinancialSheetStatus();

        $car->update([
            'fleet_status' => 'reserved',
            'available_from_date' => $validated['pick_up_date'],
        ]);

        $snapshot = array_merge($validated, [
            'balance_payable_on_pickup' => $balance,
            'reservation_id' => $reservation->id,
        ]);

        return [$reservation->id, $snapshot];
    }

    /**
     * @return array<string, mixed>
     */
    private function applyDamaged(Request $request, Tenant $tenant, Car $car): array
    {
        $tenantId = $tenant->id;

        $validated = $request->validate([
            'payload.damage_date' => 'required|date',
            'payload.driver_id' => [
                'nullable',
                Rule::exists('drivers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'payload.insurance_status' => ['required', Rule::in(['company', 'driver'])],
            'payload.insurance_excess_amount' => 'required|numeric|min:0',
            'payload.fault_type' => ['required', Rule::in(['fault', 'non_fault'])],
            'payload.incident_date' => 'required|date',
            'payload.excess_status' => [
                Rule::requiredIf(fn () => $request->input('payload.fault_type') === 'fault'),
                'nullable',
                'string',
                'max:255',
            ],
            'payload.fault_notes' => [
                Rule::requiredIf(fn () => $request->input('payload.fault_type') === 'fault'),
                'nullable',
                'string',
            ],
            'payload.insurance_claim_reference' => [
                Rule::requiredIf(fn () => $request->input('payload.fault_type') === 'non_fault'),
                'nullable',
                'string',
                'max:255',
            ],
            'payload.mechanical' => 'nullable|boolean',
            'payload.mechanical_notes' => [
                Rule::requiredIf(fn () => $request->boolean('payload.mechanical')),
                'nullable',
                'string',
            ],
            'payload.phvl_suspension_status' => [
                Rule::requiredIf(fn () => $request->input('payload.fault_type') === 'non_fault'),
                'nullable',
                Rule::in(PhvlSuspensionService::statuses()),
            ],
            'payload.phvl_suspension_status_date' => [
                Rule::requiredIf(fn () => $request->input('payload.fault_type') === 'non_fault'
                    && $request->input('payload.phvl_suspension_status', PhvlSuspensionService::STATUS_ACTIVE) !== PhvlSuspensionService::STATUS_ACTIVE),
                'nullable',
                'date',
            ],
            'payload.phvl_suspension_notes' => 'nullable|string|max:1000',
        ]);

        $payload = $validated['payload'];
        $payload['mechanical'] = $request->boolean('payload.mechanical');

        if ($payload['fault_type'] === 'non_fault') {
            $payload['phvl_suspension_status'] = $request->input(
                'payload.phvl_suspension_status',
                PhvlSuspensionService::STATUS_ACTIVE
            );
            if ($payload['phvl_suspension_status'] === PhvlSuspensionService::STATUS_ACTIVE) {
                unset($payload['phvl_suspension_status_date']);
            }
        } else {
            unset(
                $payload['phvl_suspension_status'],
                $payload['phvl_suspension_status_date'],
                $payload['phvl_suspension_notes']
            );
        }

        $this->cancelActiveReservationsAndSwapsForCar($car);

        $car->update([
            'fleet_status' => 'damaged',
            'available_from_date' => null,
        ]);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function applyWrittenOff(Request $request, Tenant $tenant, Car $car): array
    {
        $tenantId = $tenant->id;

        $validated = $request->validate([
            'payload.disposal_outcome' => [
                'required',
                Rule::in(array_keys(self::WRITTEN_OFF_DISPOSAL_OUTCOMES)),
            ],
            'payload.driver_id' => [
                'nullable',
                Rule::exists('drivers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'payload.insurance_status' => ['required', Rule::in(['company', 'driver'])],
            'payload.insurance_excess_amount' => 'required|numeric|min:0',
            'payload.fault_type' => ['required', Rule::in(['fault', 'non_fault'])],
            'payload.incident_date' => 'required|date',
            'payload.excess_status' => [
                Rule::requiredIf(fn () => $request->input('payload.fault_type') === 'fault'),
                'nullable',
                'string',
                'max:255',
            ],
            'payload.fault_notes' => [
                Rule::requiredIf(fn () => $request->input('payload.fault_type') === 'fault'),
                'nullable',
                'string',
            ],
            'payload.insurance_claim_reference' => [
                Rule::requiredIf(fn () => $request->input('payload.fault_type') === 'non_fault'),
                'nullable',
                'string',
                'max:255',
            ],
            'payload.written_notes' => 'nullable|string',
        ]);

        $this->cancelActiveReservationsAndSwapsForCar($car);

        $car->update([
            'fleet_status' => 'written_off',
            'available_from_date' => null,
        ]);

        return $validated['payload'];
    }

    /**
     * @return array<string, mixed>
     */
    private function applyStolen(Request $request, Tenant $tenant, Car $car): array
    {
        $tenantId = $tenant->id;

        $validated = $request->validate([
            'payload.driver_id' => [
                'nullable',
                Rule::exists('drivers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'payload.insurance_status' => ['required', Rule::in(['company', 'driver'])],
            'payload.insurance_excess_amount' => 'required|numeric|min:0',
            'payload.insurance_claim_reference' => 'required|string|max:255',
            'payload.notes' => 'nullable|string',
        ]);

        $this->cancelActiveReservationsAndSwapsForCar($car);

        $car->update([
            'fleet_status' => 'stolen',
            'available_from_date' => null,
        ]);

        return $validated['payload'];
    }

    /**
     * @return array<string, mixed>
     */
    private function applyForSale(Request $request, Car $car): array
    {
        $validated = $request->validate([
            'payload.preparation_date' => 'nullable|date',
            'payload.ready_date' => 'nullable|date',
            'payload.advertised_date' => 'nullable|date',
        ]);

        $this->cancelActiveReservationsAndSwapsForCar($car);

        $car->update([
            'fleet_status' => 'for_sale',
            'available_from_date' => null,
        ]);

        $payload = $validated['payload'] ?? [];
        foreach (['preparation_date', 'ready_date', 'advertised_date'] as $key) {
            if (($payload[$key] ?? '') === '') {
                $payload[$key] = null;
            }
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function validateSoldStatusPayload(Request $request, int $tenantId): array
    {
        return $this->buildSoldStatusData($request, $tenantId);
    }

    /**
     * @return array<string, mixed>
     */
    private function applySoldCarUpdate(Request $request, Car $car): array
    {
        $this->cancelActiveReservationsAndSwapsForCar($car);

        $payload = $this->buildSoldStatusData($request, $car->tenant_id, $car);

        $car->update([
            'fleet_status' => 'sold',
            'available_from_date' => null,
        ]);

        return array_merge($payload, ['documents' => []]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSoldStatusData(Request $request, int $tenantId, ?Car $car = null): array
    {
        $validated = $request->validate(array_merge([
            'payload.sell_date' => 'required|date',
            'payload.sell_price' => 'required|numeric|min:0.01',
            'payload.buyer_name' => 'required|string|max:255',
            'payload.buyer_contact' => 'required|string|max:255',
            'payload.buyer_address' => 'required|string',
            'payload.notes' => 'nullable|string',
        ], BatchPaymentInput::validationRules($request, $tenantId, 'sold_payments')));

        $paymentRows = BatchPaymentInput::normalizeRows($validated, 'sold_payments');
        $sellPrice = round((float) $validated['payload']['sell_price'], 2);
        $paymentTotal = round(array_sum(array_column($paymentRows, 'amount')), 2);

        if ($sellPrice !== $paymentTotal) {
            throw ValidationException::withMessages([
                'payload.sell_price' => 'Sell price must match the total of payment rows.',
            ]);
        }

        $payload = $validated['payload'];
        $payload['sell_price'] = $sellPrice;
        $payload['payments'] = $paymentRows;
        $payload = $this->appendLegacySoldPaymentFields($payload, $paymentRows);

        if ($car !== null) {
            $this->createSoldOtherPayments($car, $payload, $paymentRows);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<array{payment_method: string, bank_account_id: ?int, payment_date: ?string, amount: float, notes: ?string}>  $paymentRows
     * @return array<string, mixed>
     */
    private function appendLegacySoldPaymentFields(array $payload, array $paymentRows): array
    {
        if (count($paymentRows) === 1) {
            $payload['payment_terms'] = $this->legacyPaymentTermsFromMethod($paymentRows[0]['payment_method']);
            if (! empty($paymentRows[0]['bank_account_id'])) {
                $payload['bank_account_id'] = $paymentRows[0]['bank_account_id'];
            } else {
                unset($payload['bank_account_id']);
            }
        } else {
            unset($payload['payment_terms'], $payload['bank_account_id']);
        }

        return $payload;
    }

    private function legacyPaymentTermsFromMethod(string $paymentMethod): string
    {
        return in_array($paymentMethod, ['Bank Transfer', 'Card Payment'], true) ? 'bank' : 'cash';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<array{payment_method: string, bank_account_id: ?int, payment_date: ?string, amount: float, notes: ?string}>  $paymentRows
     */
    private function createSoldOtherPayments(Car $car, array $payload, array $paymentRows): void
    {
        if ($paymentRows === []) {
            return;
        }

        $registration = trim((string) ($car->registration ?? 'Vehicle'));
        $title = 'Sale — '.$registration;
        $buyerNotes = trim(implode("\n", array_filter([
            'Buyer: '.($payload['buyer_name'] ?? ''),
            'Contact: '.($payload['buyer_contact'] ?? ''),
            'Address: '.($payload['buyer_address'] ?? ''),
            $payload['notes'] ?? null,
        ])));

        foreach ($paymentRows as $paymentRow) {
            $rowNotes = trim(implode("\n\n", array_filter([
                $buyerNotes,
                $paymentRow['notes'] ?? null,
            ])));

            OtherPayment::query()->create([
                'tenant_id' => $car->tenant_id,
                'other_payment_type' => OtherPayment::TYPE_VEHICLE,
                'car_id' => $car->id,
                'title' => $title,
                'amount' => $paymentRow['amount'],
                'payment_method' => $paymentRow['payment_method'],
                'bank_account_id' => $paymentRow['bank_account_id'],
                'payment_date' => $paymentRow['payment_date'] ?? ($payload['sell_date'] ?? now()->toDateString()),
                'notes' => $rowNotes !== '' ? $rowNotes : null,
                'posting_status' => OtherPayment::POSTING_STATUS_PENDING,
                'created_by' => Auth::id(),
            ]);
        }
    }

    /**
     * @param  list<string>  $existingDocuments
     * @return list<string>
     */
    public function mergeSoldDocumentUploads(Request $request, int $historyId, array $existingDocuments = []): array
    {
        if ($request->hasFile('sold_documents')) {
            $request->validate([
                'sold_documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
            ]);
        }

        $newDocuments = $this->storeSoldDocuments($request, $historyId);

        return array_values(array_unique(array_merge($existingDocuments, $newDocuments)));
    }

    /**
     * @return list<string>
     */
    private function storeSoldDocuments(Request $request, int $historyId): array
    {
        if (! $request->hasFile('sold_documents')) {
            return [];
        }

        $relativeDir = 'uploads/cars/status_history/'.$historyId;
        $absoluteDir = public_path($relativeDir);

        if (! file_exists($absoluteDir)) {
            mkdir($absoluteDir, 0755, true);
        }

        $uploaded = [];
        $soldDocsList = $request->file('sold_documents');
        $soldDocsList = is_array($soldDocsList) ? $soldDocsList : [$soldDocsList];

        foreach ($soldDocsList as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $uploaded[] = $this->moveUploadedFile($file, $relativeDir);
            }
        }

        return $uploaded;
    }

    private function moveUploadedFile(UploadedFile $file, string $relativeDirectory): string
    {
        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'image/')) {
            $dims = @getimagesize($file->getRealPath());
            $width = $dims[0] ?? 0;
            $height = $dims[1] ?? 0;
            $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg';
            $name = time().'-'.uniqid().'-'.$width.'-'.$height.'.'.$extension;
        } else {
            $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'pdf';
            $name = time().'-'.uniqid().'.'.$extension;
        }

        $path = public_path($relativeDirectory);

        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }

        if ($file->move($path, $name)) {
            return $relativeDirectory.'/'.$name;
        }

        throw new \RuntimeException('Failed to upload file');
    }

    private function cancelActiveReservationsAndSwapsForCar(Car $car): void
    {
        CarReservation::query()
            ->where('car_id', $car->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        CarReservation::releaseCarFleetStatusIfUnused($car);

        VehicleSwap::query()
            ->active()
            ->where(function ($q) use ($car) {
                $q->where('old_car_id', $car->id)->orWhere('swapped_with_car_id', $car->id);
            })
            ->get()
            ->each(fn (VehicleSwap $s) => $s->delete());

        $car->refresh();
    }

    private function computeBalance(float $agreedRent, float $agreedAdvance, float $amountPaid): string
    {
        $balance = $agreedRent + $agreedAdvance - $amountPaid;

        return number_format(max(0, round($balance, 2)), 2, '.', '');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function sanitizeSwapReasonPayload(array $validated): array
    {
        if (($validated['reason_for_swap'] ?? '') !== VehicleSwap::REASON_PHVL_ISSUES) {
            $validated['phvl_issue_type'] = null;
            $validated['phvl_issue_notes'] = null;
        }

        if (($validated['reason_for_swap'] ?? '') !== VehicleSwap::REASON_OTHERS) {
            $validated['reason_notes'] = null;
        }

        return $validated;
    }

    private function assertCarAssignableForReservation(Car $car, ?int $reservationBeingEditedId): void
    {
        foreach (self::BLOCKED_FLEET_STATUSES as $blocked) {
            if ($car->fleet_status === $blocked) {
                throw ValidationException::withMessages([
                    'car_id' => __('This car cannot be reserved.'),
                ]);
            }
        }

        if ($car->fleet_status === 'vehicle_swap') {
            throw ValidationException::withMessages([
                'car_id' => __('This car is part of an active vehicle swap.'),
            ]);
        }

        $otherActive = CarReservation::query()
            ->where('car_id', $car->id)
            ->where('status', 'active')
            ->when($reservationBeingEditedId, fn ($q) => $q->where('id', '!=', $reservationBeingEditedId))
            ->exists();

        if ($otherActive) {
            throw ValidationException::withMessages([
                'car_id' => __('This car already has an active reservation.'),
            ]);
        }

        if ($car->fleet_status !== 'reserved') {
            return;
        }

        $keepingSameCar = $reservationBeingEditedId
            && CarReservation::query()->find($reservationBeingEditedId)?->car_id === $car->id;

        if (! $keepingSameCar) {
            throw ValidationException::withMessages([
                'car_id' => __('This car is already reserved.'),
            ]);
        }
    }

    private function assertCarUsableInSwap(Car $car, ?int $swapBeingEditedId, string $errorKey): void
    {
        foreach (self::BLOCKED_FLEET_STATUSES as $blocked) {
            if ($car->fleet_status === $blocked) {
                throw ValidationException::withMessages([
                    $errorKey => __('This car cannot be used in a swap.'),
                ]);
            }
        }

        if (CarReservation::query()->where('car_id', $car->id)->where('status', 'active')->exists()) {
            throw ValidationException::withMessages([
                $errorKey => __('This car has an active reservation.'),
            ]);
        }

        if (VehicleSwap::carHasActiveSwap($car->id, $swapBeingEditedId)) {
            throw ValidationException::withMessages([
                $errorKey => __('This car is already part of another vehicle swap.'),
            ]);
        }

        if ($car->fleet_status === 'reserved') {
            throw ValidationException::withMessages([
                $errorKey => __('This car is reserved and cannot be used in a swap.'),
            ]);
        }

        if ($car->fleet_status === 'vehicle_swap') {
            $sameSwap = $swapBeingEditedId
                && VehicleSwap::query()
                    ->whereKey($swapBeingEditedId)
                    ->where(function ($q) use ($car) {
                        $q->where('old_car_id', $car->id)->orWhere('swapped_with_car_id', $car->id);
                    })
                    ->exists();

            if (! $sameSwap) {
                throw ValidationException::withMessages([
                    $errorKey => __('This car is not available for a swap.'),
                ]);
            }
        }
    }
}
