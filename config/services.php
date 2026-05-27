<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LLM — Google Gemini
    |--------------------------------------------------------------------------
    | Chave gratuita (sem cartão): https://aistudio.google.com → Get API key
    |
    | GEMINI_VERIFY_SSL: mantenha true em produção. Defina false apenas em
    | ambiente local se o PHP não tiver o bundle de certificados CA configurado
    | (erro "SSL certificate verify failed" no Windows/Local by Flywheel).
    */
    'gemini' => [
        'key'        => env('GEMINI_API_KEY'),
        'model'      => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        'verify_ssl' => env('GEMINI_VERIFY_SSL', true),
    ],

];
