<?php

function spa_get_church_year_seasons() {
    return array(
        'Advent',
        'Christmas',
        'Epiphany',
        'Lent',
        'Holy Week',
        'Easter',
        'Season of Pentecost',
    );
}

function spa_get_church_year_special_days() {
    return array(
        'Baptism of Our Lord',
        'Transfiguration of Our Lord',
        'Ash Wednesday',
        'Palm Sunday',
        'Maundy Thursday',
        'Good Friday',
        'Easter Vigil',
        'Ascension Day',
        'Day of Pentecost',
        'Holy Trinity',
        'Reformation Day',
        'All Saints\' Day',
        'St. Stephen, Martyr',
        'St. John, Apostle and Evangelist',
        'Holy Innocents, Martyrs',
        'Confession of St. Peter',
        'Conversion of St. Paul',
        'Presentation of Our Lord',
        'St. Joseph, Guardian of Jesus',
        'Annunciation of Our Lord',
        'St. Mark, Evangelist',
        'St. Philip and St. James, Apostles',
        'St. Barnabas, Apostle',
        'St. Peter and St. Paul, Apostles',
        'St. Mary Magdalene',
        'St. James of Jerusalem',
        'St. Mary, Mother of Our Lord',
        'St. Matthew, Evangelist',
        'St. Michael and All Angels',
        'St. Luke, Evangelist',
        'St. Simon and St. Jude, Apostles',
        'St. Andrew, Apostle',
        'St. Thomas, Apostle',
        'St. Matthias, Apostle',
    );
}

function spa_get_church_year_day($event_date, $special_day = '', $season = '') {
    if ( $special_day !== '' ) {
        return $special_day;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $event_date, wp_timezone());
    if ( ! $date || $date->format('Y-m-d') !== $event_date ) {
        return $season;
    }
    if ( $date->format('w') !== '0' ) {
        return $season;
    }

    $year = intval($date->format('Y'));
    $easter = spa_get_easter_date($year);
    $pentecost = $easter->modify('+49 days');
    $days_from_pentecost = intval($pentecost->diff($date)->format('%r%a'));
    if ( $days_from_pentecost === 0 ) {
        return 'Day of Pentecost';
    }
    if ( $days_from_pentecost > 0 ) {
        return 'The ' . spa_get_ordinal($days_from_pentecost / 7) . ' Sunday after Pentecost';
    }

    $first_easter_sunday = $easter;
    $days_from_easter = intval($easter->diff($date)->format('%r%a'));
    if ( $days_from_easter >= 0 && $days_from_easter % 7 === 0 ) {
        if ( $days_from_easter === 0 ) {
            return 'Easter Sunday';
        }
        return spa_get_ordinal($days_from_easter / 7 + 1) . ' Sunday of Easter';
    }

    $palm_sunday = $easter->modify('-7 days');
    $first_lent_sunday = $easter->modify('-42 days');
    $days_from_lent_start = intval($first_lent_sunday->diff($date)->format('%r%a'));
    if ( $date >= $first_lent_sunday && $date < $palm_sunday && $days_from_lent_start % 7 === 0 ) {
        return spa_get_ordinal($days_from_lent_start / 7 + 1) . ' Sunday in Lent';
    }

    $christmas = new DateTimeImmutable($year . '-12-25', wp_timezone());
    if ( $date->format('m-d') === '12-25' ) {
        return 'Christmas Day';
    }
    if ( $date > $christmas && $date < $christmas->modify('+12 days') ) {
        return 'The Sunday after Christmas';
    }

    $epiphany = new DateTimeImmutable($year . '-01-06', wp_timezone());
    if ( $date > $epiphany && $date < $easter->modify('-46 days') ) {
        return 'The Sunday after Epiphany';
    }

    $advent_start = new DateTimeImmutable($year . '-11-27', wp_timezone());
    while ( $advent_start->format('w') !== '0' ) {
        $advent_start = $advent_start->modify('+1 day');
    }
    if ( $date >= $advent_start && $date < $christmas ) {
        return spa_get_ordinal($advent_start->diff($date)->days / 7 + 1) . ' Sunday of Advent';
    }

    return $season;
}

function spa_get_easter_date($year) {
    $century = intdiv($year, 100);
    $year_in_century = $year % 100;
    $solar_correction = intdiv($century, 4);
    $century_remainder = $century % 4;
    $moon_correction = intdiv($century + 8, 25);
    $adjusted_century = intdiv($century - $moon_correction + 1, 3);
    $moon_cycle = (19 * ($year % 19) + $century - $solar_correction - $adjusted_century + 15) % 30;
    $leap_day = intdiv($year_in_century, 4);
    $year_remainder = $year_in_century % 4;
    $weekday = (32 + 2 * $century_remainder + 2 * $leap_day - $moon_cycle - $year_remainder) % 7;
    $month_adjustment = intdiv(($year % 19) + 11 * $moon_cycle + 22 * $weekday, 451);
    $month = intdiv($moon_cycle + $weekday - 7 * $month_adjustment + 114, 31);
    $day = (($moon_cycle + $weekday - 7 * $month_adjustment + 114) % 31) + 1;

    return new DateTimeImmutable(
        sprintf('%04d-%02d-%02d', $year, $month, $day),
        wp_timezone()
    );
}

function spa_get_ordinal($number) {
    $number = intval($number);
    $words = array(
        1 => 'First',
        2 => 'Second',
        3 => 'Third',
        4 => 'Fourth',
        5 => 'Fifth',
        6 => 'Sixth',
        7 => 'Seventh',
        8 => 'Eighth',
        9 => 'Ninth',
        10 => 'Tenth',
        11 => 'Eleventh',
        12 => 'Twelfth',
        13 => 'Thirteenth',
        14 => 'Fourteenth',
        15 => 'Fifteenth',
        16 => 'Sixteenth',
        17 => 'Seventeenth',
        18 => 'Eighteenth',
        19 => 'Nineteenth',
        20 => 'Twentieth',
    );
    return $words[$number] ?? $number . 'th';
}
