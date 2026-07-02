<?php

namespace App\Services;

use App\Models\Agreement;
use Illuminate\Support\Facades\File;
use PDF;

class AgreementClientDocumentsService
{
    /**
     * @return array{
     *   attachments: list<array{path: string, as: string, label: string, mime: string}>,
     *   attachedLabels: list<string>,
     *   missingDocuments: list<string>,
     *   generatedTempFiles: list<string>
     * }
     */
    public function collectForAgreement(Agreement $agreement): array
    {
        $agreement->load([
            'company',
            'driver',
            'car.company',
            'car.carModel',
            'car.mots',
            'car.phvs',
            'car.insurances.status',
            'car.insurances.insuranceProvider',
            'status',
            'insuranceProvider',
            'parentAgreement.car',
            'upgradedFromAgreement.car',
        ]);

        $attachments = [];
        $attachedLabels = [];
        $missingDocuments = [];
        $generatedTempFiles = [];

        $this->addDriverDocuments($agreement, $attachments, $attachedLabels, $missingDocuments);
        $this->addCarDocuments($agreement, $attachments, $attachedLabels, $missingDocuments);
        $this->addAgreementDocuments($agreement, $attachments, $attachedLabels, $missingDocuments, $generatedTempFiles);

        return [
            'attachments' => $attachments,
            'attachedLabels' => $attachedLabels,
            'missingDocuments' => $missingDocuments,
            'generatedTempFiles' => $generatedTempFiles,
        ];
    }

    /**
     * @param  list<array{path: string, as: string, label: string, mime: string}>  $attachments
     * @param  list<string>  $attachedLabels
     * @param  list<string>  $missingDocuments
     */
    private function addDriverDocuments(
        Agreement $agreement,
        array &$attachments,
        array &$attachedLabels,
        array &$missingDocuments
    ): void {
        $driver = $agreement->driver;

        $map = [
            'Driving license' => $driver?->driver_license_document,
            'Driving licence summary' => $driver?->dvla_license_summary,
            'Private Hire Driver license' => $driver?->driver_phd_license_document,
            'Private Hire Driver card' => $driver?->phd_card_document,
        ];

        foreach ($map as $label => $filename) {
            if (! $filename || ! is_string($filename)) {
                $missingDocuments[] = $label;
                continue;
            }

            $this->pushFileIfExists(
                $attachments,
                $attachedLabels,
                $missingDocuments,
                $label,
                public_path('uploads/driver_licenses/'.$filename),
                $filename
            );
        }
    }

    /**
     * @param  list<array{path: string, as: string, label: string, mime: string}>  $attachments
     * @param  list<string>  $attachedLabels
     * @param  list<string>  $missingDocuments
     */
    private function addCarDocuments(
        Agreement $agreement,
        array &$attachments,
        array &$attachedLabels,
        array &$missingDocuments
    ): void {
        $car = $agreement->car;
        $latestPhv = $car?->phvs?->sortByDesc(fn ($phv) => [optional($phv->expiry_date)->timestamp ?? 0, $phv->id])->first();
        $latestMot = $car?->latestMot();
        $activeInsurance = $car?->currentActiveInsurance();

        $this->pushFileIfExists(
            $attachments,
            $attachedLabels,
            $missingDocuments,
            'Latest PHV document',
            $latestPhv?->document ? public_path('uploads/cars/phv_documents/'.$latestPhv->document) : null,
            (string) ($latestPhv?->document ?? '')
        );

        $this->pushFileIfExists(
            $attachments,
            $attachedLabels,
            $missingDocuments,
            'Latest MOT document',
            $latestMot?->document ? public_path('uploads/cars/mot_documents/'.$latestMot->document) : null,
            (string) ($latestMot?->document ?? '')
        );

        if ($agreement->using_own_insurance) {
            $proofFiles = $agreement->ownInsuranceProofFileNames();
            if ($proofFiles === []) {
                $missingDocuments[] = 'Client insurance document';
            } else {
                foreach ($proofFiles as $i => $filename) {
                    $this->pushFileIfExists(
                        $attachments,
                        $attachedLabels,
                        $missingDocuments,
                        'Client insurance document'.(count($proofFiles) > 1 ? ' #'.($i + 1) : ''),
                        public_path('uploads/insurance_documents/'.$filename),
                        $filename
                    );
                }
            }
        } else {
            $this->pushFileIfExists(
                $attachments,
                $attachedLabels,
                $missingDocuments,
                'Company insurance document',
                $activeInsurance?->insurance_document ? public_path('uploads/cars/insurance_documents/'.$activeInsurance->insurance_document) : null,
                (string) ($activeInsurance?->insurance_document ?? '')
            );
        }

        $v5Files = $car?->v5DocumentFileNames() ?? [];
        if ($v5Files === []) {
            $missingDocuments[] = 'Car logbook (V5)';
        } else {
            foreach ($v5Files as $i => $filename) {
                $this->pushFileIfExists(
                    $attachments,
                    $attachedLabels,
                    $missingDocuments,
                    'Car logbook (V5)'.(count($v5Files) > 1 ? ' #'.($i + 1) : ''),
                    public_path('uploads/cars/'.$filename),
                    $filename
                );
            }
        }
    }

    /**
     * @param  list<array{path: string, as: string, label: string, mime: string}>  $attachments
     * @param  list<string>  $attachedLabels
     * @param  list<string>  $missingDocuments
     * @param  list<string>  $generatedTempFiles
     */
    private function addAgreementDocuments(
        Agreement $agreement,
        array &$attachments,
        array &$attachedLabels,
        array &$missingDocuments,
        array &$generatedTempFiles
    ): void {
        $directory = public_path('uploads/agreements/temp');
        if (! file_exists($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        $agreementPdfPath = $directory.'/agreement_'.$agreement->id.'_client_docs_'.uniqid().'.pdf';
        $permissionPdfPath = $directory.'/permission_letter_'.$agreement->id.'_client_docs_'.uniqid().'.pdf';

        try {
            $agreementData = [
                'agreement' => $agreement,
                'driver' => $agreement->driver,
                'car' => $agreement->car,
                'company' => $agreement->documentCompany(),
                'currentDate' => now()->format('d/m/Y'),
                'previousVehicleRegistration' => $agreement->previousVehicleRegistration(),
            ];
            $agreementPdf = PDF::loadView('backend.agreements.agreement_pdf', $agreementData);
            $agreementPdf->setPaper('A4', 'portrait');
            $agreementPdf->save($agreementPdfPath);

            $generatedTempFiles[] = $agreementPdfPath;
            $this->pushFileIfExists(
                $attachments,
                $attachedLabels,
                $missingDocuments,
                'Agreement PDF',
                $agreementPdfPath,
                'Agreement_'.$agreement->id.'.pdf'
            );
        } catch (\Throwable) {
            $missingDocuments[] = 'Agreement PDF';
        }

        try {
            $activeCarInsurance = $agreement->car?->currentActiveInsurance();
            $policyNumber = $agreement->using_own_insurance
                ? $agreement->own_insurance_policy_number
                : optional($activeCarInsurance?->insuranceProvider)->policy_number;
            $insuranceExpiryDate = $agreement->using_own_insurance
                ? $agreement->own_insurance_end_date
                : $activeCarInsurance?->expiry_date;
            $documentCompany = $agreement->documentCompany();
            $letterMeta = app(PermissionLetterService::class)->resolveLetterMeta($documentCompany);

            $permissionData = [
                'agreement' => $agreement,
                'company' => $documentCompany,
                'driver' => $agreement->driver,
                'car' => $agreement->car,
                'policyNumber' => $policyNumber,
                'letterDate' => $agreement->start_date->format('d.m.Y'),
                'contractEndingDate' => $insuranceExpiryDate?->format('d.m.Y') ?? '—',
                'letterMeta' => $letterMeta,
            ];
            $permissionPdf = PDF::loadView('backend.agreements.permission_letter_pdf', $permissionData);
            $permissionPdf->setPaper('A4', 'portrait');
            $permissionPdf->save($permissionPdfPath);

            $generatedTempFiles[] = $permissionPdfPath;
            $this->pushFileIfExists(
                $attachments,
                $attachedLabels,
                $missingDocuments,
                'Permission letter',
                $permissionPdfPath,
                'Permission_Letter_'.$agreement->id.'.pdf'
            );
        } catch (\Throwable) {
            $missingDocuments[] = 'Permission letter';
        }
    }

    /**
     * @param  list<array{path: string, as: string, label: string, mime: string}>  $attachments
     * @param  list<string>  $attachedLabels
     * @param  list<string>  $missingDocuments
     */
    private function pushFileIfExists(
        array &$attachments,
        array &$attachedLabels,
        array &$missingDocuments,
        string $label,
        ?string $fullPath,
        string $fallbackName
    ): void {
        if (! $fullPath || ! is_file($fullPath)) {
            $missingDocuments[] = $label;
            return;
        }

        $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
        $name = $fallbackName !== '' ? $fallbackName : basename($fullPath);

        $attachments[] = [
            'path' => $fullPath,
            'as' => $name,
            'label' => $label,
            'mime' => $mime,
        ];
        $attachedLabels[] = $label;
    }
}
