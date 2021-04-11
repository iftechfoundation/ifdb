<?php

include_once "util.php";

session_set_cookie_params([
    'secure' => !isLocalDev(),
    'httponly' => 1,
    'samesite' => 'Lax'
]);

@session_start();

?>
