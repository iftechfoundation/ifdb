<?php
// https://stackoverflow.com/a/34149536/54829
/**
 * Generate a random string, using a cryptographically secure 
 * pseudorandom number generator (random_int)
 * 
 * For PHP 7, random_int is a PHP core function
 * For PHP 5.x, depends on https://github.com/paragonie/random_compat
 * 
 * @param int $length      How many characters do we want?
 * @param string $keyspace A string of all possible characters
 *                         to select from
 * @return string
 */
function random_str(
    $length,
    $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
) {
    $str = '';
    $max = 61; // mb_strlen($keyspace, '8bit') - 1;
    if ($max < 1) {
        throw new Exception('$keyspace must be at least two characters long');
    }
    for ($i = 0; $i < $length; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
    }
    return $str;
}
global $nonce;
$nonce = random_str(8);

// default-src should be enough, but Firefox ESR 102 doesn't support `default-src nonce`, only `style-src nonce` and `script-src nonce`.
header("Content-Security-Policy: default-src 'self' ifdb.org www.google.com 'nonce-$nonce'; script-src 'self' ifdb.org www.google.com 'nonce-$nonce'; style-src 'self' ifdb.org 'nonce-$nonce';");
