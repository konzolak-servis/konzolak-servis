<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\PostaController;
use App\Http\Controllers\TiskController;
use App\Http\Controllers\VerejnyController;
use App\Http\Controllers\Web\CenikController;
use App\Http\Controllers\Web\KontrolaController;
use App\Http\Controllers\Web\OpravyController;
use App\Http\Controllers\Web\PoptavkaController;
use App\Http\Controllers\Web\WebController;
use App\Http\Controllers\WebAuthn\WebAuthnLoginController;
use App\Http\Controllers\WebAuthn\WebAuthnRegisterController;
use App\Http\Controllers\ZalohaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * Přihlašovací brána veřejného webu (stránka „ve výstavbě") – ověří heslo
 * z config/web.php a pustí dál. Middleware App\Http\Middleware\WebGate.
 */
Route::post('/vstup', function (Request $request) {
    $heslo = (string) config('web.gate_heslo');

    if ($heslo !== '' && hash_equals($heslo, (string) $request->input('heslo'))) {
        $request->session()->put('web_gate_ok', true);

        return redirect('/');
    }

    return redirect('/')->with('web_gate_error', 'Nesprávné heslo, zkuste to znovu.');
})->name('web.vstup');

Route::get('/odhlasit', function (Request $request) {
    $request->session()->forget('web_gate_ok');

    return redirect('/');
})->name('web.odhlasit');

/*
 * Veřejný web konzolak.com. V produkci se servíruje z apexu, servisní systém
 * z poddomény servis. – lokálně je vše na jednom hostu (/ = web, /admin = systém).
 */
Route::name('web.')->group(function () {
    Route::get('/', [WebController::class, 'home'])->name('home');
    Route::get('/opravujeme', [OpravyController::class, 'index'])->name('opravy');
    Route::get('/opravujeme/{slug}', [OpravyController::class, 'detail'])->name('opravy.detail');
    Route::get('/cenik', [CenikController::class, 'index'])->name('cenik');
    Route::get('/jak-to-funguje', [WebController::class, 'jakToFunguje'])->name('jak-to-funguje');
    Route::get('/o-nas', [WebController::class, 'oNas'])->name('o-nas');
    Route::get('/reference', [WebController::class, 'reference'])->name('reference');
    Route::get('/caste-dotazy', [WebController::class, 'faq'])->name('faq');
    Route::get('/kontakt', [WebController::class, 'kontakt'])->name('kontakt');
    Route::post('/kontakt', [PoptavkaController::class, 'odeslat'])->name('poptavka');

    Route::get('/kontrola-zakazky', [KontrolaController::class, 'form'])->name('kontrola');
    Route::post('/kontrola-zakazky', [KontrolaController::class, 'najdi'])->name('kontrola.najdi');

    Route::get('/pravni/{dokument}', [WebController::class, 'pravni'])->name('pravni');
});

// Příjem e-mailů z Cloudflare Email Workeru (autorizace tokenem v controlleru).
Route::post('api/posta/prijem', [PostaController::class, 'prijem']);

// Veřejné QR platby pro e-maily (chráněné tokenem v URL).
Route::get('qr/faktura/{faktura}/{token}', [TiskController::class, 'qrFaktura'])->name('qr.faktura');
Route::get('qr/zakazka/{zakazka}/{token}', [TiskController::class, 'qrZakazka'])->name('qr.zakazka');

// Veřejná stránka stavu zakázky (QR na servisním dokladu / štítku).
Route::get('z/{zakazka}/{token}', [VerejnyController::class, 'stavZakazky'])->name('verejne.stav');

// Přihlášení přes passkey / otisk (WebAuthn). Registrace jen pro přihlášeného admina.
Route::post('passkey/login/options', [WebAuthnLoginController::class, 'options'])->name('webauthn.login.options');
Route::post('passkey/login', [WebAuthnLoginController::class, 'login'])->name('webauthn.login');
Route::middleware('auth')->group(function () {
    Route::post('passkey/register/options', [WebAuthnRegisterController::class, 'options'])->name('webauthn.register.options');
    Route::post('passkey/register', [WebAuthnRegisterController::class, 'register'])->name('webauthn.register');
});

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

    Route::get('posta/priloha/{zprava}/{index}', [PostaController::class, 'priloha'])->name('posta.priloha');
});
