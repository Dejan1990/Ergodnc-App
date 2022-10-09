<?php

use App\Http\Controllers\OfficeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TagController;
use App\Http\Controllers\OfficeImageController;

Route::get('/tags', TagController::class);

Route::get('/offices', [OfficeController::class, 'index']);
Route::get('/offices/{office}', [OfficeController::class, 'show']);
Route::post('/offices', [OfficeController::class, 'create'])->middleware(['auth:sanctum', 'verified']);
Route::put('/offices/{office}', [OfficeController::class, 'update'])->middleware(['auth:sanctum', 'verified']);
Route::delete('/offices/{office}', [OfficeController::class, 'delete'])->middleware(['auth:sanctum', 'verified']);

// Office Photos...
Route::post('/offices/{office}/images', [OfficeImageController::class, 'store'])->middleware(['auth:sanctum', 'verified']);
Route::delete('/offices/{office}/images/{image}', [OfficeImageController::class, 'delete'])->middleware(['auth:sanctum', 'verified']);