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
