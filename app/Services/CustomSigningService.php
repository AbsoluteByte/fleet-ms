<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\AgreementSignatureToken;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CustomSigningService
{
    public function sendForSigning(Agreement $agreement)
    {
        try {
            $token = $this->createPendingToken($agreement);
            $signingUrl = route('sign.show', ['token' => $token->token]);

            $this->sendSigningEmail($agreement, $token, $signingUrl);

            $agreement->update([
                'hellosign_status' => 'pending',
                'esign_sent_at' => now(),
            ]);

            Log::info('Custom signing sent', ['agreement_id' => $agreement->id]);

            return [
                'success' => true,
                'token' => $token,
                'signing_url' => $signingUrl,
            ];
        } catch (\Exception $e) {
            Log::error('Custom Signing Error: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function resendSigningLink(Agreement $agreement)
    {
        try {
            $agreement->signatureTokens()
                ->where('status', 'pending')
                ->update(['status' => 'expired']);

            return $this->sendForSigning($agreement);
        } catch (\Exception $e) {
            Log::error('Custom Signing Resend Error: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @param  array{signature_method?: string, typed_name?: string|null}  $meta
     */
    public function processSignature(AgreementSignatureToken $token, $signatureData, $ipAddress, array $meta = [])
    {
        try {
            $token->markAsSigned($signatureData, $ipAddress, $meta);

            $agreement = $token->agreement()->firstOrFail();
            $signedPdfPath = null;

            try {
                $signedPdfPath = $this->generateSignedPDF($agreement);
            } catch (\Throwable $e) {
                Log::warning('Signed PDF could not be saved after signature: '.$e->getMessage(), [
                    'agreement_id' => $agreement->id,
                    'token_id' => $token->id,
                ]);
            }

            $agreement->update([
                'hellosign_status' => 'signed',
                'esign_completed_at' => now(),
                'esign_document_path' => $signedPdfPath,
            ]);

            return ['success' => true, 'signed_pdf_path' => $signedPdfPath];
        } catch (\Exception $e) {
            Log::error('Process Signature Error: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function createPendingToken(Agreement $agreement): AgreementSignatureToken
    {
        return AgreementSignatureToken::create([
            'agreement_id' => $agreement->id,
            'token' => AgreementSignatureToken::generateToken(),
            'signer_email' => $agreement->driver->email,
            'signer_name' => $agreement->driver->full_name,
            'status' => 'pending',
            'expires_at' => now()->addHours(72),
        ]);
    }

    protected function sendSigningEmail(Agreement $agreement, $token, $signingUrl)
    {
        $agreement->load(['company', 'car.company', 'driver', 'car']);

        $pdfAttachmentPath = null;
        $filename = null;
        try {
            [$pdf, $filename] = app(AgreementPdfService::class)->makeAgreementPdf($agreement);
            $pdfAttachmentPath = app(AgreementPdfService::class)->writePdfToTempPath(
                $pdf,
                'signing_preview',
                $agreement->id
            );
        } catch (\Exception $e) {
            Log::warning('Agreement PDF attachment generate nahi ho saka: '.$e->getMessage());
            $pdfAttachmentPath = null;
        }

        $emailData = [
            'agreement' => $agreement,
            'driver' => $agreement->driver,
            'company' => $agreement->documentCompany(),
            'signing_url' => $signingUrl,
            'expires_at' => $token->expires_at->format('M d, Y h:i A'),
            'has_attachment' => ($pdfAttachmentPath && file_exists($pdfAttachmentPath)),
        ];

        Mail::send(
            'emails.custom_signing_request',
            $emailData,
            function ($message) use ($agreement, $pdfAttachmentPath, $filename) {
                $message->to($agreement->driver->email)
                    ->subject('Sign Your Vehicle Hire Agreement - '.$agreement->car->registration);

                if ($pdfAttachmentPath && file_exists($pdfAttachmentPath)) {
                    $message->attach($pdfAttachmentPath, [
                        'as' => $filename ?? ('Vehicle_Hire_Agreement_'.$agreement->car->registration.'.pdf'),
                        'mime' => 'application/pdf',
                    ]);
                }
            }
        );

        if ($pdfAttachmentPath && file_exists($pdfAttachmentPath)) {
            try {
                unlink($pdfAttachmentPath);
            } catch (\Exception $e) {
                Log::warning('Temp PDF delete nahi ho saka: '.$e->getMessage());
            }
        }
    }

    protected function generateSignedPDF(Agreement $agreement): string
    {
        [$pdf] = app(AgreementPdfService::class)->makeAgreementPdf($agreement);
        $contents = $pdf->output();
        $fileName = 'signed_agreement_'.$agreement->id.'_'.time().'.pdf';

        $targets = [
            ['dir' => storage_path('app/agreements/signed'), 'relative' => 'agreements/signed/'.$fileName],
            ['dir' => storage_path('framework/agreements/signed'), 'relative' => 'framework/agreements/signed/'.$fileName],
        ];

        $lastError = null;

        foreach ($targets as $target) {
            try {
                File::ensureDirectoryExists($target['dir'], 0775);
                $fullPath = $target['dir'].'/'.$fileName;
                $written = @file_put_contents($fullPath, $contents);

                if ($written !== false && is_file($fullPath)) {
                    return $target['relative'];
                }

                $lastError = 'Could not write '.$fullPath;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        throw new \RuntimeException($lastError ?? 'Failed to save the signed agreement PDF.');
    }
}
