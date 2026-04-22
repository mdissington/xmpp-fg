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

namespace XmppFg\Xmpp\Util;

/**
 * XML utility methods.
 * @package Xmpp\Util
 */
class XML
{

    /**
     * Quote XML string.
     */
    public static function quote(?string $string, string $encoding = 'UTF-8'): string
    {
        return htmlspecialchars($string ?: '', ENT_QUOTES|ENT_XML1, $encoding);
    }

    /**
     * Replace variables in a string and quote them before.
     *
     * <b>Hint:</b> this function works like <code>sprintf</code>
	 * @param ?string ...$variables
     */
    public static function quoteMessage(string $message, ?string ...$variables): string
    {
        return vsprintf(
            $message,
            array_map(
                function ($var) {
                    return self::quote($var);
                },
                $variables
            )
        );
    }

    /**
     * Generate a unique id.
     */
    public static function generateId(): string
    {
        return static::quote('xmppfg_xmpp_' . uniqid());
    }

    /**
     * Encode a string with Base64 and quote it.
     */
    public static function base64Encode(string $data, string $encoding = 'UTF-8'): string
    {
        return static::quote(base64_encode($data), $encoding);
    }

    /**
     * Decode a Base64 encoded string.
     */
    public static function base64Decode(string $data): string
    {
        return base64_decode($data);
    }
}
