Library for XMPP protocol connections (Jabber) for PHP.

## SYSTEM REQUIREMENTS

- PHP >= 8.3
- psr/log
- (optional) psr/log-implementation - like monolog/monolog for logging

## INSTALLATION

Install [Composer](https://getcomposer.org/download/) and then

```bash
composer require xmpp-fg/xmpp-fg
```

## DOCUMENTATION

You can check `example.php` for auto reply bot example

This library uses an object to hold options:

```php
use Fabiang\Xmpp\Options;
$options = new Options($address);
$options->setUsername($username)
    ->setPassword($password);
```

The server address must be in the format `myjabber.com:5222`.
If the server supports TLS the connection will automatically be encrypted.

If you want to use SOCKS proxy you can set it by

```php
$options->setSocksProxyAddress('localhost:9050');
```

or

```php
$options->setSocksProxyAddress('username:password@localhost:9050');
```

You can also pass a PSR-3 compatible object to the options object:

```php
$options->setLogger($logger);
```

The client manages the connection to the Jabber server and requires the options object:

```php
use Fabiang\Xmpp\Client;
$client = new Client($options);
// optional connect manually
$client->connect();
```

You can use `getMessages()` for get all incoming messages

```php
print_r($client->getMessages());
```

```
Array
(
    [0] => Array
        (
            [from] => user@myjabber.com/resource
            [message] => Message text
        )

)
```

For sending data you just need to pass a object that implements `Fabiang\Xmpp\Protocol\ProtocolImplementationInterface`:

```php
use Fabiang\Xmpp\Protocol\Roster;
use Fabiang\Xmpp\Protocol\Presence;
use Fabiang\Xmpp\Protocol\Message;

// fetch roster list; users and their groups
$client->send(new Roster);
// set status to online
$client->send(new Presence);

// send a message to another user
$message = new Message;
$message->setMessage('test')
    ->setTo('nickname@myjabber.com');
$client->send($message);

// join a channel
$channel = new Presence;
$channel->setTo('channelname@conference.myjabber.com/nickname')
    ->setPassword('channelpassword');
$client->send($channel);

// send a message to the above channel
$message = new Message;
$message->setMessage('test')
    ->setTo('channelname@conference.myjabber.com')
    ->setType(Message::TYPE_GROUPCHAT);
$client->send($message);
```

After all you should disconnect:

```php
$client->disconnect();
```

## DEVELOPING

If you like this library and you want to contribute, make sure the unit-tests and integration tests are running.
Composer will help you to install the right version of PHPUnit.

    composer install

Test with:

    composer test

Run code analysis with:

    composer analyse

You can run both together using:

    composer test-and-analyse

New features should always developed using TDD with PHPUnit.

## LICENSE

BSD-2-Clause. See the [LICENSE](LICENSE.md).

## TODO

- improve documentation
