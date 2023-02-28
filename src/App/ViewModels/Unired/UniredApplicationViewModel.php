<?php

namespace Uzwebline\Linecms\App\ViewModels\Unired;

use Uzwebline\Linecms\App\Entities\UniredApplication;
use Uzwebline\Linecms\App\ViewModels\BaseViewModel;
use Carbon\Carbon;

class UniredApplicationViewModel extends BaseViewModel
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

    public function __construct(UniredApplication $uniredApplication = null)
    {
        if (!is_null($uniredApplication)) {
            $this->id = $uniredApplication->id;
            $this->full_name = $uniredApplication->unired_user->user->full_name;
            $this->phone = $uniredApplication->unired_user->user->phone;
            $this->status = $uniredApplication->unired_user->status;
            $this->money_limit = $uniredApplication->unired_user->money_limit;
            $this->money_used = $uniredApplication->unired_user->money_used;
            $this->h_status = $this->getStatusName($uniredApplication->unired_user->status);
            $this->h_money_limit = number_format($uniredApplication->unired_user->money_limit, 0,'.', ' ');
            $this->h_money_used = number_format($uniredApplication->unired_user->money_used, 0,'.', ' ');
            $this->created_at = Carbon::parse($uniredApplication->created_at)->format('d.m.Y H:i');
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
