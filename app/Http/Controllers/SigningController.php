<?php

namespace App\Http\Controllers;

use App\Models\AgreementSignatureToken;
use App\Services\CustomSigningService;
use Illuminate\Http\Request;

class SigningController extends Controller
{
    /**
     * Show signing page
     */
    public function show($token)
    {
        $signatureToken = AgreementSignatureToken::where('token', $token)->firstOrFail();

        // Check if expired
        if ($signatureToken->isExpired()) {
            return view('signing.expired', compact('signatureToken'));
        }

        // Check if already signed
        if ($signatureToken->isSigned()) {
            return view('signing.already-signed', compact('signatureToken'));
        }

        // Load agreement
        $signatureToken->load(['agreement.company', 'agreement.driver', 'agreement.car', 'agreement.car.carModel']);

        return view('signing.sign', compact('signatureToken'));
    }

    /**
     * Process signature submission
     */
    public function submit(Request $request, $token)
    {
        $request->validate([
            'signature' => 'required|string',
        ]);

        $signatureToken = AgreementSignatureToken::where('token', $token)->firstOrFail();

        // Validate token
        if ($signatureToken->isExpired()) {
            return response()->json(['error' => 'Signing link has expired'], 400);
        }

        if ($signatureToken->isSigned()) {
            return response()->json(['error' => 'Agreement already signed'], 400);
        }

        try {
            $customSigningService = new CustomSigningService();

            $result = $customSigningService->processSignature(
                $signatureToken,
                $request->signature,
                $request->ip()
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'redirect' => route('sign.success', ['token' => $token])
                ]);
            }

            return response()->json(['error' => $result['error'] ?? 'Failed to process signature'], 500);

        } catch (\Exception $e) {
            \Log::error('Signature Submission Error: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }

    /**
     * Success page
     */
    public function success($token)
    {
        $signatureToken = AgreementSignatureToken::where('token', $token)->firstOrFail();

        if (!$signatureToken->isSigned()) {
            return redirect()->route('sign.show', ['token' => $token]);
        }

        return view('signing.success', compact('signatureToken'));
    }
}
