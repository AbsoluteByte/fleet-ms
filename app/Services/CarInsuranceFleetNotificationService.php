<?php

namespace App\Services;

use App\Mail\CarInsuranceFleetChangeMail;
use App\Models\Car;
use App\Models\Company;
use App\Models\InsuranceProvider;
use Illuminate\Support\Facades\Mail;

class CarInsuranceFleetNotificationService
{
    public const INTERNAL_RECIPIENT_EMAIL = 'jawad@samoretraders.com';

    public function notifyApplied(Car $car, InsuranceProvider $provider): void
    {
        $this->send($car, $provider, 'add');
    }

    public function notifyCancelled(Car $car, InsuranceProvider $provider): void
    {
        $this->send($car, $provider, 'remove');
    }

    private function send(Car $car, InsuranceProvider $provider, string $action): void
    {
        $car->loadMissing(['company', 'carModel']);
        $company = $car->company;

        if (! $company) {
            return;
        }

        $companyKey = $this->resolveCompanyKey($company);
        $policyNumber = trim((string) $provider->policy_number);
        $registration = trim((string) $car->registration);
        $carModel = trim((string) ($car->carModel?->name ?? ''));
        $vehicleLine = $carModel !== '' ? "{$carModel}-{$registration}" : $registration;

        $subject = $action === 'add'
            ? "Request to Add Vehicles to Fleet Insurance Policy {$policyNumber} {$registration}"
            : "Request to Remove Vehicles from Fleet Insurance Policy {$policyNumber} {$registration}";

        $body = $this->buildBody($action, $companyKey, $company, $policyNumber, $carModel, $registration, $vehicleLine);

        $recipients = $this->recipients($provider);
        if ($recipients === []) {
            return;
        }

        Mail::to($recipients)->send(new CarInsuranceFleetChangeMail(
            $car,
            $provider,
            $action,
            $subject,
            $body,
        ));
    }

    /**
     * @return list<string>
     */
    private function recipients(InsuranceProvider $provider): array
    {
        $emails = [];

        $providerEmail = trim((string) ($provider->email ?? ''));
        if ($providerEmail !== '' && filter_var($providerEmail, FILTER_VALIDATE_EMAIL)) {
            $emails[] = strtolower($providerEmail);
        }

        $emails[] = strtolower(self::INTERNAL_RECIPIENT_EMAIL);

        return array_values(array_unique($emails));
    }

    private function buildBody(
        string $action,
        ?string $companyKey,
        Company $company,
        string $policyNumber,
        string $carModel,
        string $registration,
        string $vehicleLine,
    ): string {
        if ($action === 'add') {
            return match ($companyKey) {
                'samore' => $this->samoreAddBody($policyNumber, $vehicleLine),
                'proactive' => $this->proactiveAddBody($policyNumber, $carModel, $registration),
                default => $this->genericAddBody($company->name, $policyNumber, $vehicleLine),
            };
        }

        return match ($companyKey) {
            'samore' => $this->samoreRemoveBody($policyNumber, $vehicleLine),
            'proactive' => $this->proactiveRemoveBody($policyNumber, $carModel, $registration),
            default => $this->genericRemoveBody($company->name, $policyNumber, $vehicleLine),
        };
    }

    private function samoreAddBody(string $policyNumber, string $vehicleLine): string
    {
        return implode("\n\n", [
            'Dear Brother,',
            'Assalamu Alaikum.',
            'I hope you are doing well.',
            "Kindly arrange to add the following vehicles to the fleet insurance policy of Samore Traders Ltd under Policy Number: {$policyNumber}.",
            'We would be grateful if you could issue the insurance certificate.',
            'Vehicle Details:',
            $vehicleLine,
            'Your prompt assistance in this matter would be greatly appreciated.',
            'Kind regards,',
            'Jawad Samore',
            'Samore Traders Ltd',
        ]);
    }

    private function samoreRemoveBody(string $policyNumber, string $vehicleLine): string
    {
        return implode("\n\n", [
            'Dear Brother,',
            'Assalamu Alaikum.',
            "Kindly arrange to remove the following vehicle from the fleet insurance policy of Samore Traders Ltd, Policy Number {$policyNumber}:",
            $vehicleLine,
            'We would appreciate it if you could process this request at your earliest convenience and confirm once the vehicles have been removed from the policy.',
            'Thank you for your assistance.',
            'Kind regards,',
            'Samore Traders Ltd',
        ]);
    }

    private function proactiveAddBody(string $policyNumber, string $carModel, string $registration): string
    {
        $vehicleLine = trim("{$carModel} {$registration}");

        return implode("\n\n", [
            'Dear Brother,',
            'Assalamu Alaikum.',
            'I hope you are doing well.',
            "Kindly arrange to add the following vehicle to the fleet insurance policy of Proactive Hybrid Corporate Ltd under Policy Number: {$policyNumber}.",
            'We would be grateful if you could issue the insurance certificates.',
            'Vehicle Details:',
            $vehicleLine,
            'Your prompt assistance in this matter would be greatly appreciated.',
            'Kind regards,',
            'PROACTIVE HYBRID CORPORATE LTD',
        ]);
    }

    private function proactiveRemoveBody(string $policyNumber, string $carModel, string $registration): string
    {
        $vehicleLine = trim("{$carModel} - {$registration}");

        return implode("\n\n", [
            'Assalamu Alaikum.',
            'I hope you are doing well.',
            'Kindly arrange to remove the following vehicle from the fleet insurance policy of PROACTIVE HYBRID CORPORATE LTD.',
            "Policy Number: {$policyNumber}",
            $vehicleLine,
            'I would appreciate it if you could process this request at your earliest convenience and confirm once the vehicle has been removed from the policy.',
            'Thank you for your assistance.',
            'Kind regards,',
            'PROACTIVE HYBRID CORPORATE LTD',
        ]);
    }

    private function genericAddBody(string $companyName, string $policyNumber, string $vehicleLine): string
    {
        return implode("\n\n", [
            'Dear Brother,',
            'Assalamu Alaikum.',
            'I hope you are doing well.',
            "Kindly arrange to add the following vehicles to the fleet insurance policy of {$companyName} under Policy Number: {$policyNumber}.",
            'We would be grateful if you could issue the insurance certificate.',
            'Vehicle Details:',
            $vehicleLine,
            'Your prompt assistance in this matter would be greatly appreciated.',
            'Kind regards,',
            $companyName,
        ]);
    }

    private function genericRemoveBody(string $companyName, string $policyNumber, string $vehicleLine): string
    {
        return implode("\n\n", [
            'Dear Brother,',
            'Assalamu Alaikum.',
            "Kindly arrange to remove the following vehicle from the fleet insurance policy of {$companyName}, Policy Number {$policyNumber}:",
            $vehicleLine,
            'We would appreciate it if you could process this request at your earliest convenience and confirm once the vehicles have been removed from the policy.',
            'Thank you for your assistance.',
            'Kind regards,',
            $companyName,
        ]);
    }

    private function resolveCompanyKey(Company $company): ?string
    {
        $name = strtolower($company->name);

        if (str_contains($name, 'samore')) {
            return 'samore';
        }

        if (str_contains($name, 'proactive')) {
            return 'proactive';
        }

        return null;
    }
}
