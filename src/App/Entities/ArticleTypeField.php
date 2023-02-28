<?php

namespace Uzwebline\Linecms\App\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArticleTypeField extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'article_type_id', 'type', 'repeatable', 'sorting'
    ];

    protected $casts = [
        "repeatable" => "boolean"
    ];
}
