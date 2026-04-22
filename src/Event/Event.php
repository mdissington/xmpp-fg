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

use XmppFg\Xmpp\Exception\OutOfRangeException;

/**
 * Generic event.
 *
 * @package Xmpp\Event
 */
class Event implements EventInterface
{

    protected string $name;
    protected object $target;
    protected array $parameters = [];
    protected array $eventStack = [];

    #[\Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[\Override]
    public function getTarget(): object
    {
        return $this->target;
    }

    #[\Override]
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return $this
     */
    #[\Override]
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return $this
     */
    #[\Override]
    public function setTarget(object $target): self
    {
        $this->target = $target;
        return $this;
    }

    /**
     * @return $this
     */
    #[\Override]
    public function setParameters(array $parameters): self
    {
        $this->parameters = array_values($parameters);
        return $this;
    }

    #[\Override]
    public function getEventStack()
    {
        return $this->eventStack;
    }

    /**
     * @return $this
     */
    #[\Override]
    public function setEventStack(array $eventStack): self
    {
        $this->eventStack = $eventStack;
        return $this;
    }

    #[\Override]
    public function getParameter(int $index)
    {
        $parameters = $this->getParameters();

        if (!array_key_exists($index, $parameters)) {
            throw new OutOfRangeException('The offset '.$index.' is out of range.');
        }

        return $parameters[$index];
    }
}
