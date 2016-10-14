<?php

namespace PhpCmplr\Util;

use Katzgrau\KLogger\Logger as BaseLogger;

class Logger extends BaseLogger
{
    protected function formatMessage($level, $message, $context)
    {
        $vars = [
            'date'          => $this->getFormattedTimestamp(),
            'level'         => strtoupper($level),
            'level-padding' => str_repeat(' ', 9 - strlen($level)),
            'priority'      => $this->logLevels[$level],
            'message'       => $message,
            'exception'     => '',
            'pid'           => (string)getmypid(),
        ];
        if (array_key_exists('exception', $context) &&
                is_object($vars['exception']) &&
                ($vars['exception'] instanceof \Exception || $vars['exception'] instanceof \Throwable)) {
            // This includes stack trace and everything
            $vars['exception'] = (string)$context['exception'];
        }
        $vars = array_merge($vars, $context);

        $message = $this->options['logFormat'];
        if (!$message) {
            $message = '[{date}] [{level}] {message}';
        }

        foreach ($vars as $var => $value) {
            $message = str_replace('{' . $var . '}', $value, $message);
        }

        return rtrim($message) . PHP_EOL;
    }

    protected function getFormattedTimestamp()
    {
        $originalTime = microtime(true);
        $micro = sprintf("%06d", ($originalTime - floor($originalTime)) * 1000000);
        $date = new \DateTime(date('Y-m-d H:i:s.' . $micro, $originalTime));

        return $date->format($this->options['dateFormat']);
    }
}
