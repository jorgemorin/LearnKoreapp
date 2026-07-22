<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Proveedor de IA activo
    |--------------------------------------------------------------------------
    | Valores posibles: 'fake' | 'claude' | 'openai'
    | Cambiar AI_PROVIDER en .env para alternar sin modificar código.
    */
    'provider' => env('AI_PROVIDER', 'fake'),

    /*
    |--------------------------------------------------------------------------
    | Clave de API del proveedor activo
    |--------------------------------------------------------------------------
    */
    'api_key' => env('AI_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Modelo del proveedor activo
    |--------------------------------------------------------------------------
    | Ejemplos:
    |   Claude: 'claude-3-5-sonnet-20241022'
    |   OpenAI: 'gpt-4o'
    */
    'model' => env('AI_MODEL', 'claude-3-5-sonnet-20241022'),
];
