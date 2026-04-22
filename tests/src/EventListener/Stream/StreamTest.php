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

namespace XmppFg\Xmpp\EventListener\Stream;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use XmppFg\Xmpp\Event\XMLEvent;
use XmppFg\Xmpp\Connection\ConnectionTestDouble;
use XmppFg\Xmpp\Options;

#[CoversClass(\XmppFg\Xmpp\EventListener\Stream\Stream::class)]
class StreamTest extends TestCase
{

    /**
     * @var Stream
     */
    protected Stream $object;

    /**
     *
     * @var ConnectionTestDouble
     */
    protected ConnectionTestDouble $connection;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->object     = new Stream;
        $this->connection = new ConnectionTestDouble();
        $options          = new Options;
        $options->setConnection($this->connection);
        $this->object->setOptions($options);
        $this->connection->setReady(true);
    }

    /**
     * Test what event are attached.
     *
     * @covers ::attachEvents
     * @covers ::getInputEventManager
     * @covers ::getOutputEventManager
     * @covers ::getConnection
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Event\EventManager
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @return void
     */
    public function testAttachEvents()
    {
        $this->object->attachEvents();

        $output = $this->connection->getOutputStream()->getEventManager();
        $input  = $this->connection->getInputStream()->getEventManager();
        $this->assertArrayHasKey('{http://etherx.jabber.org/streams}stream', $output->getEventList());
        $this->assertArrayHasKey('{http://etherx.jabber.org/streams}features', $input->getEventList());
    }

    /**
     * Test starting client stream.
     *
     * @covers ::streamStart
     * @covers ::streamServer
     * @covers ::features
     * @covers ::isBlocking
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Event\Event
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @return void
     */
    public function testEvents()
    {
        $element = new \DOMElement('mechanism', 'PLAIN');
        $event   = new XMLEvent;
        $event->setParameters(array($element));
        $this->connection->setReady(false);

        $this->assertFalse($this->object->isBlocking());
        $event->setIsStartTag(true);
        $this->object->streamStart($event);
        $this->assertTrue($this->object->isBlocking());

        $event->setIsStartTag(false);
        $this->object->streamServer($event);
        $this->assertFalse($this->object->isBlocking());
        $this->assertFalse($this->connection->isConnected());

        $event->setIsStartTag(true);
        $this->object->streamStart($event);
        $event->setIsStartTag(false);
        $this->object->features();
        $this->assertFalse($this->object->isBlocking());
        $this->assertTrue($this->connection->isReady());
    }

}
