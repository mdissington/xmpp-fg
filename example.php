<?php

require 'vendor/autoload.php';
error_reporting(-1);
set_time_limit(0);

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use XmppFg\Xmpp\Client;
use XmppFg\Xmpp\Options;
use XmppFg\Xmpp\Protocol\Roster;
use XmppFg\Xmpp\Protocol\Presence;
use XmppFg\Xmpp\Protocol\Message;

$logger = new Logger('xmpp');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

$address    = "myjabber.com:5222";
$socksProxy = 'localhost:9050';

$username = 'xmpp';
$password = 'test';

$initFile = __FILE__ . '.init.php';
if (file_exists($initFile)) {
    require $initFile;
}

$options = new Options($address);
$options->setLogger($logger)
    ->setUsername($username)
    ->setPassword($password)
    ->setAutoSubscribe(true)
;
if ($socksProxy) {
    $options->setSocksProxyAddress($socksProxy);
}

$client = new Client($options);

$client->connect();
$client->send(new Roster());
$client->send(new Presence());

while (true) {
    $messages = $client->getMessages(true); //blocking mode for get messages
    foreach ($messages as $msg) {
        $client->send(new Message($msg['message'], $msg['from']));
    }
}
