<?php

namespace App\Support;

/**
 * Odhad 5. pádu (oslovení) českého křestního jména. Nepokryje 100 % případů –
 * proto jde v nastavení oslovení přepsat ručně.
 */
class Vokativ
{
    /** Časté nepravidelnosti. */
    private const MAPA = [
        'petr' => 'Petře', 'pavel' => 'Pavle', 'karel' => 'Karle', 'josef' => 'Josefe',
        'jan' => 'Jane', 'jakub' => 'Jakube', 'marek' => 'Marku', 'radek' => 'Radku',
        'zdeněk' => 'Zdeňku', 'luděk' => 'Luďku', 'vítek' => 'Vítku', 'aleš' => 'Aleši',
        'tomáš' => 'Tomáši', 'lukáš' => 'Lukáši', 'matěj' => 'Matěji', 'ondřej' => 'Ondřeji',
        'jiří' => 'Jiří', 'ivo' => 'Ivo', 'hugo' => 'Hugo', 'oto' => 'Oto',
        'david' => 'Davide', 'daniel' => 'Danieli', 'gabriel' => 'Gabrieli', 'marcel' => 'Marceli',
        'filip' => 'Filipe', 'martin' => 'Martine', 'michal' => 'Michale', 'adam' => 'Adame',
        'dominik' => 'Dominiku', 'patrik' => 'Patriku', 'erik' => 'Eriku', 'viktor' => 'Viktore',
        'vojtěch' => 'Vojtěchu', 'štěpán' => 'Štěpáne', 'šimon' => 'Šimone', 'václav' => 'Václave',
        'roman' => 'Romane', 'robert' => 'Roberte', 'richard' => 'Richarde', 'radim' => 'Radime',
        'libor' => 'Libore', 'dalibor' => 'Dalibore', 'otakar' => 'Otakare', 'bohumír' => 'Bohumíre',
        'honza' => 'Honzo', 'míra' => 'Míro', 'jára' => 'Járo',
        // ženská
        'jana' => 'Jano', 'lucie' => 'Lucie', 'marie' => 'Marie', 'petra' => 'Petro',
        'tereza' => 'Terezo', 'eliška' => 'Eliško', 'hana' => 'Hano', 'lenka' => 'Lenko',
        'kateřina' => 'Kateřino', 'veronika' => 'Veroniko', 'alena' => 'Aleno', 'martina' => 'Martino',
    ];

    public static function osloveni(?string $jmeno): string
    {
        $jmeno = trim((string) $jmeno);
        if ($jmeno === '') {
            return 'vítej';
        }

        // jen první slovo
        $jmeno = preg_split('/\s+/', $jmeno)[0];
        $lc = mb_strtolower($jmeno);

        if (isset(self::MAPA[$lc])) {
            return self::MAPA[$lc];
        }

        $posl = mb_substr($lc, -1);
        $posl2 = mb_substr($lc, -2);

        // ženská jména na -a  → -o
        if ($posl === 'a') {
            return mb_substr($jmeno, 0, -1) . 'o';
        }
        // jména na -e / -i / -í / -í → beze změny
        if (in_array($posl, ['e', 'i', 'í', 'y', 'ý', 'o', 'u'], true)) {
            return $jmeno;
        }
        // -ek → -ku (Radek → Radku)
        if ($posl2 === 'ek') {
            return mb_substr($jmeno, 0, -2) . 'ku';
        }
        // -ěk → -ňku
        if ($posl2 === 'ěk') {
            return mb_substr($jmeno, 0, -2) . 'ňku';
        }
        // sykavky a měkké → -i
        if (in_array($posl, ['š', 'ž', 'č', 'ř', 'j', 'ď', 'ť', 'ň', 'c'], true)) {
            return $jmeno . 'i';
        }
        // -ch → -chu, -k/-g/-h → -u
        if ($posl2 === 'ch' || in_array($posl, ['k', 'g', 'h'], true)) {
            return $jmeno . 'u';
        }
        // -r → -ře (po souhlásce) / -re (po samohlásce)
        if ($posl === 'r') {
            $pred = mb_substr($lc, -2, 1);

            return $jmeno . (in_array($pred, ['a', 'e', 'i', 'o', 'u', 'y', 'á', 'é', 'í', 'ó', 'ú', 'ý'], true) ? 'e' : 'ře');
        }
        // -el → -le (výpustka e)
        if ($posl2 === 'el') {
            return mb_substr($jmeno, 0, -2) . 'le';
        }
        // ostatní tvrdé souhlásky → -e
        return $jmeno . 'e';
    }
}
