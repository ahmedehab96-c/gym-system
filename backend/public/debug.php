<?php
// TEMPORARY deploy-debug script — bypasses Laravel entirely to check
// what env vars this exact PHP process (the one actually serving real
// HTTP requests) can see. No secrets exposed, just var presence/values
// for non-sensitive operational config. Remove before real use.
header('Content-Type: application/json');
$keys = ['FRONTEND_URL', 'LOG_CHANNEL', 'DB_CONNECTION', 'APP_ENV', 'SESSION_DRIVER', 'CACHE_STORE', 'RENDER_EXTERNAL_URL'];
$out = [];
foreach ($keys as $k) {
    $out[$k] = [
        'getenv' => getenv($k),
        'SERVER' => $_SERVER[$k] ?? null,
        'ENV' => $_ENV[$k] ?? null,
    ];
}
$out['php_sapi'] = php_sapi_name();
echo json_encode($out, JSON_PRETTY_PRINT);
