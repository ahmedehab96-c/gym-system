<?php

return [

    /*
    |--------------------------------------------------------------------
    | Member QR check-in token lifetime
    |--------------------------------------------------------------------
    |
    | How many days a generated member QR token stays valid before a
    | front-desk scan is rejected as "expired" (Phase 28). The member's
    | app re-requests one automatically past this point.
    |
    */
    'qr_token_ttl_days' => (int) env('QR_TOKEN_TTL_DAYS', 180),

];
