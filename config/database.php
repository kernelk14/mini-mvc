<?php
return array(
    // driver: auto | pdo | mysqli | mysql
    'driver' => 'auto',
    // type: mysql | sqlite
    'type' => 'mysql',

    // MySQL / MariaDB settings
    'host' => 'localhost',
    'port' => null,
    'name' => 'app',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8',

    // SQLite settings (used when type = sqlite)
    'path' => BASE_PATH . '/storage/app/database.sqlite',
);
