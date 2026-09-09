<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Na přání majitele – návrat právního textu k původní kratší verzi
 * („Záruka na provedenou opravu je 3 měsíce."). Přepíše se jen pokud text
 * odpovídá dvojí formulaci z migrace 2026_09_08_000001.
 */
return new class extends Migration
{
    public function up(): void
    {
        $firma = DB::table('firma')->first();
        if (! $firma) {
            return;
        }

        $protokolDvoji = 'Zákazník tímto potvrzuje, že zboží přebírá ve stavu, v jakém jej do opravy předal, včetně kompletního příslušenství a vyměněných vadných součástí (neplatí u záruční opravy). Výrobek byl po opravě zákazníkovi (dle možností) předveden. Odpovědnost za vady provedené opravy: je-li objednatel spotřebitel, může práva z vadného plnění uplatnit v zákonné lhůtě 24 měsíců od převzetí opravené věci; u oprav použitých nebo starších zařízení činí tato lhůta 12 měsíců. Je-li objednatel podnikatel, činí lhůta pro uplatnění vad 3 měsíce od převzetí. Odpovědnost se vztahuje na vady provedené práce; nevztahuje se na nové závady vzniklé jinak, na poškození vzniklé po převzetí ani na vady, na které byl objednatel předem upozorněn a s opravou přesto souhlasil. Na vestavěné náhradní díly se vztahuje záruka jejich výrobce. Reklamace se vyřizuje do 30 dnů.';

        $protokolPuvodni = 'Zákazník tímto potvrzuje, že zboží přebírá ve stavu, v jakém jej do opravy předal, včetně kompletního příslušenství a vyměněných vadných součástí (neplatí u záruční opravy). Výrobek byl po opravě zákazníkovi (dle možností) předveden. Záruka na provedenou opravu je 3 měsíce.';

        $servisniPuvodni = 'Zákazník bere na vědomí, že veškeré výše uvedené informace jsou uváděny jako předběžné a během servisního zásahu se mohou změnit. Servis neručí za možné vady HW a SW, které se projeví během servisního zásahu nebo následně a neprokazatelně s ním souvisí. To se týká zejména použitých zařízení. Servis nepřebírá odpovědnost za data ponechaná na médiích. Zálohování dat je v plné odpovědnosti zákazníka. Opravený výrobek se vydává na základě předložení originálu tohoto potvrzení.';

        $doplnek = ' Odpovědnost za vady provedené opravy se u spotřebitele řídí zákonem (24 měsíců od převzetí opravené věci, u použitých nebo starších zařízení 12 měsíců); je-li objednatel podnikatel, činí lhůta 3 měsíce od převzetí.';

        $update = [];

        if (trim((string) $firma->pravni_text_protokol) === $protokolDvoji) {
            $update['pravni_text_protokol'] = $protokolPuvodni;
        }

        if (str_ends_with(trim((string) $firma->pravni_text_servisni_list), trim($doplnek))) {
            $update['pravni_text_servisni_list'] = $servisniPuvodni;
        }

        if ($update) {
            DB::table('firma')->where('id', $firma->id)->update($update);
        }
    }

    public function down(): void
    {
        // Zpět se nevrací.
    }
};
