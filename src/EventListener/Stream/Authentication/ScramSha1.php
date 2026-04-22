<?php

namespace XmppFg\Xmpp\EventListener\Stream\Authentication;

class ScramSha1 extends Scram
{
    protected const ALGO = 'sha1';
    protected const MECHANISM = 'SCRAM-SHA-1';
}
