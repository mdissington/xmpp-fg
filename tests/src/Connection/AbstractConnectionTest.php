<?php

namespace XmppFg\Xmpp\Connection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use XmppFg\Xmpp\Options;
use XmppFg\Xmpp\Connection\Socket;
use XmppFg\Xmpp\EventListener\Stream\Stream;
use XmppFg\Xmpp\Event\Event;

#[CoversClass(AbstractConnection::class)]
#[UsesClass(\XmppFg\Xmpp\EventListener\AbstractEventListener::class)]
#[UsesClass(\XmppFg\Xmpp\EventListener\Stream\Stream::class)]
#[UsesClass(\XmppFg\Xmpp\Event\Event::class)]
#[UsesClass(\XmppFg\Xmpp\Event\EventManager::class)]
#[UsesClass(\XmppFg\Xmpp\Event\XMLEvent::class)]
#[UsesClass(\XmppFg\Xmpp\Options::class)]
#[UsesClass(\XmppFg\Xmpp\Stream\XMLStream::class)]
#[UsesClass(\XmppFg\Xmpp\Util\XML::class)]
final class AbstractConnectionTest extends TestCase
{

    /**
     * @var ConnectionTestDouble
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
        $options = new Options();
        $options->setTo('test');
        $this->object = new ConnectionTestDouble();
        $this->object->setOptions($options);
        $options->setConnection($this->object);
    }

    /**
     * Test connect.
     *
     * @return void
     */
    public function testConnect()
    {
        $this->object->connect();
        $this->assertContains(
            sprintf(Socket::STREAM_START, 'test'),
            $this->object->getBuffer()
        );
        $this->assertTrue($this->object->isConnected());
    }

    /**
     * Test disconnect.
     *
     * @return void
     */
    public function testDisconnect()
    {
        $this->object->connect();
        $this->assertTrue($this->object->isConnected());
        $this->object->disconnect();
        $this->assertContains(Socket::STREAM_END, $this->object->getBuffer());
        $this->assertFalse($this->object->isConnected());
    }

    /**
     * Test receiving data.
     *
     * @return void
     */
    public function testReceive()
    {
        $received1 = '<?xml version="1.0"?><test xmlns="test">';
        $received2 = '<test></test>';

        $this->object->setData(array($received1, $received2));

        $this->assertSame($received1, $this->object->receive());
        $this->assertSame($received2, $this->object->receive());
        $this->assertNull($this->object->receive());
    }

    /**
     * Test sending data.
     *
     * @return void
     */
    public function testSend()
    {
        $this->object->connect();
        $this->object->send('<test></test>');
        $buffer = $this->object->getBuffer();
        $this->assertSame('<test></test>', $buffer[1]);
    }

    /**
     * Test setting and getting data.
     *
     * @return void
     */
    public function testSetAndGetData()
    {
        $this->assertSame(array(1, 2, 3), $this->object->setData(array(1, 2, 3))->getData());
    }

    /**
     * @return void
     */
    public function testBlockingListener()
    {
        $eventManager = $this->object->getEventManager();
        $eventListener = new Stream();

        $eventListener->setEventManager($eventManager)
            ->setOptions($this->object->getOptions())
            ->attachEvents();

        $this->object->addListener($eventListener);

        $calls = 0;
        $lastMessage = null;

        $eventManager->attach('logger', function (Event $event) use (&$calls, &$lastMessage) {
            $calls++;
            $lastMessage = $event->getParameter(0);
        });

        $this->object->setData(array(
           "<?xml version='1.0'?><stream:stream xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' "
            . "id='1234567890' from='localhost' version='1.0' xml:lang='en'><stream:features></stream:features>"
        ));

        $this->object->connect();
        $this->assertContains(sprintf(Socket::STREAM_START, 'test'), $this->object->getBuffer());
        $this->assertSame($eventListener, $this->object->getLastBlockingListener());
    }

    /**
     * Check timeout when not receiving input.
     *
     * @medium
     * @return void
     */
    public function testReceiveWithTimeout()
    {
        $this->expectException(\XmppFg\Xmpp\Exception\TimeoutException::class);

        $this->object->getOptions()->setTimeout(0);
        $this->object->connect();
        $this->object->setData(array(
           "<?xml version='1.0'?><stream:stream xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' "
            . "id='1234567890' from='localhost' version='1.0' xml:lang='en'><stream:features></stream:features>"
        ));
        $this->object->receive();
        $this->object->receive();
        $this->object->receive();
    }

    public function testSetAndIsReady()
    {
        $this->assertFalse($this->object->isReady());
        $this->object->setReady(1);
        $this->assertTrue($this->object->isReady());
    }

    public function testSetAndGetOptions()
    {
        $options = new Options();
        $this->assertSame($options, $this->object->setOptions($options)->getOptions());
    }
}
