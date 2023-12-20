<?php

namespace Stackify\Log\Monolog;

use Stackify\Log\Entities\LogEntryInterface;
use Stackify\Log\Entities\NativeError;

use Monolog\Logger as MonologLogger;
use Monolog\LogRecord as MonologLogRecord;

final class LogEntry implements LogEntryInterface
{

    private $record;
    private $exception;
    private $context;
    private $nativeError;
    private $includeChannel;
    private $channel;
    private static $kebabCache = [];

    public function __construct(MonologLogRecord $record, bool $includeChannel = false)
    {
        $this->record = $record;

        $context = $record['context'];
        // find exception and remove from context
        foreach ($context as $key => $value) {
            if ($value instanceof \Exception) {
                $this->exception = $value;
                unset($context[$key]);
                break;
            }
        }
        if ($this->isNativeError($context)) {
            $this->nativeError = new NativeError(
                $context['code'],
                $context['message'],
                $context['file'],
                $context['line']
            );
            unset(
                $context['code'],
                $context['message'],
                $context['file'],
                $context['line']
            );
        }
        if (!empty($context)) {
            $this->context = $context;
        }

        $this->includeChannel = $includeChannel;
        $this->channel = null;
        if ($record && $record['channel']) {
            $this->channel = $record['channel'];
        }
    }

    public function getContext()
    {
        return $this->context;
    }

    public function getException()
    {
        return $this->exception;
    }

    public function getLevel()
    {
        return $this->record['level_name'];
    }

    public function getMessage()
    {
        if ($this->includeChannel && $this->channel) {
            return $this->record['message']." #{$this->kebabCase($this->channel)}";
        }

        return $this->record['message'];
    }

    public function getMilliseconds()
    {
        return round($this->record['datetime']->format('Uu') / 1000);
    }

    public function getNativeError() 
    {
        return $this->nativeError;
    }

    private function isNativeError(array $context)
    {
        // four fields must be defined: code, message, file, line
        // also code must be within predefined constants
        return isset($context['code'], $context['message'], $context['file'], $context['line'])
            && in_array($context['code'], NativeError::getPHPErrorTypes());
    }

    public function isErrorLevel()
    {
        return $this->record['level'] >= MonologLogger::ERROR;
    }


    private function kebabCase($value = '')
    {
        $key = $value;
        $delimiter = '-';

        if (isset(static::$kebabCache[$key][$delimiter])) {
            return static::$kebabCache[$key][$delimiter];
        }

        if (! ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value));
        }

        return static::$kebabCache[$key][$delimiter] = $value;
    }
}