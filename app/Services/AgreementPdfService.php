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

        $signatureImage = $agreement->signedSignatureToken()?->signature_data;
        $documentCompany = $agreement->documentCompany();
        $letterMeta = $documentCompany
            ? app(PermissionLetterService::class)->resolveLetterMeta($documentCompany)
            : [];

        return [
            'agreement' => $agreement,
            'driver' => $agreement->driver,
            'car' => $agreement->car,
            'company' => $documentCompany,
            'currentDate' => Carbon::now()->format('d/m/Y'),
            'previousVehicleRegistration' => $agreement->previousVehicleRegistration(),
            'signature_image' => $signatureImage,
            'letterMeta' => $letterMeta,
        ];
    }

    /**
     * @return array{0: DomPdfInstance, 1: string}
     */
    public function makeAgreementPdf(Agreement $agreement): array
    {
        $data = $this->agreementPdfViewData($agreement);
        $view = filled($data['signature_image'] ?? null)
            ? 'backend.agreements.agreement_pdf_signed'
            : 'backend.agreements.agreement_pdf';

        $pdf = PDF::loadView($view, $data);
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

    /**
     * @return array<string, mixed>
     */
    public function financialSummaryPdfViewData(
        Agreement $agreement,
        ?array $settlementPreview = null,
        float $settlementRemainingDebt = 0
    ): array {
        $agreement->load([
            'company',
            'driver',
            'car.carModel',
            'status',
            'deductions',
            'additionalCharges.invoice',
            'discountConsumedInvoice',
            'depositRefund',
            'invoices' => function ($query) {
                $query->orderByDesc('invoice_date')->orderByDesc('id');
            },
        ]);

        return [
            'agreement' => $agreement,
            'company' => $agreement->documentCompany() ?? $agreement->company,
            'settlementPreview' => $settlementPreview,
            'settlementRemainingDebt' => $settlementRemainingDebt,
            'generatedAt' => Carbon::now()->format('d M Y H:i'),
        ];
    }

    /**
     * @return array{0: DomPdfInstance, 1: string}
     */
    public function makeFinancialSummaryPdf(
        Agreement $agreement,
        ?array $settlementPreview = null,
        float $settlementRemainingDebt = 0
    ): array {
        $pdf = PDF::loadView(
            'backend.agreements.financial_summary_pdf',
            $this->financialSummaryPdfViewData($agreement, $settlementPreview, $settlementRemainingDebt)
        );
        $pdf->setPaper('A4', 'portrait');

        $driverName = str_replace(' ', '_', $agreement->driver?->full_name ?? 'Driver');
        $filename = 'Agreement_Financial_'.$agreement->id.'_'.$driverName.'.pdf';

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
