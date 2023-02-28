<?php

namespace Uzwebline\Linecms\App\ViewModels\Article;

use Uzwebline\Linecms\App\Entities\ArticleType;
use Uzwebline\Linecms\App\Entities\ArticleTypeField;
use Uzwebline\Linecms\App\ViewModels\BaseViewModel;
use Carbon\Carbon;

class ArticleTypeFieldViewModel extends BaseViewModel
{
    public $id;
    public $type;
    public $repeatable;
    public $sorting;

    public function __construct(ArticleTypeField $articleTypeField = null)
    {
        if (!is_null($articleTypeField)) {
            $this->id = $articleTypeField->id;
            $this->type = $articleTypeField->type;
            $this->repeatable = $articleTypeField->repeatable;
            $this->sorting = $articleTypeField->sorting;
            //$this->created_at = Carbon::parse($articleTypeField->created_at)->format('d.m.Y H:i');
        }
    }
}
