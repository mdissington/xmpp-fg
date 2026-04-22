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

namespace XmppFg\Xmpp\Connection;

use XmppFg\Xmpp\Event\EventManager;
use XmppFg\Xmpp\Event\EventManagerInterface;
use XmppFg\Xmpp\EventListener\BlockingEventListenerInterface;
use XmppFg\Xmpp\EventListener\EventListenerInterface;
use XmppFg\Xmpp\Exception\TimeoutException;
use XmppFg\Xmpp\Options;
use XmppFg\Xmpp\Stream\XMLStream;
use Psr\Log\LogLevel;

/**
 * Connection test double.
 *
 * @package Xmpp\Connection
 */
abstract class AbstractConnection implements ConnectionInterface {

    /**
     * @var XMLStream
     */
    protected $outputStream;

    /**
     * @var XMLStream
     */
    protected $inputStream;

    /**
     * @var Options
     */
    protected $options;

    /**
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * Event listeners.
     *
     * @var EventListenerInterface[]
     */
    protected $listeners      = [];
    protected bool $connected = false;
    protected bool $ready     = false;

    /**
     * Timestamp of last response data received.
     */
    protected int $lastResponse = 0;

    /**
     * Last blocking event listener.
     *
     * Cached to reduce debug output a bit.
     *
     * @var BlockingEventListenerInterface
     */
    protected $lastBlockingListener;

    #[\Override]
    public function getOutputStream() {
        if ( null === $this->outputStream ) {
            $this->outputStream = new XMLStream();
        }

        return $this->outputStream;
    }

    #[\Override]
    public function getInputStream() {
        if ( null === $this->inputStream ) {
            $this->inputStream = new XMLStream();
        }

        return $this->inputStream;
    }

    #[\Override]
    public function setOutputStream( XMLStream $outputStream ) {
        $this->outputStream = $outputStream;
        return $this;
    }

    #[\Override]
    public function setInputStream( XMLStream $inputStream ) {
        $this->inputStream = $inputStream;
        return $this;
    }

    #[\Override]
    public function addListener( EventListenerInterface $eventListener ) {
        $this->listeners[] = $eventListener;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    #[\Override]
    public function isConnected() {
        return $this->connected;
    }

    /**
     * @codeCoverageIgnore
     */
    #[\Override]
    public function isReady() {
        return $this->ready;
    }

    #[\Override]
    public function setReady( $flag ) {
        $this->ready = (bool)$flag;
        return $this;
    }

    /**
     * @return void
     */
    #[\Override]
    public function resetStreams() {
        $this->getInputStream()->reset();
        $this->getOutputStream()->reset();
    }

    #[\Override]
    public function getEventManager() {
        if ( null === $this->events ) {
            $this->setEventManager(new EventManager());
        }

        return $this->events;
    }

    #[\Override]
    public function setEventManager( EventManagerInterface $events ) {
        $this->events = $events;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return EventListenerInterface[]
     */
    public function getListeners(): array {
        return $this->listeners;
    }

    /**
     * @codeCoverageIgnore
     */
    #[\Override]
    public function getOptions() {
        return $this->options;
    }

    #[\Override]
    public function setOptions( Options $options ) {
        $this->options = $options;
        return $this;
    }

    /**
     * @return void
     */
    protected function log( $message, string $level = LogLevel::DEBUG ) {
        $this->getEventManager()->trigger('logger', $this, [ $message, $level ]);
    }

    protected function checkBlockingListeners(): bool {
        $blocking = false;

        foreach ( $this->listeners as $listener ) {
            if ( $listener instanceof BlockingEventListenerInterface && $listener->isBlocking() === true ) {
                // cache the last blocking listener. Reducing output.
                if ( $this->lastBlockingListener !== $listener ) {
                    //$this->log('Listener '.get_class($listener).' is currently blocking');
                    $this->lastBlockingListener = $listener;
                }

                $blocking = true;
            }
        }

        return $blocking;
    }

    /**
     * Check for timeout.
     * @param ?string $buffer Function required current received buffer
     * @throws TimeoutException
     */
    protected function checkTimeout( ?string $buffer ): void {
        if ( !empty($buffer) ) {
            $this->lastResponse = time();

            return;
        }

        if ( empty($this->lastResponse) ) {
            $this->lastResponse = time();
        }

        $timeout = $this->getOptions()->getTimeout();

        if ( time() >= $this->lastResponse + $timeout ) {
            throw new TimeoutException(sprintf('Connection timed out after (%d) seconds', (time() - $this->lastResponse)), __LINE__);
        }
    }
}
