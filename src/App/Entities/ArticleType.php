<?php

namespace Uzwebline\Linecms\App\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArticleType extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'slug', 'type_name', 'provider', 'parent_id', 'system', 'has_comments'
    ];

    protected $casts = [
        "system" => "boolean",
        "has_comments" => "boolean"
    ];

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function taxonomies()
    {
        return $this->belongsToMany(Taxonomy::class, 'article_type_taxonomies');
    }

    public function parent_page()
    {
        return $this->belongsTo(Article::class, 'parent_id', 'id');
    }
}
