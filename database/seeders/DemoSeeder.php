<?php

namespace Database\Seeders;

use App\Models\CenikPolozka;
use App\Models\Faktura;
use App\Models\Nabidka;
use App\Models\Nakup;
use App\Models\Obchod;
use App\Models\SkladPolozka;
use App\Models\Zakaznik;
use App\Models\Zakazka;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Ukázková data pro předvedení kalendáře / nástěnky / skladu / financí.
 * Zakázky se navěsí na skutečné (naimportované) zákazníky.
 *
 * Smazání ukázek:  php artisan tinker --execute="App\Models\Zakazka::where('poznamka','UKÁZKA')->delete()"
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // --- Sklad + nákupy ---
        $hall = SkladPolozka::firstOrCreate(['nazev' => 'HALL modul (ovladač)'], ['kategorie' => 'Potenciometr', 'min_mnozstvi' => 4]);
        $hdmiPs4 = SkladPolozka::firstOrCreate(['nazev' => 'HDMI konektor PS4'], ['kategorie' => 'HDMI IC', 'min_mnozstvi' => 2]);
        $hdmiPs5 = SkladPolozka::firstOrCreate(['nazev' => 'HDMI konektor PS5'], ['kategorie' => 'HDMI IC', 'min_mnozstvi' => 2]);
        $pasta = SkladPolozka::firstOrCreate(['nazev' => 'Teplovodivá pasta (1 g)'], ['kategorie' => 'Ostatní', 'min_mnozstvi' => 5]);
        $laser = SkladPolozka::firstOrCreate(['nazev' => 'Laser KEM-497AAA (PS5)'], ['kategorie' => 'Laser', 'min_mnozstvi' => 1]);

        if (Nakup::count() === 0) {
            foreach ([
                [-95, 'AliExpress', [[$hall, 20, 1280], [$hdmiPs4, 10, 620], [$pasta, 10, 590]]],
                [-55, 'Konzoliste', [[$laser, 3, 3600], [$hdmiPs5, 8, 720]]],
                [-20, 'Hadex', [[$pasta, 10, 640], [$hall, 15, 990]]],
            ] as [$dni, $dod, $pol]) {
                $n = Nakup::create(['dodavatel' => $dod, 'datum' => now()->addDays($dni)]);
                foreach ($pol as [$sk, $ks, $castka]) {
                    $n->polozky()->create(['sklad_polozka_id' => $sk->id, 'nazev' => $sk->nazev, 'mnozstvi_ks' => $ks, 'castka_celkem' => $castka]);
                }
                $n->naskladnit();
            }
        }

        // --- Aktuální rozpracované zakázky (kalendář / nástěnka) ---
        $rozpracovane = [
            ['Hořinka Peter', 'ceka_na_dil', -6, null, 'Nejde obraz, HDMI.', true, 'HDMI konektor PS5 – Allegro'],
            ['Vitásek Tomáš', 'diagnostika', -3, null, 'Zahřívá se, vypíná.', false, null],
            ['Procházka Jiří', 'prijato', -1, null, 'Drift levé páčky.', false, null],
            ['Květák Josef', 'hotovo', -4, -1, 'Konzole je hlučná.', false, null],
        ];

        foreach ($rozpracovane as [$jmeno, $stav, $od, $do, $zav, $dilObj, $dilInfo]) {
            $this->zakazka($jmeno, $stav, $od, $do, $zav, ['dil_objednany' => $dilObj, 'dil_info' => $dilInfo]);
        }

        // --- Historie vydaných oprav za posledních ~4 měsíce (finance) ---
        $cenik = CenikPolozka::pluck('cena', 'nazev');
        $prace = [
            ['Čištění konzole, diagnostika a přepastování', 500],
            ['Oprava HDMI konektoru', 1200],
            ['Diagnostika a čištění ovladače', 400],
            ['Oprava potenciometru (driftování) / 1 ks', 250],
            ['Výměna SSD 1 TB včetně instalace OS – PS4', 2600],
            ['Rozšíření interní paměti PS5 – 1 TB', 4500],
            ['Oprava interního ventilátoru', 800],
            ['Oprava USB-C konektoru Nintendo Switch / Lite', 1000],
        ];

        $zakaznici = Zakaznik::where('typ', 'osoba')->pluck('jmeno')->all();
        if ($zakaznici) {
            $i = 0;
            for ($d = 115; $d >= 8; $d -= random_int(5, 11)) {
                $jmeno = $zakaznici[$i % count($zakaznici)];
                [$nazevPrace, $fallback] = $prace[$i % count($prace)];
                $cena = (float) ($cenik[$nazevPrace] ?? $fallback);
                $i++;

                $z = $this->zakazka($jmeno, 'vydano', -$d, -$d + random_int(0, 3), $nazevPrace, []);
                if ($z && $z->polozky()->count() === 0) {
                    $z->polozky()->create([
                        'typ' => 'prace', 'uctovat' => true,
                        'nazev' => $nazevPrace, 'mnozstvi' => 1, 'cena_ks' => $cena,
                    ]);
                }
            }
        }

        // --- Faktury (firmy) – uhrazené, promítnou se do příjmů ---
        if (Faktura::count() === 0) {
            $fakt = [
                ['Agentura Devět měsíců s.r.o.', -78, [['PS4 Slim CUH-2116A', 'Výměna SSD + instalace OS', 1600], ['Práce u zákazníka 2 h', '', 800]]],
                ['HAISET s.r.o.', -40, [['', 'Servis a čištění 3 ks PC', 2400], ['', 'Reinstalace OS a Office', 1500]]],
                ['Společenstvi vlastníků č.p. 4905-6', -12, [['', 'Oprava a čištění PC, reinstalace', 2700]]],
            ];
            foreach ($fakt as [$jmenoFirmy, $dni, $radky]) {
                $zk = Zakaznik::where('firma_nazev', 'like', substr($jmenoFirmy, 0, 12) . '%')->first();
                if (! $zk) {
                    continue;
                }
                $f = Faktura::create([
                    'zakaznik_id' => $zk->id,
                    'datum_vystaveni' => now()->addDays($dni)->toDateString(),
                    'forma_uhrady' => 'převodem',
                ]);
                foreach ($radky as [$zar, $popis, $cena]) {
                    $f->polozky()->create([
                        'zarizeni_text' => $zar,
                        'popis' => $popis ?: 'Práce',
                        'mnozstvi' => 1,
                        'cena' => $cena,
                    ]);
                }
                $f->update(['uhrazeno' => true, 'datum_uhrady' => now()->addDays($dni + 8)->toDateString()]);
            }
        }

        // --- Nabídky (sestavy PC) ---
        if (Nabidka::count() === 0) {
            $nab = [
                ['Souček Martin', -60, 'nova', [
                    ['Základní deska', 'GIGABYTE B760 GAMING X', 3300],
                    ['Procesor', 'Intel Core i5-14400F', 5200],
                    ['Operační paměť', 'Kingston FURY 32 GB DDR4', 1900],
                    ['Úložiště', 'SSD Kingston KC3000 1 TB', 2100],
                    ['Práce', 'Sestavení, instalace, test', 1500],
                ]],
                ['Plsek Martin', -18, 'prijata', [
                    ['Grafická karta', 'MSI RTX 4070 SUPER', 17500],
                    ['Zdroj', 'be quiet! 750 W', 2200],
                    ['Práce', 'Upgrade a přeinstalace', 900],
                ]],
            ];
            foreach ($nab as [$jmeno, $dni, $stav, $polozky]) {
                $zk = Zakaznik::where('jmeno', $jmeno)->first();
                if (! $zk) {
                    continue;
                }
                $n = Nabidka::create([
                    'zakaznik_id' => $zk->id,
                    'datum' => now()->addDays($dni)->toDateString(),
                    'platnost_do' => now()->addDays($dni + 14)->toDateString(),
                    'stav' => $stav,
                ]);
                foreach ($polozky as [$skupina, $popis, $cena]) {
                    $n->polozky()->create([
                        'skupina' => $skupina, 'popis' => $popis, 'mnozstvi' => 1,
                        'varianta' => 'nova', 'cena_nova' => $cena,
                        'naklad_interni' => round($cena * 0.88),
                        'eshop_url' => 'https://www.alza.cz/',
                    ]);
                }
            }
        }

        // --- Šablony textů ---
        if (\App\Models\Sablona::count() === 0) {
            \App\Models\Sablona::insert([
                ['typ' => 'zavada', 'nazev' => 'Konzole je hlučná', 'text' => 'Konzole je hlučná, zvýšené otáčky ventilátoru.', 'poradi' => 1, 'aktivni' => true, 'created_at' => now(), 'updated_at' => now()],
                ['typ' => 'zavada', 'nazev' => 'Nejde obraz (HDMI)', 'text' => 'Po zapnutí nejde obraz, podezření na HDMI konektor.', 'poradi' => 2, 'aktivni' => true, 'created_at' => now(), 'updated_at' => now()],
                ['typ' => 'zavada', 'nazev' => 'Drift páčky', 'text' => 'Ovladač – samovolný pohyb (drift) analogové páčky.', 'poradi' => 3, 'aktivni' => true, 'created_at' => now(), 'updated_at' => now()],
                ['typ' => 'reseni', 'nazev' => 'Čištění a přepastování', 'text' => 'Konzole rozebrána, vyčištěna, přepastováno kvalitní teplovodivou pastou. Otestováno, funkční.', 'poradi' => 1, 'aktivni' => true, 'created_at' => now(), 'updated_at' => now()],
                ['typ' => 'reseni', 'nazev' => 'Výměna HDMI konektoru', 'text' => 'Vyměněn HDMI konektor, zkontrolovány cesty na základní desce. Obraz OK ve všech režimech.', 'poradi' => 2, 'aktivni' => true, 'created_at' => now(), 'updated_at' => now()],
                ['typ' => 'reseni', 'nazev' => 'Výměna HALL modulu', 'text' => 'Vyměněn potenciometr / HALL modul analogové páčky, kalibrováno. Drift odstraněn.', 'poradi' => 3, 'aktivni' => true, 'created_at' => now(), 'updated_at' => now()],
                ['typ' => 'poznamka', 'nazev' => 'Doporučení výměny HDD', 'text' => 'Doporučena výměna HDD za SSD kvůli rychlosti a hlučnosti.', 'poradi' => 1, 'aktivni' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // --- Přístupy a hesla (ukázka struktury, bez reálných hesel) ---
        if (\App\Models\Pristup::count() === 0) {
            \App\Models\Pristup::create([
                'nazev' => 'Doména konzolak.com', 'kategorie' => 'domena',
                'url' => 'https://dash.cloudflare.com', 'uzivatel' => 'zdenek.gresek@gmail.com',
                'platnost_do' => now()->addMonths(11), 'pripominka_dni' => 30, 'castka' => 260,
                'poznamka' => 'Registrátor Cloudflare, obnova automaticky.',
            ]);
            \App\Models\Pristup::create([
                'nazev' => 'Wedos VPS (server)', 'kategorie' => 'hosting',
                'url' => 'https://client.wedos.com', 'uzivatel' => 'konzolak',
                'platnost_do' => now()->addDays(9), 'pripominka_dni' => 14, 'castka' => 250,
                'poznamka' => 'Měsíční platba. SSH klíč v trezoru.',
            ]);
            \App\Models\Pristup::create([
                'nazev' => 'E-mail servis@konzolak.com', 'kategorie' => 'email',
                'url' => 'https://webmail.konzolak.com', 'uzivatel' => 'servis@konzolak.com',
            ]);
        }

        // --- Objednávky dílů ---
        if (\App\Models\ObjednavkaDilu::count() === 0) {
            $cekaNaDil = Zakazka::where('stav', 'ceka_na_dil')->first();
            \App\Models\ObjednavkaDilu::create([
                'dodavatel' => 'Allegro', 'nazev_dilu' => 'HDMI konektor PS5', 'mnozstvi' => 2,
                'cena_odhad' => 180, 'datum_objednavky' => now()->subDays(4),
                'ocekavane_doruceni' => now()->addDays(2), 'stav' => 'objednano',
                'zakazka_id' => $cekaNaDil?->id,
            ]);
            \App\Models\ObjednavkaDilu::create([
                'dodavatel' => 'Konzoliste', 'nazev_dilu' => 'Laser KEM-497AAA', 'mnozstvi' => 1,
                'cena_odhad' => 1200, 'datum_objednavky' => now()->subDays(12),
                'doruceno_datum' => now()->subDays(5), 'stav' => 'dorazilo',
            ]);
        }

        // --- Výkup / prodej použitého zboží ---
        if (Obchod::count() === 0) {
            $vykup = Obchod::create([
                'typ' => 'vykup', 'datum' => now()->subDays(30), 'kategorie' => 'ovladac',
                'nazev' => 'PS5 DualSense bílý', 'seriove_cislo' => 'CFI-ZCT1W-2211',
                'stav_popis' => 'Mírný drift levé páčky, jinak OK, s USB kabelem.',
                'cena' => 600, 'protistrana_jmeno' => 'Jan Novák', 'protistrana_kontakt' => '777 111 222',
            ]);
            $vykup->vyridit();

            $vykup2 = Obchod::create([
                'typ' => 'vykup', 'datum' => now()->subDays(14), 'kategorie' => 'konzole',
                'nazev' => 'Xbox Series S 512 GB', 'seriove_cislo' => 'XSS-88213',
                'stav_popis' => 'Plně funkční, 1 ovladač, bez krabice.',
                'cena' => 3200, 'protistrana_jmeno' => 'Petr Malý', 'protistrana_kontakt' => 'petr.maly@email.cz',
            ]);
            $vykup2->vyridit();

            $prodej = Obchod::create([
                'typ' => 'prodej', 'datum' => now()->subDays(6), 'kategorie' => 'ovladac',
                'nazev' => 'PS5 DualSense bílý (repasovaný)', 'cena' => 1100,
                'sklad_polozka_id' => $vykup->fresh()->sklad_polozka_id,
                'stav_popis' => 'Vyměněný HALL modul, vyčištěno.',
                'protistrana_jmeno' => 'Lucie Horká',
            ]);
            $prodej->vyridit();
        }
    }

    private function zakazka(string $jmeno, string $stav, int $odDni, ?int $doDni, string $zavada, array $extra): ?Zakazka
    {
        $zak = Zakaznik::where('jmeno', $jmeno)->first();
        if (! $zak) {
            return null;
        }

        $datum = Carbon::now()->addDays($odDni)->toDateString();

        // idempotence – nezakládat duplicitně stejný den
        $exist = Zakazka::where('zakaznik_id', $zak->id)
            ->where('poznamka', 'UKÁZKA')
            ->where('datum_prijeti', $datum)
            ->first();
        if ($exist) {
            return $exist;
        }

        return Zakazka::create(array_merge([
            'zakaznik_id' => $zak->id,
            'zarizeni_id' => $zak->zarizeni()->first()?->id,
            'stav' => $stav,
            'datum_prijeti' => $datum,
            'datum_vyrizeni' => $doDni !== null ? Carbon::now()->addDays($doDni)->toDateString() : null,
            'popis_zavady' => $zavada,
            'poznamka' => 'UKÁZKA',
        ], $extra));
    }
}
