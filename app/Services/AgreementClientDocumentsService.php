<?php

namespace App\Services;

use App\Models\Agreement;

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
        $pdfService = app(AgreementPdfService::class);

        [$agreementPdf, $agreementFilename] = $pdfService->makeAgreementPdf($agreement);
        $agreementPdfPath = $pdfService->writePdfToTempPath($agreementPdf, 'agreement', $agreement->id);
        $generatedTempFiles[] = $agreementPdfPath;
        $this->pushGeneratedAttachment($attachments, $attachedLabels, 'Agreement PDF', $agreementPdfPath, $agreementFilename);

        [$permissionPdf, $permissionFilename] = $pdfService->makePermissionLetterPdf($agreement);
        $permissionPdfPath = $pdfService->writePdfToTempPath($permissionPdf, 'permission_letter', $agreement->id);
        $generatedTempFiles[] = $permissionPdfPath;
        $this->pushGeneratedAttachment($attachments, $attachedLabels, 'Permission letter', $permissionPdfPath, $permissionFilename);
    }

    /**
     * @param  list<array{path: string, as: string, label: string, mime: string}>  $attachments
     * @param  list<string>  $attachedLabels
     */
    private function pushGeneratedAttachment(
        array &$attachments,
        array &$attachedLabels,
        string $label,
        string $fullPath,
        string $filename
    ): void {
        $attachments[] = [
            'path' => $fullPath,
            'as' => $filename,
            'label' => $label,
            'mime' => 'application/pdf',
        ];
        $attachedLabels[] = $label;
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
