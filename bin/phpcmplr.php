#!/usr/bin/php
<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use Psr\Log\LogLevel;
use PhpCmplr\PhpCmplr;
use PhpCmplr\Symfony\SymfonyPlugin;

$opts = getopt('', ['help', 'port:', 'loglevel:']);
if (!is_array($opts)) {
    $opts = [];
}

if (array_key_exists('help', $opts)) {
    echo "PhpCmplr: PHP code auto-completion server.\n";
    echo "\n";
    echo "Options:\n";
    echo "    --port=<port>         Port to listen on.\n";
    echo "    --loglevel=<loglevel> Logging level: emergency, alert, critical, error,\n";
    echo "                          warning, notice, info, debug\n";
    echo "    --help                Print this help message and exit.\n";
    exit(0);
}

if (!array_key_exists('port', $opts)) {
    echo "Required option not provided: --port\n";
    exit(2);
}

$options = [
    'server' => [
        'port' => (int)$opts['port'],
    ],
];
if (!empty($opts['loglevel'])) {
    $options['log']['level'] = $opts['loglevel'];
}

$plugins = [
    new SymfonyPlugin(),
];

$phpcmplr = new PhpCmplr($options, $plugins);
$phpcmplr->run();

