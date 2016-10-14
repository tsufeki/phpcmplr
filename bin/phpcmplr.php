#!/usr/bin/php
<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use Psr\Log\LogLevel;
use PhpCmplr\PhpCmplr;

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

if (empty($opts['loglevel'])) {
    $opts['loglevel'] = 'warning';
}

$options = [
    'server' => [
        'port' => (int)$opts['port'],
    ],
    'log' => [
        'level' => $opts['loglevel'],
    ],
];

$phpcmplr = new PhpCmplr($options);
$phpcmplr->run();

