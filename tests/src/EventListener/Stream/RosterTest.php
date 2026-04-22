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
use XmppFg\Xmpp\Protocol\User\User;

#[CoversClass(\XmppFg\Xmpp\EventListener\Stream\Roster::class)]
class RosterTest extends TestCase
{

    /**
     * @var Roster
     */
    protected $object;

    /**
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
        $this->object     = new Roster;
        $this->connection = new ConnectionTestDouble();
        $options          = new Options;
        $options->setConnection($this->connection);
        $this->object->setOptions($options);
        $this->connection->setReady(true);
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
     * @return void
     */
    public function testAttachEvents()
    {
        $this->object->attachEvents();

        $output = $this->connection->getOutputStream()->getEventManager();
        $input  = $this->connection->getInputStream()->getEventManager();
        $this->assertArrayHasKey('{jabber:iq:roster}query', $output->getEventList());
        $this->assertArrayHasKey('{jabber:iq:roster}query', $input->getEventList());
    }

    /**
     * Test query event.
     *
     * @covers ::query
     * @covers ::isBlocking
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Options
     * @return void
     */
    public function testQuery()
    {
        $this->assertFalse($this->object->isBlocking());
        $this->object->query();
        $this->assertTrue($this->object->isBlocking());
    }

    /**
     * Test parsing result.
     *
     * @covers ::result
     * @uses XmppFg\Xmpp\EventListener\Stream\Roster::query
     * @uses XmppFg\Xmpp\EventListener\Stream\Roster::getUserObject
     * @uses XmppFg\Xmpp\EventListener\Stream\Roster::setUserObject
     * @uses XmppFg\Xmpp\EventListener\Stream\Roster::isBlocking
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Event\EventManager
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @uses XmppFg\Xmpp\Protocol\User\User
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @uses XmppFg\Xmpp\Event\Event
     * @return void
     */
    public function testResult()
    {
        $this->object->query();
        $this->assertTrue($this->object->isBlocking());

        $document = new \DOMDocument;
        $element  = $document->createElement('query');
        $item     = $document->createElement('item');
        $group    = $document->createElement('group', 'phpunit');

        $item->setAttribute('subscription', 'both');
        $item->setAttribute('name', 'Php Unit');
        $item->setAttribute('jid', 'phpunit@jabber.localhost.de');

        $element->appendChild($item);
        $item->appendChild($group);
        $document->appendChild($element);

        $event = new XMLEvent;
        $event->setParameters(array($element));

        $this->object->result($event);

        $users = $this->object->getOptions()->getUsers();
        $this->assertCount(1, $users);
        $this->assertSame('Php Unit', $users[0]->getName());
        $this->assertSame('both', $users[0]->getSubscription());
        $this->assertSame('phpunit@jabber.localhost.de', $users[0]->getJid());
        $this->assertContains('phpunit', $users[0]->getGroups());
        $this->assertFalse($this->object->isBlocking());
    }

    /**
     * Test setting and getting user object.
     *
     * @covers ::setUserObject
     * @covers ::getUserObject
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Options
     * @return void
     */
    public function testSetAndGetUserObject()
    {
        $this->assertInstanceOf(User::class, $this->object->getUserObject());
        $userObject = new User;
        $this->assertSame($userObject, $this->object->setUserObject($userObject)->getUserObject());
    }

}
