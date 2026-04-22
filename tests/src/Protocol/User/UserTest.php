<?php

namespace XmppFg\Xmpp\Protocol\User;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\XmppFg\Xmpp\Protocol\User\User::class)]
class UserTest extends TestCase
{

    /**
     * @var User
     */
    protected User $object;

    #[\Override]
    protected function setUp(): void
    {
        $this->object = new User;
    }

    /**
     * @covers XmppFg\Xmpp\Protocol\User\User
     * @return void
     */
    public function testSetterAndGetters()
    {
        $this->assertSame('1', $this->object->setName('1')->getName());
        $this->assertSame('2', $this->object->setJid('2')->getJid());
        $this->assertSame('3', $this->object->setSubscription('3')->getSubscription());
        $this->assertSame([1, 2, 3], $this->object->setGroups([1, 2, 3])->getGroups());
        $this->assertContains('test', $this->object->addGroup('test')->getGroups());
    }
}
