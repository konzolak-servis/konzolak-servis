@extends('web.layout')
@section('title', 'Ceník oprav | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('desc', 'Orientační ceník oprav herních konzolí, ovladačů a PC ve Zlíně.')

@section('body')
<section class="hero">
    <div class="wrap hero__in">
        <h1>Ceník oprav</h1>
        <p>Orientační ceny běžných úkonů. Přesnou cenu vždy řeknu po diagnostice
            a opravuji až po vašem odsouhlasení. Diagnostika je při provedení opravy zdarma.</p>
    </div>
</section>

<section class="section">
    <div class="wrap">
        @forelse($skupiny as $kategorie => $polozky)
            <div class="cenik-skup">
                <h3>{{ $label($kategorie) }}</h3>
                <table class="cenik-tab" style="max-width:680px;margin-top:.6rem">
                    @foreach($polozky as $p)
                        <tr>
                            <td>{{ $p->nazev }}</td>
                            <td class="cena">{{ $p->cena > 0 ? number_format($p->cena, 0, ',', ' ') . ' Kč' : 'dle diagnostiky' }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @empty
            <p class="lead">Ceník zatím připravuji. Napište mi a cenu opravy spočítám individuálně.</p>
        @endforelse

        <p class="cenik-pozn" style="margin-top:1.5rem">
            Ceny jsou orientační a mohou se lišit podle rozsahu závady. Na provedenou práci se vztahuje
            odpovědnost za vady dle zákona (u spotřebitele 24 měsíců, u starších zařízení 12 měsíců;
            u firem smluvně 3 měsíce). Podrobnosti v <a href="{{ route('web.pravni', 'reklamacni-rad') }}">reklamačním řádu</a>.
        </p>
    </div>
</section>
@endsection
