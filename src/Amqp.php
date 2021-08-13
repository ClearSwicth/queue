<?php
/**
 *
 * User: daikai
 * Date: 2021/8/13
 */
namespace  Amqp\Queue;
require "vendor/autoload.php";
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class Amqp
{
    static $amqpContentInfo=[
        'host'=>'',
        'port'=>'',
        'user'=>'',
        'password'=>'',
        'vhost'=>'',

    ];

    /**
     * 连接的队列信息
     * @var array
     */
    protected $contentInfo=[];

    /**
     * 队列的名字
     * @var string
     */
    protected $queueName='';

    /**
     * 队列的延迟时间
     * @var string
     */
    protected $delayTime="";

    protected $message='';
    /**
     * Amqp constructor.
     * @param array $content
     */

    /**
     * @var
     */
    protected $connection;
    /**
     * @var
     */
    protected $channel;

    /**
     * Amqp constructor.
     * @param array $content
     */
    public function __construct($content = array())
    {
        if (!empty($content) && is_array($content)) {
            $this->contentInfo = array_intersect_key($content,self::$amqpContentInfo);
        }
    }

    /**
     * 设置队列的名称
     * @param Strings $queueName
     * @return $this
     * @author clearSwitch
     */
    public function setQueueName($queueName){
        $this->queueName=$queueName;
        return $this;
    }

    /**
     * 设置消息的延迟时间
     * @param Integer $time
     * @return $this
     * @author clearSwitch
     */
    public function setDelayedTime(Int $time){
        $this->delayTime=$time;
        return $this;
    }

    /**
     * 设置发送的消息
     * @param string $message
     * @return $this
     * @author clearSwitch
     */
    public function setMessage(string $message){
       $this->message=$message;
       return $this;
    }

    /**
     * 连接amqp
     * @author clearSwitch
     */
    public function createChannel(){
        $connection = new AMQPStreamConnection($this->contentInfo['host'], $this->contentInfo['port'], $this->contentInfo['user'], $this->contentInfo['password'],$this->contentInfo['vhost']);
        $channel = $connection->channel();
        $args = new AMQPTable(['x-delayed-type' => 'direct']);
        $channel->exchange_declare($this->queueName, 'x-delayed-message', false, true, false, false, false, $args);
        $args = new AMQPTable(['x-dead-letter-exchange' => 'delayed']);
        $channel->queue_declare($this->queueName, false, true, false, false, false, $args);
        $channel->queue_bind($this->queueName, $this->queueName);
        $this->channel=$channel;
        $this->connection=$connection;
        return [$channel,$connection];
    }

    /**
     * 生产者
     * @author clearSwitch
     */
    public function send(){
        $delay =$this->delayTime;
        $message = new AMQPMessage($this->message, array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
        if(empty($delay)) {
            $headers = new AMQPTable();
        }else{
            $headers = new AMQPTable(['x-delay' => $delay]);
        }
        $message->set('application_headers', $headers);
        echo " [".date('Y-m-d H:i:s',time())."]".$this->message,"\n";
        try{
            $this->channel->basic_publish($message, $this->queueName);
        }catch (\Exception $re){
            print_r($re->getMessage());
        }
    }

    /**
     * 消费者
     * @param $queueName
     * @param $callback
     * @throws \ErrorException
     * @author clearSwitch
     */
    public function receive($queueName,$callback){
        $connection = new AMQPStreamConnection($this->contentInfo['host'], $this->contentInfo['port'], $this->contentInfo['user'], $this->contentInfo['password'],$this->contentInfo['vhost']);
        $channel = $connection->channel();
        $channel->basic_consume($queueName, '', false, false, false, false, $callback);
        while (count($channel->callbacks)) {
            $channel->wait();
        }
        $this->channel->close();
        $this->connection->close();
    }

}