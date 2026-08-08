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

/**
 * Convert a country code (ISO alpha-2) to a dial code (without +).
 * Supports common countries; extend as needed.
 */
function spa_country_to_dial($country) {
    $map = array(
        'US' => '1',
        'CA' => '1',
        'GB' => '44',
        'AU' => '61',
        'DE' => '49',
        'FR' => '33',
        'NL' => '31',
        'IE' => '353'
    );
    $country = strtoupper($country);
    return isset($map[$country]) ? $map[$country] : null;
}

/**
 * Attempt to normalize a phone number to E.164.
 * - If number already starts with + and validates as E.164, returns it.
 * - Otherwise removes non-digits, attempts to prepend default country's dial code.
 * - Returns normalized +<digits> or false on failure.
 */
function spa_normalize_phone($number, $default_country = 'US') {
    if ( empty($number) ) return false;
    $num = trim($number);

    // If already starts with +, ensure it's E.164
    if (strpos($num, '+') === 0) {
        $cand = preg_replace('/[^\d+]/', '', $num);
        if ( spa_is_e164($cand) ) return $cand;
        // if not valid, try stripping non-digits and continue
        $num = $cand;
    }

    // Strip all non-digit characters
    $digits = preg_replace('/\D+/', '', $num);
    if (strlen($digits) < 7) return false; // too short to be real

    // If digits already look like they include country code (length > 10), try adding + and validate
    $possible = '+' . $digits;
    if ( spa_is_e164($possible) ) return $possible;

    // Otherwise, try to prepend default country's dial code
    $dial = spa_country_to_dial($default_country);
    if ( $dial ) {
        // remove leading 0 from local numbers
        if (strlen($digits) > 0 && $digits[0] === '0') {
            $digits = ltrim($digits, '0');
        }
        $cand = '+' . $dial . $digits;
        if ( spa_is_e164($cand) ) return $cand;
    }

    return false;
}
