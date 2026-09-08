<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Právní úprava: „záruka 3 měsíce" je vůči spotřebiteli neúčinná.
 * Oprava/úprava věci (§ 2618 obč. zák. → § 2165) = zákonná lhůta 24 měsíců,
 * u použitých/starších zařízení lze zkrátit na 12; 3 měsíce lze sjednat jen B2B.
 * Přepis právních textů na dvojí režim (spotřebitel / podnikatel).
 * Aktualizuje se jen pokud text stále odpovídá původní seedované hodnotě.
 */
return new class extends Migration
{
    public function up(): void
    {
        $firma = DB::table('firma')->first();
        if (! $firma) {
            return;
        }

        $stareProtokol = 'Zákazník tímto potvrzuje, že zboží přebírá ve stavu, v jakém jej do opravy předal, včetně kompletního příslušenství a vyměněných vadných součástí (neplatí u záruční opravy). Výrobek byl po opravě zákazníkovi (dle možností) předveden. Záruka na provedenou opravu je 3 měsíce.';

        $noveProtokol = 'Zákazník tímto potvrzuje, že zboží přebírá ve stavu, v jakém jej do opravy předal, včetně kompletního příslušenství a vyměněných vadných součástí (neplatí u záruční opravy). Výrobek byl po opravě zákazníkovi (dle možností) předveden. '
            . 'Odpovědnost za vady provedené opravy: je-li objednatel spotřebitel, může práva z vadného plnění uplatnit v zákonné lhůtě 24 měsíců od převzetí opravené věci; u oprav použitých nebo starších zařízení činí tato lhůta 12 měsíců. Je-li objednatel podnikatel, činí lhůta pro uplatnění vad 3 měsíce od převzetí. Odpovědnost se vztahuje na vady provedené práce; nevztahuje se na nové závady vzniklé jinak, na poškození vzniklé po převzetí ani na vady, na které byl objednatel předem upozorněn a s opravou přesto souhlasil. Na vestavěné náhradní díly se vztahuje záruka jejich výrobce. Reklamace se vyřizuje do 30 dnů.';

        $stareServisni = 'Zákazník bere na vědomí, že veškeré výše uvedené informace jsou uváděny jako předběžné a během servisního zásahu se mohou změnit. Servis neručí za možné vady HW a SW, které se projeví během servisního zásahu nebo následně a neprokazatelně s ním souvisí. To se týká zejména použitých zařízení. Servis nepřebírá odpovědnost za data ponechaná na médiích. Zálohování dat je v plné odpovědnosti zákazníka. Opravený výrobek se vydává na základě předložení originálu tohoto potvrzení.';

        $noveServisni = $stareServisni
            . ' Odpovědnost za vady provedené opravy se u spotřebitele řídí zákonem (24 měsíců od převzetí opravené věci, u použitých nebo starších zařízení 12 měsíců); je-li objednatel podnikatel, činí lhůta 3 měsíce od převzetí.';

        $update = [];

        if (trim((string) $firma->pravni_text_protokol) === $stareProtokol) {
            $update['pravni_text_protokol'] = $noveProtokol;
        }
        if (trim((string) $firma->pravni_text_servisni_list) === $stareServisni) {
            $update['pravni_text_servisni_list'] = $noveServisni;
        }

        if ($update) {
            DB::table('firma')->where('id', $firma->id)->update($update);
        }
    }

    public function down(): void
    {
        // Právní text se zpět nevrací (návrat k neúčinné formulaci není žádoucí).
    }
};
