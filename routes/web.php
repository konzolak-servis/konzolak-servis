<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\PostaController;
use App\Http\Controllers\TiskController;
use App\Http\Controllers\ZalohaController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'));

// Příjem e-mailů z Cloudflare Email Workeru (autorizace tokenem v controlleru).
Route::post('api/posta/prijem', [PostaController::class, 'prijem']);

// Veřejné QR platby pro e-maily (chráněné tokenem v URL).
Route::get('qr/faktura/{faktura}/{token}', [TiskController::class, 'qrFaktura'])->name('qr.faktura');
Route::get('qr/zakazka/{zakazka}/{token}', [TiskController::class, 'qrZakazka'])->name('qr.zakazka');

Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('tisk')->name('tisk.')->group(function () {
        Route::get('zakazka/{zakazka}/servisni-doklad', [TiskController::class, 'servisniDoklad'])->name('zakazka.doklad');
        Route::get('zakazka/{zakazka}/servisni-protokol', [TiskController::class, 'servisniProtokol'])->name('zakazka.protokol');
        Route::get('zakazka/{zakazka}/stitek', [TiskController::class, 'stitek'])->name('zakazka.stitek');
        Route::get('faktura/{faktura}', [TiskController::class, 'faktura'])->name('faktura');
        Route::get('nabidka/{nabidka}', [TiskController::class, 'nabidka'])->name('nabidka');
        Route::get('obchod/{obchod}', [TiskController::class, 'obchod'])->name('obchod');

        // náhledy z rozpracovaného formuláře (data se předávají přes session)
        Route::get('nahled/nabidka', [TiskController::class, 'nahledNabidka'])->name('nahled.nabidka');
        Route::get('nahled/faktura', [TiskController::class, 'nahledFaktura'])->name('nahled.faktura');
    });

    Route::prefix('export')->name('export.')->group(function () {
        Route::get('sklad', [ExportController::class, 'sklad'])->name('sklad');
        Route::get('naklady/{rok?}', [ExportController::class, 'naklady'])->name('naklady');
        Route::get('penezni-denik/{rok?}', [ExportController::class, 'penezniDenik'])->name('denik');
        Route::get('danove-priznani/{rok?}', [ExportController::class, 'danovePriznani'])->name('dan');
    });

    Route::get('zaloha/stahnout', [ZalohaController::class, 'stahnout'])->name('zaloha.stahnout');
});
