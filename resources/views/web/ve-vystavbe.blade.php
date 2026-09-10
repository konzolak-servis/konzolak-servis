<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Spouštíme brzy – Konzolák Zlín</title>
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#0b1a30">
    <link rel="icon" type="image/png" sizes="512x512" href="/images/konzolak-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@600;700&family=Inter:wght@400;500;600&display=swap">
    <style>
        :root{
            --navy-900:#0b1a30; --navy-800:#0f2038; --navy-700:#16294a;
            --gold:#d1a13a; --gold-hi:#ffcf5c;
            --ink:#eaf0f8; --ink-soft:#c3d0e2; --muted:#8698b6;
            --err:#ff8a80;
            --line:rgba(255,255,255,.1);
            --cut:polygon(0 0, calc(100% - 14px) 0, 100% 14px, 100% 100%, 14px 100%, 0 calc(100% - 14px));
            --ff-head:"Chakra Petch","Segoe UI",Roboto,Helvetica,Arial,sans-serif;
            --ff-body:"Inter","Segoe UI",Roboto,-apple-system,Helvetica,Arial,sans-serif;
        }
        *{box-sizing:border-box}
        html,body{height:100%}
        body{
            margin:0; font-family:var(--ff-body); color:var(--ink);
            background:
                radial-gradient(1100px 620px at 78% -8%, rgba(209,161,58,.16), transparent 60%),
                radial-gradient(900px 600px at 12% 108%, rgba(37,73,128,.38), transparent 55%),
                linear-gradient(180deg, var(--navy-800), var(--navy-900));
            display:flex; flex-direction:column; align-items:center; justify-content:center;
            gap:2.6rem; padding:3rem 1.25rem; min-height:100%;
        }
        .brand-wrap{
            position:relative; display:flex; justify-content:center;
            width:min(80vw, 420px);
        }
        .brand-wrap::after{
            content:""; position:absolute; left:50%; bottom:-14%;
            width:78%; height:46%; transform:translateX(-50%);
            background:radial-gradient(ellipse at center, rgba(255,207,92,.45), rgba(209,161,58,.22) 45%, transparent 72%);
            filter:blur(26px); z-index:0; pointer-events:none;
        }
        .brand{
            position:relative; z-index:1;
            width:100%; height:auto; display:block;
            filter:drop-shadow(0 14px 34px rgba(0,0,0,.5));
        }
        body::before{
            content:""; position:fixed; inset:0; pointer-events:none; opacity:.5;
            background-image:
                linear-gradient(rgba(255,255,255,.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.035) 1px, transparent 1px);
            background-size:44px 44px;
            -webkit-mask-image:radial-gradient(650px 480px at 50% 42%, #000 30%, transparent 78%);
                    mask-image:radial-gradient(650px 480px at 50% 42%, #000 30%, transparent 78%);
        }
        .box{
            position:relative; width:100%; max-width:440px;
            background:linear-gradient(180deg, rgba(255,255,255,.055), rgba(255,255,255,.02));
            border:1px solid var(--line);
            clip-path:var(--cut);
            padding:2.4rem 2rem 2rem;
            box-shadow:0 30px 80px -30px rgba(0,0,0,.7), 0 0 0 1px rgba(236,205,130,.12);
        }
        .box::after{
            content:""; position:absolute; left:0; top:0; width:56px; height:3px;
            background:linear-gradient(90deg, var(--gold-hi), var(--gold));
        }
        .eyebrow{
            font-family:var(--ff-head); text-transform:uppercase; letter-spacing:.24em;
            font-size:.68rem; color:var(--gold-hi); margin:0 0 .6rem;
        }
        h1{
            font-family:var(--ff-head); font-weight:700; line-height:1.12;
            font-size:1.7rem; margin:0 0 .8rem; color:#fff;
        }
        p.lead{margin:0 0 1.7rem; color:var(--ink-soft); font-size:.96rem; line-height:1.55}
        form{display:flex; flex-direction:column; gap:.7rem}
        label{
            font-family:var(--ff-head); font-weight:600; font-size:.82rem;
            text-transform:uppercase; letter-spacing:.06em; color:var(--ink-soft);
        }
        input[type=password]{
            width:100%; padding:.8rem .9rem; font-size:1rem; font-family:var(--ff-body);
            color:var(--ink); background:rgba(4,10,22,.55);
            border:1px solid var(--line); border-radius:4px; outline:none;
        }
        input[type=password]:focus{border-color:var(--gold); box-shadow:0 0 0 3px rgba(209,161,58,.18)}
        button{
            margin-top:.3rem; padding:.85rem 1rem; cursor:pointer;
            font-family:var(--ff-head); font-weight:700; text-transform:uppercase; letter-spacing:.06em;
            font-size:.9rem; color:#241a05; border:0; border-radius:4px;
            background:linear-gradient(180deg, var(--gold-hi), var(--gold));
            transition:filter .15s, box-shadow .15s;
        }
        button:hover{filter:brightness(1.06); box-shadow:0 0 30px -4px rgba(209,161,58,.7)}
        .err{
            margin:.1rem 0 0; color:var(--err); font-size:.85rem;
            font-family:var(--ff-head); font-weight:600;
        }
        .foot{
            margin-top:1.9rem; text-align:center; color:var(--muted); font-size:.82rem; line-height:1.7;
        }
        .foot a{color:var(--gold-hi); text-decoration:none; font-weight:600}
        @media (max-width:420px){ h1{font-size:1.45rem} .box{padding:2rem 1.4rem} .brand-wrap{width:74vw} }
    </style>
</head>
<body>
    <div class="brand-wrap">
        <img class="brand" src="/images/konzolak-logo-print.png" alt="Konzolák Zlín">
    </div>

    <main class="box">
        <p class="eyebrow">Spouštíme brzy</p>
        <h1>Stránky ve výstavbě</h1>
        <p class="lead">
            Nové stránky jsou ve výstavbě. Máte-li přístupové heslo, přihlaste se a podívejte se,
            jak web postupuje.
        </p>

        <form method="POST" action="/vstup">
            @csrf
            <label for="heslo">Heslo</label>
            <input type="password" id="heslo" name="heslo" autocomplete="current-password" autofocus required>
            @if(session('web_gate_error'))<p class="err">{{ session('web_gate_error') }}</p>@endif
            <button type="submit">Vstoupit</button>
        </form>

        <p class="foot">
            Servis herních konzolí a PC ve Zlíně<br>
            @php($tel = \App\Support\Web::telMezinarodne())
            @if($tel)<a href="tel:+{{ $tel }}">{{ optional(\App\Support\Web::firma())->telefon }}</a>@endif
        </p>
    </main>
</body>
</html>
