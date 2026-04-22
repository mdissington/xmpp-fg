<?php

/**
 * Copyright 2014 Fabian Grutschus. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The views and conclusions contained in the software and documentation are those
 * of the authors and should not be interpreted as representing official policies,
 * either expressed or implied, of the copyright holders.
 *
 * @author    Fabian Grutschus <f.grutschus@lubyte.de>
 * @copyright 2014 Fabian Grutschus. All rights reserved.
 * @license   BSD
 * @link      http://github.com/fabiang/xmpp
 */

namespace XmppFg\Xmpp\Event;

use XmppFg\Xmpp\Exception\InvalidArgumentException;

/**
 * Event manager.
 *
 * The EventManager holds and triggers events.
 *
 * @package Xmpp\Event
 */
class EventManager implements EventManagerInterface
{

    const WILDCARD = '*';

    /**
     * Attached events
     * @var array<string,array<int,callable>>
     */
    protected array $events = [self::WILDCARD => []];
    protected EventInterface $eventObject;

    /**
     * Constructor sets default event object.
     */
    public function __construct(?EventInterface $eventObject = null)
    {
        if (null === $eventObject) {
            $eventObject = new Event();
        }

        $this->eventObject = $eventObject;
    }

    #[\Override]
    public function attach(string $event, callable $callback): self
    {
        if (!isset($this->events[$event])) {
            $this->events[$event] = [];
        }

        if (!in_array($callback, $this->events[$event], true)) {
            $this->events[$event][] = $callback;
        }

        return $this;
    }

    #[\Override]
    public function trigger(string $event, object $caller, array $parameters): void
    {
        if (empty($this->events[$event]) && empty($this->events[self::WILDCARD])) {
            return;
        }

        $events = [];
        if (!empty($this->events[$event])) {
            $events = $this->events[$event];
        }

        $callbacks = array_merge($events, $this->events[self::WILDCARD]);
        $previous  = [];

        $eventObject = clone $this->getEventObject();
        $eventObject->setName($event);
        $eventObject->setTarget($caller);
        $eventObject->setParameters($parameters);

        do {
            $current = array_shift($callbacks);

            $current($eventObject);

            $previous[]  = $current;
            $eventObject = clone $eventObject;
            $eventObject->setEventStack($previous);
        } while (count($callbacks) > 0);
    }

    #[\Override]
    public function getEventObject(): EventInterface
    {
        return $this->eventObject;
    }

    #[\Override]
    public function setEventObject(EventInterface $eventObject): self
    {
        $this->eventObject = $eventObject;
        return $this;
    }

    #[\Override]
    public function getEventList(): array
    {
        return $this->events;
    }
}
