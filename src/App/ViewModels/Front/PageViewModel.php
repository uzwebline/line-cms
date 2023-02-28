<?php

namespace Uzwebline\Linecms\App\ViewModels\Front;

use Carbon\Carbon;
use Uzwebline\Linecms\App\Containers\FrontPageContainer;
use Uzwebline\Linecms\App\Entities\{Article, ArticleType, Region, Taxonomy};
use Uzwebline\Linecms\App\TransferObjects\{DTOCache, Front\BreadCrumb};
use Uzwebline\Linecms\App\ViewModels\{Article\ArticleCommentViewModel, BaseViewModel};
use Illuminate\Support\{Facades\Storage, Facades\View, Fluent, Str};
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\Response;

class PageViewModel extends BaseViewModel
{
    public int|null    $id;
    public string|null $title;
    public string|null $description;
    public string|null $content;
    public string|null $meta_title;
    public string|null $meta_description;
    public string|null $meta_keywords;
    public string|null $slug;
    public string|null $link;
    public             $image;
    public             $items;
    public             $published;
    public             $published_at;
    public             $template;
    public             $article_type_name;
    public             $article_type_slug;

    public $locale;

    /**
     * @var Article
     */
    protected $article;

    protected int $http_status = 200;
    /**
     * @var $page_container FrontPageContainer
     */
    protected FrontPageContainer $page_container;

    protected $ignore = ['getStatusClass', 'getStatusName', 'getTaxonomiesList', 'getParentPagesList'];

    public function __construct(array $parameters = [])
    {
        $this->locale = app()->getLocale();

        $this->populate($parameters);

        $this->article = Article::find($this->id);

        $view_prefix = "";

        if ($this->article_type_name) {
            $view_prefix = Str::plural($this->article_type_name) . 'Front';
        }

        $view = $view_prefix . $this->template;

        if ($this->template === "default" && $this->article_type_slug) {
            $checking_view = $view_prefix . Str::singular($this->article_type_slug);
            if (View::exists("theme::$checking_view")) {
                $view = $checking_view;
            }
        }

        $this->view = "theme::$view";

        $this->page_container = app('front.page');
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

    public function setHttpStatus(int $http_status = 200): self
    {
        $this->http_status = $http_status;

        return $this;
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

    public function toResponse($request): Response
    {
        if ($this->article) {
            $this->article->views_count++;
            $this->article->save();
        }

        return response()->view($this->view, [], $this->http_status);
    }

    public function getMetaTitle(): string
    {
        return $this->meta_title ? : $this->title;
    }

    public function getPublishDate(string $format = 'd.m.Y'): string
    {
        return Carbon::parse($this->published_at)->format($format);
    }

    public function hasImage(): bool
    {
        return (bool)$this->image;
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
                    $img->fit($width, $height)->resizeCanvas($width, $height)
                        ->save(storage_path('app/public/' . $path));
                } else {
                    $img->fit($width, $height, function ($constraint) {
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
                    $img->fit(null, $height)->resizeCanvas(null, $height)
                        ->save(storage_path('app/public/' . $path));
                } else {
                    $img->fit(null, $height, function ($constraint) {
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
                    $img->fit($width)->resizeCanvas($width, null)
                        ->save(storage_path('app/public/' . $path));
                } else {
                    $img->fit($width, null, function ($constraint) {
                        $constraint->upsize();
                    })->save(storage_path('app/public/' . $path));
                }
            }

            return Storage::disk('public')->url($path);
        } else {
            return Storage::disk('public')->url('/images/' . $this->image);
        }
    }

    public function getPermalink(array $parameters = []): string
    {
        $locale_prefix = "";
        $url           = "";
        $query         = "";

        if (config('translatable.locale') !== $this->locale) {
            $locale_prefix = $this->locale;
        }

        if (count($parameters)) {
            $query = '?' . http_build_query($parameters);
        }

        switch ($this->article->article_type->type_name) {
            case 'post':
                $translated_parent_page = $this->article->article_type->parent_page->translate($this->locale);
                $url                    = PageViewModel . phpurl(collect([
                                                                             $locale_prefix,
                                                                             $translated_parent_page->slug ?? "not_found",
                                                                             $this->slug,
                                                                         ])->join('/')) . $query;
                break;
            case 'page':
                $url_pieces = collect();
                $url_pieces->push($locale_prefix);
                $this->article->ancestors->map(function ($item) use (&$url_pieces) {
                    $translated_item = $item->translate($this->locale);
                    $url_pieces->push($translated_item->slug);
                });
                $url_pieces->push($this->slug);
                $url = PageViewModel . phpurl($url_pieces->join('/')) . $query;
                break;
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

    public function getRelatedPosts(string $post_slug, $limit = 25, array $filter = [])
    {
        $query     = Article::query();
        $has_order = false;
        if (count($filter)) {
            foreach ($filter as $key => $value) {
                if (($key === 'tag' || $key === 'term') && !empty($value)) {
                    $query->whereHas('terms', function ($sub_query) use ($value) {
                        $sub_query->join('taxonomy_term_translations', function ($join) use ($value) {
                            $join
                                ->on('taxonomy_term_translations.taxonomy_term_id', 'taxonomy_terms.id')
                                ->where('taxonomy_term_translations.locale', $this->locale)
                                ->where('taxonomy_term_translations.slug', $value);
                        });
                    });
                }
                if (($key === 'order_by') && !empty($value)) {
                    $query->orderBy($value, 'DESC');
                    $has_order = true;
                }
                if (($key === 'order_by_asc') && !empty($value)) {
                    $query->orderBy($value, 'ASC');
                    $has_order = true;
                }
            }
        }

        if (!$has_order) {
            $query = $query->defaultOrder()->orderBy('published_at', 'DESC');
        }

        $items = $query->byTypeSlug($post_slug)
            ->withTranslations()
            ->limit($limit)
            ->get();

        $items->transform(function ($item) {
            return new ElementViewModel($item);
        });

        return $items;
    }

    public function paginateRelatedPosts(string $post_slug, $limit = 25, array $filter = [])
    {
        $query     = Article::query();
        $has_order = false;
        if (count($filter)) {
            foreach ($filter as $key => $value) {
                if (($key === 'tag' || $key === 'term') && !empty($value)) {
                    $query->whereHas('terms', function ($sub_query) use ($value) {
                        $sub_query->join('taxonomy_term_translations', function ($join) use ($value) {
                            $join
                                ->on('taxonomy_term_translations.taxonomy_term_id', 'taxonomy_terms.id')
                                ->where('taxonomy_term_translations.locale', $this->locale)
                                ->where('taxonomy_term_translations.slug', $value);
                        });
                    });
                }
                if (($key === 'order_by') && !empty($value)) {
                    $query->orderBy($value, 'DESC');
                    $has_order = true;
                }
                if (($key === 'order_by_asc') && !empty($value)) {
                    $query->orderBy($value, 'ASC');
                    $has_order = true;
                }
            }
        }

        if (!$has_order) {
            $query = $query->defaultOrder()->orderBy('published_at', 'DESC');
        }

        $items = $query->byTypeSlug($post_slug)
            ->withTranslations()
            ->paginate($limit);

        $items->transform(function ($item) {
            return new ElementViewModel($item);
        });

        return $items;
    }

    public function getParentPage()
    {
        if ($this->article->article_type->type_name === 'post') {
            return new ElementViewModel($this->article->article_type->parent_page);
        }

        return null;
    }

    public function getRelatedTaxonomyTerms(string $taxonomy_alias, string $return_type = 'array')
    {
        $tags = $this->article->terms()
            ->whereIn('taxonomy_id', function ($sub_query) use ($taxonomy_alias) {
                $sub_query
                    ->select('id')
                    ->where('alias', $taxonomy_alias)
                    ->from(with(new Taxonomy())->getTable());
            })
            ->get()
            ->map(function ($tag) {
                return new Fluent([
                                      'title' => $tag->title,
                                      'slug'  => $tag->slug,
                                  ]);
            });

        if ($return_type === 'string') {
            return $tags->implode('title', ',');
        }

        if ($return_type === 'string_first') {
            if ($tags->count() === 0) {
                return null;
            }

            return $tags->first()->title;
        }

        return $tags;
    }

    public function getField(string $slug, bool $get_translated = false, bool $get_as_array = false)
    {
        $af = $this->article->af ?? [];

        if ($get_translated) {
            $field = $af[$slug][app()->getLocale()] ?? null;
            $pair  = $af[$slug]['pair'] ?? null;
            if ($field && is_array($field)) {
                if ($get_as_array) {
                    return $field;
                }
                if (count($field) === 1) {
                    return $field[0];
                } else {
                    return $field;
                }
            } elseif (!is_null($pair)) {
                $keys   = $pair['keys'];
                $values = $pair['values'];
                $res    = collect();
                $iter   = 0;
                foreach ($keys[app()->getLocale()] as $key) {
                    $res->push(new Fluent([
                                              'key'   => $key,
                                              'value' => $values[app()->getLocale()][$iter++],
                                          ]));
                }

                return $res->toArray();
            }
        } else {
            $field = $af[$slug]['value'] ?? null;
            $pair  = $af[$slug]['pair'] ?? null;
            if ($field && is_array($field)) {
                if ($get_as_array) {
                    return $field;
                }
                if (count($field) === 1) {
                    return $field[0];
                } else {
                    return $field;
                }
            } elseif (!is_null($pair)) {
                $keys   = $pair['keys'];
                $values = $pair['values'];
                $res    = collect();
                $iter   = 0;
                foreach ($keys['default'] as $key) {
                    $res->push(new Fluent([
                                              'key'   => $key,
                                              'value' => $values['default'][$iter++],
                                          ]));
                }

                return $res->toArray();
            }
        }
        if ($get_as_array) {
            return [];
        }

        return null;
    }

    public function getComments()
    {
        $query = $this->article
            ->comments()
            ->where('status', 1)
            ->orderBy('created_at', 'DESC');

        return $query->get()->map(function ($item) {
            return new ArticleCommentViewModel($item);
        });
    }

    public function getChildren(int $depth = 1)
    {
        $descendants = $this->article->descendants()->withDepth()->get();
        $res         = collect();
        foreach ($descendants as $item) {
            if ($item->depth <= $depth) {
                $res->push(new ElementViewModel($item));
            }
        }

        return $res;
    }

    public function hasParent()
    {
        return $this->article->parent()->exists();
    }

    public function getParent()
    {
        return new ElementViewModel($this->article->parent()->first());
    }

    public function getBreadCrumbs(?string $template = null)
    {
        $items = collect();

        $locale_prefix = "";

        if (config('translatable.locale') !== $this->locale) {
            $locale_prefix = $this->locale;
        }

        $items->push(new BreadCrumb(trans('theme::all.home'), url('/' . $locale_prefix)));

        if ($this->article->article_type->type_name === "post") {
            $article_type_parent_page = $this->article->article_type->parent_page->translate($this->locale);
            $parent                   = $this->article->parent;
            if (is_null($parent)) {
                $items->push(new BreadCrumb($article_type_parent_page->title, url($locale_prefix . '/' . $article_type_parent_page->slug)));
                $items->push(new BreadCrumb($this->title, url($locale_prefix . '/' . $article_type_parent_page->slug . '/' . $this->slug), true));
            } else {
                $url_pieces = collect($locale_prefix);
                $parent->ancestors->map(function ($item) use (&$items, &$url_pieces) {
                    $translated_item = $item->translate($this->locale);
                    $url_pieces->push($translated_item->slug);
                    $url = url($url_pieces->join('/'));
                    $items->push(new BreadCrumb($translated_item->title, $url));
                });
                $url_pieces->push($parent->slug);
                $url = url($url_pieces->join('/'));
                $items->push(new BreadCrumb($parent->title, $url));
                $url_pieces->push($this->slug);
                $url = url($url_pieces->join('/'));
                $items->push(new BreadCrumb($this->title, $url, true));
            }
        } elseif ($this->article->article_type->type_name === "page") {
            $url_pieces = collect($locale_prefix);
            $this->article->ancestors->map(function ($item) use (&$items, &$url_pieces) {
                $translated_item = $item->translate($this->locale);
                $url_pieces->push($translated_item->slug);
                $url = url($url_pieces->join('/'));
                $items->push(new BreadCrumb($translated_item->title, $url));
            });
            $url_pieces->push($this->slug);
            $url = url($url_pieces->join('/'));
            $items->push(new BreadCrumb($this->title, $url, true));
        }

        return view()
            ->first(['theme::breadcrumbs.' . $template, 'theme::breadcrumbs.default'], compact('items'))
            ->render();
    }

    public function getRegions()
    {
        $res = collect();
        foreach (Region::query()->orderBy('sorting', 'ASC')->get() as $region) {
            $res->put($region->id, [
                "ru" => $region->title_ru,
                "uz" => $region->title_uz,
            ]);
        }

        return $res;
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
}
