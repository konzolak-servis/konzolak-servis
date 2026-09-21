@extends('web.layout')
@section('title', 'Reference a recenze | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('desc', 'Co říkají zákazníci servisu Konzolák Zlín – hodnocení a recenze.')

@php
    $fbZdroj = $zdroje['facebook'] ?? null;
    // Pole se sestavuje tady (ne přímo v {!! !!}) – Blade jinak literální '@context'
    // v poli spletl s vlastní direktivou @context a rozbil výstup (viz layout.blade.php).
    $schemaRecenze = $fbZdroj && $fbZdroj['procento'] !== null ? [
        '@context' => 'https://schema.org',
        '@type' => 'ElectronicsStore',
        'name' => $firma->nazev ?: 'Konzolák Zlín',
        'url' => route('web.home'),
        'aggregateRating' => [
            '@type' => 'AggregateRating',
            'ratingValue' => '5',
            'bestRating' => '5',
            'reviewCount' => (string) $fbZdroj['pocet'],
        ],
        'review' => collect($recenze)->map(fn ($r) => [
            '@type' => 'Review',
            'author' => ['@type' => 'Person', 'name' => $r['jmeno']],
            'datePublished' => $r['datum_iso'] ?? null,
            'reviewBody' => $r['text'],
            'reviewRating' => ['@type' => 'Rating', 'ratingValue' => '5', 'bestRating' => '5'],
        ])->all(),
    ] : null;
@endphp

@push('head')
    @if($schemaRecenze)
        {{-- Recenze jsou na téhle stránce opravdu zobrazené jako text (viz níže) –
             Google to u strukturovaných dat o recenzích vyžaduje. --}}
        <script type="application/ld+json">{!! json_encode($schemaRecenze, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif
@endpush

@section('body')
<section class="hero">
    <div class="wrap hero__in">
        <h1>Reference</h1>
        <p>Hodnocení a recenze zákazníků servisu Konzolák Zlín.</p>

        <div class="stats" style="margin-top:1.6rem">
            @foreach($zdroje as $z)
                <div>
                    <b>{{ $z['procento'] !== null ? $z['procento'] . ' %' : '—' }}</b>
                    <span>{{ $z['nazev'] }}{{ $z['procento'] !== null ? ' doporučuje' : '' }}</span>
                </div>
            @endforeach
        </div>

        <div class="hero__cta" style="margin-top:1.4rem;gap:.6rem">
            @foreach($zdroje as $z)
                <a class="btn btn--ghost btn--sm" href="{{ $z['url'] }}" target="_blank" rel="noopener">
                    {{ $z['nazev'] }}{{ $z['popis'] ? ' · ' . $z['popis'] : '' }}
                </a>
            @endforeach
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div style="display:flex;flex-wrap:wrap;gap:1.15rem;justify-content:{{ count($recenze) < 3 ? 'center' : 'flex-start' }}">
            @foreach($recenze as $r)
                @php($z = $zdroje[$r['zdroj']] ?? null)
                <div class="card" style="flex:1 1 300px;max-width:380px">
                    <p style="color:var(--gold-300);font-size:1.1rem;margin:0 0 .4rem">★★★★★</p>
                    <p><em>„{{ $r['text'] }}"</em></p>
                    <p style="color:#8a8578;margin:0">
                        — {{ $r['jmeno'] }}, {{ $r['datum'] }}
                        @if($z)<br><span style="font-size:.82rem">přes {{ $z['nazev'] }}</span>@endif
                    </p>
                </div>
            @endforeach
        </div>
        <p style="text-align:center;margin-top:2rem">
            <a href="{{ $facebookUrl }}/reviews" target="_blank" rel="noopener"
                style="color:var(--gold-300);font-weight:600;text-decoration:none;font-size:.92rem">
                Zobrazit všechny recenze na Facebooku →
            </a>
        </p>
    </div>
</section>
@endsection
