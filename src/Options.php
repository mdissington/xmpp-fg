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

namespace XmppFg\Xmpp;

use XmppFg\Xmpp\Connection\ConnectionInterface;
use XmppFg\Xmpp\Exception\InvalidArgumentException;
use XmppFg\Xmpp\Protocol\ImplementationInterface;
use XmppFg\Xmpp\Protocol\DefaultImplementation;
use Psr\Log\LoggerInterface;

/**
 * Xmpp connection options.
 *
 * @package Xmpp
 */
class Options
{

    protected ImplementationInterface $implementation;
    protected ?string $address                 = null;
    protected ?ConnectionInterface $connection = null;
    protected LoggerInterface $logger;
    protected string $to                       = '';
    protected string $username                 = '';
    protected string $password                 = '';
    protected string $jid                      = '';
    protected string $sid                      = '';
    protected bool $authenticated              = false;

    /**
     * @var array<\XmppFg\Xmpp\Protocol\User\User>
     */
    protected array $users = [];

    /**
     * Timeout for connection
     */
    protected int $timeout              = 30;
    protected string $socksProxyAddress = '';

    /**
     * Auto approve subscriptions
     */
    protected bool $autoSubscribe = false;

    /**
     * Authentication methods and their implementation classes
     * @var array<string,string>
     */
    protected $authenticationClasses = [
        'anonymous'     => \XmppFg\Xmpp\EventListener\Stream\Authentication\Anonymous::class,
        'digest-md5'    => \XmppFg\Xmpp\EventListener\Stream\Authentication\DigestMd5::class,
        'plain'         => \XmppFg\Xmpp\EventListener\Stream\Authentication\Plain::class,
        'scram-sha-1'   => \XmppFg\Xmpp\EventListener\Stream\Authentication\ScramSha1::class,
        'scram-sha-256' => \XmppFg\Xmpp\EventListener\Stream\Authentication\ScramSha256::class,
        'scram-sha-512' => \XmppFg\Xmpp\EventListener\Stream\Authentication\ScramSha512::class,
    ];

    /**
     * Options used to create a stream context
     * @var array<string,mixed>
     */
    protected array $contextOptions = [];

    /**
     * @param ?string $address Server address
     */
    public function __construct(?string $address = null)
    {
        $this->logger = new \Psr\Log\NullLogger();
        $this->setImplementation(new DefaultImplementation());

        if ($address !== null) {
            $this->setAddress($address);
        }
    }

    /**
     * Get protocol implementation
     * @codeCoverageIgnore
     */
    public function getImplementation(): ImplementationInterface
    {
        return $this->implementation;
    }

    /**
     * Set protocol implementation
     * @return $this
     * @codeCoverageIgnore
     */
    public function setImplementation(ImplementationInterface $implementation): self
    {
        $this->implementation = $implementation;
        return $this;
    }

    /**
     * Get server address
     * @codeCoverageIgnore
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * When an address is passed this setter also calls setTo with the hostname part of the address
     * @return $this
     * @throws InvalidArgumentException if $address does not contain a host part
     */
    public function setAddress(string $address): self
    {
        $this->address = $address;
        $host          = parse_url($address, PHP_URL_HOST);

        if (!is_string($host)) {
            throw new InvalidArgumentException('Argument #1 $address of '.__CLASS__.'::'.__METHOD__.'() must contain "host" part');
        }

        $this->setTo($host);

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getConnection(): ?ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * @return $this
     * @codeCoverageIgnore
     */
    public function setConnection(ConnectionInterface $connection): self
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return $this
     * @codeCoverageIgnore
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Get server name
     * @codeCoverageIgnore
     */
    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * Set server name
     *
     * This value is send to the server in requests as to="" attribute.
     * @return $this
     * @codeCoverageIgnore
     */
    public function setTo(string $to): self
    {
        $this->to = (string) $to;
        return $this;
    }

    public function getUsername(): string
    {
        /** @phpstan-ignore nullCoalesce.offset */
        return explode('/', $this->username)[0] ?? '';
    }

    /**
     * @return $this
     * @codeCoverageIgnore
     */
    public function setUsername(string $username): self
    {
        $this->username = (string) $username;
        return $this;
    }

    public function getResource(): string
    {
        return explode('/', $this->username)[1] ?? '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return $this
     * @codeCoverageIgnore
     */
    public function setPassword(string $password): self
    {
        $this->password = (string) $password;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getJid(): string
    {
        return $this->jid;
    }

    /**
     * @return $this
     * @codeCoverageIgnore
     */
    public function setJid(string $jid): self
    {
        $this->jid = $jid;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getSid(): string
    {
        return $this->sid;
    }

    /**
     * @return $this
     * @codeCoverageIgnore
     */
    public function setSid(string $sid): self
    {
        $this->sid = $sid;
        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    /**
     * @return $this
     * @codeCoverageIgnore
     */
    public function setAuthenticated(bool $authenticated): self
    {
        $this->authenticated = $authenticated;
        return $this;
    }

    /**
     * Get users list
     * @return array<\XmppFg\Xmpp\Protocol\User\User>
     * @codeCoverageIgnore
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * Set users list
     * @param array<\XmppFg\Xmpp\Protocol\User\User> $users
     * @return $this
     * @codeCoverageIgnore
     */
    public function setUsers(array $users): self
    {
        $this->users = $users;
        return $this;
    }

    /**
     * @return array<string,string>
     * @codeCoverageIgnore
     */
    public function getAuthenticationClasses(): array
    {
        return $this->authenticationClasses;
    }

    /**
     * @param array<string,string> $authenticationClasses
     * @return $this
     * @codeCoverageIgnore
     */
    public function setAuthenticationClasses(array $authenticationClasses): self
    {
        $this->authenticationClasses = $authenticationClasses;
        return $this;
    }

    /**
     * Get timeout for connection
     * @codeCoverageIgnore
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Set timeout for connection
     * @param int $timeout Seconds
     * @return $this
     * @codeCoverageIgnore
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Get context options for connection
     * @return array<string,mixed>
     * @codeCoverageIgnore
     */
    public function getContextOptions(): array
    {
        return $this->contextOptions;
    }

    /**
     *  Set context options for connection
     * @param array<string,mixed> $contextOptions
     * @return $this
     * @codeCoverageIgnore
     */
    public function setContextOptions(array $contextOptions): self
    {
        $this->contextOptions = $contextOptions;
        return $this;
    }

    /**
     * Get SOCKS proxy address
     * @codeCoverageIgnore
     */
    public function getSocksProxyAddress(): string
    {
        return $this->socksProxyAddress;
    }

    /**
     * Set SOCKS proxy address
     *
     * @param string $socksProxyAddress
     * @return $this
     * @codeCoverageIgnore
     */
    public function setSocksProxyAddress(string $socksProxyAddress): self
    {
        $this->socksProxyAddress = $socksProxyAddress;
        return $this;
    }

    /**
     * Get auto approve subscriptions
     * @codeCoverageIgnore
     */
    public function getAutoSubscribe(): bool
    {
        return $this->autoSubscribe;
    }

    /**
     * Set auto approve subscriptions
     * @return $this
     * @codeCoverageIgnore
     */
    public function setAutoSubscribe(bool $autoSubscribe): self
    {
        $this->autoSubscribe = $autoSubscribe;
        return $this;
    }
}
