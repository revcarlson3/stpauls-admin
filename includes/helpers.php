<?php
/**
 * Helpers for SPA plugin
 */

/**
 * Simple E.164 validation: + followed by 8-15 digits (per E.164 max 15 digits)
 * Returns true if $number appears to be valid E.164
 */
function spa_is_e164($number) {
    if ( empty($number) ) return false;
    $number = trim($number);
    return (bool) preg_match('/^\+[1-9]\d{7,14}$/', $number);
}
