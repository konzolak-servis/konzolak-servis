<?php

return [

    /*
    | Dokud veřejný web není spuštěn, je celý za jednoduchou přihlašovací
    | bránou (stránka „ve výstavbě"). Heslo se zadává v .env; smazáním
    | WEB_GATE_HESLO se brána vypne a web je veřejný.
    */
    'gate_heslo' => env('WEB_GATE_HESLO'),

];
