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

namespace XmppFg\Xmpp\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use XmppFg\Xmpp\Options;
use XmppFg\Xmpp\Connection\ConnectionTestDouble;

#[CoversClass(\XmppFg\Xmpp\Protocol\DefaultImplementation::class)]
class DefaultImplementationTest extends TestCase
{

    /**
     * @var DefaultImplementation
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->object = new DefaultImplementation;
    }

    /**
     * Test registering implementation.
     *
     * @covers ::register
     * @covers ::registerListener
     * @uses XmppFg\Xmpp\Protocol\DefaultImplementation::getOptions
     * @uses XmppFg\Xmpp\Protocol\DefaultImplementation::setOptions
     * @uses XmppFg\Xmpp\Protocol\DefaultImplementation::getEventManager
     * @uses XmppFg\Xmpp\Protocol\DefaultImplementation::setEventManager
     * @uses XmppFg\Xmpp\EventListener\Stream\StreamError
     * @uses XmppFg\Xmpp\EventListener\Stream\Authentication
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\EventListener\Stream\Bind
     * @uses XmppFg\Xmpp\EventListener\Stream\Roster
     * @uses XmppFg\Xmpp\EventListener\Stream\StartTls
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Event\EventManager
     * @uses XmppFg\Xmpp\EventListener\Stream\Session
     * @uses XmppFg\Xmpp\EventListener\Stream\Stream
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @return void
     */
    public function testRegister()
    {
        $connection   = new ConnectionTestDouble();
        $options      = new Options();
        $options->setConnection($connection);
        $this->object->setOptions($options);
        $eventManager = $this->object->getEventManager();

        $this->object->register();

        foreach ($connection->getListeners() as $listener) {
            $this->assertSame($eventManager, $listener->getEventManager());
            $this->assertSame($connection, $listener->getOptions()->getConnection());
        }
    }

    /**
     * Test setting and getting options.
     *
     * @covers ::getOptions
     * @covers ::setOptions
     * @return void
     */
    public function testSetAndGetOptions()
    {
        $options = $this->getMockBuilder(Options::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertSame($options, $this->object->setOptions($options)->getOptions());
    }

    /**
     * Test setting and getting event manager.
     *
     * @covers ::getEventManager
     * @covers ::setEventManager
     * @uses XmppFg\Xmpp\Event\EventManager
     * @return void
     */
    public function testSetAndGetEventManager()
    {
        $this->assertInstanceOf(\XmppFg\Xmpp\Event\EventManager::class, $this->object->getEventManager());
        $eventManager = $this->getMockBuilder(\XmppFg\Xmpp\Event\EventManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertSame($eventManager, $this->object->setEventManager($eventManager)->getEventManager());
    }

}
