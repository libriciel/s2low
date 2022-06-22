<?php

use Pheanstalk\PheanstalkInterface;
use Pheanstalk\Pheanstalk;

class BeanstalkdWrapper
{
    private const DEFAULT_TTR = 60; /* 60 secondes * 20 */

    private $mode_beanstalkd;
    private $beanstalkd_server;
    private $beanstalkd_port;

    private $logger;

    public function __construct($mode_beanstalkd, $beanstalkd_server, $beanstalkd_port, S2lowLogger $s2lowLogger)
    {
        $this->mode_beanstalkd = $mode_beanstalkd;
        $this->beanstalkd_server = $beanstalkd_server;
        $this->beanstalkd_port = $beanstalkd_port;
        $this->logger = $s2lowLogger;
    }

    public function put($queue_name, $data, $delay = PheanstalkInterface::DEFAULT_DELAY)
    {
        if (! $this->mode_beanstalkd) {
            return true;
        }
        $queue = "undefined";
        try {
            $queue = new Pheanstalk($this->beanstalkd_server);
            $queue->useTube($queue_name)->put(
                $data,
                PheanstalkInterface::DEFAULT_PRIORITY,
                $delay,
                self::DEFAULT_TTR
            );
        } catch (Exception $e) {
            $this->logger->critical(
                "Unable to send data to queue : " . $e->getMessage(),
                ['queue' => $queue,'data' => $data]
            );
            return false;
        }
        return true;
    }

    public function isModeBeanstalked()
    {
        return $this->mode_beanstalkd;
    }

    /**
     * @param $queue_name
     * @return Pheanstalk
     */
    public function getQueue($queue_name)
    {
        $queue = new Pheanstalk($this->beanstalkd_server);
        $queue->watch($queue_name);
        return $queue;
    }


    public function emptyQueue($queue_name)
    {
        if (! $this->mode_beanstalkd) {
            return true;
        }
        $queue = new Pheanstalk($this->beanstalkd_server);
        $queue->watch($queue_name);
        while ($job = $queue->reserve(0)) {
            $queue->delete($job);
        }
        return true;
    }
}
