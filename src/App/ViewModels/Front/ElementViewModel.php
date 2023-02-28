<?php

namespace Uzwebline\Linecms\App\ViewModels\Front;

use Uzwebline\Linecms\App\Containers\FrontPageContainer;
use Uzwebline\Linecms\App\Entities\Article;
use Uzwebline\Linecms\App\Entities\ArticleType;
use Uzwebline\Linecms\App\Entities\Taxonomy;
use Uzwebline\Linecms\App\TransferObjects\DTOCache;
use Uzwebline\Linecms\App\ViewModels\BaseViewModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ElementViewModel extends BaseViewModel
{
    public $id;
    public $title;
    public $description;
    public $content;
    public $slug;
    public $link;
    public $image;
    public $published;
    public $published_at;

    public $locale;
    public $children;
    public $article_type;

    /**
     * @var FrontPageContainer
     */
    protected $page_container;

    protected $article;

    protected $ignore = ['getStatusClass', 'getStatusName', 'getTaxonomiesList', 'getParentPagesList'];

    public function __construct(?Article $article = null)
    {
        $this->article = $article;

        $this->locale = app()->getLocale();

        if (!is_null($this->article)) {
            $array             = $this->article->toArray();
            $translation       = [];
            $translation_model = $this->article->translate($this->locale);
            if ($translation_model) {
                $translation = $translation_model->toArray();
            }
            foreach ($this->article->translatedAttributes as $attr) {
                unset($array[$attr]);
            }

            unset($array['children']);

            if (!is_null($this->article->children)) {
                foreach ($this->article->children as $child) {
                    $array['children'][] = new static($child);
                }
            } else {
                $array['children'] = collect();
            }

            $this->populate(array_merge($translation, $array));

            $this->page_container = app('front.page');
            $this->article_type   = $this->article->article_type->type_name ?? 'item';
        }
    }

    protected function populate(array $parameters = [])
    {
        $fields = $this->getFields();

        foreach ($fields as $field => $validator) {
            $value = $parameters[$field] ?? $this->{$field} ?? null;

            $this->{$field} = $value;

            unset($parameters[$field]);
        }
    }

    protected function getFields(): array
    {
        return DTOCache::resolve(static::class, function () {
            $class = new \ReflectionClass(static::class);

            $properties = [];

            foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
                // Skip static properties
                if ($reflectionProperty->isStatic()) {
                    continue;
                }

                $field = $reflectionProperty->getName();

                $properties[$field] = $reflectionProperty;//FieldValidator::fromReflection($reflectionProperty);
            }

            return $properties;
        });
    }

    public function getTitleExcerpt(?int $limit = 20)
    {
        return Str::limit(strip_tags($this->title), $limit);
    }

    public function getContentExcerpt(?string $highlight = null)
    {
        return Str::limit(strip_tags($this->content), 200);
    }

    public function getDescriptionExcerpt(?string $highlight = null)
    {
        return Str::limit(strip_tags($this->description), 200);
    }


    public function getDescriptionAsContent()
    {
        $description_arr = explode("\n", $this->description);
        $res             = "";
        foreach ($description_arr as $desc_item) {
            $res .= "<p>$desc_item</p>";
        }

        return $res;
    }

    public function getTitleBroken()
    {
        $title_arr = explode(" ", $this->title);
        $res       = "";
        foreach ($title_arr as $title_item) {
            $res .= "$title_item<br/>";
        }

        return $res;
    }

    public function getPublishDate(string $format = 'd.m.Y')
    {
        return Carbon::parse($this->published_at)->format($format);
    }

    public function hasImage(): bool
    {
        return $this->image !== null;
    }

    public function getImageUrl(?int $width = null, ?int $height = null, bool $force = true)
    {
        if (!$this->image) {
            return null;
        }

        $prefix = "";

        if ($force) {
            $prefix = "f_";
        }

        if ($width && $height) {
            $path = '/images/' . $prefix . $width . '_' . $height . '_' . $this->image;
            if (!Storage::disk('public')->exists($path)) {
                try {
                    $image_contents = Storage::disk('public')->get('/images/' . $this->image);
                    $img            = Image::make($image_contents);
                } catch (\Exception $exception) {
                    return null;
                }
                if ($force) {
                    $img->fit($width, $height, function ($constraint) {
                        //$constraint->upsize();
                    })
                        ->resizeCanvas($width, $height, 'center', false)
                        ->save(storage_path('app/public/' . $path));
                } else {
                    $img->fit($width, $height, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })->save(storage_path('app/public/' . $path));
                }
            }

            return Storage::disk('public')->url($path);
        } elseif ($height) {
            $path = '/images/' . $prefix . 'x_' . $height . '_' . $this->image;
            if (!Storage::disk('public')->exists($path)) {
                try {
                    $image_contents = Storage::disk('public')->get('/images/' . $this->image);
                    $img            = Image::make($image_contents);
                } catch (\Exception $exception) {
                    return null;
                }
                if ($force) {
                    $img->fit(null, $height, function ($constraint) {
                        /*$constraint->aspectRatio();
                        $constraint->upsize();*/
                    })
                        ->resizeCanvas(null, $height, 'center', false)
                        ->save(storage_path('app/public/' . $path));
                } else {
                    $img->fit(null, $height, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })->save(storage_path('app/public/' . $path));
                }
            }

            return Storage::disk('public')->url($path);
        } elseif ($width) {
            $path = '/images/' . $prefix . $width . '_x_' . $this->image;
            if (!Storage::disk('public')->exists($path)) {
                try {
                    $image_contents = Storage::disk('public')->get('/images/' . $this->image);
                    $img            = Image::make($image_contents);
                } catch (\Exception $exception) {
                    return null;
                }
                if ($force) {
                    $img->fit($width, null, function ($constraint) {
                        /*$constraint->aspectRatio();
                        $constraint->upsize();*/
                    })
                        ->resizeCanvas($width, null, 'center', false)
                        ->save(storage_path('app/public/' . $path));
                } else {
                    $img->fit($width, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })->save(storage_path('app/public/' . $path));
                }
            }

            return Storage::disk('public')->url($path);
        } else {
            return Storage::disk('public')->url('/images/' . $this->image);
        }
    }

    public function previewImage(): string
    {
        if ($this->image) {
            return Storage::disk('public')->url('/images/preview/' . $this->image);
        }else{
            return '';
        }
    }

    public function getPermalink(array $parameters = [])
    {
        $locale_prefix = "";

        if (config('translatable.locale') !== $this->locale) {
            $locale_prefix = $this->locale;
        }

        $url = "";

        $query = '';

        if (count($parameters)) {
            $query = '?' . http_build_query($parameters);
        }

        if ($this->article->article_type->type_name === "post") {
            $article_type_parent_page = $this->article->article_type->parent_page->translate($this->locale);
            $parent                   = $this->article->parent;
            if (is_null($parent)) {
                $url = ElementViewModel . phpurl(collect([
                                                                 $locale_prefix,
                                                                 $article_type_parent_page->slug ?? "not_found",
                                                                 $this->slug,
                                                         ])->join('/')) . $query;
            } else {
                $url_pieces = collect();
                $url_pieces->push($locale_prefix);
                $parent->ancestors->map(function ($item) use (&$url_pieces) {
                    $translated_item = $item->translate($this->locale);
                    $url_pieces->push($translated_item->slug);
                });
                $url_pieces->push($parent->slug);
                $url_pieces->push($this->slug);
                $url = ElementViewModel . phpurl($url_pieces->join('/')) . $query;
            }
        } elseif ($this->article->article_type->type_name === "page") {
            $url_pieces = collect();
            $url_pieces->push($locale_prefix);
            $this->article->ancestors->map(function ($item) use (&$url_pieces) {
                $translated_item = $item->translate($this->locale);
                $url_pieces->push($translated_item->slug);
            });
            $url_pieces->push($this->slug);
            $url = ElementViewModel . phpurl($url_pieces->join('/')) . $query;
        }

        return $url;
    }

    public function getRelatedPostTaxonomyTerms(string $post_slug, string $taxonomy_alias)
    {
        $article_type = ArticleType::query()
            ->where('parent_id', $this->id)
            ->where('type_name', 'post')
            ->firstWhere('slug', $post_slug);

        if (is_null($article_type)) {
            return collect();
        }

        $taxonomy = $article_type->taxonomies()->firstWhere('alias', $taxonomy_alias);

        if (is_null($taxonomy)) {
            return collect();
        }

        if ($taxonomy->term_type === 'tag') {
            return $taxonomy->terms()->orderBy('sorting')->get()->map(function ($tag) {
                return new Fluent([
                                      'title' => $tag->title,
                                      'slug'  => $tag->slug,
                                  ]);
            });
        } else {
            return $taxonomy->terms->map(function ($tag) {
            });
        }
    }

    /**
     * @param string $taxonomy_alias
     * @param string $return_type
     *
     * @return mixed
     */
    public function getTags(?string $taxonomy_alias = null, string $return_type = 'array')
    {
        $query = $this->article->terms();

        if ($taxonomy_alias) {
            $query->whereIn('taxonomy_id', function ($sub_query) use ($taxonomy_alias) {
                $sub_query
                    ->select('id')
                    ->where('alias', $taxonomy_alias)
                    ->from(with(new Taxonomy())->getTable());
            });
        }

        $tags = $query->get()
            ->map(function ($tag) {
                return new Fluent([
                                      'title' => $tag->title,
                                      'slug'  => $tag->slug,
                                  ]);
            });

        if ($return_type === 'string') {
            return $tags->implode('title', ', ');
        }

        if ($return_type === 'string_first') {
            if ($tags->count() === 0) {
                return null;
            }

            return $tags->first()->title;
        }

        return $tags;
    }

    public function getClearContent()
    {
        return strip_tags($this->content);
    }

    public function getField(string $slug, bool $get_translated = false, bool $get_as_array = false)
    {
        $af = $this->article->af ?? [];

        if ($get_translated) {
            $field = $af[$slug][app()->getLocale()] ?? null;
            if ($field && is_array($field)) {
                if ($get_as_array) {
                    return $field;
                }
                if (count($field) === 1) {
                    return $field[0];
                } else {
                    return $field;
                }
            }
        } else {
            $field = $af[$slug]['value'] ?? null;
            if ($field && is_array($field)) {
                if ($get_as_array) {
                    return $field;
                }
                if (count($field) === 1) {
                    return $field[0];
                } else {
                    return $field;
                }
            }
        }
        if ($get_as_array) {
            return [];
        }

        return null;
    }

}
