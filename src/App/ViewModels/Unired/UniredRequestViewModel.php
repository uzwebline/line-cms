<?php

namespace Uzwebline\Linecms\App\ViewModels\Unired;

use Uzwebline\Linecms\App\Entities\UniredApplicationRequest;
use Uzwebline\Linecms\App\ViewModels\BaseViewModel;
use Carbon\Carbon;

class UniredRequestViewModel extends BaseViewModel
{
    public $id;
    public $status;
    public $created_at;

    public function __construct(UniredApplicationRequest $uniredRequest = null)
    {
        if (!is_null($uniredRequest)) {
            $this->id = $uniredRequest->id;
            $this->status = $uniredRequest->status;
            $this->created_at = Carbon::parse($uniredRequest->created_at)->format('d.m.Y H:i');
        }
    }
}
