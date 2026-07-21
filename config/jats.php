<?php

return [
    'journal_abbrev' => env('JATS_JOURNAL_ABBREV', 'journal'),
    'publisher_name' => env('JATS_PUBLISHER_NAME', env('APP_NAME', 'Laravel')),
    'publisher_loc' => env('JATS_PUBLISHER_LOC'),
];
