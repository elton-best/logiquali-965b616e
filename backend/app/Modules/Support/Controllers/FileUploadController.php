<?php

namespace App\Modules\Support\Controllers;

use App\Http\Controllers\Controller;
use App\Rules\SecureFileUpload;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FileUploadController extends Controller
{
    public function __construct(private FileUploadService $fileService)
    {
        $this->middleware('permission:support.documents.create');
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', new SecureFileUpload()],
            'directory' => 'string|max:100'
        ]);

        $directory = $request->input('directory', 'uploads');
        $result = $this->fileService->uploadSecure($request->file('file'), $directory);

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        $deleted = $this->fileService->deleteFile($request->input('path'));

        return response()->json([
            'success' => $deleted,
            'message' => $deleted ? 'Fichier supprimé' : 'Erreur lors de la suppression'
        ]);
    }
}