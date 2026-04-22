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

#[CoversClass(\XmppFg\Xmpp\Protocol\Presence::class)]
class PresenceTest extends TestCase
{

    /**
     * @var Presence
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
        $this->object = new Presence;
    }

    /**
     * Test truning object into string.
     *
     * @covers ::toString
     * @uses XmppFg\Xmpp\Protocol\Presence::__construct
     * @uses XmppFg\Xmpp\Protocol\Presence::getTo
     * @uses XmppFg\Xmpp\Protocol\Presence::setTo
     * @uses XmppFg\Xmpp\Protocol\Presence::setPriority
     * @uses XmppFg\Xmpp\Protocol\Presence::getPriority
     * @uses XmppFg\Xmpp\Protocol\Presence::setNickname
     * @uses XmppFg\Xmpp\Protocol\Presence::getNickname
     * @uses XmppFg\Xmpp\Util\XML::generateId
     * @uses XmppFg\Xmpp\Util\XML::quote
     * @return void
     */
    public function testToString()
    {
        $this->assertSame('<presence><priority>1</priority></presence>', $this->object->toString());
        $this->object->setTo('foobar/phpunit');
        $this->assertSame('<presence to="foobar/phpunit"><priority>1</priority></presence>', $this->object->toString());
    }

    /**
     * Test constructor.
     *
     * @covers ::__construct
     * @uses XmppFg\Xmpp\Protocol\Presence::getTo
     * @uses XmppFg\Xmpp\Protocol\Presence::setTo
     * @uses XmppFg\Xmpp\Protocol\Presence::setPriority
     * @uses XmppFg\Xmpp\Protocol\Presence::getPriority
     * @uses XmppFg\Xmpp\Protocol\Presence::setNickname
     * @uses XmppFg\Xmpp\Protocol\Presence::getNickname
     * @return void
     */
    public function testConstructor()
    {
        $object = new Presence('2', 'foo', 3);
        $this->assertSame('foo', $object->getTo());
        $this->assertSame(2, $object->getPriority());
        $this->assertSame('3', $object->getType());
    }

    /**
     * Test setter and getter.
     *
     * @covers ::getTo
     * @covers ::setTo
     * @covers ::getPriority
     * @covers ::setPriority
     * @covers ::setNickname
     * @covers ::getNickname
     * @uses XmppFg\Xmpp\Protocol\Presence::__construct
     * @return void
     */
    public function testSetterAndGetter()
    {
        $this->assertSame('foobar', $this->object->setTo('foobar')->getTo());
        $this->assertSame(2, $this->object->setPriority('2')->getPriority());
        $this->assertSame('3', $this->object->setType(3)->getType());
    }

}
