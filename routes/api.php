<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadFiles\FileController;


Route::prefix('files')->middleware('throttle:20,1')->group(function () {
        Route::post('encrypt', [ FileController::class, 'encrypt'] );
        Route::post('decrypt', [ FileController::class, 'decrypt'] );
});
