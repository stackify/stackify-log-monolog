<?php

namespace Stackify\Log\Monolog\Tests;

use Monolog\Level;
use Monolog\Test\TestCase;
use Psr\Log\LogLevel;
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

    public function testLogRecordChannel()
    {
        $level = Level::Info;
        $message = 'Hello, world! ' . $level->value;
        $context = ['foo' => 'bar', 'level' => $level->value];

        $transport = $this->createDummyTransport();

        $includeChannel = true;
        $channel = "test";
        $handler = $this->createDummyHandler($transport, $level, $includeChannel);
        $record = $this->getRecord($level, $message, context: $context, channel: $channel);
        $handler->handle($record);

        $logEntries = $transport->getEntries();

        $firstLogEntry = $logEntries[0];
        $this->assertEquals(1, count($logEntries));
        $this->assertEquals($firstLogEntry->getLevel(), strtoupper($record->level->name));
        $this->assertEquals($firstLogEntry->getMessage(), $record->message . " #{$channel}");

        // Reset every call
        $transport->reset();
    }

    public function testLogRecordChannelWithSpace()
    {
        $level = Level::Info;
        $message = 'Hello, world! ' . $level->value;
        $context = ['foo' => 'bar', 'level' => $level->value];

        $transport = $this->createDummyTransport();

        $includeChannel = true;
        $channel = "Test Channel";
        $handler = $this->createDummyHandler($transport, $level, $includeChannel);
        $record = $this->getRecord($level, $message, context: $context, channel: $channel);
        $handler->handle($record);

        $logEntries = $transport->getEntries();

        $firstLogEntry = $logEntries[0];
        $this->assertEquals(1, count($logEntries));
        $this->assertEquals($firstLogEntry->getLevel(), strtoupper($record->level->name));
        $this->assertEquals($firstLogEntry->getMessage(), $record->message . " #test-channel");

        // Reset every call
        $transport->reset();
    }


    public function testLogRecordWithExtra()
    {
        $level = Level::Info;
        $message = 'Hello, world! ' . $level->value;
        $context = ['foo' => 'bar', 'level' => $level->value];
        $extra = ['extra' => 'bar'];
        $mergedContext = array_merge($context, $extra);

        $transport = $this->createDummyTransport();

        $includeChannel = true;
        $includeExtra = true;
        $channel = "test";
        $handler = $this->createDummyHandler($transport, $level, $includeChannel, $includeExtra);
        $record = $this->getRecord($level, $message, context: $context, channel: $channel, extra: $extra);
        $handler->handle($record);

        $logEntries = $transport->getEntries();

        $firstLogEntry = $logEntries[0];
        $this->assertEquals(1, count($logEntries));
        $this->assertEquals($firstLogEntry->getLevel(), strtoupper($record->level->name));
        $this->assertEquals($firstLogEntry->getMessage(), $record->message . " #{$channel}");
        $this->assertSame($firstLogEntry->getContext(), $mergedContext);

        // Reset every call
        $transport->reset();
    }

    public function testLogRecordWithExtraButDisabledSetting()
    {
        $level = Level::Info;
        $message = 'Hello, world! ' . $level->value;
        $context = ['foo' => 'bar', 'level' => $level->value];
        $extra = ['extra' => 'bar'];
        $mergedContext = array_merge($context, $extra);

        $transport = $this->createDummyTransport();

        $includeChannel = true;
        $includeExtra = false;
        $channel = "test";
        $handler = $this->createDummyHandler($transport, $level, $includeChannel, $includeExtra);
        $record = $this->getRecord($level, $message, context: $context, channel: $channel, extra: $extra);
        $handler->handle($record);

        $logEntries = $transport->getEntries();

        $firstLogEntry = $logEntries[0];
        $this->assertEquals(1, count($logEntries));
        $this->assertEquals($firstLogEntry->getLevel(), strtoupper($record->level->name));
        $this->assertEquals($firstLogEntry->getMessage(), $record->message . " #{$channel}");
        $this->assertNotSame($firstLogEntry->getContext(), $mergedContext);

        // Reset every call
        $transport->reset();
    }

    private function createDummyHandler($transport, Level $level = null, $includeChannel = false, $includeExtra = false) {
        return $this->createHandler($this->appName, $this->environmentName, $transport, $level, $includeChannel, $includeExtra);
    }

    private function createHandler($appName, $environmentName, $transport, Level $level = null, bool $includeChannel = false, bool $includeExtra = false): Handler
    {
        $config = [];
        if ($includeChannel) {
            $config['IncludeChannel'] = true;
        }

        if ($includeExtra) {
            $config['IncludeExtraInContext'] = true;
        }

        if (null === $level) {
            $handler = new Handler($appName, $environmentName, $transport, false, $config, $level);
        } else {
            $handler = new Handler($appName, $environmentName, $transport, false, $config);
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
