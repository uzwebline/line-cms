<?php

namespace Uzwebline\Linecms\App\Entities;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $table = 'regions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'title_uz',
        'title_ru',
        'sorting',
    ];
}
