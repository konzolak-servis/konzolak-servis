@extends('web.layout')
@section('title', 'Zápůjčka herních konzolí ve Zlíně | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('desc', 'Půjčení PlayStation 5 a ovladačů ve Zlíně na den nebo víkend. Náhradní konzole po dobu opravy zdarma.')

@section('body')
@php
    $K = \App\Support\Web::kontakty();
    $kc = fn ($v) => number_format((float) $v, 0, ',', ' ') . ' Kč';
@endphp
<section class="hero">
    <div class="wrap hero__in">
        <h1>Zápůjčka konzolí</h1>
        <p>Potřebujete konzoli na víkend, na návštěvu nebo jako náhradu, než opravím tu vaši?
            Půjčím vám PlayStation 5 i ovladače – stačí se ozvat a domluvit termín.</p>
    </div>
</section>

<section class="section">
    <div class="wrap grid grid--2" style="align-items:stretch">
        <div class="card">
            <h3>Náhradní konzole po dobu opravy</h3>
            <p>Opravuji vaši konzoli? Podle dostupnosti vám po dobu opravy půjčím náhradní <strong>zdarma</strong>
                (jen za vratnou kauci). Vrátíte ji, až si vyzvednete opravenou.</p>
        </div>
        <div class="card">
            <h3>Půjčení na den nebo víkend</h3>
            <p>Oslava, návštěva, víkend s kamarády nebo vyzkoušení před koupí – konzoli si půjčíte na libovolný počet dní,
                celý víkend (pátek–neděle) je zvýhodněný.</p>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="wrap">
        <h2>Ceník</h2>
        @if($zarizeni->count())
            <table class="cenik-tab" style="max-width:720px;margin-top:.8rem">
                <tr>
                    <td><strong>Zařízení</strong></td>
                    <td class="cena"><strong>1 den</strong></td>
                    <td class="cena"><strong>Víkend Pá–Ne</strong></td>
                </tr>
                @foreach($zarizeni as $d)
                    <tr>
                        <td>{{ $d['nazev'] }}</td>
                        <td class="cena">{{ $kc($d['den']) }}</td>
                        <td class="cena">{{ $d['vikend'] ? $kc($d['vikend']) : '—' }}</td>
                    </tr>
                @endforeach
            </table>
            <p class="cenik-pozn" style="margin-top:1rem">
                Účtuje se každý započatý den. Ovladač navíc si můžete přibalit ke konzoli.
                Při zápůjčce se skládá vratná kauce{{ $kauceOd ? ' (od ' . $kc($kauceOd) . ' podle zařízení)' : '' }},
                cena zapůjčení se z ní při vrácení odečte.
            </p>
        @else
            <p class="lead">Ceník aktuálně připravuji – zavolejte nebo napište a domluvíme se.</p>
        @endif
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="wrap grid grid--2" style="align-items:start">
        <div>
            <h2>Jak to probíhá</h2>
            <div class="kroky" style="grid-template-columns:1fr;margin-top:.8rem">
                <div class="kroky__i"><div class="kroky__n">1</div><h3>Domluvíme termín</h3>
                    <p>Zavolejte nebo napište, co a na kdy potřebujete. Ověřím dostupnost.</p></div>
                <div class="kroky__i"><div class="kroky__n">2</div><h3>Převzetí ve Zlíně</h3>
                    <p>Konzoli si vyzvednete v provozovně. Ukážu vám ji zapnutou, společně zkontrolujeme stav a příslušenství
                        a podepíšeme předávací protokol. Vezměte si občanský průkaz a kauci (hotově nebo převodem).</p></div>
                <div class="kroky__i"><div class="kroky__n">3</div><h3>Vrácení a vyúčtování</h3>
                    <p>V domluvený den konzoli vrátíte kompletní. Po kontrole vám vrátím kauci sníženou o cenu zapůjčení.</p></div>
            </div>
        </div>
        <div>
            <h2>Základní pravidla</h2>
            <ul class="prose">
                <li>Zařízení nepůjčujte dál, neotevírejte a neupravujte (jailbreak, firmware).</li>
                <li>Chraňte ho před vodou, pádem, přehřátím a prachem.</li>
                <li>Konzole má profil Konzolák – přidejte si vlastní profil (PSN) a stáhněte své hry; hry a nákupy hradíte vy. Před vrácením svůj profil a stažené hry smažte, profil Konzolák ponechte.</li>
                <li>Závadu hned hlaste – nezaviněnou závadu řeším náhradou nebo vrácením části ceny.</li>
                <li>Za prodlení s vrácením se účtuje denní sazba, poškození nebo chybějící příslušenství jde z kauce.</li>
            </ul>
            <p class="cenik-pozn">Kompletní pravidla jsou součástí předávacího protokolu, který dostanete při převzetí.</p>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="wrap">
        <div class="cta-box">
            <div>
                <h2>Chcete si půjčit konzoli?</h2>
                <p class="lead">Ozvěte se – zkontroluji volný termín a domluvíme převzetí.</p>
            </div>
            <div class="cta-box__btns">
                @if($K['tel'])<a class="btn btn--primary" href="{{ $K['tel'] }}">Zavolat</a>@endif
                @if($K['whatsapp'])<a class="btn btn--ghost" href="{{ $K['whatsapp'] }}" target="_blank" rel="noopener">WhatsApp</a>@endif
                <a class="btn btn--dark" href="{{ route('web.kontakt') }}#poptavka">Formulář</a>
            </div>
        </div>
    </div>
</section>
@endsection
