@php $typ = $typ ?? 'oprava'; @endphp
@if(session('odeslano'))
    <div class="msg msg--ok">Děkuji, zpráva odešla. Ozvu se co nejdřív.</div>
@endif
@if(session('chyba'))
    <div class="msg msg--err">{{ session('chyba') }}</div>
@endif
@if($errors->any())
    <div class="msg msg--err">
        @foreach($errors->all() as $e){{ $e }}<br>@endforeach
    </div>
@endif

<form class="form" method="POST" action="{{ route('web.poptavka') }}" style="margin-top:1rem">
    @csrf
    <input type="hidden" name="typ" value="{{ $typ }}">
    <div class="hp"><label>Nevyplňujte<input type="text" name="web" tabindex="-1" autocomplete="off"></label></div>
    <div>
        <label for="jmeno">Jméno a příjmení *</label>
        <input id="jmeno" name="jmeno" required value="{{ old('jmeno') }}">
    </div>
    <div>
        <label for="email">E-mail *</label>
        <input id="email" type="email" name="email" required value="{{ old('email') }}">
    </div>
    <div>
        <label for="telefon">Telefon</label>
        <input id="telefon" name="telefon" value="{{ old('telefon') }}">
    </div>
    <div>
        <label for="platforma">Zařízení</label>
        <select id="platforma" name="platforma">
            <option value="">— vyberte —</option>
            @foreach(\App\Support\Platformy::volby() as $skupina => $volby)
                <optgroup label="{{ $skupina }}">
                    @foreach($volby as $k => $v)
                        <option value="{{ $k }}" @selected(old('platforma') === $k)>{{ $v }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>
    <div>
        <label for="zprava">{{ $typ === 'vykup' ? 'Co chcete prodat (model, stav, příslušenství) *' : 'Popis závady *' }}</label>
        <textarea id="zprava" name="zprava" required>{{ old('zprava') }}</textarea>
    </div>
    <button class="btn btn--primary" type="submit">{{ $typ === 'vykup' ? 'Odeslat nabídku k výkupu' : 'Odeslat nezávaznou poptávku' }}</button>
    <p class="note">Odesláním berete na vědomí zpracování osobních údajů za účelem vyřízení
        poptávky. Více v <a href="{{ route('web.pravni', 'ochrana-osobnich-udaju') }}">zásadách ochrany osobních údajů</a>.
        Pokud se vám neozvu, zkontrolujte prosím i složku Nevyžádaná pošta (SPAM).</p>
</form>
