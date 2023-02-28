<?php

namespace Uzwebline\Linecms\App\ViewModels\Taxonomy;

use Uzwebline\Linecms\App\Entities\Taxonomy;
use Uzwebline\Linecms\App\Entities\Term;
use Uzwebline\Linecms\App\ViewModels\Article\ArticleViewModel;
use Uzwebline\Linecms\App\ViewModels\BaseViewModel;

class TermViewModel extends BaseViewModel
{
    public $id;
    public $title;
    public $slug;
    public $title_t;
    public $slug_t;
    public $sorting;
    public $article;

    public function __construct(Term $term = null)
    {
        if (!is_null($term)) {
            $this->id = $term->id;
            $this->title = $term->getTranslation()->title;
            $this->slug = $term->getTranslation()->slug;
            $translations = $term->getTranslationsArray();
            foreach ($translations as $locale => $translation) {
                $this->title_t[$locale] = $translation['title'] ?? "";
                $this->slug_t[$locale] = $translation['slug'] ?? "";
            }
            $this->sorting = $term->sorting;
            $this->article = new ArticleViewModel($term->article);
        }
    }
}
