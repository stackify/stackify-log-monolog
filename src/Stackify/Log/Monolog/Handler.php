<?php
namespace Stackify\Log\Monolog;

use Stackify\Log\Builder\MessageBuilder;
use Stackify\Log\Transport\TransportInterface;
use Stackify\Log\Transport\AgentSocketTransport;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

class Handler extends AbstractProcessingHandler
{
    /**
     * Stackify transport
     *
     * @var \Stackify\Log\Transport\TransportInterface
     */
    private $_transport;

    /**
     * Stackify monolog handler
     *
     * @param string             $appName
     * @param string             $environmentName
     * @param TransportInterface $transport
     * @param boolean            $logServerVariables
     * @param int                $level
     * @param boolean            $bubble
     */
    public function __construct(
        $appName,
        $environmentName = null,
        TransportInterface $transport = null,
        $logServerVariables = false,
        $level = Logger::DEBUG,
        $bubble = true
    ) {
        parent::__construct($level, $bubble);
        $messageBuilder = new MessageBuilder('Stackify Monolog v.1.0', $appName, $environmentName, $logServerVariables);
        if (null === $transport) {
            $transport = new AgentSocketTransport();
        }
        $transport->setMessageBuilder($messageBuilder);
        $this->_transport = $transport;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $record
     *
     * @return void
     */
    public function write(array $record)
    {
        $this->_transport->addEntry(new LogEntry($record));
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function close()
    {
        parent::close();
        $this->_transport->finish();
    }
}
