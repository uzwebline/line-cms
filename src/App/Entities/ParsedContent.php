<?php

namespace Uzwebline\Linecms\App\Entities;

use Illuminate\Database\Eloquent\Model;

class ParsedContent extends Model
{

    protected $table = 'parsed_contents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'parsing_id',
        'title',
        'description',
        'link',
        'img',
        'local_image',
        'date',
    ];
}
