<?php

namespace Uzwebline\Linecms\App\TransferObjects\Front;

use Uzwebline\Linecms\App\TransferObjects\ResultBase;

class BreadCrumb extends ResultBase
{
    public $title;
    public $url;
    public $last;

    public function __construct($title, $url, bool $last = false)
    {
        parent::__construct([
            'title' => $title,
            'url' => $url,
            'last' => $last,
        ]);
    }
}
