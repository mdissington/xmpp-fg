<?php

namespace XmppFg\Xmpp\EventListener\Stream\Authentication;

class ScramSha256 extends Scram
{
    protected const ALGO = 'sha256';
    protected const MECHANISM = 'SCRAM-SHA-256';
}
