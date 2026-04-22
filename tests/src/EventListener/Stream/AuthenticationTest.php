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

use XmppFg\Xmpp\Connection\ConnectionTestDouble;
use XmppFg\Xmpp\Event\EventManager;
use XmppFg\Xmpp\Event\XMLEvent;
use XmppFg\Xmpp\Exception\Stream\StreamErrorException;
use XmppFg\Xmpp\Options;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\XmppFg\Xmpp\EventListener\Stream\Authentication::class)]
class AuthenticationTest extends TestCase
{

    /**
     * @var Authentication
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
        $this->object = new Authentication;
        $this->connection = new ConnectionTestDouble();

        $options = new Options;
        $options->setConnection($this->connection);
        $this->object->setOptions($options);
        $this->connection->setReady(true);
    }

    /**
     * Test what events are attached.
     *
     * @covers ::attachEvents
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Event\EventManager
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @return void
     */
    public function testAttachEvents()
    {
        $connection = new ConnectionTestDouble();
        $options = new Options;
        $options->setConnection($connection);
        $this->object->setOptions($options);
        $this->object->attachEvents();

        $input = $connection->getInputStream()->getEventManager();
        $this->assertArrayHasKey('{urn:ietf:params:xml:ns:xmpp-sasl}mechanisms', $input->getEventList());
        $this->assertArrayHasKey('{urn:ietf:params:xml:ns:xmpp-sasl}mechanism', $input->getEventList());
    }

    /**
     * Test collecting mechanisms from event.
     *
     * @covers ::collectMechanisms
     * @covers ::getMechanisms
     * @covers ::isBlocking
     * @covers ::isAuthenticated
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Event\Event
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @uses XmppFg\Xmpp\Options
     * @return void
     */
    public function testCollectMechanisms()
    {
        $element = new \DOMElement('mechanism', 'PLAIN');
        $event   = new XMLEvent;
        $event->setParameters(array($element));
        $this->object->collectMechanisms($event);
        $this->assertSame(array('plain'), $this->object->getMechanisms());

        $element = new \DOMElement('mechanism', 'DIGEST-MD5');
        $event->setParameters(array($element));
        $this->object->collectMechanisms($event);
        $this->assertSame(array('plain', 'digest-md5'), $this->object->getMechanisms());

        $this->assertTrue($this->object->isBlocking());
    }

    /**
     * Test authentication.
     *
     * @covers ::authenticate
     * @covers ::determineMechanismClass
     * @uses XmppFg\Xmpp\EventListener\Stream\Authentication::collectMechanisms
     * @uses XmppFg\Xmpp\EventListener\Stream\Authentication::isAuthenticated
     * @uses XmppFg\Xmpp\Util\XML
     * @uses XmppFg\Xmpp\Event\Event
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Event\EventManager
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @uses XmppFg\Xmpp\EventListener\Stream\Authentication\Plain
     * @return void
     */
    public function testAuthenticate()
    {
        $this->object->setEventManager(new EventManager);
        $this->object->getOptions()->setUsername('aaa')
            ->setPassword('bbb');

        $element = new \DOMElement('mechanism', 'PLAIN');
        $event   = new XMLEvent;
        $event->setParameters(array($element));
        $this->object->collectMechanisms($event);

        $element = new \DOMElement('mechanisms');
        $event   = new XMLEvent;
        $event->setParameters(array($element));

        $this->object->authenticate($event);
        $this->assertContains(
            '<auth xmlns="urn:ietf:params:xml:ns:xmpp-sasl" mechanism="PLAIN">AGFhYQBiYmI=</auth>',
            $this->connection->getBuffer()
        );
    }

    /**
     * Test authentication when no mechanisms where collected.
     *
     * @covers ::authenticate
     * @covers ::determineMechanismClass
     * @uses XmppFg\Xmpp\EventListener\Stream\Authentication::isAuthenticated
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Event\Event
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @return void
     */
    public function testAuthenticateWithoutMechanism()
    {
        $this->expectException(\XmppFg\Xmpp\Exception\RuntimeException::class);
        $this->expectExceptionMessage('No supported authentication mechanism found.');

        $element = new \DOMElement('mechanisms');
        $event   = new XMLEvent;
        $event->setParameters(array($element));

        $this->object->authenticate($event);
    }

    /**
     * Test authentication when mechanism class is invalid instance.
     *
     * @covers ::authenticate
     * @covers ::determineMechanismClass
     * @uses XmppFg\Xmpp\EventListener\Stream\Authentication::collectMechanisms
     * @uses XmppFg\Xmpp\EventListener\Stream\Authentication::isAuthenticated
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Event\Event
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @return void
     */
    public function testAuthenticateInvalidMechanismHandler()
    {
        $this->expectException(\XmppFg\Xmpp\Exception\RuntimeException::class);

        $this->object->getOptions()->setAuthenticationClasses(array('plain' => '\stdClass'));

        $element = new \DOMElement('mechanism', 'PLAIN');
        $event   = new XMLEvent;
        $event->setParameters(array($element));
        $this->object->collectMechanisms($event);

        $element = new \DOMElement('mechanisms');
        $event   = new XMLEvent;
        $event->setParameters(array($element));

        $this->object->authenticate($event);
    }

    /**
     * Test authentication failure.
     *
     * @covers ::failure
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Event\EventManager
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @uses XmppFg\Xmpp\Event\Event
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @uses XmppFg\Xmpp\Exception\Stream\StreamErrorException
     * @return void
     */
    public function testFailure()
    {
        $this->expectException(StreamErrorException::class);

        $document = new \DOMDocument;
        $element = new \DOMElement('failure');
        $document->appendChild($element);
        $event   = new XMLEvent;
        $event->setParameters(array($element));

        try {
            $this->object->failure($event);
        } catch (StreamErrorException $e) {
            $this->assertFalse($this->object->isBlocking());
            $this->assertSame('<failure/>', $e->getContent());
            throw $e;
        }
    }

    /**
     * Test successful authentication.
     *
     * @covers ::success
     * @uses XmppFg\Xmpp\EventListener\Stream\Authentication::isBlocking
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @uses XmppFg\Xmpp\Event\Event
     * @return void
     */
    public function testSuccess()
    {
        $element = new \DOMElement('success');
        $event   = new XMLEvent;
        $event->setParameters(array($element));

        $connection = $this->createMock(ConnectionTestDouble::class);
        $this->object->getOptions()->setConnection($connection);

        $connection->expects($this->once())
            ->method('resetStreams');
        $connection->expects($this->once())
            ->method('connect');

        $this->object->success($event);
        $this->assertFalse($this->object->isBlocking());
        $this->assertTrue($this->object->getOptions()->isAuthenticated());
    }

}
