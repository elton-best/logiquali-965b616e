<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DocumentSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DocumentSignatureController extends Controller
{
    public function __construct(
        private DocumentSignatureService $signatureService
    ) {}

    /**
     * Signer un document
     */
    public function sign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string|max:50',
            'document_id' => 'required|integer',
            'password' => 'required|string',
            'document_content' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $signature = $this->signatureService->signDocument(
                $request->document_type,
                $request->document_id,
                $request->user(),
                $request->password,
                $request->document_content
            );

            return response()->json([
                'message' => 'Document signé avec succès',
                'signature' => [
                    'id' => $signature->id,
                    'signature_text' => $signature->signature_text,
                    'signed_at' => $signature->signed_at->toIso8601String(),
                    'user' => [
                        'id' => $signature->user->id,
                        'name' => $signature->user->name,
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Récupérer signatures d'un document
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string',
            'document_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $signatures = $this->signatureService->getDocumentSignatures(
            $request->document_type,
            $request->document_id
        );

        return response()->json([
            'signatures' => $signatures->map(fn($sig) => [
                'id' => $sig->id,
                'signature_text' => $sig->signature_text,
                'signed_at' => $sig->signed_at->toIso8601String(),
                'user' => [
                    'id' => $sig->user->id,
                    'name' => $sig->user->name,
                    'email' => $sig->user->email,
                ],
            ]),
        ]);
    }

    /**
     * Vérifier si utilisateur a signé
     */
    public function checkSigned(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string',
            'document_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $hasSigned = $this->signatureService->hasUserSigned(
            $request->document_type,
            $request->document_id,
            $request->user()->id
        );

        return response()->json(['has_signed' => $hasSigned]);
    }
}
