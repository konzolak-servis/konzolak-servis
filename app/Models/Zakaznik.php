<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zakaznik extends Model
{
    protected $table = 'zakaznici';

    protected $guarded = [];

    protected $casts = [
        'typ' => 'string',
    ];

    public function zarizeni(): HasMany
    {
        return $this->hasMany(Zarizeni::class, 'zakaznik_id');
    }

    public function zakazky(): HasMany
    {
        return $this->hasMany(Zakazka::class, 'zakaznik_id');
    }

    public function faktury(): HasMany
    {
        return $this->hasMany(Faktura::class, 'zakaznik_id');
    }

    /** Zobrazované jméno – u firmy název firmy, u osoby jméno. */
    public function getNazevAttribute(): string
    {
        return $this->typ === 'firma'
            ? ($this->firma_nazev ?: $this->jmeno ?: 'Bez názvu')
            : ($this->jmeno ?: $this->firma_nazev ?: 'Bez názvu');
    }

    public function getAdresaRadekAttribute(): string
    {
        return trim(collect([$this->ulice, trim(($this->psc ?? '') . ' ' . ($this->mesto ?? ''))])
            ->filter()->implode(', '));
    }

    /** Telefon jen číslice, posledních 9 (bez předvolby +420). */
    public static function normTelefon(?string $t): string
    {
        $d = preg_replace('/\D/', '', (string) $t);

        return strlen($d) > 9 ? substr($d, -9) : $d;
    }

    /** Jméno bez diakritiky, malými písmeny a se seřazenými slovy („Maliňák Adam" = „Adam Maliňák"). */
    public static function normJmeno(?string $s): string
    {
        $slova = preg_split('/\s+/', trim(\Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) $s))), -1, PREG_SPLIT_NO_EMPTY);
        sort($slova);

        return implode(' ', $slova);
    }

    /** Text do výběru zákazníka – jméno + telefon, ať se dají rozlišit. */
    public function getVyberLabelAttribute(): string
    {
        return $this->nazev . ($this->telefon ? ' · ' . $this->telefon : ($this->email ? ' · ' . $this->email : ''));
    }

    /**
     * Existující zákazníci, kteří odpovídají zadaným údajům: stejný telefon, stejný e-mail,
     * nebo stejné jméno (v libovolném pořadí slov, bez ohledu na diakritiku), pokud se telefony neliší.
     */
    public static function podobni(array $data, ?int $krome = null): \Illuminate\Support\Collection
    {
        $tel = self::normTelefon($data['telefon'] ?? null);
        $email = \Illuminate\Support\Str::lower(trim((string) ($data['email'] ?? '')));
        $jmeno = self::normJmeno(($data['typ'] ?? 'osoba') === 'firma' ? ($data['firma_nazev'] ?? $data['jmeno'] ?? '') : ($data['jmeno'] ?? ''));

        if (strlen($tel) < 9 && $email === '' && $jmeno === '') {
            return collect();
        }

        return static::query()->when($krome, fn ($q) => $q->whereKeyNot($krome))->get()
            ->filter(function (Zakaznik $z) use ($tel, $email, $jmeno) {
                $zTel = self::normTelefon($z->telefon);
                if (strlen($tel) >= 9 && $tel === $zTel) {
                    return true;
                }
                if ($email !== '' && $email === \Illuminate\Support\Str::lower(trim((string) $z->email))) {
                    return true;
                }
                $shodaJmena = $jmeno !== '' && ($jmeno === self::normJmeno($z->jmeno) || $jmeno === self::normJmeno($z->firma_nazev));

                // stejné jméno, ale jiný telefon = nejspíš jiný člověk
                return $shodaJmena && ! (strlen($tel) >= 9 && strlen($zTel) >= 9 && $tel !== $zTel);
            })
            ->sortByDesc(fn ($z) => $z->zakazky()->count())
            ->values();
    }

    /**
     * Nový zákazník z formuláře – když už existuje (viz podobni), vrátí existujícího
     * a doplní mu chybějící údaje. Vrací [Zakaznik, bool $novy].
     */
    public static function vytvorNeboNajdi(array $data): array
    {
        $vynutit = ! empty($data['vytvorit_noveho']);
        unset($data['vytvorit_noveho']);

        if (! $vynutit && ($z = self::podobni($data)->first())) {
            $doplnit = collect($data)->filter(fn ($v, $k) => filled($v) && blank($z->{$k}) && $k !== 'typ')->all();
            if ($doplnit) {
                $z->update($doplnit);
            }

            return [$z, false];
        }

        return [static::create($data), true];
    }

    /** Hledání ve výběru zákazníka: každé slovo musí sedět na jméno, firmu, telefon nebo e-mail. */
    public static function hledej(string $hledat, int $limit = 50): array
    {
        $slova = preg_split('/\s+/', trim($hledat), -1, PREG_SPLIT_NO_EMPTY);

        return static::query()
            ->where(function ($q) use ($slova) {
                foreach ($slova as $s) {
                    $cislice = preg_replace('/\D/', '', $s);
                    $q->where(function ($w) use ($s, $cislice) {
                        $w->where('jmeno', 'like', "%{$s}%")
                            ->orWhere('firma_nazev', 'like', "%{$s}%")
                            ->orWhere('email', 'like', "%{$s}%");
                        if (strlen($cislice) >= 3) {
                            $w->orWhereRaw("REPLACE(REPLACE(telefon, ' ', ''), '+', '') LIKE ?", ["%{$cislice}%"]);
                        }
                    });
                }
            })
            ->orderBy('jmeno')->limit($limit)->get()
            ->mapWithKeys(fn (Zakaznik $z) => [$z->id => $z->vyber_label])->all();
    }

    /**
     * Sloučí jiné zákazníky do tohoto: převede jejich zakázky, zařízení, faktury, nabídky a zprávy,
     * doplní chybějící údaje a duplicity smaže.
     */
    public function sloucit(iterable $ostatni): int
    {
        $pocet = 0;

        \Illuminate\Support\Facades\DB::transaction(function () use ($ostatni, &$pocet) {
            foreach ($ostatni as $o) {
                if ($o->id === $this->id) {
                    continue;
                }
                foreach (['zakazky', 'zarizeni', 'faktury', 'nabidky', 'zpravy'] as $tabulka) {
                    if (\Illuminate\Support\Facades\Schema::hasColumn($tabulka, 'zakaznik_id')) {
                        \Illuminate\Support\Facades\DB::table($tabulka)->where('zakaznik_id', $o->id)->update(['zakaznik_id' => $this->id]);
                    }
                }
                foreach (['jmeno', 'firma_nazev', 'ico', 'dic', 'telefon', 'email', 'ulice', 'psc', 'mesto'] as $pole) {
                    if (blank($this->{$pole}) && filled($o->{$pole})) {
                        $this->{$pole} = $o->{$pole};
                    }
                }
                if (filled($o->poznamka) && ! str_contains((string) $this->poznamka, $o->poznamka)) {
                    $this->poznamka = trim($this->poznamka . "\n" . $o->poznamka);
                }
                $o->delete();
                $pocet++;
            }
            $this->save();
        });

        return $pocet;
    }

    /**
     * Najde zákazníka (firmu) podle IČO; když ještě není, založí ho z údajů v ARES.
     * Vrací [Zakaznik, bool $novy]. Při chybě registru vyhodí \RuntimeException.
     */
    public static function najdiNeboZalozPodleIco(string $ico): array
    {
        $norm = \App\Support\Ares::normalizuj($ico);
        if ($norm && ($existujici = static::where('ico', $norm)->orWhere('ico', ltrim($norm, '0'))->first())) {
            return [$existujici, false];
        }

        $a = \App\Support\Ares::najdi($ico);

        // mohl už být uložený s jiným zápisem IČO (mezery apod.)
        $existujici = static::whereRaw("REPLACE(ico, ' ', '') = ?", [$a['ico']])->first();
        if ($existujici) {
            return [$existujici, false];
        }

        return [static::create([
            'typ' => 'firma',
            'firma_nazev' => $a['nazev'],
            'ico' => $a['ico'],
            'dic' => $a['dic'],
            'ulice' => $a['ulice'],
            'psc' => $a['psc'],
            'mesto' => $a['mesto'],
        ]), true];
    }
}
