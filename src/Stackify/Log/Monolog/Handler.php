<?php

namespace Stackify\Log\Monolog;

use Stackify\Log\Builder\MessageBuilder;
use Stackify\Log\Transport\TransportInterface;
use Stackify\Log\Transport\AgentTransport;

use Monolog\Logger;
use Monolog\Handler\AbstractHandler;

class Handler extends AbstractHandler
{

    /**
     * @var \Stackify\Log\Transport\TransportInterface
     */
    private $transport;

    public function __construct($appName, $environmentName = null, TransportInterface $transport = null, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);
        $messageBuilder = new MessageBuilder('Stackify Monolog v.2.0', $appName, $environmentName);
        if (null === $transport) {
            $transport = new AgentTransport();
        }
        $transport->setMessageBuilder($messageBuilder);
        $this->transport = $transport;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $record): bool
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        $logEntry = new LogEntry($record);
        $this->transport->addEntry($logEntry);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        parent::close();
        $this->transport->finish();
    }
}