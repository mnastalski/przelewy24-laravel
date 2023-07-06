<?php

return [

    'merchant_id' => env('PRZELEWY24_MERCHANT_ID'),
    'reports_key' => env('PRZELEWY24_REPORTS_KEY'),
    'crc' => env('PRZELEWY24_CRC'),
    'is_live' => (bool) env('PRZELEWY24_IS_LIVE', false),
    'pos_id' => env('PRZELEWY24_POS_ID'),

];
