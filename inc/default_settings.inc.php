<?php

return [
    'sitetitle'   => "Mikron",
    'dbfile'      => "data/mikron.db",
    'formats'     => ['markdown'], // why..? you're on borrowed time, buddy
    'stylesheets' => [],
    'users'       => [        // this one should eventually move elsewhere..
        '127.0.0.1' => 'A. Utho',
    ],
];

date_default_timezone_set("UTC");

