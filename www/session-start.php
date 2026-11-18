<?php

define("PRODUCTION_SERVER_NAME", "ifdb.org");
define("STAGING_SERVER_NAME", "dev.ifdb.org");

function isProduction() {
    return $_SERVER['SERVER_NAME'] === PRODUCTION_SERVER_NAME;
}

function isStaging() {
    return $_SERVER['SERVER_NAME'] === STAGING_SERVER_NAME;
}

function isLocalDev() {
    return !isProduction() && !isStaging();
}

// Protecting against CSRF in 2025: https://words.filippo.io/csrf/
function checkCrossOrigin() {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) return;

    $site = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($site !== '') {
        $allowed = in_array($site, ['same-origin', 'none'], true);
    } else {
        $allowed = in_array($origin, ['', "https://$host", "http://$host"], true);
    }
    if ($allowed) return;

    error_log("Blocked cross-origin request: $method " . $_SERVER['REQUEST_URI']
        . " Sec-Fetch-Site: $site Origin: $origin Host: $host");
    http_response_code(403);
    header('Content-Type: text/plain');
    exit("Cross-origin request blocked");
}

checkCrossOrigin();

session_set_cookie_params([
    'secure' => !isLocalDev(),
    'httponly' => 1,
    'samesite' => 'Lax'
]);

// session_start automatically sets a cache-control header by default; disable that
session_cache_limiter('');

header('Cache-Control: private, no-cache');

@session_start();

?>
