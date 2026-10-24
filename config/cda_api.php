<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Clave API (plugin WordPress / formularios públicos)
    |--------------------------------------------------------------------------
    |
    | Misma clave que el sistema PHP legado (config/api.php). Envíala en el
    | encabezado X-API-KEY. Cámbiala en producción vía .env.
    |
    */

    'key' => env('CDA_API_KEY', 'cda_sistema_web_2026'),

    'sesion_horas' => (int) env('CDA_API_SESION_HORAS', 8),

];
