<?php

namespace Uzwebline\Linecms\App\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

class MenuItem extends Model
{
    protected $table = 'menu_items';

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
        'menu_id',
        'parent_id',
        'title',
        'link',
        'sort',
        'class',
        'icon',
    ];

    /**
     * @return BelongsTo
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}
