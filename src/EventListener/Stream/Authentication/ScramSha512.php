<?php

namespace XmppFg\Xmpp\EventListener\Stream\Authentication;

class ScramSha512 extends Scram
{
    protected const ALGO = 'sha512';
    protected const MECHANISM = 'SCRAM-SHA-512';
}
