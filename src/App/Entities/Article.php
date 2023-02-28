<?php

namespace Uzwebline\Linecms\App\Entities;

use Illuminate\Database\Eloquent\{Builder, Model};
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Kalnoy\Nestedset\NodeTrait;

class Article extends Model implements TranslatableContract
{
    use Translatable, NodeTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'parent_id',
        'article_type_id',
        'template',
        'image',
        'af',
        'published',
        'published_at',
        'views_count',
    ];

    protected $casts = [
        "published" => "boolean",
        'af'        => 'array',
    ];

    public $translatedAttributes = [
        'title',
        'description',
        'content',
        'slug',
        'link',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    public function article_type()
    {
        return $this->belongsTo(ArticleType::class);
    }

    public function parent()
    {
        return $this->belongsTo(Article::class, 'parent_id', 'id');
    }

    public function terms()
    {
        return $this->belongsToMany(Term::class, 'article_taxonomy_terms', 'article_id', 'taxonomy_term_id');
    }

    public function comments()
    {
        return $this->hasMany(ArticleComment::class);
    }

    public function scopeByType(Builder $query, string $type_name)
    {
        return $query->whereIn('article_type_id', function ($query) use ($type_name) {
            $query->select('id')
                ->from(with(new ArticleType())->getTable())
                ->where('type_name', $type_name);
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', 1);
    }

    public function scopeByTypeSlug(Builder $query, $type_slug)
    {
        return $query->whereIn('article_type_id', function ($query) use ($type_slug) {
            $query->select('id')
                ->from(with(new ArticleType())->getTable());
            if (is_array($type_slug)) {
                $query->whereIn('slug', $type_slug);
            } else {
                $query->where('slug', $type_slug);
            }
        });
    }

    public function scopeWithTranslations(Builder $query, ?string $locale = null)
    {
        $query->with([
                         'translations' => function (Relation $query) use ($locale) {
                             if (is_null($locale)) {
                                 $locale = app()->getLocale();
                             }

                             return $query->where($this->getTranslationsTable() . 'Entities' . $this->getLocaleKey(), $locale);
                         },
                     ]);
    }
}
