<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Zprava extends Model
{
    protected $table = 'zpravy';

    protected $guarded = [];

    protected $casts = [
        'datum' => 'datetime',
        'precteno_at' => 'datetime',
        'spam' => 'boolean',
        'prilohy' => 'array',
    ];

    protected static function booted(): void
    {
        // Při smazání zprávy ukliď i její uložené přílohy z disku.
        static::deleting(function (Zprava $zprava) {
            if (is_array($zprava->prilohy) && $zprava->prilohy !== []) {
                Storage::disk('local')->deleteDirectory('posta/' . $zprava->id);
            }
        });
    }

    public function zakazka(): BelongsTo
    {
        return $this->belongsTo(Zakazka::class);
    }

    public function zakaznik(): BelongsTo
    {
        return $this->belongsTo(Zakaznik::class);
    }

    public function scopePrichozi($q)
    {
        return $q->where('smer', 'in');
    }

    public function scopeNeprectene($q)
    {
        return $q->where('smer', 'in')->whereNull('precteno_at')->where('spam', false);
    }

    public function jePrectena(): bool
    {
        return $this->precteno_at !== null;
    }

    /** Krátký náhled těla pro seznam. */
    public function nahled(int $delka = 120): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $this->teloCisty()));

        return mb_strlen($text) > $delka ? mb_substr($text, 0, $delka) . '…' : $text;
    }

    /**
     * Tělo zprávy očištěné pro zobrazení: dekóduje HTML entity (některé bulkové
     * e-maily mají v textové alternativě doslova nedekódované kódy jako "&#847;"
     * místo neviditelných znaků, co představují – vypadá to jako rozbitý text) a
     * odstraní neviditelné/mezerové znaky, co se používají jako výplň proti
     * spamovým filtrům (zero-width mezery, spojovací znaky, figure space…).
     */
    public function teloCisty(): string
    {
        $text = $this->telo_text ?: strip_tags((string) $this->telo_html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);

        // Neviditelné znaky (zero-width space/joiner/non-joiner, soft hyphen,
        // combining grapheme joiner, word joiner, BOM) – pryč úplně.
        $text = preg_replace('/[\x{200B}-\x{200F}\x{00AD}\x{034F}\x{2060}\x{FEFF}]/u', '', $text);
        // Řádky, co po odstranění výplně zbyly jen z mezer (obyčejných i "vzácných"
        // typu figure space, thin space…) – sjednotit na prázdný řádek.
        $text = preg_replace('/^[ \t\x{2000}-\x{200A}\x{2007}\x{202F}]+$/mu', '', $text);
        // Vícenásobné mezery (obyčejné i vzácné) v řádku sjednotit na jednu.
        $text = preg_replace('/[ \t\x{2000}-\x{200A}\x{2007}\x{202F}]{2,}/u', ' ', $text);
        // Přemíra prázdných řádků po odstranění výplně.
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        $text = trim($text);

        return $text !== '' ? $text : '(prázdná zpráva)';
    }

    /** Má smysluplný HTML obsah k zobrazení (ne jen prázdné/whitespace tagy)? */
    public function maHtml(): bool
    {
        $html = (string) $this->telo_html;

        return trim($html) !== '' && trim(strip_tags($html)) !== '';
    }

    /**
     * HTML tělo pro vykreslení v izolovaném <iframe sandbox> (bez allow-scripts –
     * prohlížeč z něj nespustí žádný kód). Pro jistotu navíc odstraní <script>
     * tagy a on*="" handlery, kdyby snad sandbox v nějakém prohlížeči selhal.
     */
    public function teloHtmlBezpecne(): string
    {
        $html = (string) $this->telo_html;
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);

        return $html;
    }
}
