@extends('web.layout')
@section('title', 'Reference a recenze | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('desc', 'Co říkají zákazníci servisu Konzolák Zlín.')

@section('body')
<section class="hero">
    <div class="wrap hero__in">
        <h1>Reference</h1>
        <p>Na Facebooku servis doporučuje 100 % hodnotících. Sem doplníme vybrané recenze
            a fotky „před / po".</p>
    </div>
</section>

<section class="section">
    <div class="wrap grid grid--3">
        @for($i = 0; $i < 3; $i++)
            <div class="card">
                <p style="color:#C8992E;font-size:1.1rem;margin:0 0 .4rem">★★★★★</p>
                <p><em>„Sem přijde citace recenze zákazníka."</em></p>
                <p style="color:#8a8578;margin:0">— jméno, {{ now()->year }}</p>
            </div>
        @endfor
    </div>
    <div class="wrap" style="margin-top:1.5rem">
        <p style="color:#8a8578"><em>(Recenze dodáš – klidně screenshoty z Facebooku / Google, přepíšu je sem.)</em></p>
    </div>
</section>
@endsection
