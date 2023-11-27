<?php
namespace Stackify\Log\Monolog;

use Stackify\Log\Builder\MessageBuilder;
use Stackify\Log\Transport\TransportInterface;
use Stackify\Log\Transport\AgentSocketTransport;
use Stackify\Log\Transport\Config\Agent as AgentConfig;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class Handler extends AbstractProcessingHandler
{
    /**
     * Stackify transport
     *
     * @var \Stackify\Log\Transport\TransportInterface
     */
    private TransportInterface $_transport;

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
        string $appName,
        string $environmentName = null,
        TransportInterface $transport = null,
        bool $logServerVariables = false,
        array $config = null,
        int|string|Level $level = Level::Debug,
        bool $bubble = true
    ) {
        
        parent::__construct($level, $bubble);

        if ($config) {
            // NOTE/TODO: Make extractOptions public for the transport 
            // so we have more control.
            AgentConfig::getInstance()->extract($config);
        }

        $messageBuilder = new MessageBuilder('Stackify Monolog v.3.0', $appName, $environmentName, $logServerVariables);

        if (null === $transport) {
            $transport = new AgentSocketTransport();
        }

        $transport->setMessageBuilder($messageBuilder);
        $this->_transport = $transport;
    }

    /**
     * {@inheritdoc}
     *
     * @param LogRecord $record
     *
     * @return void
     */
    public function write(LogRecord $record): void
    {
        $this->_transport->addEntry(new LogEntry($record));
    }

    /**
     * Flush logs to API
     *
     * @return void
     */
    public function flush(): void
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
    public function reset(): void
    {
        $this->flush();
        parent::reset();
    }
}
