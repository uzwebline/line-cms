<?php

namespace Uzwebline\Linecms\App\Services;

use Uzwebline\Linecms\App\Containers\FrontPageContainer;
use Uzwebline\Linecms\App\Entities\{Article, ArticleTranslation, ArticleType, Taxonomy};
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\UrlGenerator;
use Uzwebline\Linecms\App\ViewModels\Front\{NotFoundPageViewModel, PageViewModel, SitemapViewModel};
use Uzwebline\Linecms\App\ViewModels\Front\{CommandPageViewModel, ElementViewModel, HomePageViewModel};
use Astrotomic\Translatable\Locales;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\{Facades\Cache, Facades\DB, Fluent, Str};

class FrontService
{
    protected int|float $cache_time_out;
    /**
     * @var FrontPageContainer
     */
    protected mixed $page_container;

    protected int|\Illuminate\Support\Carbon $queryCTL = 0;

    public function __construct()
    {
        $this->page_container = app('front.page');
        $this->page_container->setFrontService($this);
        $this->cache_time_out = 60 * 60 * 12;
        $this->queryCTL = now()->addHour();
    }

    /**
     * @return string|null
     */
    public static function setLocalePrefix(): ?string
    {
        $locale = request()->segment(1);

        if (!$locale || !app(Locales::class)->has($locale)) {
            return null;
        }

        app()->setLocale($locale);

        return $locale;
    }


    public function processHomePage()
    {
        return $this->page_container->setPage(new HomePageViewModel());
    }

    public function processRequest(string $path)
    {
        $path_pieces = collect(explode('/', $path))->filter(function ($piece) {
            return !empty($piece);
        });

        if ($path_pieces->count() === 0) {
            return $this->page_container->setPage(new HomePageViewModel());
        }

        $first_slug = $path_pieces->first();

        $root_page = Cache::remember('root_page.' . $first_slug . app()->getLocale(), $this->queryCTL,
            function () use ($first_slug) {
                return DB::table('articles')
                    ->join('article_types', function ($join) {
                        $join->on('article_types.id', 'articles.article_type_id');
                        $join->where('article_types.type_name', 'page');
                    })
                    ->leftJoin('article_translations', function ($join) {
                        $join
                            ->on('articles.id', 'article_translations.article_id')
                            ->where('article_translations.locale', app()->getLocale());
                    })
                    ->whereNull('articles.parent_id')
                    ->where('article_translations.slug', $first_slug)
                    ->where('articles.published', true)
                    ->select(
                        'article_types.type_name as article_type_name',
                        'article_types.slug as article_type_slug',
                        'article_translations.*',
                        'articles.id',
                        'articles.template',
                        'articles.image',
                        'articles.parent_id'
                    )
                    ->first();
            });

        if (is_null($root_page)) {
            return $this->page_container->setPage(new NotFoundPageViewModel());
        }

        if ($path_pieces->count() === 1) { // this root page

            $root_page = (array)$root_page;

            return $this->page_container->setPage(new PageViewModel($root_page));
        } else {
            $root_page_descendants = Article::query()
                ->withTranslations()
                ->descendantsAndSelf($root_page->id)
                ->toTree();

            $path_variants = collect();

            $traverse = function ($page_descendants, &$path, $prefix) use (&$traverse) {
                foreach ($page_descendants as $page_descendant) {
                    $page_descendant_translation = $page_descendant->translate(app()->getLocale());
                    $path->put($page_descendant->id, $prefix . '/' . $page_descendant_translation->slug);
                    $traverse($page_descendant->children, $path, $prefix . '/' . $page_descendant_translation->slug);
                }
            };

            foreach ($root_page_descendants as $page_descendant) {
                $path = collect();
                $page_descendant_translation = $page_descendant->translate(app()->getLocale());
                $path->put($page_descendant->id, $page_descendant_translation->slug);
                $traverse($page_descendant->children, $path, $page_descendant_translation->slug);
                $path_variants->put($page_descendant->id, $path);
            }

            $searching_full_path = $path_pieces->join('/');

            $path_found = false;
            $exact_path_found = false;
            $exact_page_id = 0;

            foreach ($path_variants->first() as $path_id => $path_variant) {
                if (Str::startsWith($searching_full_path, $path_variant)) {
                    $path_found = true;
                }
                if ($searching_full_path === $path_variant) {
                    $exact_path_found = true;
                    $exact_page_id = $path_id;
                }
            }

            if ($path_found === false) {
                return $this->page_container->setPage(new NotFoundPageViewModel());
            }

            if ($exact_path_found) {
                $exact_page = Cache::remember('exact_page.' . $exact_page_id . app()->getLocale(), $this->queryCTL,
                    function () use ($exact_page_id) {
                        return DB::table('articles')
                            ->join('article_types', function ($join) {
                                $join->on('article_types.id', 'articles.article_type_id');
                                $join->whereIn('article_types.type_name', ['page', 'post']);
                            })
                            ->leftJoin('article_translations', function ($join) {
                                $join
                                    ->on('articles.id', 'article_translations.article_id')
                                    ->where('article_translations.locale', app()->getLocale());
                            })
                            ->where('articles.id', $exact_page_id)
                            ->where('articles.published', true)
                            ->select(
                                'article_types.type_name as article_type_name',
                                'article_types.slug as article_type_slug',
                                'article_translations.*',
                                'articles.id',
                                'articles.template',
                                'articles.image',
                                'articles.parent_id'
                            )
                            ->first();
                    });

                $exact_page_data = (array)$exact_page;
                if (!isset($exact_page_data['template']) || $exact_page_data['template'] === 'default') {
                    $exact_page_data['template'] = $root_page->template;
                }

                return $this->page_container->setPage(new PageViewModel($exact_page_data));
            }

            $last_slug = $path_pieces->last();
            $term = Cache::remember('term.' . $last_slug . $root_page->id . app()->getLocale(), $this->queryCTL,
                function () use ($root_page, $last_slug) {
                    return DB::table('articles')
                        ->join('article_types', function ($join) use ($root_page) {
                            $join
                                ->on('article_types.id', 'articles.article_type_id')
                                ->where('article_types.type_name', 'term');
                        })
                        ->join('taxonomy_terms', function ($join) use ($root_page) {
                            $join
                                ->on('taxonomy_terms.article_id', 'articles.id');
                        })
                        ->join('taxonomy_translations', function ($join) use ($root_page) {
                            $join
                                ->on('taxonomy_translations.taxonomy_id', 'taxonomy_terms.taxonomy_id')
                                ->where('taxonomy_translations.locale', app()->getLocale());
                        })
                        ->leftJoin('article_translations', function ($join) {
                            $join
                                ->on('articles.id', 'article_translations.article_id')
                                ->where('article_translations.locale', app()->getLocale());
                        })
                        ->whereNull('articles.parent_id')
                        ->whereRaw("CONCAT_WS('-', taxonomy_translations.slug, article_translations.slug) = ?", [$last_slug])
                        ->where('articles.published', true)
                        ->select(
                            'article_types.type_name as article_type_name',
                            'article_types.slug as article_type_slug',
                            'article_translations.*',
                            'articles.id',
                            'articles.template',
                            'articles.image',
                            'articles.parent_id'
                        )
                        ->first();
                });

            if (!is_null($term)) {
                $term = (array)$term;

                return $this->page_container->setPage(new PageViewModel($term));
            }
            $post = Cache::remember('post.' . $last_slug . $root_page->id . app()->getLocale(), $this->queryCTL,
                function () use ($root_page, $last_slug) {
                    return DB::table('articles')
                        ->join('article_types', function ($join) use ($root_page) {
                            $join
                                ->on('article_types.id', 'articles.article_type_id')
                                ->where('article_types.type_name', 'post')
                                ->where('article_types.parent_id', $root_page->id);
                        })
                        ->leftJoin('article_translations', function ($join) {
                            $join
                                ->on('articles.id', 'article_translations.article_id')
                                ->where('article_translations.locale', app()->getLocale());
                        })
                        //->whereNull('articles.parent_id')
                        ->where('article_translations.slug', $last_slug)
                        ->where('articles.published', true)
                        ->select(
                            'article_types.type_name as article_type_name',
                            'article_types.slug as article_type_slug',
                            'article_translations.*',
                            'articles.id',
                            'articles.template',
                            'articles.image',
                            'articles.parent_id'
                        )
                        ->first();
                });

            if (!is_null($post)) {
                $post = (array)$post;

                return $this->page_container->setPage(new PageViewModel($post));
            }

            return $this->page_container->setPage(new NotFoundPageViewModel());
        }
    }

    public function getLocales()
    {
        $default_locale = config('translatable.locale');

        $locales = SettingsService::locales();

        $page = $this->page_container->getPage();

        $path = request()->path();

        foreach ($locales as $locale) {
            if (Str::startsWith($path, $locale->code)) {
                $path = Str::replaceFirst($locale->code, '', $path);
                break;
            }
        }

        return $locales->map(function ($locale) use ($page, $default_locale, $path) {
            $translatedUrl = $page->id ?
                $this->getPermalink($page->id, request()->except(['request_path', 'type']), $locale->code)
                : ($default_locale === $locale->code ? url($path) : url($locale->code . '/' . $path));

            return new Fluent([
                'code' => $locale->code,
                'name' => $locale->name,
                'home_url' => ($default_locale === $locale->code ? url('/') : url($locale->code . '/')),
                'translated_url' => $translatedUrl,
                'current_locale' => app()->getLocale() === $locale->code,
            ]);
        });
    }

    /**
     * @return string|UrlGenerator|Application
     */
    public function getHomePageUrl(): string|UrlGenerator|Application
    {
        return config('translatable.locale') === app()->getLocale()
            ? url('/')
            : url('/' . app()->getLocale() . '/');
    }

    /**
     * @param string $slug
     * @param string $template
     *
     * @return string
     */
    public function renderMenu(string $slug, string $template = 'none'): string
    {
        $items = [];

        try {
            $items = (new MenuService())->getMenuItemsBySlug($slug, app()->getLocale());
        } catch (\Exception $ex) {
        }

        return view()->first(['theme::menus.' . $template,
            'theme::menus.' . $slug,
            'theme::menus.default'], compact('items'))->render();
    }

    public function getSetting(string $slug)
    {
        return SettingsService::getSetting($slug, app()->getLocale());

        $cache_monitor = Cache::get('cache_monitor:settings', []);
        $cache_monitor[$slug] = $slug;
        Cache::put('cache_monitor:settings', $cache_monitor);

        return Cache::remember('settings:' . $slug, $this->cache_time_out, function () use ($slug) {
            return SettingsService::getSetting($slug, app()->getLocale());
        });
    }

    public function getArticles(
        $article_type_slug,
        ?array $filter = [],
        int $limit = 25,
        $paginate = false
    )
    {
        /**
         * @var $query Article
         */
        $query = Article::query()->published(1)
            ->byTypeSlug($article_type_slug)->withTranslations();

        $has_order = false;
        if ($filter && count($filter)) {
            foreach ($filter as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                switch ($key) {
                    case 'in_taxonomy':
                        $query->whereHas('terms', function ($sub_query) use ($value) {
                            $sub_query->join('taxonomies', function ($join) use ($value) {
                                $join->on('taxonomies.id', 'taxonomy_terms.taxonomy_id')
                                    ->where('taxonomies.alias', $value);
                            });
                        });
                        break;
                    case 'not_in_taxonomy':
                        $query->whereDoesntHave('terms', function ($sub_query) use ($value) {
                            $sub_query->join('taxonomies', function ($join) use ($value) {
                                $join->on('taxonomies.id', 'taxonomy_terms.taxonomy_id')
                                    ->where('taxonomies.alias', $value);
                            });
                        });
                        break;
                    case 'tags':
                    case 'terms':
                        if (is_array($value)) {
                            foreach ($value as $taxonomy => $term) {
                                $query->whereExists(function ($query) use ($taxonomy, $term) {
                                    $query->select(DB::raw(1))
                                        ->from('article_taxonomy_terms')
                                        ->join('taxonomy_term_translations', function ($join) use ($term) {
                                            $join
                                                ->on('taxonomy_term_translations.taxonomy_term_id', 'article_taxonomy_terms.taxonomy_term_id')
                                                ->where('taxonomy_term_translations.locale', app()->getLocale())
                                                ->where('taxonomy_term_translations.slug', $term);
                                        })
                                        ->join('taxonomy_terms', function ($join) {
                                            $join
                                                ->on('taxonomy_terms.id', 'taxonomy_term_translations.taxonomy_term_id');
                                        })
                                        ->join('taxonomies', function ($join) use ($taxonomy) {
                                            $join
                                                ->on('taxonomies.id', 'taxonomy_terms.taxonomy_id')
                                                ->where('taxonomies.alias', $taxonomy);
                                        })
                                        ->whereRaw('articles.id = article_taxonomy_terms.article_id');
                                });
                            }
                        }
                        break;
                    case 'except':
                        $query->whereNotIn('id', is_array($value) ? $value : [$value]);
                        break;
                    case 'order_by':
                        $query->when($value === "random", function ($q) {
                            $q->inRandomOrder();
                        }, function ($q) use ($value) {
                            $q->orderBy($value, 'DESC');
                        });
                        $has_order = true;
                        break;
                    case 'order_by_asc':
                        $query->orderBy($value, 'ASC');
                        $has_order = true;
                        break;
                }
            }
        }

        $items = $query->when(!$has_order, fn($q) => $q->defaultOrder()->orderBy('published_at', 'DESC'))
            ->when($paginate, function ($q) use ($limit) {
                return $q->paginate($limit);
            }, function ($q) use ($limit) {
                return $q->limit($limit)->get();
            });

        $items->transform(function ($item) {
            return new ElementViewModel($item);
        });

        return $items;
    }

    public function getPermalink(
        ?int    $article_id = null,
        array   $parameters = [],
        ?string $locale = null
    )
    {
        $article = Cache::remember('article.' . $article_id, $this->queryCTL, function () use ($article_id) {
            return Article::query()->find($article_id);
        });

        if (is_null($article)) {
            return null;
        }

        if (!$locale) {
            $locale = app()->getLocale();
        }

        $locale_prefix = "";

        if (config('translatable.locale') !== $locale) {
            $locale_prefix = $locale;
        }

        $url = "";

        $query = '';

        if (count($parameters)) {
            $query = '?' . http_build_query($parameters);
        }

        $translated_article = $article->translate($locale);

        switch ($article->article_type->type_name) {
            case "post":
                $article_type_parent_page = $article->article_type->parent_page->translate($locale);
                $parent = $article->parent;
                if (is_null($parent)) {
                    $url = FrontService . phpurl(collect([
                                                             $locale_prefix,
                                                             $article_type_parent_page->slug ?? "not_found",
                                                             $translated_article->slug,
                                                         ])->join('/')) . $query;
                } else {
                    $url_pieces = collect();
                    $url_pieces->push($locale_prefix);
                    $parent->ancestors->map(function ($item) use (&$url_pieces, $locale) {
                        $translated_item = $item->translate($locale);
                        if ($translated_item) {
                            $url_pieces->push($translated_item->slug);
                        }
                    });
                    $url_pieces->push($parent->slug);
                    $url_pieces->push($article->slug);
                    $url = FrontService . phpurl($url_pieces->join('/')) . $query;
                }
                break;
            case "page":
                $url_pieces = collect();
                $url_pieces->push($locale_prefix);
                $article->ancestors->map(function ($item) use (&$url_pieces, $locale) {
                    $translated_item = $item->translate($locale);
                    if ($translated_item) {
                        $url_pieces->push($translated_item->slug);
                    }
                });
                if ($translated_article) {
                    $url_pieces->push($translated_article->slug);
                }
                $url = FrontService . phpurl($url_pieces->join('/')) . $query;
                break;
        }

        return $url;
    }

    public function getTaxonomyTerms(string $taxonomy_alias)
    {
        $taxonomy = Taxonomy::query()->firstWhere('alias', $taxonomy_alias);

        if (is_null($taxonomy)) {
            return collect();
        }

        if ($taxonomy->term_type === 'tag') {
            return $taxonomy->terms()->orderBy('sorting')->get()->map(function ($tag) {
                return new Fluent([
                    'title' => $tag->title,
                    'slug' => $tag->slug,
                ]);
            });
        } else {
            return $taxonomy->terms->map(function ($tag) {
            });
        }
    }

    public function getSocialLoginButtons()
    {
        $res = [];
        foreach (SettingsService::getThemeConfig('social_login_providers') as $social_login_provider) {
            $res[] = new Fluent([
                'provider' => $social_login_provider['name'],
                'title' => $social_login_provider['title'][app()->getLocale()] ?? $social_login_provider['name'],
            ]);
        }

        return $res;
    }

    public function searchArticle(
        ?string $search = null,
        int     $limit = 25
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        if (!$search) {
            return new LengthAwarePaginator([], 0, $limit);
        }

        $query = Article::orderBy('published_at', 'DESC')
            ->whereIn('article_type_id', function ($sub_query) {
                $sub_query->select('id')
                    ->from(with(new ArticleType())->getTable())
                    ->whereNull('deleted_at')
                    ->whereIn('type_name', ['post', 'page']);
            })->withTranslations();

        $pagination = $query->whereExists(function ($sub_query) use ($search) {
            $sub_query->select(DB::raw(1))
                ->from(with(new ArticleTranslation())->getTable())
                ->where('locale', app()->getLocale())
                ->where(function ($sub_sub_query) use ($search) {
                    $sub_sub_query->where('title', 'LIKE', "%$search%")
                        ->orWhere('description', 'LIKE', "%$search%")
                        ->orWhere('content', 'LIKE', "%$search%");
                })->whereRaw('article_translations.article_id = articles.id');
        })->paginate($limit);

        $pagination->transform(function ($item) {
            return new ElementViewModel($item);
        });

        return $pagination;
    }

    public function getParsedContents(string $parser_name, string $url)
    {
        try {
            $parsers = SettingsService::getThemeConfig('parsers');
            $parser = $parsers->where('name', $parser_name)->first();
            if ($parser) {
                $content_parser = app($parser['provider'], ['base_url' => $parser['base_url']]);
                $srv = new SiteContentParserService($content_parser);

                return $srv->getParsedList($url);
            }

            return [];
        } catch (\Exception $exception) {
            return [];
        }
    }

    public function getChildrenTree(int $parent_id)
    {
        $tree = Article::query()->descendantsOf($parent_id)->toTree();

        return $tree->transform(function ($item) {
            return new ElementViewModel($item);
        });
    }

    public function getChildren(?int $id = null, int $depth = 1)
    {
        $article = Cache::remember('getChildren_' . $id . app()->getLocale(), $this->queryCTL, function () use ($id) {
            return Article::query()->withTranslations()->find($id);
        });

        $res = collect();
        if (is_null($article)) {
            return $res;
        }
        $descendants = $article->descendants()->withDepth()->get();
        foreach ($descendants as $item) {
            if ($item->depth <= $depth) {
                $res->push(new ElementViewModel($item));
            }
        }

        return $res;
    }

    public function getArticle(?int $id = null): ElementViewModel
    {
        $item = Cache::remember('article.' . $id . app()->getLocale(), $this->queryCTL, function () use ($id) {
            return Article::query()->withTranslations()->find($id);
        });

        return new ElementViewModel($item);
    }

    public function getCommands()
    {
        return $this->page_container->setPage(new CommandPageViewModel());
    }

    public function getSitemap()
    {
        return $this->page_container->setPage(new SitemapViewModel());
    }
}
