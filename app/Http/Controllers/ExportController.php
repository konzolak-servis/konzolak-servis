<?php

namespace App\Http\Controllers;

use App\Models\Nakup;
use App\Models\PenezniDenik;
use App\Models\SkladPolozka;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /** Stav skladu – pro vlastní přehled i inventuru. */
    public function sklad(): StreamedResponse
    {
        $rows = SkladPolozka::orderBy('nazev')->get()->map(fn ($s) => [
            $s->nazev,
            $s->kod,
            $s->kategorie,
            $this->cislo($s->mnozstvi_skladem, 3),
            $this->cislo($s->min_mnozstvi, 3),
            $this->cislo($s->cena_ks_prumer),
            $this->cislo($s->mnozstvi_skladem * $s->cena_ks_prumer),
            $s->umisteni,
            $s->pod_minimem ? 'ANO' : '',
        ]);

        return $this->csv('sklad_' . now()->format('Y-m-d'), [
            'Název', 'Kód', 'Kategorie', 'Množství skladem', 'Min. množství',
            'Cena/ks (Ø)', 'Hodnota skladem', 'Umístění', 'Pod minimem',
        ], $rows);
    }

    /** Náklady = nákupy materiálu a vybavení (výdaje) za rok. */
    public function naklady(?int $rok = null): StreamedResponse
    {
        $rok ??= (int) now()->year;

        $rows = Nakup::with('polozky')
            ->whereYear('datum', $rok)
            ->orderBy('datum')
            ->get()
            ->flatMap(fn (Nakup $n) => $n->polozky->map(fn ($p) => [
                optional($n->datum)->format('d.m.Y'),
                $n->cislo,
                $n->dodavatel,
                $p->nazev,
                $this->cislo($p->mnozstvi_ks, 3),
                $this->cislo($p->cena_ks),
                $this->cislo($p->castka_celkem),
                $n->naskladneno ? 'ano' : 'ne',
            ]));

        return $this->csv("naklady_{$rok}", [
            'Datum', 'Doklad', 'Dodavatel', 'Položka', 'Počet ks', 'Cena/ks', 'Částka celkem', 'Naskladněno',
        ], $rows);
    }

    /** Peněžní deník za rok – volitelně jen jedna skupina dokladů. */
    public function penezniDenik(?int $rok = null): StreamedResponse
    {
        $rok ??= (int) now()->year;
        $skupina = request()->query('skupina');

        $rows = PenezniDenik::whereYear('datum', $rok)
            ->when($skupina, fn ($q) => $q->skupina($skupina))
            ->orderBy('datum')->orderBy('id')
            ->get()
            ->map(fn ($d) => [
                $d->datum->format('d.m.Y'),
                $d->typ === 'prijem' ? 'Příjem' : 'Výdej',
                $d->popis,
                $d->kategorie,
                $d->doklad()[0] ?? '',
                match ($d->zpusob) { 'hotove' => 'Hotově', 'ucet' => 'Na účet', default => '' },
                $d->typ === 'prijem' ? $this->cislo($d->castka) : '',
                $d->typ === 'vydej' ? $this->cislo($d->castka) : '',
            ]);

        $nazev = 'penezni_denik_' . $rok . ($skupina ? '_' . $skupina : '');

        return $this->csv($nazev, [
            'Datum', 'Typ', 'Popis', 'Kategorie', 'Doklad', 'Platba', 'Příjem (Kč)', 'Výdej (Kč)',
        ], $rows);
    }

    /** Podklad pro daňové přiznání – měsíční a roční souhrn příjmů a výdajů. */
    public function danovePriznani(?int $rok = null): StreamedResponse
    {
        $rok ??= (int) now()->year;

        $data = PenezniDenik::whereYear('datum', $rok)->get();

        $rows = collect(range(1, 12))->map(function ($m) use ($data) {
            $mesic = $data->filter(fn ($d) => (int) $d->datum->month === $m);
            $prijem = $mesic->where('typ', 'prijem')->sum('castka');
            $vydej = $mesic->where('typ', 'vydej')->sum('castka');

            return [
                sprintf('%02d', $m),
                $this->cislo($prijem),
                $this->cislo($vydej),
                $this->cislo($prijem - $vydej),
            ];
        });

        $prijemR = $data->where('typ', 'prijem')->sum('castka');
        $vydejR = $data->where('typ', 'vydej')->sum('castka');
        $rows->push(['CELKEM', $this->cislo($prijemR), $this->cislo($vydejR), $this->cislo($prijemR - $vydejR)]);

        return $this->csv("danove_priznani_{$rok}", [
            'Měsíc', 'Příjmy (Kč)', 'Výdaje (Kč)', 'Rozdíl (Kč)',
        ], $rows);
    }

    // ---- pomocné --------------------------------------------------------

    private function cislo($v, int $des = 2): string
    {
        return number_format((float) $v, $des, ',', '');
    }

    private function csv(string $nazev, array $hlavicka, $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($hlavicka, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM pro Excel
            fputcsv($out, $hlavicka, ';');
            foreach ($rows as $r) {
                fputcsv($out, $r, ';');
            }
            fclose($out);
        }, $nazev . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
