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
 * @link      http://github.com/XmppFg/xmpp
 */

namespace XmppFg\Xmpp\Connection;

use XmppFg\Xmpp\Event\Event;
use XmppFg\Xmpp\Event\EventManager;
use XmppFg\Xmpp\EventListener\Stream\Stream;
use XmppFg\Xmpp\Options;
use XmppFg\Xmpp\Stream\SocketClient;
use XmppFg\Xmpp\Stream\XMLStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

#[CoversClass(\XmppFg\Xmpp\Connection\Socket::class)]
class SocketTest extends TestCase
{

    protected Socket $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     * @return void
     */
    #[\Override]
    protected function setUp(): void
    {
        $mock = $this->getMockBuilder(\XmppFg\Xmpp\Stream\SocketClient::class)
            ->setConstructorArgs(array( 'tcp://localhost:9999' ))
            ->onlyMethods([ 'read', 'write', 'connect', 'close', 'setBlocking' ])
            ->getMock();

        $this->object = new Socket($mock);
        $this->object->setOptions(new Options('tcp://localhost:9999'));
    }

    /**
     * Test constructor.
     *
     * @covers ::__construct
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Connection\Socket::setSocket
     * @uses XmppFg\Xmpp\Connection\Socket::getSocket
     * @uses XmppFg\Xmpp\Stream\SocketClient
     * @uses XmppFg\Xmpp\Options
     * @return void
     */
    public function testConstructor()
    {
        $mock   = $this->createMock(SocketClient::class);
        $object = new Socket($mock);
        $this->assertSame($mock, $object->getSocket());
    }

    /**
     * Test receivding data.
     *
     * @covers ::receive
     * @uses XmppFg\Xmpp\Connection\Socket::__construct
     * @uses XmppFg\Xmpp\Connection\Socket::setSocket
     * @uses XmppFg\Xmpp\Stream\SocketClient
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Connection\Socket::getAddress
     * @uses XmppFg\Xmpp\Connection\Socket::getSocket
     * @uses XmppFg\Xmpp\Stream\SocketClient
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @uses XmppFg\Xmpp\Util\XML
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Event\EventManager
     * @return void
     */
    public function testReceive()
    {
        $return = '<xml xmlns="test"></xml>';

        /** @var SocketClient&\PHPUnit\Framework\MockObject\MockObject $mock */
        $mock   = $this->object->getSocket();

        $mock->expects($this->once())
            ->method('read')
            ->with($this->equalTo(4096))
            ->willReturn($return);

        $this->assertSame($return, $this->object->receive());
    }

    /**
     * Test sending data.
     *
     * @covers ::send
     * @uses XmppFg\Xmpp\Connection\Socket::__construct
     * @uses XmppFg\Xmpp\Connection\Socket::setSocket
     * @uses XmppFg\Xmpp\Connection\Socket::connect
     * @uses XmppFg\Xmpp\Connection\Socket::getAddress
     * @uses XmppFg\Xmpp\Connection\Socket::getSocket
     * @uses XmppFg\Xmpp\Stream\SocketClient
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @uses XmppFg\Xmpp\Util\XML
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Event\EventManager
     * @return void
     */
    public function testSend() {
        $data               = '<xml xmlns="test"></xml>';

        /** @var SocketClient&\PHPUnit\Framework\MockObject\MockObject $mock */
        $mock               = $this->object->getSocket();
        $invocation_matcher = $this->exactly(2);

        $mock->expects($invocation_matcher)->method('write')
            ->willReturnCallback(function ( $value ) use ( $invocation_matcher, $data ) {
                switch ( $invocation_matcher->numberOfInvocations() ) {
                    case 1:
                        // not checking the first call's argument
                        break;
                    case 2:
                        $this->assertEquals($data, $value);
                        break;
                }

                return strlen($data);
            });

        $this->object->send($data);
        $this->assertTrue($this->object->isConnected());
    }

    /**
     * Test connecting.
     *
     * @covers ::connect
     * @covers ::isConnected
     * @uses XmppFg\Xmpp\Connection\Socket::__construct
     * @uses XmppFg\Xmpp\Connection\Socket::setSocket
     * @uses XmppFg\Xmpp\Connection\Socket::send
     * @uses XmppFg\Xmpp\Connection\Socket::getAddress
     * @uses XmppFg\Xmpp\Connection\Socket::getSocket
     * @uses XmppFg\Xmpp\Stream\SocketClient
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @uses XmppFg\Xmpp\Util\XML
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Event\EventManager
     * @return void
     */
    public function testConnect()
    {
        /** @var SocketClient&\PHPUnit\Framework\MockObject\MockObject $mock */
        $mock = $this->object->getSocket();
        $mock->expects($this->once())->method('connect');
        $this->object->connect();
        $this->assertTrue($this->object->isConnected());
    }

    /**
     * Test logging of events.
     *
     * @covers ::log
     * @covers ::getAddress
     * @uses XmppFg\Xmpp\Connection\Socket::__construct
     * @uses XmppFg\Xmpp\Connection\Socket::setSocket
     * @uses XmppFg\Xmpp\Connection\Socket::send
     * @uses XmppFg\Xmpp\Connection\Socket::connect
     * @uses XmppFg\Xmpp\Connection\Socket::getSocket
     * @uses XmppFg\Xmpp\Stream\SocketClient
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Event\EventManager
     * @uses XmppFg\Xmpp\Util\XML
     * @uses XmppFg\Xmpp\Event\Event
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @return void
     */
    public function testLogging()
    {
        /* @var $event Event */
        $events = [];
        $this->object->getEventManager()->attach('logger', function (Event $eventObject) use(&$events) {
            $events[] = $eventObject;
        });

        /** @var SocketClient&\PHPUnit\Framework\MockObject\MockObject $mock */
        $mock = $this->object->getSocket();
        $mock->expects($this->once())->method('connect');

        $this->object->connect();

        $this->assertInstanceOf(Event::class, $events[0]);
        $parameters = $events[0]->getParameters();
        $this->assertStringContainsString('tcp://localhost:9999', $parameters[0]);
        $this->assertSame(LogLevel::DEBUG, $parameters[1]);
    }

    /**
     * Test reseting streams.
     *
     * @covers ::resetStreams
     * @uses XmppFg\Xmpp\Connection\Socket::__construct
     * @uses XmppFg\Xmpp\Connection\Socket::setSocket
     * @uses XmppFg\Xmpp\Stream\SocketClient
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @return void
     */
    public function testResetStreams()
    {
        $oldInput  = $this->object->getInputStream()->getParser();
        $oldOutput = $this->object->getOutputStream()->getParser();

        $this->object->resetStreams();

        $this->assertNotSame($oldInput, $this->object->getInputStream()->getParser());
        $this->assertNotSame($oldOutput, $this->object->getOutputStream()->getParser());
    }

    /**
     * Test disconnecting.
     *
     * @covers ::disconnect
     * @covers ::isConnected
     * @uses XmppFg\Xmpp\Connection\Socket::__construct
     * @uses XmppFg\Xmpp\Connection\Socket::setSocket
     * @uses XmppFg\Xmpp\Connection\Socket::send
     * @uses XmppFg\Xmpp\Connection\Socket::connect
     * @uses XmppFg\Xmpp\Connection\Socket::getAddress
     * @uses XmppFg\Xmpp\Connection\Socket::getSocket
     * @uses XmppFg\Xmpp\Stream\SocketClient
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @uses XmppFg\Xmpp\Event\EventManager
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Util\XML
     * @return void
     */
    public function testDisconnect()
    {
        /** @var SocketClient&\PHPUnit\Framework\MockObject\MockObject $mock */
        $mock = $this->object->getSocket();
        $mock->expects($this->any())->method('write');
        $mock->expects($this->once())->method('connect');
        $mock->expects($this->once())->method('close');

        $this->object->connect();
        $this->assertTrue($this->object->isConnected());

        $this->object->disconnect();
        $this->assertFalse($this->object->isConnected());
    }

    /**
     * @covers ::getSocket
     * @covers ::setSocket
     * @uses XmppFg\Xmpp\Connection\Socket::__construct
     * @uses XmppFg\Xmpp\Stream\SocketClient
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Options
     * @return void
     */
    public function testSetAndGetSocket()
    {
        $socket = new SocketClient('tcp://localhost:9999');
        $this->assertSame($socket, $this->object->setSocket($socket)->getSocket());
    }

    /**
     * Test adding listeners.
     *
     * @covers ::addListener
     * @covers ::getListeners
     * @uses XmppFg\Xmpp\Connection\Socket::__construct
     * @uses XmppFg\Xmpp\Connection\Socket::setSocket
     * @uses XmppFg\Xmpp\Stream\SocketClient
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Options
     * @return void
     */
    public function testAddListener()
    {
        $eventListener = new Stream;
        $this->object->addListener($eventListener);
        $this->assertSame(array($eventListener), $this->object->getListeners());
    }

    /**
     * Test setting and getting output stream.
     *
     * @covers ::getOutputStream
     * @covers ::setOutputStream
     * @uses XmppFg\Xmpp\Connection\Socket::__construct
     * @uses XmppFg\Xmpp\Connection\Socket::setSocket
     * @uses XmppFg\Xmpp\Stream\SocketClient
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @uses XmppFg\Xmpp\Options
     * @return void
     */
    public function testGetAndSetOutputStream()
    {
        $this->assertInstanceOf(XMLStream::class, $this->object->getOutputStream());
        $outputStream = new XMLStream;
        $this->assertSame($outputStream, $this->object->setOutputStream($outputStream)->getOutputStream());
    }

    /**
     * Test setting and getting input stream.
     *
     * @covers ::getInputStream
     * @covers ::setInputStream
     * @uses XmppFg\Xmpp\Connection\Socket::__construct
     * @uses XmppFg\Xmpp\Connection\Socket::setSocket
     * @uses XmppFg\Xmpp\Stream\SocketClient
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @uses XmppFg\Xmpp\Options
     * @return void
     */
    public function testGetAndSetInputStream()
    {
        $this->assertInstanceOf(XMLStream::class, $this->object->getInputStream());
        $inputStream = new XMLStream;
        $this->assertSame($inputStream, $this->object->setInputStream($inputStream)->getInputStream());
    }

    /**
     * Test setting and getting event manager.
     *
     * @covers ::getEventManager
     * @covers ::setEventManager
     * @uses XmppFg\Xmpp\Connection\Socket::__construct
     * @uses XmppFg\Xmpp\Connection\Socket::setSocket
     * @uses XmppFg\Xmpp\Stream\SocketClient
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Event\EventManager
     * @uses XmppFg\Xmpp\Options
     * @return void
     */
    public function testSetAndGetEventManager()
    {
        $this->assertInstanceOf(EventManager::class, $this->object->getEventManager());
        $eventManager = new EventManager;
        $this->assertSame($eventManager, $this->object->setEventManager($eventManager)->getEventManager());
    }

    /**
     * Check timeout when not receiving input.
     *
     * @covers ::receive
     * @covers ::checkTimeout
     * @covers ::reconnectTls
     * @uses XmppFg\Xmpp\Connection\Socket::__construct
     * @uses XmppFg\Xmpp\Connection\Socket::send
     * @uses XmppFg\Xmpp\Connection\Socket::connect
     * @uses XmppFg\Xmpp\Connection\Socket::getAddress
     * @uses XmppFg\Xmpp\Connection\Socket::getSocket
     * @uses XmppFg\Xmpp\Connection\Socket::setSocket
     * @uses XmppFg\Xmpp\Stream\SocketClient
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Event\EventManager
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @uses XmppFg\Xmpp\Event\Event
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @uses XmppFg\Xmpp\Util\XML
     * @ expectedException \XmppFg\Xmpp\Exception\TimeoutException
     * @ expectedExceptionMessage Connection lost after 0 seconds
     * @medium
     * @return void
     */
    public function testReceiveWithTimeout()
    {
        /** @var SocketClient&\PHPUnit\Framework\MockObject\MockObject $mock */
        $mock = $this->object->getSocket();
        $mock->expects($this->exactly(2))->method('write');
        $mock->expects($this->exactly(3))->method('connect');
        $mock->expects($this->exactly(1))->method('close');

        $this->object->getOptions()->setTimeout(0);
        $this->object->connect();
        $this->assertSame('tcp://localhost:9999', $this->object->getOptions()->getAddress());
        $this->assertSame('tcp://localhost:9999', $this->object->getSocket()->getAddress());
        $this->object->receive();
        $this->assertSame('tls://localhost:9999', $this->object->getOptions()->getAddress());
        $this->assertSame('tls://localhost:9999', $this->object->getSocket()->getAddress());
        $this->object->receive();
    }

}
