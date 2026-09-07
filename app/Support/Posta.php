<?php

namespace App\Support;

use App\Models\Zakazka;
use App\Models\Zakaznik;
use App\Models\Zprava;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Posta
{
    /** Uloží příchozí e-mail (z Cloudflare Email Workeru) a zkusí ho spárovat se zakázkou. */
    public static function ulozPrichozi(array $d): Zprava
    {
        $messageId = $d['messageId'] ?? null;

        if ($messageId && $existujici = Zprava::where('message_id', $messageId)->first()) {
            return $existujici;
        }

        $od = mb_strtolower(trim($d['from'] ?? ''));
        $predmet = $d['subject'] ?? '';
        $telo = $d['text'] ?? '';

        [$zakazka, $zakaznik] = self::najdiZakazku($od, $predmet . ' ' . $telo, $d);

        $zprava = Zprava::create([
            'smer' => 'in',
            'schranka' => mb_strtolower(trim($d['to'] ?? '')) ?: null,
            'od' => $od ?: null,
            'od_jmeno' => $d['fromName'] ?? null,
            'pro' => $d['to'] ?? null,
            'predmet' => $predmet ?: '(bez předmětu)',
            'telo_text' => $telo ?: null,
            'telo_html' => $d['html'] ?? null,
            'message_id' => $messageId,
            'in_reply_to' => $d['inReplyTo'] ?? null,
            'reference' => $d['references'] ?? null,
            'datum' => ! empty($d['date']) ? \Illuminate\Support\Carbon::parse($d['date']) : now(),
            'prilohy' => null,
            'spam' => (bool) ($d['spam'] ?? false),
            'zakazka_id' => $zakazka?->id,
            'zakaznik_id' => $zakaznik?->id ?? $zakazka?->zakaznik_id,
        ]);

        if (! empty($d['attachments']) && is_array($d['attachments'])) {
            $ulozene = self::ulozPrilohy($zprava->id, $d['attachments']);
            if ($ulozene) {
                $zprava->update(['prilohy' => $ulozene]);
            }
        }

        return $zprava;
    }

    /**
     * Uloží binární přílohy z příchozího e-mailu na disk a vrátí jejich seznam
     * (název, mime, velikost, cesta) pro sloupec `prilohy`.
     *
     * @param  array<int, array<string, mixed>>  $attachments
     * @return array<int, array{nazev:string, mime:string, velikost:int, soubor:string}>
     */
    protected static function ulozPrilohy(int $zpravaId, array $attachments): array
    {
        $out = [];
        $celkem = 0;

        foreach (array_values($attachments) as $i => $a) {
            if (! is_array($a)) {
                continue;
            }

            $base64 = $a['content'] ?? $a['data'] ?? $a['contentBytes'] ?? null;
            if (! is_string($base64) || $base64 === '') {
                continue;
            }

            $bin = base64_decode(strtr($base64, '-_', '+/'), true);
            if ($bin === false) {
                $bin = base64_decode($base64, false);
            }
            if (! is_string($bin) || $bin === '') {
                continue;
            }

            $celkem += strlen($bin);
            if (strlen($bin) > 25 * 1024 * 1024 || $celkem > 30 * 1024 * 1024 || $i >= 20) {
                break;
            }

            $nazev = self::bezpecnyNazevSouboru($a['filename'] ?? $a['name'] ?? 'priloha');
            $soubor = "posta/{$zpravaId}/{$i}-{$nazev}";

            Storage::disk('local')->put($soubor, $bin);

            $out[] = [
                'nazev' => $nazev,
                'mime' => (string) ($a['mimeType'] ?? $a['contentType'] ?? $a['mime'] ?? 'application/octet-stream'),
                'velikost' => strlen($bin),
                'soubor' => $soubor,
            ];
        }

        return $out;
    }

    protected static function bezpecnyNazevSouboru(string $nazev): string
    {
        $nazev = basename(str_replace('\\', '/', $nazev));
        $nazev = preg_replace('/[^\p{L}\p{N}._-]+/u', '_', $nazev) ?: 'priloha';
        $nazev = trim($nazev, '_.');

        return mb_substr($nazev !== '' ? $nazev : 'priloha', 0, 120);
    }

    /**
     * Spárování zprávy. K zakázce se přiřadí JEN při jasném signálu:
     *   1) číslo zakázky (SL-2026-0001) v předmětu / textu,
     *   2) skutečná odpověď – hlavička In-Reply-To / References ukazuje na náš
     *      dřívější e-mail, který zakázku měl.
     * Jinak se zpráva naváže pouze na zákazníka (podle e-mailu) a zakázku
     * si přiřadíš ručně tlačítkem „Přiřadit k zakázce".
     *
     * @param  array<string, mixed>  $d  celý příchozí payload (kvůli reply-hlavičkám)
     * @return array{0: ?Zakazka, 1: ?Zakaznik}
     */
    public static function najdiZakazku(string $od, string $text, array $d = []): array
    {
        $zakaznik = $od
            ? Zakaznik::whereRaw('LOWER(email) = ?', [$od])->first()
            : null;

        // 1) číslo zakázky přímo ve zprávě
        if (preg_match('/\bSL-\d{4}-\d{3,}\b/i', $text, $m)) {
            $z = Zakazka::where('cislo', strtoupper($m[0]))->first();
            if ($z) {
                return [$z, $z->zakaznik ?? $zakaznik];
            }
        }

        // 2) odpověď na náš dřívější e-mail
        $reply = collect([
            $d['inReplyTo'] ?? null,
            ...preg_split('/\s+/', trim((string) ($d['references'] ?? ''))) ?: [],
        ])->filter()->unique();

        if ($reply->isNotEmpty()) {
            $puvodni = Zprava::whereIn('message_id', $reply->all())
                ->whereNotNull('zakazka_id')
                ->orderByDesc('id')
                ->first();

            if ($puvodni?->zakazka) {
                return [$puvodni->zakazka, $puvodni->zakazka->zakaznik ?? $zakaznik];
            }
        }

        // 3) nic jistého – jen zákazník, zakázka zůstane nepřiřazená
        return [null, $zakaznik];
    }

    /**
     * Odešle e-mail přes Brevo API (transakční). Vrací true při úspěchu.
     * Zapíše i odchozí Zprávu do historie.
     */
    public static function odesli(
        string $pro,
        string $predmet,
        string $telo,
        string $odesilatel = null,
        ?Zprava $odpovedNa = null,
        ?int $zakazkaId = null,
    ): bool {
        $klic = config('services.brevo.key');
        $odesilatel ??= config('mail.from.address');
        $odesilatelJmeno = config('services.brevo.from_name', config('app.name'));

        $firma = \App\Models\Firma::get();
        $podpis = trim((string) ($firma->podpis_email ?? ''));

        $teloHtml = view('mail.odpoved', [
            'firma' => $firma,
            'telo' => $telo,
            'podpis' => $podpis,
        ])->render();

        $teloText = trim($telo) . ($podpis !== '' ? "\n\n" . $podpis : '');
        $ok = false;

        if ($klic) {
            $payload = [
                'sender' => ['email' => $odesilatel, 'name' => $odesilatelJmeno],
                'to' => [['email' => $pro]],
                'subject' => $predmet,
                'textContent' => $teloText,
                'htmlContent' => $teloHtml,
            ];

            if ($odpovedNa?->message_id) {
                $payload['headers'] = [
                    'In-Reply-To' => $odpovedNa->message_id,
                    'References' => trim(($odpovedNa->reference ? $odpovedNa->reference . ' ' : '') . $odpovedNa->message_id),
                ];
            }

            $res = Http::withHeaders([
                'api-key' => $klic,
                'accept' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', $payload);

            $ok = $res->successful();
        }

        Zprava::create([
            'smer' => 'out',
            'schranka' => $odesilatel,
            'od' => $odesilatel,
            'pro' => $pro,
            'predmet' => $predmet,
            'telo_text' => $telo,
            'message_id' => 'out-' . Str::uuid(),
            'in_reply_to' => $odpovedNa?->message_id,
            'datum' => now(),
            'precteno_at' => now(),
            'zakazka_id' => $zakazkaId ?? $odpovedNa?->zakazka_id,
            'zakaznik_id' => $odpovedNa?->zakaznik_id,
            'spam' => false,
        ]);

        return $ok;
    }
}
