#  Rabbitmq Queue
#使用要求
需要 php-amqp 的拓展
需要rabbitmq 的rabbitmq_delayed_message_exchange 的插件
为什么用这个插件：当我们设置每一个消息不同的过期的时候，如：message1(10s过期)；message2(2s过期)；message3(5s过期)；
如果不用这个插件，由于的队列先进先出的原则，即是message2先过期，他消费的顺序也是message1，message2，message3，但是我们期望的是
message2，message3，message1 这样的顺序去消费，这个插件很好解决这个问题

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
//最后创建队列
$channel=$Amqp->createChannel();
//当然如果觉得上面的写法太繁琐也可以链式调用
list($channel,$connection)=$Amqp->setDelayedTime($delayedTime)->setMessage($message)->setQueueName($queueName)->createChannel();
```
## 如果需要监听消息是否发送成功
```php
$Amqp->setConfrim(true);//默认是的false 是不开启comfirm 机制的额
//发送成功是监听这个方法，$msg 是返回的一些数据，参数是回调函数，在回调函数在是是你的业务逻辑
$Amqp->sendSuccess(function ($msg){
            echo $msg->body;
});
//发送失败 用法和失败一样
$Amqp->sendFiled(function ($msg){
            echo $msg->body;
});               
```
## 发送消息
```php
$Amqp->send();
//同理你也可以循环的发送消息
//需要注意的是$Amqp->setQueueName($queueName)->createChannel()；者两个不能放在循环里面
for($i=0;$i<10;$i++){
    $Amqp->setDelayedTime($delayedTime)->setMessage($message)->send();
}        
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

****
