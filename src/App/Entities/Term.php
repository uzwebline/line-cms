<?php

namespace Uzwebline\Linecms\App\Entities;

use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Term extends Model implements TranslatableContract
{
    use Translatable;

    protected $table = "taxonomy_terms";

    protected $translationForeignKey = "taxonomy_term_id";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'taxonomy_id',
        'sorting',
        'article_id',
    ];

    public array $translatedAttributes = ['title', 'slug'];

    /**
     * @return BelongsTo
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * @return string
     */
    protected function locale(): string
    {
        return app()->getLocale();
    }
}
