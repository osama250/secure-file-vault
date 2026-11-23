<?php

namespace App\Http\Controllers\UploadFiles;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadFiles\EncryptFileRequest;
use App\Http\Requests\UploadFiles\DecryptFileRequest;
use App\Services\FileEncryptionService;
use Illuminate\Http\Response;
use RuntimeException;

class FileController extends Controller
{
    public function __construct(
        private readonly FileEncryptionService $encryptionService
    ) {
    }

    public function index()
    {
        return view('files.index');
    }

    public function encrypt(EncryptFileRequest $request): Response
    {
        $file       = $request->file('file');
        $content    = file_get_contents($file->getRealPath());

        if ($content === false) {
            throw new RuntimeException('Unable to read uploaded file.');
        }

        $encryptedContent = $this->encryptionService->encrypt( $content , $request->input('password') );
        $outputName       = $request->input('output_name') . '.enc';

        return response($encryptedContent, 200, [
            'Content-Type'           => 'application/octet-stream',
            'Content-Disposition'    => 'attachment; filename="' . $outputName . '"',
            'Cache-Control'          => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function decrypt(DecryptFileRequest $request): Response
    {
        $file       = $request->file('file');
        $content    = file_get_contents($file->getRealPath());

        if ($content === false) {
            throw new RuntimeException('Unable to read uploaded file.');
        }

        try {
            $decryptedContent = $this->encryptionService->decrypt( $content, $request->input('password'));
        } catch (RuntimeException $e) {
            return response('Invalid password or corrupted file.', 422);
        }

        $outputName = $request->input('output_name');
        return response($decryptedContent, 200, [
            'Content-Type'           => 'application/octet-stream',
            'Content-Disposition'    => 'attachment; filename="' . $outputName . '"',
            'Cache-Control'          => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
