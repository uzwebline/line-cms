<?php

namespace Uzwebline\Linecms\App\Entities;

use Illuminate\Database\Eloquent\Model;

class Resumes extends Model
{
    protected $table = 'resumes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'full_name',
        'body',
        'phone',
        'file',
    ];

    protected $casts = [
    ];
}
