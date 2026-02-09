<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     * Send agreement for e-signature using direct HTTP request
     */
    public function sendAgreementForSignature($agreement, $pdfPath)
    {
        try {
            $fullPdfPath = public_path($pdfPath);

            if (!file_exists($fullPdfPath)) {
                throw new \Exception("PDF file not found: " . $fullPdfPath);
            }

            // Prepare multipart form data
            $multipart = [
                [
                    'name' => 'test_mode',
                    'contents' => $this->testMode ? '1' : '0'
                ],
                [
                    'name' => 'title',
                    'contents' => "Fleet Agreement #{$agreement->id}"
                ],
                [
                    'name' => 'subject',
                    'contents' => "Fleet Management Agreement"
                ],
                [
                    'name' => 'message',
                    'contents' => "Dear {$agreement->driver->full_name},\n\nPlease review and sign the attached fleet management agreement for vehicle {$agreement->car->registration}.\n\nThank you"
                ],
                [
                    'name' => 'signers[0][email_address]',
                    'contents' => $agreement->driver->email
                ],
                [
                    'name' => 'signers[0][name]',
                    'contents' => $agreement->driver->full_name
                ],
                [
                    'name' => 'signers[0][role]',
                    'contents' => 'Driver'
                ],
                [
                    'name' => 'metadata[agreement_id]',
                    'contents' => (string)$agreement->id
                ],
                [
                    'name' => 'metadata[vehicle]',
                    'contents' => $agreement->car->registration
                ],
                [
                    'name' => 'file',
                    'contents' => fopen($fullPdfPath, 'r'),
                    'filename' => basename($fullPdfPath),
                    'headers' => [
                        'Content-Type' => 'application/pdf'
                    ]
                ]
            ];

            // Add CC if admin email exists
            if (auth()->check() && auth()->user()->email) {
                $multipart[] = [
                    'name' => 'cc_email_addresses[]',
                    'contents' => auth()->user()->email
                ];
            }

            Log::info('Sending to HelloSign API with test_mode: ' . ($this->testMode ? 'YES' : 'NO'));

            // **IMPORTANT: SSL verify disable for local development**
            $client = new \GuzzleHttp\Client([
                'verify' => false, // SSL certificate verify disable
                'timeout' => 60,
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':'),
                    'Accept' => 'application/json',
                ]
            ]);

            try {
                $response = $client->post($this->baseUrl . '/signature_request/send', [
                    'multipart' => $multipart
                ]);

                $responseData = json_decode($response->getBody()->getContents(), true);

                Log::info('HelloSign API Response Status: ' . $response->getStatusCode());

                if ($response->getStatusCode() === 200) {
                    return [
                        'success' => true,
                        'request_id' => $responseData['signature_request']['signature_request_id'],
                        'signing_url' => $responseData['signature_request']['signing_url'],
                        'details_url' => $responseData['signature_request']['details_url'],
                        'response' => $responseData
                    ];
                } else {
                    throw new \Exception($responseData['error']['error_msg'] ?? 'HelloSign API Error');
                }

            } catch (\GuzzleHttp\Exception\RequestException $e) {
                if ($e->hasResponse()) {
                    $errorResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
                    throw new \Exception($errorResponse['error']['error_msg'] ?? $e->getMessage());
                }
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('HelloSign Error: ' . $e->getMessage());
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
            // Create directory
            $directory = public_path('uploads/agreements/signed');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $fileName = "signed_agreement_{$agreementId}.pdf";
            $savePath = "{$directory}/{$fileName}";
            $relativePath = "uploads/agreements/signed/{$fileName}";

            // Download file
            $response = Http::withBasicAuth($this->apiKey, '')
                ->timeout(60)
                ->sink($savePath) // Save directly to file
                ->get($this->baseUrl . "/signature_request/files/{$requestId}", [
                    'file_type' => 'pdf'
                ]);

            if ($response->successful() && file_exists($savePath)) {
                return [
                    'success' => true,
                    'path' => $relativePath,
                    'url' => asset($relativePath)
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
     * Get signature request status
     */
    public function getSignatureStatus($requestId)
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, '')
                ->get($this->baseUrl . "/signature_request/{$requestId}");

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'is_complete' => $data['signature_request']['is_complete'],
                    'is_signed' => !empty($data['signature_request']['signatures']),
                    'status' => 'signed', // Simplified
                    'details' => $data
                ];
            }

            throw new \Exception("Failed to get status");

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
