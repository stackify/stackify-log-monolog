<?php

namespace Stackify\Log\Monolog\Tests;

use Monolog\Level;
use Monolog\Test\TestCase;
use Stackify\Log\Builder\BuilderInterface;
use Stackify\Log\Entities\LogEntryInterface;
use Stackify\Log\Monolog\Handler;
use Stackify\Log\Transport\TransportInterface;

class HandlerTest extends TestCase {
    
    protected $appName = 'Test App';
    protected $environmentName = 'Test Environment';

    public static function logLevelProvider()
    {
        return array_map(
            fn (Level $level) => [$level],
            Level::cases()
        );
    }

    /**
     * @dataProvider logLevelProvider
     */
    public function testHandlesAllLevels(Level $level)
    {
        $message = 'Hello, world! ' . $level->value;
        $context = ['foo' => 'bar', 'level' => $level->value];

        $transport = $this->createDummyTransport();

        $handler = $this->createDummyHandler($transport, $level);
        $record = $this->getRecord($level, $message, context: $context);
        $handler->handle($record);

        $logEntries = $transport->getEntries();

        $firstLogEntry = $logEntries[0];

        $this->assertEquals(1, count($logEntries));
        $this->assertEquals($firstLogEntry->getLevel(), strtoupper($record->level->name));
        $this->assertEquals($firstLogEntry->getMessage(), $record->message);

        // Reset every call
        $transport->reset();
    }

    private function createDummyHandler($transport, Level $level = null) {
        return $this->createHandler($this->appName, $this->environmentName, $transport, $level);
    }

    private function createHandler($appName, $environmentName, $transport, Level $level = null): Handler
    {
        if (null === $level) {
            $handler = new Handler($appName, $environmentName, $transport, false, null, $level);
        } else {
            $handler = new Handler($appName, $environmentName, $transport);
        }

        return $handler;
    }

    private function createDummyTransport()
    {
        return new SpyTransport();
    }
}


class SpyTransport implements TransportInterface {
    protected BuilderInterface $messageBuilder;
    protected array $logEntries;
    protected bool $hasFinished;

    public function __construct() {
        $this->logEntries = [];
        $this->hasFinished = false;
    }

    public function setMessageBuilder(BuilderInterface $messageBuilder) {
        $this->messageBuilder = $messageBuilder;
    }

    public function addEntry(LogEntryInterface $logEntry): int {
        $this->logEntries[] = $logEntry;
        return count($this->logEntries) - 1;
    }

    public function finish() {
        $this->hasFinished = true;
    }

    public function hasFinish() {
        return $this->hasFinished;
    }

    public function getEntries() {
        return $this->logEntries;
    }

    public function reset() {
        $this->logEntries = [];
        $this->hasFinished = false;
    }
}
