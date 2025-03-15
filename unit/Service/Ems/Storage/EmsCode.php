<?php

declare(strict_types=1);
/**
 * This file is part of hyperf-ext/mail.
 *
 * @link     https://github.com/hyperf-ext/mail
 * @contact  eric@zhu.email
 * @license  https://github.com/hyperf-ext/mail/blob/master/LICENSE
 */
namespace Upp\Service\Ems\Storage;

use HyperfExt\Contract\ShouldQueue;
use HyperfExt\Mail\Mailable;

class EmsCode extends Mailable implements ShouldQueue
{

    public $code;
    public $subject;

    /**
     * Create a new message instance.
     */
    public function __construct($code,$subject = "")
    {
        $this->code = $code;
        $this->subject = $subject;
    }

    /**
     * Build the message.
     */
    public function build()
    {

        $html = <<<ht
    <p>Hello, your verification code is：<em style="font-weight: 700;">{$this->code}</em></p>
ht;
        $res = $this->subject($this->subject)->htmlBody($html);

        return $res;
    }


}
