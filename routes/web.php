<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadFiles\FileController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/',  [ FileController::class, 'index'] )->name('files.index');
