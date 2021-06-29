<?php


namespace App\Service;

use ErrorException;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel as AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


class RabbitMQ
{
    const QUEUE_DECLARE_PASSIVE = false;
    const QUEUE_DECLARE_DURABLE = false;
    const QUEUE_DECLARE_EXCLUSIVE = false;
    const QUEUE_DECLARE_AUTO_DELETE = false;

    const MSG_EXCHANGE = '';

    const CONSUME_NO_LOCAL = false;
    const CONSUME_NO_ACK = true;
    const CONSUME_EXCLUSIVE = false;
    const CONSUME_NOWAIT = false;


    /**
     * @var AMQPStreamConnection $connection
     */
    private $connection;

    /**
     * @var AMQPChannel $chanel
     */
    private $channel;

    /**
     * Create a connection to RabbitAMQP
     * @return AMQPStreamConnection
     */
    public function setConnection(): AMQPStreamConnection
    {
        $this->connection = new AMQPStreamConnection(
            $_ENV['RABBIT_HOST'],
            $_ENV['RABBIT_PORT'],
            $_ENV['RABBIT_USER'],
            $_ENV['RABBIT_PASS']
        );
        return $this->connection;
    }

    /**
     * @return AMQPStreamConnection
     */
    public function getConnection(): AMQPStreamConnection
    {
        return $this->connection;
    }

    /**
     * @return Void
     */
    public function setChanel()
    {
        $this->channel = $this->connection->channel();
    }

    /**
     * @return AMQPChannel
     */
    public function getChannel(): AMQPChannel
    {
        return $this->channel;
    }

    /**
     * @param string $queueName
     */
    public function queueDeclare(string $queueName)
    {
        $this->channel->queue_declare(
            $queueName,
            RabbitMQ::QUEUE_DECLARE_PASSIVE,
            RabbitMQ::QUEUE_DECLARE_DURABLE,
            RabbitMQ::QUEUE_DECLARE_EXCLUSIVE,
            RabbitMQ::QUEUE_DECLARE_AUTO_DELETE
        );
    }

    /**
     * @param string $message
     * @param string $queueName
     */
    public function sendMessage(string $message, string $queueName)
    {
        $msg = new AMQPMessage($message);

        $this->channel->basic_publish(
            $msg,
            RabbitMQ::MSG_EXCHANGE,
            $queueName
        );
    }

    /**
     * @throws Exception
     */
    public function close()
    {
        $this->channel->close();
        $this->connection->close();
    }

    /**
     * @param string $queueName
     * @param $callback
     * @throws ErrorException
     */
    public function consume(string $queueName, $callback)
    {
        $queue = $this->channel->basic_consume(
            $queueName,                    #queue
            RabbitMQ::MSG_EXCHANGE,
            RabbitMQ::CONSUME_NO_LOCAL,
            RabbitMQ::CONSUME_NO_ACK,
            RabbitMQ::CONSUME_EXCLUSIVE,
            RabbitMQ::CONSUME_NOWAIT,
            $callback
        );

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

}