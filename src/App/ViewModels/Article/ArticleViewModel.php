<?php

namespace Uzwebline\Linecms\App\ViewModels\Article;

use Uzwebline\Linecms\App\Entities\Article;
use Uzwebline\Linecms\App\Entities\ArticleType;
use Uzwebline\Linecms\App\Services\SettingsService;
use Uzwebline\Linecms\App\ViewModels\BaseViewModel;
use Uzwebline\Linecms\App\ViewModels\Taxonomy\TermViewModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;

class ArticleViewModel extends BaseViewModel
{
    public $id;
    public $title;
    public $slug;
    public $link;
    public $image;
    public $image_preview_url;
    public $template;
    public $title_t;
    public $description_t;
    public $content_t;
    public $slug_t;
    public $link_t;
    public $meta_title_t;
    public $meta_description_t;
    public $meta_keywords_t;
    public $published;
    public $published_at;

    public $parent_id;

    protected $article;

    protected $ignore = [
        'getStatusClass', 'getStatusName', 'getTaxonomiesList',
        'getParentPagesList', 'getPermalink', 'getRelatedTaxonomies',
        'getComments', 'getParentsList', 'getDepth'
    ];

    public function __construct(Article $article = null)
    {
        $this->article = $article;

        if (!is_null($article)) {
            $this->id = $article->id;
            $this->title = $article->getTranslation()->title ?? "";
            $this->slug = $article->getTranslation()->slug  ?? "";
            $this->link = $article->getTranslation()->link  ?? "";
            $translations = $article->getTranslationsArray();
            foreach ($translations as $locale => $translation) {
                $this->title_t[$locale] = $translation['title'] ?? "";
                $this->description_t[$locale] = $translation['description'] ?? "";
                $this->content_t[$locale] = $translation['content'] ?? "";
                $this->slug_t[$locale] = $translation['slug'] ?? "";
                $this->link_t[$locale] = $translation['link'] ?? "";
                $this->meta_title_t[$locale] = $translation['meta_title'] ?? "";
                $this->meta_description_t[$locale] = $translation['meta_description'] ?? "";
                $this->meta_keywords_t[$locale] = $translation['meta_keywords'] ?? "";
            }
            $this->published = $article->published;
            $this->template = $article->template;
            $this->image = $article->image;
            if ($this->image) {
                $this->image_preview_url = Storage::disk('public')->url('/images/preview/' . $this->image);
            }
            $this->published_at = Carbon::parse($article->published_at)->format('d.m.Y H:i');
            $this->parent_id = $article->parent_id;
        }
    }

    public function getStatusClass()
    {
        return $this->published === true ? "success" : "danger";
    }

    public function getStatusName()
    {
        return $this->published === true ? trans('all.status_published') : trans('all.status_unpublished');
    }

    public function getTaxonomiesList(?string $article_type_slug = null)
    {
        if ($article_type_slug) {
            $article_type = ArticleType::where('slug', $article_type_slug)->first();
        } else {
            $article_type = $this->article->article_type;
        }
        return $article_type->taxonomies()->with('terms')->get()->map(function ($item) {
            return new Fluent([
                "id" => $item->id,
                "name" => $item->name,
                "terms" => $item->terms->map(function ($term) {
                    return new TermViewModel($term);
                })
            ]);
        });
    }

    public function getRelatedTermIds(): array
    {
        return $this->article->terms->map(function ($term) {
            return $term->id;
        })->toArray();
    }

    public function getPermalink()
    {
        $url = "";

        if ($this->article->article_type->type_name === "post") {
            $url = url(collect([
                $this->article->article_type->parent_page->slug ?? "not_found",
                $this->article->slug
            ])->join('/'));
        } elseif ($this->article->article_type->type_name === "page") {
            $ancestors = $this->article->ancestors->map(function ($item) {
                return $item->slug;
            });
            $ancestors->push($this->article->slug);
            $url = url($ancestors->join('/'));
            /*$url = url(collect([
                $this->article->slug
            ])->join('/'));*/
        }

        return $url;
    }

    public function getAdditionalFields(?string $slug = null)
    {
        if (!$slug) {
            $slug = $this->article ? $this->article->article_type->slug : null;
        }
        if (!$slug) {
            return [];
        }
        $fields_collection = SettingsService::getThemeConfig('fields');
        foreach ($fields_collection as $fields) {
            if (in_array($slug, $fields['article_types'])) {
                $af = $this->article->af ?? [];
                return collect($fields['params'])->map(function ($item) use ($af) {
                    return array_merge($item, ["values" => $af[$item['slug']] ?? []]);
                });
            }
        }
        return [];
    }

    public function getComments(?int $status = null): array
    {
        $query = $this->article->comments();

        if ($query) {
            $query->where('status', $status);
        }

        return $query->get()->map(function ($item) {
            return new ArticleCommentViewModel($item);
        })->toArray();
    }

    public function getParentsList(string $slug)
    {
        $res = collect();
        $article_type = ArticleType::query()->where('slug', $slug)->first();
        if ($article_type->type_name === 'post') {
            $parents = $article_type->parent_page->article_type
                ->articles()
                ->defaultOrder()
                ->withDepth()
                ->descendantsOf($article_type->parent_page->id);
            foreach ($parents as $parent) {
                $res->push(new Fluent($parent->toArray()));
            }
        } else {
            $descendant_ids = $article_type->articles()->descendantsOf($this->id)->pluck('id');
            $parents = $article_type->articles()
                ->defaultOrder()
                ->withDepth()
                ->whereNotIn('id', $descendant_ids)
                ->where('id', '!=', $this->id)
                ->get();
            foreach ($parents as $parent) {
                $res->push(new Fluent($parent->toArray()));
            }

        }
        return $res;
    }

    public function getDepth()
    {
        $result = Article::query()->withDepth()->find($this->id);
        return $result->depth;
    }
}
