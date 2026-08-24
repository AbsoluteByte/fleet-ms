<?php

namespace App\Http\Controllers;

use App\Models\AgreementSignatureToken;
use App\Services\AgreementPdfService;
use App\Services\CustomSigningService;
use Illuminate\Http\Request;

class SigningController extends Controller
{
    public function show(Request $request, $token)
    {
        $signatureToken = AgreementSignatureToken::where('token', $token)->firstOrFail();
        $signatureToken->load(['agreement.company', 'agreement.driver', 'agreement.car', 'agreement.car.carModel']);

        if ($signatureToken->isExpired() && ! $signatureToken->isSigned()) {
            return view('signing.expired', compact('signatureToken'));
        }

        if ($signatureToken->isSigned()) {
            return view('signing.already-signed', compact('signatureToken'));
        }

        $signatureToken->recordFirstOpen($request);

        return view('signing.sign', compact('signatureToken'));
    }

    public function preview($token)
    {
        $signatureToken = AgreementSignatureToken::where('token', $token)->firstOrFail();

        $signatureToken->load([
            'agreement.company',
            'agreement.driver',
            'agreement.car',
            'agreement.car.carModel',
            'agreement.status',
        ]);

        try {
            [$pdf] = app(AgreementPdfService::class)->makeAgreementPdf($signatureToken->agreement);

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="agreement_preview.pdf"',
                'X-Frame-Options' => 'SAMEORIGIN',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Agreement signing preview failed: '.$e->getMessage(), [
                'token' => $token,
                'agreement_id' => $signatureToken->agreement_id,
            ]);

            abort(500, 'Unable to generate the agreement preview.');
        }
    }

    public function submit(Request $request, $token)
    {
        $validated = $request->validate([
            'signature' => 'required|string',
            'signature_method' => 'required|in:draw,typed',
            'typed_name' => 'nullable|required_if:signature_method,typed|string|max:255',
        ]);

        $signatureToken = AgreementSignatureToken::where('token', $token)->firstOrFail();

        if ($signatureToken->isExpired()) {
            return response()->json(['error' => 'Signing link has expired'], 400);
        }

        if ($signatureToken->isSigned()) {
            return response()->json(['error' => 'Agreement already signed'], 400);
        }

        try {
            $customSigningService = new CustomSigningService;

            $result = $customSigningService->processSignature(
                $signatureToken,
                $validated['signature'],
                $request->ip(),
                [
                    'signature_method' => $validated['signature_method'],
                    'typed_name' => $validated['typed_name'] ?? null,
                ]
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'redirect' => route('sign.success', ['token' => $token]),
                ]);
            }

            return response()->json(['error' => $result['error'] ?? 'Failed to process signature'], 500);
        } catch (\Exception $e) {
            \Log::error('Signature Submission Error: '.$e->getMessage());

            return response()->json(['error' => 'An error occurred'], 500);
        }
    }

    public function success($token)
    {
        $signatureToken = AgreementSignatureToken::where('token', $token)->firstOrFail();

        if (! $signatureToken->isSigned()) {
            return redirect()->route('sign.show', ['token' => $token]);
        }

        return view('signing.success', compact('signatureToken'));
    }
}
