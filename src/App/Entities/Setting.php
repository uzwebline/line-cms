<?php

namespace Uzwebline\Linecms\App\Entities;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class Setting extends Model
{
    protected $table = 'settings';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($post) {
            $post->{$post->getKeyName()} = Uuid::uuid4()->getHex();
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'value',
        'sort',
    ];

    protected $casts = [
        'value' => 'array',
    ];
}
