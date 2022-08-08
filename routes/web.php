<?php

use App\Http\Controllers\Web\SettingController;
use App\Http\Controllers\Web\SupportController;
use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\Web\indexController;
use \App\Http\Controllers\Config\DeleteVendorApiController;


Route::post('/CheckSave/{accountId}', [indexController::class, 'CheckSave'])->name('CheckSave');


Route::get('/', [indexController::class, 'index'])->name('index');
Route::get('/{accountId}', [indexController::class, 'show'])->name("indexMain");


Route::get('/Setting/{accountId}', [SettingController::class, 'index'])->name('indexSetting');
Route::get('/Setting/Document/{accountId}', [SettingController::class, 'indexDocument'])->name('indexDocument');
Route::get('/Setting/Add/{accountId}', [SettingController::class, 'indexAdd'])->name('indexAdd');

Route::get('/Setting/Error/{accountId}/{message}', [SettingController::class, 'indexError'])->name('indexError');

Route::post('/setSetting/{accountId}', [SettingController::class, 'postSettingIndex'])->name('setSettingIndex');
Route::post('/setSetting/Document/{accountId}', [SettingController::class, 'postSettingDocument'])->name('setSettingDocument');
Route::post('/setSetting/Add/{accountId}', [SettingController::class, 'postSettingAdd'])->name('setSettingAdd');


Route::get('/Help/Support/{accountId}', [SupportController::class, 'index'])->name('indexSupport');

Route::post('/Help/Support/Send/{accountId}', [SupportController::class, 'postSendSupport'])->name('indexSendSupport');







Route::get('DeleteVendorApi/{appId}/{accountId}', [DeleteVendorApiController::class, 'Delete'])->name('Delete');
