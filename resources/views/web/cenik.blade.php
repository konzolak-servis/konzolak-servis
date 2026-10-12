@extends('web.layout')
@section('title', 'Ceník oprav | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('desc', 'Orientační ceník oprav herních konzolí, ovladačů a PC ve Zlíně.')

@section('body')
@php $K = \App\Support\Web::kontakty(); @endphp
<section class="hero">
    <div class="wrap hero__in">
        <h1>Ceník oprav</h1>
        <p>Orientační ceny běžných úkonů. Přesnou cenu vždy řeknu po diagnostice
            a opravuji až po vašem odsouhlasení. Diagnostika je při provedení opravy zdarma.</p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        @if($skupiny->count())
            <div class="cenik-nastroje">
                <input type="search" id="cenik-hledat" class="cenik-hledat" placeholder="Hledat opravu (např. HDMI, drift, čištění)…"
                       value="{{ request('q') }}" aria-label="Hledat v ceníku" autocomplete="off">
                <div class="cenik-chips" id="cenik-chips">
                    <button type="button" class="chip is-on" data-skup="">Vše</button>
                    @foreach($skupiny as $kategorie => $polozky)
                        <button type="button" class="chip" data-skup="skup-{{ $kategorie }}">{{ $label($kategorie) }}</button>
                    @endforeach
                </div>
            </div>
        @endif

        @forelse($skupiny as $kategorie => $polozky)
            <div class="cenik-skup" id="skup-{{ $kategorie }}" data-skup="skup-{{ $kategorie }}">
                <h3>{{ $label($kategorie) }}</h3>
                <table class="cenik-tab" style="max-width:720px;margin-top:.6rem">
                    @foreach($polozky as $p)
                        <tr data-hledat="{{ \Illuminate\Support\Str::lower($p->nazev . ' ' . $label($kategorie)) }}">
                            <td>{{ $p->nazev }}</td>
                            <td class="cena">{{ $p->cena > 0 ? number_format($p->cena, 0, ',', ' ') . ' Kč' : 'dle diagnostiky' }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @empty
            <p class="lead">Ceník zatím připravuji. Napište mi a cenu opravy spočítám individuálně.</p>
        @endforelse

        <p id="cenik-nic" class="lead" hidden>Nic jsme nenašli. Napište nebo zavolejte – většinu závad umím opravit i mimo ceník.</p>

        <p class="cenik-pozn" style="margin-top:1.5rem">
            Ceník obsahuje nejběžnější opravy. Nenašli jste, co hledáte? Ozvěte se. Při opravě více závad na jednom zařízení
            cenu spočítám individuálně (součet použitých dílů a práce). Ceny jsou orientační, změna cen vyhrazena.
            Na provedenou práci se vztahuje odpovědnost za vady dle zákona (u spotřebitele 24 měsíců, u starších zařízení 12 měsíců;
            u firem smluvně 3 měsíce). Podrobnosti v <a href="{{ route('web.pravni', 'reklamacni-rad') }}">reklamačním řádu</a>.
        </p>

        <div class="cta-box" style="margin-top:2rem">
            <div>
                <h2>Nejste si jistí závadou?</h2>
                <p class="lead">Napište, co zařízení dělá nebo nedělá. Poradím postup a orientační cenu.</p>
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
