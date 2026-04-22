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
use XmppFg\Xmpp\Connection\ConnectionTestDouble;
use XmppFg\Xmpp\Options;
use XmppFg\Xmpp\Event\XMLEvent;

#[CoversClass(\XmppFg\Xmpp\EventListener\Stream\Bind::class)]
class BindTest extends TestCase
{

    /**
     * @var Bind
     */
    protected $object;

    /**
     *
     * @var ConnectionTestDouble
     */
    protected $connection;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->object     = new Bind();
        $this->connection = new ConnectionTestDouble();

        $options = new Options;
        $options->setConnection($this->connection);
        $options->setUsername('foo/baz@test.com');
        $this->object->setOptions($options);
        $this->connection->setReady(true)->setOptions($options);
        $this->connection->connect();
    }

    /**
     * Test attaching events.
     *
     * @covers ::attachEvents
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Event\EventManager
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @uses XmppFg\Xmpp\Util\XML
     * @return void
     */
    public function testAttachEvents()
    {
        // run the test
        $this->object->attachEvents();

        // declare expected...
        $expected = [
            '*'                                      => [],
            '{urn:ietf:params:xml:ns:xmpp-bind}bind' => [[$this->object, 'bindFeatures']],
            '{urn:ietf:params:xml:ns:xmpp-bind}jid'  => [[$this->object, 'jid']]
        ];

        // now parse out the actual event list...
        $actual = [];

        foreach ($this->connection->getInputStream()->getEventManager()->getEventList() as $event => $callback_list) {
            $actual[$event] = [];

            foreach ($callback_list as $id => $callback) {
                $reflected_callback  = new \ReflectionFunction($callback);
                $actual[$event][$id] = [$reflected_callback->getClosureThis(), $reflected_callback->getName()];
            }
        }

        $this->assertSame($expected, $actual);
    }

    /**
     * Test handling bind event.
     *
     * @covers ::bindFeatures
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Event\EventManager
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @uses XmppFg\Xmpp\EventListener\Stream\AbstractSessionEvent
     * @uses XmppFg\Xmpp\Event\Event
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @uses XmppFg\Xmpp\Util\XML
     * @return void
     */
    public function testBind()
    {
        $document = new \DOMDocument;
        $document->loadXML('<features><bind/></features>');

        $event = new XMLEvent;
        $event->setParameters([$document->firstChild->firstChild]);

        $this->object->bindFeatures($event);

        $this->assertTrue($this->object->isBlocking());
        $buffer = $this->connection->getBuffer();
        $this->assertMatchesRegularExpression(
            '#<iq type="set" id="[^"]+">'
            .'<bind xmlns="urn:ietf:params:xml:ns:xmpp-bind">'
            .'<resource>baz@test.com</resource></bind></iq>#',
            $buffer[1]
        );
    }

    /**
     * Test handling jid event.
     *
     * @covers ::jid
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Event\EventManager
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @uses XmppFg\Xmpp\Event\Event
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @uses XmppFg\Xmpp\Util\XML
     * @uses XmppFg\Xmpp\EventListener\Stream\AbstractSessionEvent
     * @depends testBind
     * @return void
     */
    public function testJid()
    {
        $document = new \DOMDocument;
        $document->loadXML('<iq><bind><jid>nicejid</jid></bind></iq>');

        $event = new XMLEvent;
        $event->setParameters([$document->firstChild->firstChild->firstChild]);

        $this->object->jid($event);

        $this->assertFalse($this->object->isBlocking());
        $this->assertSame('nicejid', $this->object->getOptions()->getJid());
    }
}
