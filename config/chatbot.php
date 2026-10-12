<?php

return [

    /*
    | AI asistent na veřejném webu (chat v pravém dolním rohu). Bez ANTHROPIC_API_KEY je vypnutý
    | a widget se na webu vůbec nezobrazí. Klíč se zadává v .env na serveru.
    */
    'api_key' => env('ANTHROPIC_API_KEY'),
    'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
    'model' => env('CHATBOT_MODEL', 'claude-sonnet-5-5'),

    // ochrana před zneužitím a nákladovým stropem
    'limit_ip_10min' => 20,
    'limit_ip_den' => 120,
    'limit_celkem_den' => (int) env('CHATBOT_DENNI_LIMIT', 600),
    'max_zprav' => 16,
    'max_delka' => 1000,
];
