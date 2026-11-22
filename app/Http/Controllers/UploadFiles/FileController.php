<?php

namespace App\Http\Controllers\UploadFiles;

use App\Http\Controllers\Controller;
use App\Services\FileEncryptionService;

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
}
