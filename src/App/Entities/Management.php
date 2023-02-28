<?php

namespace Uzwebline\Linecms\App\Entities;

use Illuminate\Database\Eloquent\Model;

class Management extends Model
{
    protected $table = 'management';

    protected $fillable = [
        'id', 'full_name', 'position', 'phone', 'fax', 'acceptance', 'description', 'image'
    ];

    protected $casts = [
        'full_name' => 'array',
        'position' => 'array',
        'acceptance' => 'array',
        'description' => 'array',
    ];
}
