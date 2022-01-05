<?php
namespace Stackify\Log\Monolog;

use Stackify\Log\Builder\MessageBuilder;
use Stackify\Log\Transport\TransportInterface;
use Stackify\Log\Transport\AgentSocketTransport;
use Stackify\Log\Transport\Config\Agent as AgentConfig;

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
        $config = null,
        $level = Logger::DEBUG,
        $bubble = true
    ) {
        parent::__construct($level, $bubble);

        if ($config) {
            // NOTE/TODO: Make extractOptions public for the transport 
            // so we have more control.
            AgentConfig::getInstance()->extract($config);
        }

        $messageBuilder = new MessageBuilder('Stackify Monolog v.2.0', $appName, $environmentName, $logServerVariables);

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
    public function write(array $record): void
    {
        $this->_transport->addEntry(new LogEntry($record));
    }

    /**
     * Flush logs to API
     *
     * @return void
     */
    public function flush()
    {
        $this->_transport->finish();
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function close(): void
    {
        $this->flush();
        parent::close();
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function reset()
    {
        $this->flush();
        parent::reset();
    }
}
