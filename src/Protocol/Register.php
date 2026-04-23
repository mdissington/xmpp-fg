<?php

namespace XmppFg\Xmpp\Protocol;

use XmppFg\Xmpp\Util\XML;

/**
 * Protocol setting for Xmpp.
 *
 * @package Xmpp\Protocol
 */
class Register implements ProtocolImplementationInterface
{

    protected string $to;

    protected string $from;

    protected string $step;

    protected string $accountjid;

    protected string $password;

    protected string $sid;

    public function __construct( string|null $to = null, string|null $from = null, string $step = 'one' )
    {
        $this->setTo($to);
        $this->setFrom($from);
        $this->setStep($step);
    }

    public function toString(): string
    {
        $req ='';

        if($this->step == 'one')
        {
            $req = XML::quoteMessage(
                "<iq from='%s' id='%s' to='%s' type='set' xml:lang='en'>
                    <command xmlns='http://jabber.org/protocol/commands' action='execute' node='http://jabber.org/protocol/admin#add-user'/>
                </iq>",
                $this->getFrom(),
                XML::generateId(),
                $this->getTo()
            );
        }
        else
        {
            $req = XML::quoteMessage(
                "<iq from='%s' id='%s' to='%s' type='set' xml:lang='en'>
                    <command xmlns='http://jabber.org/protocol/commands' node='http://jabber.org/protocol/admin#add-user' sessionid='%s'>
                        <x xmlns='jabber:x:data' type='submit'>
                          <field type='hidden' var='FORM_TYPE'>
                            <value>http://jabber.org/protocol/admin</value>
                          </field>
                          <field var='accountjid'>
                            <value>%s</value>
                          </field>
                          <field var='password'>
                            <value>%s</value>
                          </field>
                          <field var='password-verify'>
                            <value>%s</value>
                          </field>
                        </x>
                    </command>
                </iq>",
                $this->getFrom(),
                XML::generateId(),
                $this->getTo(),
                $this->getSID(),
                $this->getJabberID(),
                $this->getPassword(),
                $this->getPassword()
            );
        }

        return $req;
    }

    public function getJabberID(): string
    {
        return $this->accountjid;
    }

    /**
     * @return $this
     */
    public function setJabberID( string $accountjid ): self
    {
        $this->accountjid = $accountjid;

        return $this;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * @return $this
     */
    public function setTo( string $to ): self
    {
        $this->to = $to;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return $this
     */
    public function setPassword( string $password ): self
    {
        $this->password = $password;

        return $this;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * @return $this
     */
    public function setFrom( string $from): self
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @return $this
     */
    public function setStep( string $step): self
    {
        $this->step = $step;

        return $this;
    }

    /**
     * @return $this
     */
    public function setSID( string $sid): self
    {
        $this->sid = $sid;

        return $this;
    }

    public function getSID(): string
    {
        return $this->sid;
    }
}
