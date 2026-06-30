<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\AgreementSignatureToken;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use PDF;
use Carbon\Carbon;

class CustomSigningService
{
    /**
     * Send agreement for custom signing
     */
    public function sendForSigning(Agreement $agreement)
    {
        try {
            // Create signature token (expires in 72 hours)
            $token = AgreementSignatureToken::create([
                'agreement_id' => $agreement->id,
                'token' => AgreementSignatureToken::generateToken(),
                'signer_email' => $agreement->driver->email,
                'signer_name' => $agreement->driver->full_name,
                'status' => 'pending',
                'expires_at' => now()->addHours(72)
            ]);

            // Generate signing URL
            $signingUrl = route('sign.show', ['token' => $token->token]);

            // Send email
            $this->sendSigningEmail($agreement, $token, $signingUrl);

            // Update agreement
            $agreement->update([
                'hellosign_status' => 'pending',
                'esign_sent_at' => now()
            ]);

            Log::info('Custom signing sent', ['agreement_id' => $agreement->id]);

            return [
                'success' => true,
                'token' => $token,
                'signing_url' => $signingUrl
            ];

        } catch (\Exception $e) {
            Log::error('Custom Signing Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send signing email
     */
    protected function sendSigningEmail(Agreement $agreement, $token, $signingUrl)
    {
        $agreement->load(['company', 'car.company', 'driver', 'car']);

        // ✅ Step 1: Agreement PDF generate karo attachment k liye
        $pdfAttachmentPath = null;
        try {
            $data = [
                'agreement' => $agreement,
                'driver'    => $agreement->driver,
                'car'       => $agreement->car,
                'company'   => $agreement->documentCompany(),
                'currentDate' => \Carbon\Carbon::now()->format('d/m/Y'),
            ];

            $pdf = \PDF::loadView('backend.agreements.agreement_pdf', $data);
            $pdf->setPaper('A4', 'portrait');

            // Temp directory mein save karo
            $tempDir = public_path('uploads/agreements/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $fileName = "agreement_{$agreement->id}_preview.pdf";
            $pdfAttachmentPath = "{$tempDir}/{$fileName}";
            $pdf->save($pdfAttachmentPath);

        } catch (\Exception $e) {
            \Log::warning('Agreement PDF attachment generate nahi ho saka: ' . $e->getMessage());
            // PDF attach na ho to bhi email send karo — koi problem nahi
            $pdfAttachmentPath = null;
        }

        // ✅ Step 2: Email data
        $emailData = [
            'agreement'  => $agreement,
            'driver'     => $agreement->driver,
            'company'    => $agreement->documentCompany(),
            'signing_url' => $signingUrl,
            'expires_at' => $token->expires_at->format('M d, Y h:i A'),
            'has_attachment' => ($pdfAttachmentPath && file_exists($pdfAttachmentPath)),
        ];

        // ✅ Step 3: Email send karo + PDF attach karo
        Mail::send(
            'emails.custom_signing_request',
            $emailData,
            function ($message) use ($agreement, $pdfAttachmentPath) {
                $message->to($agreement->driver->email)
                    ->subject('Sign Your Vehicle Hire Agreement - ' . $agreement->car->registration);

                // ✅ PDF attach karo agar successfully generate hui ho
                if ($pdfAttachmentPath && file_exists($pdfAttachmentPath)) {
                    $message->attach($pdfAttachmentPath, [
                        'as'   => 'Vehicle_Hire_Agreement_' . $agreement->car->registration . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
                }
            }
        );

        // ✅ Step 4: Temp PDF delete karo email send hone k baad
        if ($pdfAttachmentPath && file_exists($pdfAttachmentPath)) {
            try {
                unlink($pdfAttachmentPath);
            } catch (\Exception $e) {
                \Log::warning('Temp PDF delete nahi ho saka: ' . $e->getMessage());
            }
        }
    }

    /**
     * Process signature
     */
    public function processSignature(AgreementSignatureToken $token, $signatureData, $ipAddress)
    {
        try {
            // Mark as signed
            $token->markAsSigned($signatureData, $ipAddress);

            // Generate signed PDF
            $signedPdfPath = $this->generateSignedPDF($token->agreement, $signatureData);

            // Update agreement
            $token->agreement->update([
                'hellosign_status' => 'signed',
                'esign_document_path' => $signedPdfPath,
                'esign_completed_at' => now()
            ]);

            return ['success' => true, 'signed_pdf_path' => $signedPdfPath];

        } catch (\Exception $e) {
            Log::error('Process Signature Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate signed PDF
     */
    protected function generateSignedPDF(Agreement $agreement, $signatureData)
    {
        $agreement->load([
            'company',
            'driver',
            'car',
            'car.company',
            'car.carModel',
            'status',
            'parentAgreement.car',
            'upgradedFromAgreement.car',
        ]);

        $data = [
            'agreement' => $agreement,
            'driver' => $agreement->driver,
            'car' => $agreement->car,
            'company' => $agreement->documentCompany(),
            'currentDate' => Carbon::now()->format('d/m/Y'),
            'signature_image' => $signatureData,
            'previousVehicleRegistration' => $agreement->previousVehicleRegistration(),
        ];

        $pdf = PDF::loadView('backend.agreements.agreement_pdf_signed', $data);
        $pdf->setPaper('A4', 'portrait');

        $directory = public_path('uploads/agreements/signed');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $fileName = "signed_agreement_{$agreement->id}_" . time() . ".pdf";
        $pdf->save("{$directory}/{$fileName}");

        return "uploads/agreements/signed/{$fileName}";
    }
}
