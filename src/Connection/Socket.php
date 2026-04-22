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

use XmppFg\Xmpp\Exception\SocketException;
use XmppFg\Xmpp\Exception\TimeoutException;
use XmppFg\Xmpp\Exception\Stream\StreamErrorException;
use XmppFg\Xmpp\Stream\SocketClient;
use XmppFg\Xmpp\Stream\SocksProxy;
use XmppFg\Xmpp\Options;
use XmppFg\Xmpp\Util\XML;

/**
 * Connection to a socket stream.
 *
 * @package Xmpp\Connection
 */
class Socket extends AbstractConnection implements SocketConnectionInterface
{

    const DEFAULT_LENGTH = 4096;
    const STREAM_START   = <<<'XML'
                            <?xml version="1.0" encoding="UTF-8"?>
                            <stream:stream to="%s" xmlns:stream="http://etherx.jabber.org/streams" xmlns="jabber:client" version="1.0">
                            XML;
    const STREAM_END     = '</stream:stream>';

    protected SocketClient $socket;

    /**
     * Have we received any data yet?
     */
    private bool $receivedAnyData = false;

    public function __construct(SocketClient $socket)
    {
        $this->setSocket($socket);
    }

    public static function factory(Options $options): static
    {
        if ($options->getSocksProxyAddress()) {
            $socket = new SocksProxy($options);
        } else {
            $socket = new SocketClient($options->getAddress() ?? '', $options->getContextOptions());
        }

        $object = new static($socket);
        $object->setOptions($options);

        return $object;
    }

    /**
     * @throws SocketException
     * @throws StreamErrorException
     * @throws \XmppFg\Xmpp\Exception\ErrorException
     * @throws \XmppFg\Xmpp\Exception\XMLParserException
     */
    #[\Override]
    public function receive(): string
    {
        $buffer = '';

        try {
            if (false === $this->isConnected()) {
                $this->connect();
            }

            $buffer = $this->getSocket()->read(static::DEFAULT_LENGTH);

            if ($buffer) {
                $this->lastResponse    = time();
                $this->receivedAnyData = true;
                $message               = $this->getInputStream()->parse($buffer);
                $this->getEventManager()->trigger('receive', $this, [$message, $buffer]);
                $this->log(sprintf('read buffer: "%s"', $buffer));
            }

            $this->checkTimeout($buffer);
        } catch (StreamErrorException | SocketException $e) {
            if ($this->connected) {
                //$this->disconnect(); // don't call disconnect as it tries to do a "clean shutdown" and send a "</stream>" to close the stream - but we've already lost the connection...
                $this->setReady(false);
                $this->connected = false;
                $this->getOptions()->setAuthenticated(false);
                $this->getSocket()->close();
            }

            throw $e;
        } catch (TimeoutException $e) {
            $this->reconnectTls();
        }

        return $buffer;
    }

    /**
     * Try to reconnect via TLS if connecting via TCP failed
     * @throws \XmppFg\Xmpp\Exception\ErrorException
     */
    private function reconnectTls(): void
    {
        // check if we've received any data, if not, we retry to connect via TLS
        if ($this->receivedAnyData === false) {
            $matches         = [];
            $previousAddress = $this->getOptions()->getAddress();

            // only reconnect via tls if we've used tcp before.
            if (preg_match('#tcp://(?<address>.+)#', $previousAddress, $matches)) {
                $this->log('Connecting via TCP failed, now trying to connect via TLS');

                $address         = 'tls://'.$matches['address'];
                $this->connected = false;
                $this->getOptions()->setAddress($address);
                $this->getSocket()->reconnect($address);
                $this->connect();
            }
        }
    }

    /**
     * @throws SocketException
     * @throws StreamErrorException
     * @throws \XmppFg\Xmpp\Exception\ErrorException
     * @throws \XmppFg\Xmpp\Exception\XMLParserException
     */
    #[\Override]
    public function send($buffer): void
    {
        try {
            if (false === $this->isConnected()) {
                $this->connect();
            }

            $this->getSocket()->write($buffer);
            $message        = $this->getOutputStream()->parse($buffer);
            $this->getEventManager()->trigger('send', $this, [$message, $buffer]);
            $receive_buffer = '';

            while ($this->checkBlockingListeners()) {
                $receive_buffer = $this->receive();
            }
        } catch (StreamErrorException | SocketException $e) {
            if ($this->connected) {
                //$this->disconnect(); // don't call disconnect as it tries to do a "clean shutdown" and send a "</stream>" to close the stream - but we've already lost the connection...
                $this->setReady(false);
                $this->connected = false;
                $this->getOptions()->setAuthenticated(false);
                $this->getSocket()->close();
            }

            throw $e;
        }
    }

    /**
     * @throws \XmppFg\Xmpp\Exception\ErrorException
     */
    #[\Override]
    public function connect(): void
    {
        if (false === $this->connected) {
            $address         = $this->getAddress();
            $this->log(sprintf('Connecting to "%s" timeout: (%d)...', $address, $this->getOptions()->getTimeout()));
            $this->getSocket()->connect($this->getOptions()->getTimeout());
            $this->getSocket()->setBlocking(true);
            $this->connected = true;
            $this->log(sprintf('Connected to "%s"', $address));
        }

        $this->send(sprintf(static::STREAM_START, XML::quote($this->getOptions()->getTo())));
    }

    #[\Override]
    public function disconnect(): void
    {
        if (true === $this->connected) {
            $address         = $this->getAddress();
            $this->send(static::STREAM_END);
            $this->getSocket()->close();
            $this->connected = false;
            $this->log(sprintf('Disconnected from "%s"', $address));
        }
    }

    /**
     * Get address from options object
     */
    protected function getAddress(): ?string
    {
        return $this->getOptions()->getAddress();
    }

    #[\Override]
    public function getSocket(): SocketClient
    {
        return $this->socket;
    }

    #[\Override]
    public function setSocket(SocketClient $socket): self
    {
        $this->socket = $socket;
        return $this;
    }
}
