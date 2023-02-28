<?php

namespace Uzwebline\Linecms\App\Entities;

use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Taxonomy extends Model implements TranslatableContract
{
    use Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'alias',
        'term_type', // tag, term
    ];

    public array $translatedAttributes = ['title', 'slug'];

    /**
     * @return HasMany
     */
    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }
}
