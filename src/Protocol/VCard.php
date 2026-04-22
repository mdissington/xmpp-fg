<?php

namespace XmppFg\Xmpp\Protocol;

use XmppFg\Xmpp\Util\XML;

/**
 * Protocol setting for Xmpp.
 *
 * @package Xmpp\Protocol
 */
class VCard implements ProtocolImplementationInterface
{
    protected string $firstname;

    protected string $lastname;

    protected string $jabberid;

    protected string $mime = '';

    protected string $image = '';

    protected string $url = '';

    public function __construct( string $firstname = '', string $lastname = '', string $jabberid = '' )
    {
        $this->setFirstname($firstname);
        $this->setLastname($lastname);
        $this->setJabberID($jabberid);
    }

    public function toString(): string
    {
         return XML::quoteMessage(
            '<iq id="' . XML::generateId() . '" type="set">
              <vCard xmlns="vcard-temp">
                <FN>%s</FN>
                <N>
                  <FAMILY>%s</FAMILY>
                  <GIVEN>%s</GIVEN>
                  <MIDDLE/>
                </N>
                <NICKNAME>%s</NICKNAME>
                <URL>%s</URL>
                <PHOTO>
                  <TYPE>%s</TYPE>
                  <BINVAL>
                    %s
                  </BINVAL>
                </PHOTO>
                <JABBERID>%s</JABBERID>
                <DESC/>
              </vCard>
            </iq>',
            $this->getFirstname().' '.$this->getLastname(),
            $this->getLastname(),
            $this->getFirstname(),
            $this->getFirstname().' '.$this->getLastname(),
            $this->getUrl(),
            $this->getMime(),
            $this->getImage(),
            $this->getJabberID()
        );
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * @return $this
     */
    public function setFirstname( string $firstname): self
    {
        $this->firstname = (string) $firstname;

        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    /**
     * @return $this
     */
    public function setLastname( string $lastname): self
    {
        $this->lastname = (string) $lastname;

        return $this;
    }

    public function getJabberID(): string
    {
        return $this->jabberid;
    }

    /**
     * @return $this
     */
    public function setJabberID(string $jabberid): self
    {
        $this->jabberid = (string) $jabberid;

        return $this;
    }

    public function getMime(): string
    {
        return $this->mime;
    }

    /**
     * @return $this
     */
    public function setMime( string $mime): self
    {
        $this->mime = (string) $mime;

        return $this;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * @return $this
     */
    public function setImage( string $image): self
    {
        $this->image = (string) $image;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return $this
     */
    public function setUrl( string $url): self
    {
        $this->url = (string) $url;

        return $this;
    }
}
