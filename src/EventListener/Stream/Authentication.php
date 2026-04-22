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

use XmppFg\Xmpp\Event\XMLEvent;
use XmppFg\Xmpp\Exception\RuntimeException;
use XmppFg\Xmpp\EventListener\Stream\Authentication\AuthenticationInterface;
use XmppFg\Xmpp\Exception\Stream\AuthenticationErrorException;
use XmppFg\Xmpp\EventListener\AbstractEventListener;
use XmppFg\Xmpp\EventListener\BlockingEventListenerInterface;

/**
 * Listener
 *
 * @package Xmpp\EventListener
 */
class Authentication extends AbstractEventListener implements BlockingEventListenerInterface
{

    /**
     * Listener is blocking.
     *
     * @var boolean
     */
    protected bool $blocking = false;

    /**
     * Collected mechanisms.
     *
     * @var array
     */
    protected $mechanisms = [];

    /**
     * {@inheritDoc}
     */
    public function attachEvents()
    {
        $input = $this->getConnection()->getInputStream()->getEventManager();
        $input->attach('{urn:ietf:params:xml:ns:xmpp-sasl}mechanisms', $this->authenticate(...));
        $input->attach('{urn:ietf:params:xml:ns:xmpp-sasl}mechanism', $this->collectMechanisms(...));
        $input->attach('{urn:ietf:params:xml:ns:xmpp-sasl}failure', $this->failure(...));
        $input->attach('{urn:ietf:params:xml:ns:xmpp-sasl}success', $this->success(...));
    }

    /**
     * Collect authentication mechanisms.
     *
     * @param XMLEvent $event
     * @return void
     */
    public function collectMechanisms(XMLEvent $event)
    {
        if ($this->getConnection()->isReady() && false === $this->isAuthenticated()) {
            /* @var $element \DOMElement */
            list($element) = $event->getParameters();
            $this->blocking = true;
            if (false === $event->isStartTag()) {
                $this->mechanisms[] = strtolower($element->nodeValue);
            }
        }
    }

    /**
     * Authenticate after collecting mechanisms.
     *
     * @param XMLEvent $event
     * @return void
     */
    public function authenticate(XMLEvent $event)
    {
        if ($this->getConnection()->isReady() && false === $this->isAuthenticated() && false === $event->isStartTag()) {
            $this->blocking = true;

            $authentication = $this->determineMechanismClass();

            $authentication->setEventManager($this->getEventManager())
                ->setOptions($this->getOptions())
                ->attachEvents();

            $this->getConnection()->addListener($authentication);
            $authentication->authenticate($this->getOptions()->getUsername(), $this->getOptions()->getPassword());
        }
    }

    /**
     * Determine mechanismclass from collected mechanisms.
     *
     * @return AuthenticationInterface
     * @throws RuntimeException
     */
    protected function determineMechanismClass()
    {
        $authenticationClass   = null;
        $authenticationClasses = $this->getOptions()->getAuthenticationClasses();

        foreach ($authenticationClasses as $mechanism => $authClass) {
            if (in_array($mechanism, $this->mechanisms)) {
                $authenticationClass = $authClass;
                break;
            }
        }

        if (null === $authenticationClass) {
            throw new RuntimeException('No supported authentication mechanism found.');
        }

        $authentication = new $authenticationClass;

        if (!($authentication instanceof AuthenticationInterface)) {
            $message = 'Authentication class "'.get_class($authentication)
                .'" is no instanceof  AuthenticationInterface';
            throw new RuntimeException($message);
        }

        return $authentication;
    }

    /**
     * Authentication failed
     * @throws AuthenticationErrorException
     */
    public function failure(XMLEvent $event): void
    {
        if (false === $event->isStartTag()) {
            $this->blocking = false;
            throw AuthenticationErrorException::createFromEvent($event);
        }
    }

    /**
     * Authentication successful
     */
    public function success(XMLEvent $event): void
    {
        if (false === $event->isStartTag()) {
            $this->blocking = false;

            $connection = $this->getConnection();
            $connection->resetStreams();
            $connection->connect();

            $this->getOptions()->setAuthenticated(true);
        }
    }

    public function isBlocking(): bool
    {
        return $this->blocking;
    }

    /**
     * Get collected mechanisms
     */
    public function getMechanisms(): array
    {
        return $this->mechanisms;
    }

    protected function isAuthenticated(): bool
    {
        return $this->getOptions()->isAuthenticated();
    }
}
