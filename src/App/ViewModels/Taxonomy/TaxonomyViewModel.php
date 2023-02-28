<?php
/**
 * Created by PhpStorm.
 * User: umid
 * Date: 7/11/20
 * Time: 12:09 PM
 */

namespace Uzwebline\Linecms\App\ViewModels\Taxonomy;


use Uzwebline\Linecms\App\Entities\Taxonomy;
use Uzwebline\Linecms\App\ViewModels\BaseViewModel;

class TaxonomyViewModel extends BaseViewModel
{
    public $id;
    public $name;
    public $alias;
    public $term_type;
    public $title;
    public $slug;

    public function __construct(Taxonomy $taxonomy = null)
    {
        if (!is_null($taxonomy)) {
            $this->id = $taxonomy->id;
            $this->alias = $taxonomy->alias;
            $this->name = $taxonomy->name;
            $this->term_type = $taxonomy->term_type;

            $translations = $taxonomy->getTranslationsArray();
            foreach ($translations as $locale => $translation) {
                $this->title[$locale] = $translation['title'] ?? "";
                $this->slug[$locale] = $translation['slug'] ?? "";
            }
            //$this->created_at = Carbon::parse($articleTypeField->created_at)->format('d.m.Y H:i');
        }
    }
}
