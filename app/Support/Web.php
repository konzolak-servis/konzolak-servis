<?php

namespace App\Support;

use App\Models\Firma;

/**
 * Obsah a pomocné údaje pro veřejný web konzolak.com.
 * Texty jsou zatím tady (draft) – později se přesunou do editovatelného nastavení.
 */
class Web
{
    /** Singleton nastavení firmy (kontakt, adresa, IČO…). */
    public static function firma(): Firma
    {
        return Firma::get();
    }

    /** Telefon ve tvaru pro odkaz tel: / wa.me (jen číslice, s předvolbou). */
    public static function telMezinarodne(): ?string
    {
        $tel = preg_replace('/\D+/', '', (string) self::firma()->telefon);
        if (! $tel) {
            return null;
        }
        if (str_starts_with($tel, '420')) {
            return $tel;
        }

        return '420' . ltrim($tel, '0');
    }

    /** Pilíře důvěry pod hero sekcí. */
    public static function duvera(): array
    {
        return [
            ['ikona' => 'shield', 'titulek' => 'Odpovědnost za vady dle zákona',
                'text' => 'U spotřebitele 24 měsíců (12 u starších zařízení), u firem 3 měsíce.'],
            ['ikona' => 'search', 'titulek' => 'Diagnostika zdarma',
                'text' => 'Při provedení opravy neplatíte za diagnostiku nic.'],
            ['ikona' => 'pin', 'titulek' => 'Osobně ve Zlíně i poštou',
                'text' => 'Přineste zařízení osobně, nebo pošlete Zásilkovnou či poštou.'],
            ['ikona' => 'tag', 'titulek' => 'Cena předem odsouhlasená',
                'text' => 'Opravuji až po vaší e-mailové nebo telefonické domluvě.'],
        ];
    }

    /** 4 kroky „Jak to funguje". */
    public static function kroky(): array
    {
        return [
            ['cislo' => 1, 'titulek' => 'Popíšete závadu',
                'text' => 'Vyplňte formulář, zavolejte nebo napište na WhatsApp. Čím víc detailů, tím lépe.'],
            ['cislo' => 2, 'titulek' => 'Předáte zařízení',
                'text' => 'Osobně ve Zlíně (vždy po domluvě), nebo poštou / Zásilkovnou. Přibalte popis závady a kontakt.'],
            ['cislo' => 3, 'titulek' => 'Diagnostika a cena',
                'text' => 'Ozvu se s výsledkem diagnostiky a cenou. Opravuji až po vašem odsouhlasení.'],
            ['cislo' => 4, 'titulek' => 'Vrácení a doklad',
                'text' => 'Opravené zařízení vydám osobně nebo pošlu zpět. Na práci se vztahuje odpovědnost za vady dle zákona.'],
        ];
    }

    /**
     * Podstránky „Opravujeme". Klíč = slug v URL.
     * `platformy` = klíče z App\Support\Platformy (pro chipy a odkaz do ceníku).
     */
    public static function sluzby(): array
    {
        return [
            'playstation' => [
                'nadpis' => 'Servis Sony PlayStation ve Zlíně',
                'perex' => 'Profesionální servis a opravy herních konzolí Sony PlayStation – od PlayStation 5 po starší generace a ovladače DualSense.',
                'platformy' => ['ps5', 'ps4', 'ps3', 'ps2', 'ps1'],
                'modely' => ['PlayStation 5', 'PlayStation 5 Slim', 'PlayStation 4 / Slim / Pro', 'PlayStation 3', 'DualSense', 'DualShock 4'],
                'opravy' => [
                    'Oprava HDMI konektoru', 'Výměna ventilátoru', 'Čištění a přepastování',
                    'Oprava základní desky', 'Nefunkční mechanika / nečte hry', 'DualSense / DualShock drift páčky',
                    'Nenabíjí se ovladač', 'Přehřívání a samovolné vypínání',
                ],
            ],
            'xbox' => [
                'nadpis' => 'Servis Microsoft Xbox ve Zlíně',
                'perex' => 'Opravy konzolí Microsoft Xbox všech generací – Xbox Series X/S, Xbox One i Xbox 360 – a bezdrátových ovladačů.',
                'platformy' => ['xbox_series', 'xbox_one', 'xbox_360'],
                'modely' => ['Xbox Series X', 'Xbox Series S', 'Xbox One / S / X', 'Xbox 360', 'ovladač Xbox'],
                'opravy' => [
                    'Oprava HDMI konektoru', 'Výměna ventilátoru', 'Výměna optické mechaniky',
                    'Oprava základní desky', 'Čištění a přepastování', 'Drift páčky ovladače',
                    'Nenabíjí / nefunkční ovladač', 'Přehřívání a vypínání',
                ],
            ],
            'nintendo' => [
                'nadpis' => 'Servis Nintendo ve Zlíně',
                'perex' => 'Opravy konzolí Nintendo – Nintendo Switch, Switch OLED, Switch Lite i starší Wii a 3DS – včetně Joy-Conů.',
                'platformy' => ['switch', 'wii', '3ds'],
                'modely' => ['Nintendo Switch', 'Switch OLED', 'Switch Lite', 'Joy-Con', 'Wii / Wii U', 'Nintendo 3DS / DS'],
                'opravy' => [
                    'Joy-Con drift (výměna joysticku)', 'Výměna USB-C konektoru', 'Výměna displeje',
                    'Výměna baterie', 'Nefunkční dotyk', 'Nenabíjí se', 'Nepřipojí se k dokovací stanici',
                    'Čištění a přepastování',
                ],
            ],
            'pc-a-notebooky' => [
                'nadpis' => 'Servis PC a notebooků ve Zlíně',
                'perex' => 'Servis počítačů a notebooků, čištění, výměna komponent, návrh a upgrade herních i pracovních sestav.',
                'platformy' => ['pc', 'notebook'],
                'modely' => ['stolní PC', 'herní sestavy', 'notebooky', 'all-in-one'],
                'opravy' => [
                    'Čištění a výměna teplovodivé pasty', 'Upgrade (SSD, RAM, grafická karta)',
                    'Výměna napájecího zdroje', 'Reinstalace systému', 'Odvirování a zrychlení',
                    'Výměna displeje / klávesnice notebooku', 'Nestartuje / modrá obrazovka',
                    'Návrh nové sestavy na míru',
                ],
            ],
            'ovladace' => [
                'nadpis' => 'Oprava herních ovladačů ve Zlíně',
                'perex' => 'Nejčastější závada ovladačů je „drift" páčky. Opravím ovladače PlayStation, Xbox i Nintendo.',
                'platformy' => ['ovladac'],
                'modely' => ['DualSense', 'DualShock 4', 'ovladač Xbox Series / One', 'Joy-Con', 'Pro Controller'],
                'opravy' => [
                    'Drift páčky – výměna joysticku', 'Nefunkční tlačítka / spouště',
                    'Výměna USB / nabíjecího konektoru', 'Nenabíjí se / nedrží baterie',
                    'Čištění po zalití', 'Prasklý kryt',
                ],
            ],
        ];
    }

    /** Dlaždice „Co opravujeme" na úvodní stránce (odkaz na podstránky). */
    public static function dlazdice(): array
    {
        $out = [];
        foreach (self::sluzby() as $slug => $s) {
            $out[] = [
                'slug' => $slug,
                'nazev' => match ($slug) {
                    'playstation' => 'PlayStation',
                    'xbox' => 'Xbox',
                    'nintendo' => 'Nintendo',
                    'pc-a-notebooky' => 'PC a notebooky',
                    'ovladace' => 'Ovladače',
                    default => $s['nadpis'],
                },
                'perex' => $s['perex'],
            ];
        }

        return $out;
    }

    /** Časté dotazy (draft – zákazník doplní/upraví). */
    public static function faq(): array
    {
        return [
            ['q' => 'Kolik stojí oprava?',
                'a' => 'Orientační ceny běžných úkonů najdete v ceníku. Přesnou cenu řeknu až po diagnostice a opravuji vždy až po vašem odsouhlasení.'],
            ['q' => 'Platí se diagnostika?',
                'a' => 'Pokud opravu provedu, diagnostiku neúčtuji. Pokud se oprava nevyplatí nebo ji odmítnete, může být účtován jen čas diagnostiky (řekneme si předem).'],
            ['q' => 'Jak dlouho oprava trvá?',
                'a' => 'Běžné opravy zvládnu řádově do několika dnů. Pokud je potřeba objednat díl, dám vědět předem.'],
            ['q' => 'Jakou mám záruku na opravu?',
                'a' => 'U spotřebitele se práva z vad uplatňují v zákonné lhůtě 24 měsíců od převzetí opravené věci (u oprav starších zařízení 12 měsíců). U firemních zákazníků je smluvní lhůta 3 měsíce. Reklamace se vyřizuje do 30 dnů.'],
            ['q' => 'Přijdu o data v zařízení?',
                'a' => 'Snažím se data zachovat, ale za data uložená na discích a paměťových kartách nemůžu ručit. Před předáním si prosím vše zálohujte.'],
            ['q' => 'Můžu zařízení poslat poštou?',
                'a' => 'Ano. Zabalte ho do dostatečné výplně, přiložte popis závady a kontakt (telefon, e-mail) a pošlete Zásilkovnou nebo poštou. Adresu najdete v kontaktech.'],
            ['q' => 'Opravujete i konzole koupené v bazaru?',
                'a' => 'Ano. U použitých a starších zařízení jen předem upozorním, pokud je něco na hraně životnosti.'],
            ['q' => 'Vykupujete staré konzole?',
                'a' => 'Připravuji – výkup a prodej použitých konzolí bude na webu brzy. Zatím se domluvíme individuálně telefonicky.'],
        ];
    }
}
