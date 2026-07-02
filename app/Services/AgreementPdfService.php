<?php

namespace App\Services;

use App\Models\Agreement;
use Barryvdh\DomPDF\PDF as DomPdfInstance;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use PDF;

class AgreementPdfService
{
    /**
     * @return array<string, mixed>
     */
    public function agreementPdfViewData(Agreement $agreement): array
    {
        $agreement->load([
            'company',
            'driver',
            'car',
            'car.company',
            'car.carModel',
            'status',
            'insuranceProvider',
            'parentAgreement.car',
            'upgradedFromAgreement.car',
        ]);

        return [
            'agreement' => $agreement,
            'driver' => $agreement->driver,
            'car' => $agreement->car,
            'company' => $agreement->documentCompany(),
            'currentDate' => Carbon::now()->format('d/m/Y'),
            'previousVehicleRegistration' => $agreement->previousVehicleRegistration(),
        ];
    }

    /**
     * @return array{0: DomPdfInstance, 1: string}
     */
    public function makeAgreementPdf(Agreement $agreement): array
    {
        $pdf = PDF::loadView('backend.agreements.agreement_pdf', $this->agreementPdfViewData($agreement));
        $pdf->setPaper('A4', 'portrait');

        $driverName = str_replace(' ', '_', $agreement->driver?->full_name ?? 'Driver');
        $filename = 'Agreement_'.$agreement->id.'_'.$driverName.'.pdf';

        return [$pdf, $filename];
    }

    /**
     * @return array{0: DomPdfInstance, 1: string}
     */
    public function makePermissionLetterPdf(Agreement $agreement): array
    {
        $agreement->load([
            'company',
            'driver',
            'car',
            'car.company',
            'car.carModel',
            'car.insurances.status',
            'car.insurances.insuranceProvider',
        ]);

        $activeCarInsurance = $agreement->car?->currentActiveInsurance();

        $policyNumber = $agreement->using_own_insurance
            ? $agreement->own_insurance_policy_number
            : optional($activeCarInsurance?->insuranceProvider)->policy_number;

        $insuranceExpiryDate = $agreement->using_own_insurance
            ? $agreement->own_insurance_end_date
            : $activeCarInsurance?->expiry_date;

        $documentCompany = $agreement->documentCompany() ?? $agreement->company;

        if (! $documentCompany) {
            throw new \RuntimeException('Company is required to generate the permission letter.');
        }

        $letterMeta = app(PermissionLetterService::class)->resolveLetterMeta($documentCompany);

        $data = [
            'agreement' => $agreement,
            'company' => $documentCompany,
            'driver' => $agreement->driver,
            'car' => $agreement->car,
            'policyNumber' => $policyNumber,
            'letterDate' => $agreement->start_date->format('d.m.Y'),
            'contractEndingDate' => $insuranceExpiryDate?->format('d.m.Y') ?? '—',
            'letterMeta' => $letterMeta,
        ];

        $pdf = PDF::loadView('backend.agreements.permission_letter_pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        $driverName = str_replace(' ', '_', $agreement->driver?->full_name ?? 'Driver');
        $filename = 'Permission_Letter_'.$agreement->id.'_'.$driverName.'.pdf';

        return [$pdf, $filename];
    }

    public function writePdfToTempPath(DomPdfInstance $pdf, string $prefix, int $agreementId): string
    {
        $directory = storage_path('app/temp/agreement_client_docs');
        File::ensureDirectoryExists($directory);

        $path = $directory.'/'.$prefix.'_'.$agreementId.'_'.uniqid('', true).'.pdf';
        $written = file_put_contents($path, $pdf->output());

        if ($written === false || ! is_file($path) || filesize($path) === 0) {
            throw new \RuntimeException('Failed to write generated PDF.');
        }

        return $path;
    }
}
