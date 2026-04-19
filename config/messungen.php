<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kontrolleur-ID für Amt-Export
    |--------------------------------------------------------------------------
    | Format: NNNNN.N (z.B. 45235.1)
    | Wird als Kopfzeile der Export-Datei geschrieben:
    |   "Kontrolleur------------45235.1"
    */
    'kontrolleur_id' => env('KONTROLLEUR_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Standard-E-Mail-Empfänger für Amt-Export
    |--------------------------------------------------------------------------
    */
    'amt_email_default' => env('AMT_EMAIL_DEFAULT', ''),

    /*
    |--------------------------------------------------------------------------
    | Absender für Amt-Export-Mails
    |--------------------------------------------------------------------------
    */
    'amt_email_from' => env('AMT_EMAIL_FROM', env('MAIL_FROM_ADDRESS')),
    'amt_email_from_name' => env('AMT_EMAIL_FROM_NAME', env('MAIL_FROM_NAME', 'UschiWeb')),

    /*
    |--------------------------------------------------------------------------
    | Brennstoff-Mapping: intern → Amt-Code (1 Ziffer)
    |--------------------------------------------------------------------------
    */
    'brennstoff_amt_code' => [
        'FUEL_LIGHT_OIL' => 1,   // Heizöl
        'FUEL_HEAVY_OIL' => 1,   // Heizöl
        'FUEL_NAT_GAS'   => 3,   // Erdgas
        'FUEL_PROPANE'   => 6,   // Flüssiggas
        'FUEL_BUTANE'    => 6,   // Flüssiggas
        'FUEL_PELLETS'   => 7,   // Pellets
        'FUEL_WOOD'      => 7,   // Holz
    ],

    /*
    |--------------------------------------------------------------------------
    | Reverse-Mapping: Amt-Code → interner FuelId (Default pro Code)
    |--------------------------------------------------------------------------
    | Wichtig: 7 ist Pellets UND Holz — beim Import nicht eindeutig.
    | Default: 7 → FUEL_PELLETS (häufiger). Kann beim Import überschrieben
    | werden, wenn die Anlage einen anderen Brennstoff hinterlegt hat.
    */
    'amt_code_to_brennstoff' => [
        0 => null,
        1 => 'FUEL_LIGHT_OIL',
        3 => 'FUEL_NAT_GAS',
        6 => 'FUEL_PROPANE',
        7 => 'FUEL_PELLETS',
    ],
];
