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

namespace XmppFg\Xmpp\EventListener\Stream\Authentication;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use XmppFg\Xmpp\Event\XMLEvent;
use XmppFg\Xmpp\Connection\ConnectionTestDouble;
use XmppFg\Xmpp\Options;
use XmppFg\Xmpp\Util\XML;

#[CoversClass(\XmppFg\Xmpp\EventListener\Stream\Authentication\DigestMd5::class)]
class DigestMd5Test extends TestCase
{

    /**
     * @var DigestMd5
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
        $this->object     = new DigestMd5;
        $this->connection = new ConnectionTestDouble;

        $options = new Options;
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
        // run the test
        $this->object->attachEvents();

        // declare expected InputStream...
        $expected = [
            '*'                                           => [],
            '{urn:ietf:params:xml:ns:xmpp-sasl}challenge' => [[$this->object, 'challenge']],
            '{urn:ietf:params:xml:ns:xmpp-sasl}success'   => [[$this->object, 'success']]
        ];

        // now parse out the actual InputStream event list...
        $actual     = [];
        $event_list = $this->connection->getInputStream()->getEventManager()->getEventList();

        foreach ($event_list as $event => $callback_list) {
            $actual[$event] = [];

            foreach ($callback_list as $id => $callback) {
                $reflected_callback  = new \ReflectionFunction($callback);
                $actual[$event][$id] = [$reflected_callback->getClosureThis(), $reflected_callback->getName()];
            }
        }

        $this->assertSame($expected, $actual);

        // declare expected getOutputStream...
        $expected = [
            '*'                                      => [],
            '{urn:ietf:params:xml:ns:xmpp-sasl}auth' => [[$this->object, 'auth']]
        ];

        // now parse out the actual getOutputStream event list...
        $actual = [];

        foreach ($this->connection->getOutputStream()->getEventManager()->getEventList() as $event => $callback_list) {
            $actual[$event] = [];

            foreach ($callback_list as $id => $callback) {
                $reflected_callback  = new \ReflectionFunction($callback);
                $actual[$event][$id] = [$reflected_callback->getClosureThis(), $reflected_callback->getName()];
            }
        }

        $this->assertSame($expected, $actual);
    }

    /**
     * Test authentication.
     *
     * @covers ::authenticate
     * @covers ::setUsername
     * @covers ::getUsername
     * @covers ::setPassword
     * @covers ::getPassword
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Event\EventManager
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @return void
     */
    public function testAuthenticate()
    {
        $this->object->authenticate('aaa', 'bbb');

        $this->assertContains(
            '<auth xmlns="urn:ietf:params:xml:ns:xmpp-sasl" mechanism="DIGEST-MD5"/>',
            $this->connection->getBuffer()
        );
        $this->assertSame('aaa', $this->object->getUsername());
        $this->assertSame('bbb', $this->object->getPassword());
    }

    /**
     * Test blocking when authentication element is send.
     *
     * @covers ::auth
     * @uses XmppFg\Xmpp\EventListener\Stream\Authentication\DigestMd5::isBlocking
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Options
     * @return void
     */
    public function testAuth()
    {
        $this->assertFalse($this->object->isBlocking());
        $this->object->auth();
        $this->assertTrue($this->object->isBlocking());
    }

    /**
     * Test parsing challenge and sending response.
     *
     * @covers ::challenge
     * @covers ::response
     * @covers ::parseCallenge
     * @uses XmppFg\Xmpp\EventListener\Stream\Authentication\DigestMd5::getUsername
     * @uses XmppFg\Xmpp\EventListener\Stream\Authentication\DigestMd5::setUsername
     * @uses XmppFg\Xmpp\EventListener\Stream\Authentication\DigestMd5::getPassword
     * @uses XmppFg\Xmpp\EventListener\Stream\Authentication\DigestMd5::setPassword
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Event\EventManager
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @uses XmppFg\Xmpp\Event\Event
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @uses XmppFg\Xmpp\Util\XML
     * @return void
     */
    public function testChallenge()
    {
        $this->object->setUsername('aaa')->setPassword('bbb');
        $this->object->getOptions()->setTo('localhost');

        $document = new \DOMDocument;
        $document->loadXML(
            '<challenge xmlns="urn:ietf:params:xml:ns:xmpp-sasl">'
            .XML::quote(base64_encode(
                    'realm="localhost",nonce="abcdefghijklmnopqrstuvw",'
                    .'qop="auth",charset=utf-8,algorithm=md5-sess'
                ))
            .'</challenge>'
        );

        $event = new XMLEvent;
        $event->setParameters(array($document->documentElement));
        $this->object->challenge($event);

        $buffer   = $this->connection->getBuffer();
        $this->assertCount(1, $buffer);
        $response = $buffer[0];
        $this->assertMatchesRegularExpression('#^<response xmlns="urn:ietf:params:xml:ns:xmpp-sasl">.+</response>$#', $response);

        $parser = new \DOMDocument;
        $parser->loadXML($response);
        $value  = base64_decode($parser->documentElement->textContent);
        $this->assertMatchesRegularExpression(
            '#^username="aaa",realm="localhost",nonce="abcdefghijklmnopqrstuvw",cnonce="[^"]+",nc=00000001,'
            .'qop=auth,digest-uri="xmpp/localhost",response=[^,]+,charset=utf-8$#',
            $value
        );
    }

    /**
     * Test sending a rspauth challenge.
     *
     * @covers ::challenge
     * @uses XmppFg\Xmpp\EventListener\Stream\Authentication\DigestMd5::parseCallenge
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Event\EventManager
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Stream\XMLStream
     * @uses XmppFg\Xmpp\Event\Event
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @uses XmppFg\Xmpp\Util\XML
     * @return void
     */
    public function testChallengeRspauth()
    {
        $document = new \DOMDocument;
        $document->loadXML(
            '<challenge xmlns="urn:ietf:params:xml:ns:xmpp-sasl">'
            .XML::base64Encode('rspauth=1234567890').'</challenge>'
        );

        $event = new XMLEvent;
        $event->setParameters(array($document->documentElement));
        $this->object->challenge($event);

        $buffer   = $this->connection->getBuffer();
        $response = $buffer[0];
        $this->assertSame('<response xmlns="urn:ietf:params:xml:ns:xmpp-sasl"/>', $response);
    }

    /**
     * Test sending an empty challenge.
     *
     * @covers ::challenge
     * @uses XmppFg\Xmpp\EventListener\Stream\Authentication\DigestMd5::parseCallenge
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Event\XMLEvent
     * @uses XmppFg\Xmpp\Event\Event
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\Util\XML
     * @return void
     */
    public function testChallengeEmpty()
    {
        $this->expectException(\XmppFg\Xmpp\Exception\Stream\AuthenticationErrorException::class);
        $this->expectExceptionMessage('Error when receiving challenge: ""');

        $document = new \DOMDocument;
        $document->loadXML('<challenge xmlns="urn:ietf:params:xml:ns:xmpp-sasl"></challenge>');

        $event = new XMLEvent;
        $event->setParameters(array($document->documentElement));
        $this->object->challenge($event);
    }

    /**
     * Test handling success event.
     *
     * @covers ::success
     * @covers ::isBlocking
     * @uses XmppFg\Xmpp\EventListener\AbstractEventListener
     * @uses XmppFg\Xmpp\Connection\AbstractConnection
     * @uses XmppFg\Xmpp\Options
     * @uses XmppFg\Xmpp\EventListener\Stream\Authentication\DigestMd5::auth
     * @return void
     */
    public function testSuccess()
    {
        $this->object->auth();
        $this->assertTrue($this->object->isBlocking());
        $this->object->success();
        $this->assertFalse($this->object->isBlocking());
    }
}
