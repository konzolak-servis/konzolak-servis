<div class="kroky">
    @foreach($kroky as $k)
        <div class="kroky__i">
            <div class="kroky__n">{{ $k['cislo'] }}</div>
            <h3>{{ $k['titulek'] }}</h3>
            <p>{{ $k['text'] }}</p>
        </div>
    @endforeach
</div>
