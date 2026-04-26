<?php

namespace App\Support;

class CountryNames
{
    public static function fromAlpha2(string $code): ?string
    {
        return [
            'AL' => 'Albania',
            'AT' => 'Austria',
            'BA' => 'Bosnia and Herzegovina',
            'BE' => 'Belgium',
            'BG' => 'Bulgaria',
            'BY' => 'Belarus',
            'CH' => 'Switzerland',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DE' => 'Germany',
            'DK' => 'Denmark',
            'EE' => 'Estonia',
            'ES' => 'Spain',
            'FI' => 'Finland',
            'FR' => 'France',
            'GB' => 'United Kingdom',
            'GR' => 'Greece',
            'HR' => 'Croatia',
            'HU' => 'Hungary',
            'IE' => 'Ireland',
            'IS' => 'Iceland',
            'IT' => 'Italy',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'LV' => 'Latvia',
            'ME' => 'Montenegro',
            'MK' => 'North Macedonia',
            'MT' => 'Malta',
            'NL' => 'Netherlands',
            'NO' => 'Norway',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'RO' => 'Romania',
            'RS' => 'Serbia',
            'SE' => 'Sweden',
            'SI' => 'Slovenia',
            'SK' => 'Slovakia',
            'SM' => 'San Marino',
            'UA' => 'Ukraine',
            'VA' => 'Vatican City',
        ][$code] ?? null;
    }
}
