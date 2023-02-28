<?php

namespace Uzwebline\Linecms\App\ViewModels\Requests;

use Uzwebline\Linecms\App\Entities\Resumes;
use Uzwebline\Linecms\App\ViewModels\BaseViewModel;
use Carbon\Carbon;

class ResumesViewModel extends BaseViewModel
{
    public $id;
    public $full_name;
    public $phone;
    public $file;
    public $body;
    public $created_at;

    protected $ignore = ['getStatusClass', 'getStatusName', 'getRolesList'];

    public function __construct(Resumes $requests = null)
    {
        if (!is_null($requests)) {
            $this->id = $requests->id;
            $this->full_name = $requests->full_name;
            $this->phone = $requests->phone;
            $this->file = $requests->file;
            $this->body = $requests->body;
            $this->created_at = Carbon::parse($requests->created_at)->format('d.m.Y H:i');
        }
    }
}
