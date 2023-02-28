<?php

namespace Uzwebline\Linecms\App\ViewComponents;

use Illuminate\View\Component;

class AdditionalField extends Component
{
    public $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function render()
    {
        return view('components.additional_field');
    }
}
