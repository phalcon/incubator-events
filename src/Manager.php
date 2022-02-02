<?php

namespace Phalcon\Incubator\Events;

use Closure;
use Phalcon\Events\Event;
use Phalcon\Events\Manager as PhalconManager;
use SplPriorityQueue;

class Manager extends PhalconManager
{
    public const EXCEPTION_HANDLER_INVALID = 'Event handler must be an Object or Callable';
    public const EXCEPTION_EVENT_TYPE_INVALID = 'Event type is not valid';

    public const HANDLER_KEY = 'class';
    public const PRIORITY_KEY = 'priority';

    /**
     *@param array $handlers
     *@return void
     */
    public function loadHandlers(array $handlers): void
    {
        foreach ($handlers as $event => $handler) {
            $this->loadHandler($event, $handler);
        }
    }

    /**
     * @param string $event
     * @param mixed $handler
     * @param int $priority
     * @return void
     */
    public function loadHandler(string $event, mixed $handler, int $priority = self::DEFAULT_PRIORITY): void
    {
        $this->checkEventType($event);

        if ($this->isValidHandler($handler)) {
            $this->attach($event, $handler, $priority);
        } elseif (is_string($handler) && class_exists($handler)) {
            $handler = new $handler();
            if ($this->isValidHandler($handler)) {
                $this->attach($event, $handler, $priority);
            } else {
                throw new \RuntimeException(self::EXCEPTION_HANDLER_INVALID);
            }
        } elseif (is_array($handler)) {
            if (isset($handler[self::HANDLER_KEY])) {
                if (isset($handler[self::PRIORITY_KEY])) {
                    $priority = (int) $handler[self::PRIORITY_KEY];
                }
                $this->loadHandler($event, $handler[self::HANDLER_KEY], $priority);
            } else {
                foreach ($handler as $subHandler) {
                    $this->loadHandler($event, $subHandler);
                }
            }
        } else {
            throw new \RuntimeException(self::EXCEPTION_HANDLER_INVALID);
        }
    }

    /**
     * @param string $eventType
     * @param mixed $handler
     * @param int $priority
     * @return void
     */
    public function attach(string $eventType, $handler, int $priority = self::DEFAULT_PRIORITY): void
    {
        if (false === $this->isValidHandler($handler)) {
            throw new \RuntimeException(self::EXCEPTION_HANDLER_INVALID);
        }

        $priorityQueue = $this->events[$eventType] ?? null;
        if (null === $priorityQueue) {
            $priorityQueue = new SplPriorityQueue();
            $priorityQueue->setExtractFlags(SplPriorityQueue::EXTR_DATA);
            $this->events[$eventType] = $priorityQueue;
        }

        if (!$this->enablePriorities) {
            $priority = self::DEFAULT_PRIORITY;
        }

        $priorityQueue->insert($handler, $priority);
    }

    /**
     * @param string $eventType
     * @param mixed $handler
     * @return void
     */
    public function detach(string $eventType, $handler): void
    {
        if (false === $this->isValidHandler($handler)) {
            throw new \RuntimeException(self::EXCEPTION_HANDLER_INVALID);
        }

        $priorityQueue = $this->events[$eventType] ?? null;
        if (!$priorityQueue instanceof SplPriorityQueue) {
            $newPriorityQueue = new SplPriorityQueue();
            $newPriorityQueue->setExtractFlags(SplPriorityQueue::EXTR_DATA);

            $priorityQueue->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
            $priorityQueue->top();

            while ($priorityQueue->valid()) {
                $data = $priorityQueue->current();
                $priorityQueue->next();

                if ($data['data'] !== $handler) {
                    $newPriorityQueue->insert(
                        $data['data'],
                        $data['priority']
                    );
                }
            }

            $this->events[$eventType] = $newPriorityQueue;
        }
    }

    /**
     * @param string $eventType
     * @param mixed $source
     * @param mixed|null $data
     * @param bool $cancelable
     * @param bool $strict
     */
    public function fire(string $eventType, $source, $data = null, bool $cancelable = true, bool $strict = false)
    {
        $this->checkEventType($eventType);

        if (!is_array($this->events)) {
            return null;
        }
        if ($this->collect) {
            $this->responses = null;
        }

        [$type, $eventName] = explode(':', $eventType);
        $event = new Event($eventName, $source, $data, $cancelable);
        $status = $this->fireEvents($type, $event, $status);
        $status = $this->fireEvents($eventType, $event, $status);

        return $status;
    }

    /**
     * @param string $eventType
     * @param Event $event
     * @param mixed $status
     * @return bool|null
     */
    public function fireEvents(string $eventType, Event $event, &$status = null): ?bool
    {
        $events = $this->events;
        $fireEvents = $events[$eventType] ?? null;
        if ($fireEvents instanceof SplPriorityQueue) {
            $status = $this->firePriorityQueue($fireEvents, $event);
        }

        return $status;
    }

    /**
     * @param SplPriorityQueue $queue
     * @param Event $event
     */
    public function firePriorityQueue(SplPriorityQueue $queue, Event $event)
    {
        $eventName = $event->getType();
        if (!is_string($eventName)) {
            throw new \RuntimeException(self::EXCEPTION_EVENT_TYPE_INVALID);
        }

        $source = $event->getSource();
        $data = $event->getData();
        $cancelable = $event->isCancelable();

        $status = null;
        $collect = (bool) $this->collect;
        $iterator = clone $queue;
        $iterator->top();

        while ($iterator->valid()) {
            $handler = $iterator->current();

            $iterator->next();

            if (false === $this->isValidHandler($handler)) {
                continue;
            }

            if ($handler instanceof Closure) {
                $status = $handler($event, $source, $data);
            } else {
                if (!method_exists($handler, $eventName)) {
                    continue;
                }

                $status = $handler->$eventName($event, $source, $data);
            }

            if ($collect) {
                $this->responses[] = $status;
            }

            if ($cancelable && $event->isStopped()) {
                break;
            }
        }

        return $status;
    }

    /**
     * @param string $eventType
     * @return bool
     */
    protected function checkEventType(string $eventType): bool
    {
        if (false === strpos($eventType, ':')) {
            throw new \RuntimeException(self::EXCEPTION_EVENT_TYPE_INVALID);
        }

        return true;
    }
}
