<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proxies confiáveis
    |--------------------------------------------------------------------------
    |
    | IPs ou faixas (separados por vírgula) cujos cabeçalhos X-Forwarded-* o
    | app aceita. Vazio, nenhum proxy é confiável. No acesso pelo celular, o
    | `tailscale serve` chega ao container pela rede do Docker (ADR 0005).
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
