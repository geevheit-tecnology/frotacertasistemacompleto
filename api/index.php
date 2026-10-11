<?php

declare(strict_types=1);

foreach ([
    'APP_SERVICES_CACHE' => '/tmp/frotacerta/cache/services.php',
    'APP_PACKAGES_CACHE' => '/tmp/frotacerta/cache/packages.php',
    'APP_CONFIG_CACHE' => '/tmp/frotacerta/cache/config.php',
    'APP_ROUTES_CACHE' => '/tmp/frotacerta/cache/routes.php',
    'APP_EVENTS_CACHE' => '/tmp/frotacerta/cache/events.php',
] as $name => $value) {
    $_ENV[$name] = $_SERVER[$name] = $value;
    putenv($name.'='.$value);
}

if (! is_dir('/tmp/frotacerta/cache')) {
    mkdir('/tmp/frotacerta/cache', 0775, true);
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';

require __DIR__ . '/../public/index.php';
