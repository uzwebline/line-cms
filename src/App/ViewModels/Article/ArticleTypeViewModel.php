<?php

namespace Uzwebline\Linecms\App\ViewModels\Article;

use Uzwebline\Linecms\App\Entities\Article;
use Uzwebline\Linecms\App\Entities\ArticleType;
use Uzwebline\Linecms\App\Entities\Taxonomy;
use Uzwebline\Linecms\App\Providers\Article\ArticleProviderContract;
use Uzwebline\Linecms\App\ViewModels\BaseViewModel;
use Uzwebline\Linecms\App\ViewModels\Taxonomy\TaxonomyViewModel;
use Carbon\Carbon;

class ArticleTypeViewModel extends BaseViewModel
{
    public $id;
    public $slug;
    public $type_name;
    public $name;
    public $parent_id;
    public $provider;
    public $must_have_parent;
    public $can_have_taxonomy;
    public $can_have_fields;
    public $can_have_hierarchy;
    public $taxonomies;
    public $system;
    public $created_at;

    protected $ignore = ['getTaxonomiesList', 'getParentPagesList'];

    public function __construct(ArticleType $article_type = null)
    {
        if (!is_null($article_type)) {
            $this->id = $article_type->id;
            $this->name = $article_type->name;
            $this->type_name = $article_type->type_name;
            $this->slug = $article_type->slug;
            $this->parent_id = $article_type->parent_id;
            $this->provider = $article_type->provider;
            $instance = app($this->provider);
            if ($instance instanceof ArticleProviderContract) {
                $this->must_have_parent = $instance->mustHaveParentPage();
                $this->can_have_taxonomy = $instance->canHaveTaxonomy();
                $this->can_have_fields = $instance->canHaveFields();
                $this->can_have_hierarchy = $instance->canHaveHierarchy();
            }
            $this->taxonomies = $article_type->taxonomies->transform(function ($item) {
                return new TaxonomyViewModel($item);
            });
            $this->system = $article_type->system;
            $this->created_at = Carbon::parse($article_type->created_at)->format('d.m.Y H:i');
        }
    }

    public function getParentPagesList()
    {
        return Article::byType('page')->get()->map(function ($item) {
            return new ArticleViewModel($item);
        });
    }

    public function getTaxonomiesList()
    {
        return Taxonomy::get()->transform(function ($value) {
            return new TaxonomyViewModel($value);
        });
    }
}
