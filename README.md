#  Rabbitmq Queue
生产者
## 创建生产者
```php
use Amqp\Queue\Amqp;
/**
 * 创建生产者
 *  $rabbitmqInfo=['host'=>'127.0.0.1',
     'port'=>'5672',
     'user'=>'admin',
     'password'=>'123456',
     'vhost'=>'qa1']
 * 通过选择构造器来设置请求参数的
 */
       $Amqp=new Amqp($rabbitmqInfo);
//设置队列的延迟时间 单位是毫秒级的
$Amqp->setDelayedTime(1000);
//设置发送消息体  string
$Amqp->setMessage($body);
//设置队列的名字
$Amqp->setQueueName($queueName);
//最后穿件队列
$channel=$Amqp->createChannel();
//当然如果觉得上面的写法太繁琐也可以链式调用
list($channel,$connection)=$Amqp->setDelayedTime($delayedTime)->setMessage($message)->setQueueName($queueName)->createChannel();
```
## 发送消息
```php
$Amqp->send();
```

## 发送完消息就可以 关闭通道和连接
```php
$channel->close();
$connection->close();
```

## 消费者
```php
$Amqp=new Amqp($rabbitmqInfo);
//这个是消费者的回调函数，就这这里面处理接受到的消息
$callback = function ($message) {
 //获得生产者的生产的消息
$message->getBody();
//消息处理完之后，删除消息
$message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
};
//监听者
$Amqp->receive('haha',$callback);

