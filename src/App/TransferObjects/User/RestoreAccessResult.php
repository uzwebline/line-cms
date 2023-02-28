<?php

namespace Uzwebline\Linecms\App\TransferObjects\User;

use Uzwebline\Linecms\App\TransferObjects\ResultBase;

class RestoreAccessResult extends ResultBase
{
    public $token;
    public $phone;
    public $ttl;
}
