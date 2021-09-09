<?php


namespace Swift\Framework\EventDispatcher;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
/**
 * Class GlobalEventDispatcher
 * @package Tiny\Framework
 *
 * @method static dispatch($event, string $eventName = null)
 * @method static addListener($eventName, $listener, $priority = 0)
 * @method static getListeners($eventName = null)
 * @method static getListenerPriority($eventName, $listener)
 * @method static hasListeners($eventName = null)
 * @method static removeListener($eventName, $listener)
 * @method static addSubscriber(EventSubscriberInterface $subscriber)
 * @method static removeSubscriber(EventSubscriberInterface $subscriber)
 */
class GlobalEventDispatcher
{
    /**
     * @var EventDispatcher | null
     */
    private static $dispatcher = null;

    /**
     * 静态代理方法
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([self::getDispatcher(), $name], $arguments);
    }

    /**
     * 移除所有指定名称的事件
     * @param string|null $eventName
     */
    public static function removeAllListeners(string $eventName = null)
    {
        if ($eventName !== null) {
            foreach (self::$dispatcher->getListeners($eventName) as $listener) {
                self::$dispatcher->removeListener($eventName, $listener);
            }
        } else {
            foreach (self::$dispatcher->getListeners() as $name => $listeners) {
                foreach ($listeners as $listener) {
                    self::$dispatcher->removeListener($name, $listener);
                }
            }
        }
    }

    /**
     * 获取事件调度器
     * @return EventDispatcher
     */
    public static function getDispatcher(): ?EventDispatcher
    {
        if (self::$dispatcher === null) {
            self::$dispatcher = new EventDispatcher();
        }

        return self::$dispatcher;
    }
}
