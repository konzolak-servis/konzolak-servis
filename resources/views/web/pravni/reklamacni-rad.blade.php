@extends('web.layout')
@section('title', 'Reklamační řád a podmínky opravy | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('robots', 'noindex,follow')

@section('body')
<section class="section">
    <div class="wrap prose">
        <p style="color:#b3261e"><strong>Pracovní verze (draft).</strong> Před spuštěním webu bude
            text zkontrolován.</p>

        <h1>Reklamační řád a podmínky opravy</h1>

        <h2>1. Úvod</h2>
        <p>Oprava, čištění nebo úprava zařízení se řídí smlouvou o dílo dle § 2586 a § 2609–2619
            občanského zákoníku. Provozovatelem servisu je {{ $firma->nazev ?? 'Konzolák Zlín' }}@if($firma->ico),
            IČO {{ $firma->ico }}@endif.</p>

        <h2>2. Cena a její odsouhlasení</h2>
        <p>Ceny uvedené v ceníku jsou orientační a mohou se během servisního zásahu upřesnit.
            Přesná cena je sdělena po diagnostice; oprava je provedena až po odsouhlasení
            zákazníkem. Je-li oprava provedena, diagnostika se neúčtuje.</p>

        <h2>3. Odpovědnost za vady provedené opravy</h2>
        <p><strong>Spotřebitel:</strong> práva z vadného plnění lze uplatnit ve lhůtě 24 měsíců
            od převzetí opravené věci; u oprav použitých nebo starších zařízení činí tato lhůta
            12 měsíců. V prvních 12 měsících se má za to, že vada existovala již při převzetí.</p>
        <p><strong>Podnikatel:</strong> lhůta pro uplatnění vad provedené práce činí 3 měsíce
            od převzetí; vady je nutné oznámit bez zbytečného odkladu po jejich zjištění.</p>
        <p>Odpovědnost se vztahuje na vady samotné provedené práce. Nevztahuje se na:</p>
        <ul>
            <li>nové závady vzniklé jinou příčinou,</li>
            <li>mechanické poškození vzniklé po převzetí zařízení zákazníkem,</li>
            <li>vady, na které byl zákazník předem upozorněn a s opravou přesto souhlasil.</li>
        </ul>
        <p>Na vestavěné náhradní díly se vztahuje samostatná záruka jejich výrobce.</p>

        <h2>4. Uplatnění reklamace</h2>
        <p>Reklamaci uplatněte bez zbytečného odkladu po zjištění vady, osobně nebo na
            @if($firma->email){{ $firma->email }}@endif, s uvedením čísla zakázky a popisu vady.
            Reklamace se vyřizuje nejpozději do 30 dnů.</p>

        <h2>5. Data v zařízení</h2>
        <p>Servis nepřebírá odpovědnost za data uložená na discích a paměťových médiích.
            Zálohování dat je v plné odpovědnosti zákazníka.</p>

        <h2>6. Vydání zařízení a zádržné právo</h2>
        <p>Opravené zařízení se vydává proti předložení originálu dokladu o převzetí. Do úplného
            zaplacení ceny opravy má servis k zařízení zádržné právo (§ 2609 obč. zák.).</p>

        <h2>7. Nevyzvednuté zařízení</h2>
        <p>Nevyzvedne-li zákazník zařízení do <em>[doplní se lhůta]</em> od výzvy k převzetí,
            může být účtováno skladné a po uplynutí <em>[doplní se lhůta]</em> může být zařízení
            zlikvidováno nebo prodáno k úhradě nákladů.</p>

        <h2>8. Odstoupení od smlouvy uzavřené na dálku</h2>
        <p>Byla-li objednávka opravy učiněna distančně, má spotřebitel právo odstoupit do 14 dnů.
            Požádá-li spotřebitel výslovně o zahájení opravy před uplynutím této lhůty a je-li
            oprava provedena, právo na odstoupení zaniká (§ 1837 obč. zák.).</p>

        <h2>9. Mimosoudní řešení sporů</h2>
        <p>K mimosoudnímu řešení spotřebitelských sporů je příslušná Česká obchodní inspekce
            (adr.coi.cz).</p>
    </div>
</section>
@endsection
