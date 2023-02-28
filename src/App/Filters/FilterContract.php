<?php
/**
 * Created by PhpStorm.
 * User: umid
 * Date: 6/27/20
 * Time: 9:03 PM
 */

namespace Uzwebline\Linecms\App\Filters;


use Illuminate\Database\Eloquent\Builder;

interface FilterContract
{
    public function apply(Builder $model): Builder;
}
