<?php

use App\Models\Sablona;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $vychozi = [
            'Hotovo – k vyzvednutí' => "Dobrý den, Vaše zakázka {cislo} ({zarizeni}) je hotová a připravená k vyzvednutí. K úhradě {doplatek} Kč. Detaily a stav: {odkaz}\nKonzolák Zlín, tel. {tel}",
            'Čeká na díl' => "Dobrý den, k opravě zakázky {cislo} ({zarizeni}) objednáváme náhradní díl. Jakmile dorazí, pokračujeme a ozveme se. Stav: {odkaz}\nKonzolák Zlín",
            'Diagnostika hotová – cena' => "Dobrý den, u zakázky {cislo} ({zarizeni}) máme hotovou diagnostiku. Cena opravy: {cena} Kč. Můžeme pokračovat? Konzolák Zlín, tel. {tel}",
            'Oprava nerentabilní' => "Dobrý den, oprava zakázky {cislo} ({zarizeni}) se bohužel nevyplatí (cena by převýšila hodnotu zařízení). Zařízení si můžete vyzvednout. Konzolák Zlín, tel. {tel}",
        ];

        $poradi = 10;
        foreach ($vychozi as $nazev => $text) {
            Sablona::firstOrCreate(
                ['typ' => 'whatsapp', 'nazev' => $nazev],
                ['text' => $text, 'poradi' => $poradi, 'aktivni' => true],
            );
            $poradi += 10;
        }
    }

    public function down(): void
    {
        Sablona::where('typ', 'whatsapp')->delete();
    }
};
