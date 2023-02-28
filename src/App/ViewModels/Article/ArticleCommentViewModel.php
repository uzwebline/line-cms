<?php

namespace Uzwebline\Linecms\App\\ViewModels\Article;

use Uzwebline\Linecms\App\Entities\ArticleComment;
use Uzwebline\Linecms\App\ViewModels\BaseViewModel;
use Carbon\Carbon;

class ArticleCommentViewModel extends BaseViewModel
{
    public $id;
    public $comment;
    public $status;
    public $user_id;
    public $article_id;
    public $member_name;
    public $created_at;

    public function __construct(ArticleComment $articleComment = null)
    {
        if (!is_null($articleComment)) {
            $this->id = $articleComment->id;
            $this->comment = $articleComment->comment;
            $this->status = $articleComment->status;
            $this->article_id = $articleComment->article_id;
            $this->user_id = $articleComment->user_id;
            $this->member_name = $articleComment->user->f_name . ' ' . $articleComment->user->l_name;
            $this->created_at = Carbon::parse($articleComment->created_at)->format('d.m.Y H:i');
        }
    }
}
