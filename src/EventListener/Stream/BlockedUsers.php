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
use XmppFg\Xmpp\EventListener\AbstractEventListener;
use XmppFg\Xmpp\EventListener\BlockingEventListenerInterface;
use XmppFg\Xmpp\Protocol\User\User;

/**
 * Listener
 *
 * @package Xmpp\EventListener
 */
class BlockedUsers extends AbstractEventListener implements BlockingEventListenerInterface
{

    protected bool $blocking = false;
    protected User $userObject;

    #[\Override]
    public function attachEvents()
    {
        $this->getOutputEventManager()->attach('{urn:xmpp:blocking}blocklist', $this->query(...));
        $this->getInputEventManager()->attach('{urn:xmpp:blocking}blocklist', $this->result(...));
    }

    /**
     * Sending a query request for roster sets listener to blocking mode
     */
    public function query(): void
    {
        $this->blocking = true;
    }

    /**
     * Result received
     */
    public function result(XMLEvent $event): void
    {
        if ($event->isEndTag()) {
            $users = [];

            /* @var $element \DOMElement */
            $element = $event->getParameter(0);
            $items   = $element->getElementsByTagName('item');

            /* @var $item \DOMElement */
            foreach ($items as $item) {
                $users[] = $item->getAttribute('jid');
            }

            //$this->getOptions()->setUsers($users);
            $this->blocking = false;
        }
    }

    public function getUserObject(): User
    {
        if (!isset($this->userObject)) {
            $this->setUserObject(new User());
        }

        return $this->userObject;
    }

    /**
     * @return $this
     */
    public function setUserObject(User $userObject): self
    {
        $this->userObject = $userObject;
        return $this;
    }

    #[\Override]
    public function isBlocking(): bool
    {
        return $this->blocking;
    }
}
