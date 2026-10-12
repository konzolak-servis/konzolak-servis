@extends('web.layout')
@section('title', 'Výkup herních konzolí a zařízení | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('desc', 'Vykoupíme herní konzole, ovladače, příslušenství a počítače – funkční i nefunkční. Nezávazné ocenění online.')

@section('body')
<section class="hero">
    <div class="wrap hero__in">
        <h1>Výkup zařízení</h1>
        <p>Máte doma konzoli, ovladač nebo počítač, který nepoužíváte? Ozvěte se – nezávazně ocením, funkční i nefunkční kusy.</p>
    </div>
</section>

<section class="section">
    <div class="wrap grid grid--2" style="align-items:start">
        <div>
            <h2>Co vykupuji</h2>
            <ul class="prose">
                <li>Herní konzole PlayStation, Xbox a Nintendo – funkční i vadné.</li>
                <li>Ovladače a herní příslušenství.</li>
                <li>Počítače, notebooky a jejich komponenty.</li>
                <li>Zařízení na díly (po zalití, s vadnou deskou apod.).</li>
            </ul>

            <h3 style="margin-top:1.6rem">Jak to probíhá</h3>
            <div class="kroky" style="grid-template-columns:1fr;margin-top:.8rem">
                <div class="kroky__i"><div class="kroky__n">1</div><h3>Pošlete popis a fotky</h3>
                    <p>Model, stav, příslušenství a případné závady. Čím víc podrobností, tím přesnější nabídka.</p></div>
                <div class="kroky__i"><div class="kroky__n">2</div><h3>Dostanete nabídku</h3>
                    <p>Ozvu se s orientační cenou. Nic vás nezavazuje.</p></div>
                <div class="kroky__i"><div class="kroky__n">3</div><h3>Předání a platba</h3>
                    <p>Osobně ve Zlíně, nebo poštou. Po zkontrolování zařízení platím hotově či převodem.</p></div>
            </div>
        </div>

        <div id="poptavka">
            <h2>Nabídněte zařízení</h2>
            @include('web.partials.poptavka-form', ['typ' => 'vykup'])
        </div>
    </div>
</section>
@endsection
