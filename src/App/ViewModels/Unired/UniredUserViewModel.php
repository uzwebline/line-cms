<?php

namespace Uzwebline\Linecms\App\ViewModels\Unired;

use Uzwebline\Linecms\App\Entities\ArticleType;
use Uzwebline\Linecms\App\Entities\UniredRequest;
use Uzwebline\Linecms\App\Entities\UniredUser;
use Uzwebline\Linecms\App\ViewModels\BaseViewModel;
use Carbon\Carbon;

class UniredUserViewModel extends BaseViewModel
{
    public $id;
    public $full_name;
    public $phone;
    public $status;
    public $money_limit;
    public $money_used;
    public $h_status;
    public $h_money_limit;
    public $h_money_used;
    public $created_at;

    public function __construct(UniredUser $uniredUser = null)
    {
        if (!is_null($uniredUser)) {
            $this->id = $uniredUser->id;
            $this->full_name = $uniredUser->user->full_name ?? "---";
            $this->phone = $uniredUser->user->phone ?? "---";
            $this->status = $uniredUser->status;
            $this->money_limit = $uniredUser->money_limit / 100;
            $this->money_used = $uniredUser->money_used / 100;
            $this->h_status = $this->getStatusName($uniredUser->status);
            $this->h_money_limit = number_format($this->money_limit, 0, '.', ' ');
            $this->h_money_used = number_format($this->money_used, 0, '.', ' ');
            $this->created_at = Carbon::parse($uniredUser->created_at)->format('d.m.Y H:i');
        }
    }

    protected function getStatusName($status)
    {
        switch ($status) {
            case 0:
                return trans('all.unired_request_status_new');
            case 1:
                return trans('all.unired_request_status_app_in_review');
            case 2:
                return trans('all.unired_request_status_app_rejected');
            case 3:
                return trans('all.unired_request_status_app_approved');
            case 4:
                return trans('all.unired_request_status_card_making');
            case 5:
                return trans('all.unired_request_status_card_made');
        }
    }
}
