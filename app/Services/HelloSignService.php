<?php
// app/Services/HelloSignService.php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class HelloSignService
{
    protected $apiKey;
    protected $testMode;
    protected $baseUrl = 'https://api.hellosign.com/v3';

    public function __construct()
    {
        $this->apiKey = config('services.hellosign.api_key');
        $this->testMode = config('services.hellosign.test_mode', true);
    }

    /**
     * Send agreement for e-signature
     */
    public function sendAgreementForSignature($agreement, $pdfPath)
    {
        try {
            $fullPdfPath = public_path($pdfPath);

            if (!file_exists($fullPdfPath)) {
                throw new \Exception("PDF file not found");
            }

            $driverEmail = $agreement->driver->email;
            $driverName = $agreement->driver->full_name;
            $ccEmail = auth()->user()->email;

            $multipart = [
                ['name' => 'test_mode', 'contents' => $this->testMode ? '1' : '0'],
                ['name' => 'title', 'contents' => "Vehicle Hire Agreement #{$agreement->id}"],
                ['name' => 'subject', 'contents' => "Please sign your Vehicle Hire Agreement"],
                ['name' => 'message', 'contents' => "Dear {$driverName},\n\nPlease review and sign the attached vehicle hire agreement for {$agreement->car->registration}.\n\nThank you,\n{$agreement->company->name}"],

                // ✅ Driver as Signer with signature field position
                ['name' => 'signers[0][email_address]', 'contents' => $driverEmail],
                ['name' => 'signers[0][name]', 'contents' => $driverName],
                ['name' => 'signers[0][order]', 'contents' => '0'],

                // ✅ CC to Admin
                ['name' => 'cc_email_addresses[]', 'contents' => $ccEmail],

                // ✅ Metadata
                ['name' => 'metadata[agreement_id]', 'contents' => (string)$agreement->id],

                // ✅ SIGNATURE FIELD POSITIONING - Client section mein
                ['name' => 'form_fields_per_document[0][0][type]', 'contents' => 'signature'],
                ['name' => 'form_fields_per_document[0][0][name]', 'contents' => 'Client Signature'],
                ['name' => 'form_fields_per_document[0][0][signer]', 'contents' => '0'], // Driver signs
                ['name' => 'form_fields_per_document[0][0][x]', 'contents' => '60'], // X position (left to right)
                ['name' => 'form_fields_per_document[0][0][y]', 'contents' => '650'], // Y position (top to bottom)
                ['name' => 'form_fields_per_document[0][0][width]', 'contents' => '150'], // Width of signature box
                ['name' => 'form_fields_per_document[0][0][height]', 'contents' => '40'], // Height of signature box
                ['name' => 'form_fields_per_document[0][0][page]', 'contents' => '2'], // Page number (adjust based on your PDF)
                ['name' => 'form_fields_per_document[0][0][required]', 'contents' => 'true'],

                // ✅ PDF File
                ['name' => 'file[0]', 'contents' => fopen($fullPdfPath, 'r'), 'filename' => basename($fullPdfPath)],
            ];

            $client = new Client(['verify' => false, 'timeout' => 60]);

            $response = $client->post($this->baseUrl . '/signature_request/send', [
                'auth' => [$this->apiKey, ''],
                'multipart' => $multipart
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() === 200) {
                return [
                    'success' => true,
                    'request_id' => $responseData['signature_request']['signature_request_id'],
                    'response' => $responseData
                ];
            }

            throw new \Exception('HelloSign API error');

        } catch (\Exception $e) {
            Log::error('HelloSign Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get signature request status
     */
    public function getSignatureStatus($requestId)
    {
        try {
            $client = new Client([
                'verify' => false,
                'timeout' => 30
            ]);

            $response = $client->get($this->baseUrl . "/signature_request/{$requestId}", [
                'auth' => [$this->apiKey, ''] // ✅ HTTP Basic Auth
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $isComplete = $data['signature_request']['is_complete'] ?? false;

            return [
                'success' => true,
                'is_complete' => $isComplete,
                'status' => $isComplete ? 'signed' : 'pending',
                'details' => $data
            ];

        } catch (\Exception $e) {
            Log::error('HelloSign Status Check Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Download signed PDF
     */
    public function downloadSignedPDF($requestId, $agreementId)
    {
        try {
            $directory = public_path('uploads/agreements/signed');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $fileName = "signed_agreement_{$agreementId}.pdf";
            $savePath = "{$directory}/{$fileName}";

            $client = new Client([
                'verify' => false,
                'timeout' => 60
            ]);

            $response = $client->get(
                $this->baseUrl . "/signature_request/files/{$requestId}",
                [
                    'auth' => [$this->apiKey, ''], // ✅ HTTP Basic Auth
                    'query' => ['file_type' => 'pdf'],
                    'sink' => $savePath
                ]
            );

            if ($response->getStatusCode() === 200 && file_exists($savePath)) {
                return [
                    'success' => true,
                    'path' => "uploads/agreements/signed/{$fileName}",
                    'url' => asset("uploads/agreements/signed/{$fileName}")
                ];
            }

            throw new \Exception("Failed to download file");

        } catch (\Exception $e) {
            Log::error('HelloSign Download Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * ✅ Send reminder - FIXED METHOD
     */
    public function sendReminder($requestId, $email)
    {
        try {
            Log::info('Sending HelloSign Reminder', [
                'request_id' => $requestId,
                'email' => $email,
                'api_key_set' => !empty($this->apiKey)
            ]);

            $client = new Client([
                'verify' => false,
                'timeout' => 30
            ]);

            // ✅ CORRECT HelloSign API call
            $response = $client->post(
                $this->baseUrl . "/signature_request/remind/{$requestId}",
                [
                    'auth' => [$this->apiKey, ''], // ✅ Must use HTTP Basic Auth
                    'form_params' => [
                        'email_address' => $email
                    ]
                ]
            );

            $responseData = json_decode($response->getBody()->getContents(), true);

            Log::info('HelloSign Reminder Response:', $responseData);

            return [
                'success' => true,
                'message' => 'Reminder sent successfully'
            ];

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->hasResponse()) {
                $errorResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
                $errorMsg = $errorResponse['error']['error_msg'] ?? $e->getMessage();

                Log::error('HelloSign Reminder Error', [
                    'error' => $errorMsg,
                    'status_code' => $e->getResponse()->getStatusCode(),
                    'response' => $errorResponse
                ]);

                return [
                    'success' => false,
                    'error' => $errorMsg
                ];
            }

            Log::error('HelloSign Reminder Network Error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];

        } catch (\Exception $e) {
            Log::error('HelloSign Reminder General Error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Cancel signature request
     */
    public function cancelRequest($requestId)
    {
        try {
            $client = new Client([
                'verify' => false,
                'timeout' => 30
            ]);

            $response = $client->post(
                $this->baseUrl . "/signature_request/cancel/{$requestId}",
                ['auth' => [$this->apiKey, '']] // ✅ HTTP Basic Auth
            );

            return [
                'success' => true,
                'message' => 'Request cancelled successfully'
            ];

        } catch (\Exception $e) {
            Log::error('HelloSign Cancel Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
