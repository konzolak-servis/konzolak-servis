<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Jak jsou data zálohovaná</x-slot>

        <div class="ks-info">
            <p><strong>1. Automaticky na serveru</strong> – každou noc ve 3:00 se udělá ZIP (databáze + nahrané soubory). Drží se posledních 21.</p>
            <p><strong>2. Offline kopie</strong> – tlačítkem <em>„Stáhnout poslední zálohu"</em> si stáhneš aktuální ZIP do počítače. Doporučujeme 1× týdně a občas přehrát na USB disk.</p>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Zálohy na serveru</x-slot>

        @if (empty($this->seznam))
            <p class="ks-empty">Zatím nebyla vytvořena žádná záloha. Použij tlačítko „Vytvořit zálohu nyní".</p>
        @else
            <table class="ks-tbl">
                <thead>
                    <tr>
                        <th>Soubor</th>
                        <th class="num">Velikost</th>
                        <th class="num">Vytvořeno</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->seznam as $z)
                        <tr>
                            <td>{{ $z['nazev'] }}</td>
                            <td class="num">{{ number_format($z['velikost'], 2, ',', ' ') }} MB</td>
                            <td class="num">{{ $z['datum']->format('d.m.Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>

    <style>
        .ks-info p { margin: .35rem 0; font-size: .9rem; }
        .ks-empty { font-size: .9rem; color: #6b7280; }
        .ks-tbl { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .ks-tbl th { text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .03em;
            color: #6b7280; padding: .4rem .5rem; border-bottom: 1px solid #e5e7eb; }
        .ks-tbl td { padding: .45rem .5rem; border-bottom: 1px solid #f1f2f4; }
        .ks-tbl .num { text-align: right; white-space: nowrap; }
        :is(.dark) .ks-tbl th { border-color: #374151; }
        :is(.dark) .ks-tbl td { border-color: #2b3440; }
    </style>
</x-filament-panels::page>
