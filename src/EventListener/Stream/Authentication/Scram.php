<?php

namespace XmppFg\Xmpp\EventListener\Stream\Authentication;

use XmppFg\Xmpp\EventListener\AbstractEventListener;
use XmppFg\Xmpp\Event\XMLEvent;
use XmppFg\Xmpp\Util\XML;
use XmppFg\Xmpp\Exception\Stream\AuthenticationErrorException;

abstract class Scram extends AbstractEventListener implements AuthenticationInterface
{
    protected const ALGO = '';
    protected const MECHANISM = '';

    /**
     * Is event blocking stream.
     *
     * @var boolean
     */
    protected $blocking = false;

    /**
     *
     * @var string
     */
    protected $username;

    /**
     *
     * @var string
     */
    protected $password;
    protected $cNonce;
    protected $firstMessageBare;
    protected $serverSignature;

    /**
     * {@inheritDoc}
     */
    public function attachEvents()
    {
        $input = $this->getInputEventManager();
        $input->attach('{urn:ietf:params:xml:ns:xmpp-sasl}challenge', $this->challenge(...));
        $input->attach('{urn:ietf:params:xml:ns:xmpp-sasl}success', $this->success(...));

        $output = $this->getOutputEventManager();
        $output->attach('{urn:ietf:params:xml:ns:xmpp-sasl}auth', $this->auth(...));
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate($username, $password)
    {
        $this->username = str_replace(['=', ','], ['=3D', '=2C'], $username);
        $this->password = $password;
        $this->cNonce = bin2hex(random_bytes(32));
        $this->firstMessageBare = sprintf('n=%s,r=%s', $this->username, $this->cNonce);
        $msg = base64_encode('n,,' . $this->firstMessageBare);
        $auth = sprintf('<auth xmlns="urn:ietf:params:xml:ns:xmpp-sasl" mechanism="%s">%s</auth>', static::MECHANISM, $msg);
        $this->getConnection()->send($auth);
    }

    /**
     * Authentication starts -> blocking.
     *
     * @return void
     */
    public function auth()
    {
        $this->blocking = true;
    }

    /**
     * Challenge string received.
     *
     * @see https://wiki.xmpp.org/web/SASL_Authentication_and_SCRAM
     * @return void
     */
    public function challenge(XMLEvent $event)
    {
        if ($event->isEndTag()) {
            list($element) = $event->getParameters();

            $challenge = XML::base64Decode($element->nodeValue);
            $values = $this->parseChallenge($challenge);
            $sNonce = $values['r'] ?? '';
            if ($this->cNonce !== substr($sNonce, 0, strlen($this->cNonce))) {
                throw new AuthenticationErrorException('serverNonce does not started with clientNonce, which means probably MitM attack');
            }
            $salt = base64_decode($values['s'] ?? '');
            $iterations = $values['i'] ?? 0;
            $finalMessage = 'c=biws,r=' . $sNonce;
            $saltedPassword = hash_pbkdf2(static::ALGO, $this->password, $salt, $iterations, 0, true);
            $clientKey = hash_hmac(static::ALGO, 'Client Key', $saltedPassword, true);
            $storedKey = hash(static::ALGO, $clientKey, true);
            $authMessage = sprintf('%s,%s,%s', $this->firstMessageBare, $challenge, $finalMessage);
            $clientSignature = hash_hmac(static::ALGO, $authMessage, $storedKey, true);
            $clientProof = $clientKey ^ $clientSignature;
            $serverKey = hash_hmac(static::ALGO, 'Server Key', $saltedPassword, true);
            $this->serverSignature = base64_encode(hash_hmac(static::ALGO, $authMessage, $serverKey, true));
            $finalMessage = sprintf('%s,p=%s', $finalMessage, base64_encode($clientProof));

            $send = sprintf('<response xmlns="urn:ietf:params:xml:ns:xmpp-sasl">%s</response>', base64_encode($finalMessage));
            $this->getConnection()->send($send);
        }
    }

    /**
     * Parse challenge string and return its values as array.
     *
     * @param string $challenge
     * @return array
     */
    protected function parseChallenge($challenge)
    {
        if (!$challenge) {
            return [];
        }

        $matches = [];
        preg_match_all('#(\w+)\=(?:"([^"]+)"|([^,]+))#', $challenge, $matches);
        list(, $variables, $quoted, $unquoted) = $matches;
        // filter empty strings; preserve keys
        $quoted = array_filter($quoted);
        $unquoted = array_filter($unquoted);
        // replace "unquoted" values into "quoted" array and combine variables array with it
        return array_combine($variables, array_replace($quoted, $unquoted));
    }

    /**
     * Handle success event.
     *
     * @return void
     */
    public function success(XMLEvent $event)
    {
        if ($event->isEndTag()) {
            list($element) = $event->getParameters();
            $serverFinalMessage = XML::base64Decode($element->nodeValue);
            $values = $this->parseChallenge($serverFinalMessage);
            $serverSignature = $values['v'] ?? '';
            if ($serverSignature !== $this->serverSignature) {
                throw new AuthenticationErrorException('serverSignature does not match, which means probably MitM attack');
            }
            $this->blocking = false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isBlocking()
    {
        return $this->blocking;
    }
}
