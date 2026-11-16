<?php
// A form POST with no g-recaptcha-response field (for example, because the
// reCAPTCHA script was blocked and the widget never rendered) must be
// reported as an error, not silently treated as "no CAPTCHA submitted".

// Keep the warning from include_once "local-credentials.php" out of the output;
// this test doesn't need the local credentials file.
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);

// captcha.php loads dbconnect.php, which needs the mysqli extension at load
// time. This test never connects to a database, so stub what's missing.
if (!extension_loaded("mysqli")) {
    define("MYSQLI_ASSOC", 1);
    define("MYSQLI_BOTH", 3);
    define("MYSQLI_REPORT_OFF", 0);
    function mysqli_report($flags) { return true; }
}

// captcha.php reads the reCAPTCHA keys from a function that normally lives
// in local-credentials.php. Load that file first if it exists, so a developer
// machine's real definition is used and not redeclared; otherwise stub it.
@include_once __DIR__ . "/../../www/local-credentials.php";
if (!function_exists("localRecaptchaKeys")) {
    function localRecaptchaKeys() {
        return ["public" => "public-key", "private" => "private-key"];
    }
}

require_once __DIR__ . "/../../www/captcha.php";

$_SERVER["REQUEST_METHOD"] = "POST";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
$_POST = ["userid" => "someone@example.com"];

[$ok, $err] = captchaCheckPost("test");
if ($ok || $err !== "captcha-not-loaded") {
    echo "Failed test: POST without a CAPTCHA response should be rejected with error captcha-not-loaded, got ok="
        . var_export($ok, true) . " error=" . var_export($err, true) . "\n";
    exit(1);
}
