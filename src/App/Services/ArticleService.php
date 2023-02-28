<?php

namespace Uzwebline\Linecms\App\Services;

use Uzwebline\Linecms\App\Entities\Article;
use Uzwebline\Linecms\App\Entities\ArticleComment;
use Uzwebline\Linecms\App\Entities\ArticleType;
use Uzwebline\Linecms\App\Entities\ArticleTypeField;
use Uzwebline\Linecms\App\Entities\Taxonomy;
use Uzwebline\Linecms\App\Entities\Term;
use Uzwebline\Linecms\App\Exceptions\OperationException;
use Uzwebline\Linecms\App\Providers\Article\ArticleProviderContract;
use Uzwebline\Linecms\App\Requests\Article\CreateArticleRequest;
use Uzwebline\Linecms\App\Requests\Article\CreateArticleTypeRequest;
use Uzwebline\Linecms\App\Requests\Article\UpdateArticleRequest;
use Uzwebline\Linecms\App\Requests\Article\UpdateArticleTypeRequest;
use Uzwebline\Linecms\App\Requests\Front\AddCommentRequest;
use Uzwebline\Linecms\App\Requests\Taxonomy\CreateTagRequest;
use Uzwebline\Linecms\App\Requests\Taxonomy\CreateTaxonomyRequest;
use Uzwebline\Linecms\App\Requests\Taxonomy\CreateTermRequest;
use Uzwebline\Linecms\App\Requests\Taxonomy\UpdateTagRequest;
use Uzwebline\Linecms\App\Requests\Taxonomy\UpdateTaxonomyRequest;
use Uzwebline\Linecms\App\Requests\Taxonomy\UpdateTermRequest;
use Uzwebline\Linecms\App\ViewModels\Article\ArticleCommentViewModel;
use Uzwebline\Linecms\App\ViewModels\Article\ArticleTypeFieldViewModel;
use Uzwebline\Linecms\App\ViewModels\Article\ArticleTypeViewModel;
use Uzwebline\Linecms\App\ViewModels\Article\ArticleViewModel;
use Uzwebline\Linecms\App\ViewModels\Taxonomy\TaxonomyViewModel;
use Uzwebline\Linecms\App\ViewModels\Taxonomy\TermViewModel;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ArticleService
{
    /**
     * @return \Illuminate\Support\Collection
     * @throws \ReflectionException
     */
    public static function getArticleProvidersList()
    {
        $files = File::files(app_path('Providers/Article'));
        $res = collect();
        foreach ($files as $file) {
            $name = str_replace('.php', '', $file->getFilename());
            $class_name = "\Uzwebline\Linecms\App\Providers\Article\\" . $name;
            $reflection = new \ReflectionClass($class_name);
            if (!$reflection->isInterface() and $reflection->implementsInterface(ArticleProviderContract::class)) {
                $instance = app($class_name);
                if ($instance instanceof ArticleProviderContract and $instance->creatable()) {
                    $res->put($name, [
                        "class_name" => $class_name,
                        "must_have_parent" => $instance->mustHaveParentPage(),
                        "can_have_taxonomy" => $instance->canHaveTaxonomy(),
                    ]);
                }
            }
        }
        return $res;
    }

    public static function getArticleProvider(string $slug): ArticleProviderContract
    {
        $item = ArticleType::where('slug', $slug)->first();
        if (is_null($item))
            throw new OperationException("Article Type not found");

        return app($item->provider);
    }

    protected function getSystemArticleType(string $type_name)
    {
        $item = ArticleType::where('type_name', $type_name)->first();
        if (is_null($item))
            throw new OperationException("Article Type not found");
        return $item;
    }

    // region ArticleTypes

    /**
     * @param int $limit
     * @return LengthAwarePaginator
     */
    public function paginateArticleTypes($limit = 25): LengthAwarePaginator
    {
        $pagination = ArticleType::paginate($limit);
        $pagination->getCollection()->transform(function ($value) {
            return new ArticleTypeViewModel($value);
        });
        return $pagination;
    }

    public function getArticleTypes($limit = 25, ...$types)
    {
        $model = ArticleType::limit($limit);
        if (count($types) > 0)
            $model->whereIn('type_name', $types);
        $model->orderBy('name');
        $items = $model->get();
        $items->transform(function ($value) {
            return new ArticleTypeViewModel($value);
        });
        return $items;
    }

    /**
     * @param int $id
     * @return ArticleTypeViewModel
     * @throws OperationException
     */
    public function getArticleType(int $id)
    {
        $item = ArticleType::find($id);
        if (is_null($item))
            throw new OperationException("Article Type not found");
        return new ArticleTypeViewModel($item);
    }

    /**
     * @param CreateArticleTypeRequest $request
     * @return ArticleTypeViewModel
     */
    public function createArticleType(CreateArticleTypeRequest $request)
    {
        $data = $request->validated();
        $create_article_type = collect($data)->only(['name', 'slug', 'provider'])->toArray();
        $create_article_type['type_name'] = app($data['provider'])->getTypeName();
        $create_article_type['parent_id'] = $data['parent'];
        $item = ArticleType::query()->withTrashed()->where('slug', $create_article_type['slug'])->first();
        if (is_null($item)) {
            $item = ArticleType::query()->create($create_article_type);
        } else {
            $item->restore();
            $item->update($create_article_type);
        }
        $item->taxonomies()->sync($data['taxonomies'] ?? []);
        return new ArticleTypeViewModel($item);
    }

    /**
     * @param int $id
     * @param UpdateArticleTypeRequest $request
     * @return mixed
     * @throws OperationException
     */
    public function updateArticleType(int $id, UpdateArticleTypeRequest $request)
    {
        $data = $request->validated();

        $update_data = collect($data)->only(['name', 'slug', 'provider'])->toArray();

        $item = ArticleType::find($id);

        if (is_null($item))
            throw new OperationException("Article Type not found");

        $update_data['type_name'] = app($data['provider'])->getTypeName();

        $update_data['parent_id'] = $data['parent'];

        $item->taxonomies()->sync($data['taxonomies'] ?? []);

        return $item->update($update_data);
    }

    /**
     * @param int $id
     * @return mixed
     * @throws OperationException
     */
    public function deleteArticleType(int $id)
    {
        $item = ArticleType::find($id);
        if (is_null($item))
            throw new OperationException("Article Type not found");

        return $item->delete();
    }

    // endregion

    // region Articles

    /**
     * @param string $slug
     * @param int $limit
     * @return LengthAwarePaginator
     * @throws OperationException
     */
    public function paginateArticles(string $slug, $limit = 25): LengthAwarePaginator
    {
        $article_type = ArticleType::where('slug', $slug)->first();
        if (is_null($article_type))
            throw new OperationException("Article Type not found");
        $pagination = $article_type->articles()->defaultOrder()->paginate($limit);
        $pagination->getCollection()->transform(function ($value) {
            return new ArticleViewModel($value);
        });
        return $pagination;
    }

    public function createArticle(string $slug, CreateArticleRequest $request)
    {
        $article_type = ArticleType::where('slug', $slug)->first();

        if (is_null($article_type))
            throw new OperationException("Article Type not found");

        $data = $request->validated();

        $create_data = collect($data)->only(['published_at', 'published', 'image', 'template'])->toArray();

        $localized_attrs = ['title', 'description', 'content', 'slug', 'link', 'meta_title', 'meta_description', 'meta_keywords'];

        foreach ($localized_attrs as $attr) {
            if ($data[$attr] ?? false) {
                foreach ($data[$attr] as $locale => $value) {
                    if ($attr === "slug") {
                        if (empty($value)) {
                            $value = $data['title'][$locale];
                        }
                        $value = Str::slug($value, '-');
                    }
                    $create_data[$locale][$attr] = $value;
                }
            }
        }
        if (!isset($create_data['published'])) {
            $create_data['published'] = false;
        }
        $create_data['article_type_id'] = $article_type->id;
        $create_data['published_at'] = Carbon::parse($create_data['published_at']);
        $create_data['af'] = $data['af'] ?? [];
        $create_data['parent_id'] = $data['parent'] ?? null;
        $item = Article::create($create_data);
        $item->terms()->sync($data['terms'] ?? []);
        return new ArticleViewModel($item);
    }

    public function getArticle(int $id)
    {
        $item = Article::find($id);

        if (is_null($item))
            throw new OperationException("Article not found");

        return new ArticleViewModel($item);
    }

    public function updateArticle(int $id, UpdateArticleRequest $request)
    {
        $item = Article::find($id);

        if (is_null($item))
            throw new OperationException("Article not found");

        $data = $request->validated();

        $update_data = collect($data)->only(['published_at', 'published', 'image', 'template'])->toArray();

        $localized_attrs = ['title', 'description', 'content', 'slug', 'link', 'meta_title', 'meta_description', 'meta_keywords'];

        foreach ($localized_attrs as $attr) {
            if ($data[$attr] ?? false) {
                foreach ($data[$attr] as $locale => $value) {
                    if ($attr === "slug") {
                        if (empty($value)) {
                            $value = $data['title'][$locale];
                        }
                        $value = Str::slug($value, '-');
                    }
                    $update_data[$locale][$attr] = $value;
                }
            }
        }

        if (!isset($update_data['published'])) {
            $update_data['published'] = false;
        }

        $update_data['published_at'] = Carbon::parse($update_data['published_at']);

        $item->terms()->sync($data['terms'] ?? []);

        $update_data['af'] = $data['af'] ?? [];

        $update_data['parent_id'] = $data['parent'] ?? null;

        Article::fixTree();

        return $item->update($update_data);
    }

    public function deleteArticle(int $id)
    {
        $item = Article::find($id);

        if (is_null($item))
            throw new OperationException("Article not found");

        return $item->delete();
    }

    public function addComment(int $user_id, AddCommentRequest $request)
    {
        $data = $request->validated();

        $create_data = [
            'user_id' => $user_id,
            'article_id' => $data['article'],
            'comment' => $data['comment'],
            'status' => 1
        ];

        $item = ArticleComment::create($create_data);

        return new ArticleCommentViewModel($item);
    }

    /**
     * @param string $slug
     * @param int $id
     * @param string $sort
     * @return mixed
     * @throws OperationException
     */
    public function sortArticle(string $slug, int $id, string $sort)
    {
        $article_type = ArticleType::query()->where('slug', $slug)->first();
        if (is_null($article_type))
            throw new OperationException("Article Type not found");

        $scope = $article_type->articles->pluck('id')->toArray();

        $item = $article_type->articles()->find($id);

        if (is_null($item))
            throw new OperationException("Article not found");

        if ($sort === 'down') {
            $siblings = $item->getNextSiblings();
            $index = 1;
            foreach ($siblings as $sibling) {
                if (in_array($sibling->id, $scope)) {
                    break;
                } else {
                    $index++;
                }
            }
            return $item->down($index);
        } else {
            return $item->up();
        }
    }

    // endregion

    // region Fields

    public function paginateArticleFields(int $article_type_id, $limit = 25): LengthAwarePaginator
    {
        $pagination = ArticleTypeField::where('article_type_id', $article_type_id)->paginate($limit);
        $pagination->getCollection()->transform(function ($value) {
            return new ArticleTypeFieldViewModel($value);
        });
        return $pagination;
    }

    // endregion

    // region Taxonomies

    public function paginateTaxonomies(int $limit = 25)
    {
        $pagination = Taxonomy::paginate($limit);
        $pagination->getCollection()->transform(function ($value) {
            return new TaxonomyViewModel($value);
        });
        return $pagination;
    }

    public function createTaxonomy(CreateTaxonomyRequest $request)
    {
        $data = $request->validated();
        $create_data = collect($data)->only(['name', 'term_type'])->toArray();
        $localized_attrs = ['title', 'slug'];
        foreach ($localized_attrs as $attr) {
            foreach ($data[$attr] as $locale => $value) {
                if ($attr === "slug") {
                    $value = Str::slug($value, '-');
                }
                $create_data[$locale][$attr] = $value;
            }
        }
        $create_data['alias'] = Str::slug($create_data['name'], '_');
        $item = Taxonomy::create($create_data);
        return new TaxonomyViewModel($item);
    }

    public function getTaxonomy(int $id)
    {
        $item = Taxonomy::find($id);
        if (is_null($item))
            throw new OperationException("Taxonomy not found");
        return new TaxonomyViewModel($item);
    }

    public function getTaxonomyByAlias(string $alias)
    {
        $item = Taxonomy::where('alias', $alias)->first();

        if (is_null($item))
            throw new OperationException("Taxonomy not found");

        return new TaxonomyViewModel($item);
    }

    public function updateTaxonomy(int $id, UpdateTaxonomyRequest $request)
    {
        $item = Taxonomy::find($id);
        if (is_null($item))
            throw new OperationException("Taxonomy not found");

        $data = $request->validated();
        $update_data = collect($data)->only(['name', 'term_type'])->toArray();
        $localized_attrs = ['title', 'slug'];
        foreach ($localized_attrs as $attr) {
            foreach ($data[$attr] as $locale => $value) {
                if ($attr === "slug") {
                    $value = Str::slug($value, '-');
                }
                $update_data[$locale][$attr] = $value;
            }
        }
        $update_data['alias'] = Str::slug($update_data['name'], '_');
        //dd($update_data);
        $item->update($update_data);
        return new TaxonomyViewModel($item);
    }

    public function deleteTaxonomy(int $id)
    {
        $item = Taxonomy::find($id);

        if (is_null($item))
            throw new OperationException("Taxonomy not found");

        return $item->delete();
    }

    // endregion

    // region Term

    public function paginateTerms(string $alias, int $limit = 25)
    {
        $taxonomy = Taxonomy::where('alias', $alias)->first();

        if (is_null($taxonomy))
            throw new OperationException("Taxonomy not found");

        $pagination = $taxonomy->terms()->orderBy('sorting')->paginate($limit);

        $pagination->getCollection()->transform(function ($value) {
            return new TermViewModel($value);
        });

        return $pagination;
    }

    public function createTerm(string $alias, CreateTermRequest $request)
    {
        $taxonomy = Taxonomy::where('alias', $alias)->first();

        if (is_null($taxonomy))
            throw new OperationException("Taxonomy not found");

        $data = $request->validated();
        $create_data = collect($data)->only(['sorting'])->toArray();
        $localized_attrs = ['title', 'slug'];
        foreach ($localized_attrs as $attr) {
            foreach ($data[$attr] as $locale => $value) {
                if ($attr === "slug") {
                    if (empty($value)) {
                        $value = $data['title'][$locale];
                    }
                    $value = Str::slug($value, '-');
                }
                $create_data[$locale][$attr] = $value;
            }
        }

        if (!isset($create_data['sorting'])) {
            $create_data['sorting'] = 1;
        }

        $create_data['taxonomy_id'] = $taxonomy->id;

        $term = Term::create($create_data);

        $create_data = collect($data)->only(['published_at', 'published', 'image'])->toArray();

        $localized_attrs = ['title', 'description', 'content', 'slug', 'meta_title', 'meta_description', 'meta_keywords'];

        foreach ($localized_attrs as $attr) {
            foreach ($data[$attr] as $locale => $value) {
                if ($attr === "slug") {
                    if (empty($value)) {
                        $value = $data['title'][$locale];
                    }
                    $value = Str::slug($value, '-');
                }
                $create_data[$locale][$attr] = $value;
            }
        }
        if (!isset($create_data['published'])) {
            $create_data['published'] = false;
        }
        $article_type = $this->getSystemArticleType('term');
        $create_data['article_type_id'] = $article_type->id;
        $create_data['published_at'] = Carbon::parse($create_data['published_at']);
        $item = Article::create($create_data);

        $term->update(['article_id' => $item->id]);

        return new TermViewModel($term);
    }

    public function getTerm(int $id)
    {
        $item = Term::find($id);

        if (is_null($item))
            throw new OperationException("Term not found");

        return new TermViewModel($item);
    }

    public function updateTerm(int $id, UpdateTermRequest $request)
    {
        $term = Term::find($id);

        if (is_null($term))
            throw new OperationException("Term not found");

        $data = $request->validated();
        $update_data = collect($data)->only(['sorting'])->toArray();
        $localized_attrs = ['title', 'slug'];
        foreach ($localized_attrs as $attr) {
            foreach ($data[$attr] as $locale => $value) {
                if ($attr === "slug") {
                    if (empty($value)) {
                        $value = $data['title'][$locale];
                    }
                    $value = Str::slug($value, '-');
                }
                $update_data[$locale][$attr] = $value;
            }
        }

        $term->update($update_data);

        $update_data = collect($data)->only(['published_at', 'published', 'image'])->toArray();

        $localized_attrs = ['title', 'description', 'content', 'slug', 'meta_title', 'meta_description', 'meta_keywords'];

        foreach ($localized_attrs as $attr) {
            foreach ($data[$attr] as $locale => $value) {
                if ($attr === "slug") {
                    if (empty($value)) {
                        $value = $data['title'][$locale];
                    }
                    $value = Str::slug($value, '-');
                }
                $update_data[$locale][$attr] = $value;
            }
        }

        if (!isset($update_data['published'])) {
            $update_data['published'] = false;
        }

        $update_data['published_at'] = Carbon::parse($update_data['published_at']);

        $article = $term->article()->first();

        if (is_null($article)) {
            $article_type = $this->getSystemArticleType('term');
            $update_data['article_type_id'] = $article_type->id;
            $update_data['published_at'] = Carbon::parse($update_data['published_at']);
            $article = Article::create($update_data);
            $term->update(['article_id' => $article->id]);
        }

        return $article->update($update_data);
    }

    public function createTag(string $alias, CreateTagRequest $request)
    {
        $taxonomy = Taxonomy::where('alias', $alias)->first();

        if (is_null($taxonomy))
            throw new OperationException("Taxonomy not found");

        $data = $request->validated();
        $create_data = collect($data)->only(['sorting'])->toArray();
        $localized_attrs = ['title', 'slug'];
        foreach ($localized_attrs as $attr) {
            foreach ($data[$attr] as $locale => $value) {
                if ($attr === "slug") {
                    if (empty($value)) {
                        $value = $data['title'][$locale];
                    }
                    $value = Str::slug($value, '-');
                }
                $create_data[$locale][$attr] = $value;
            }
        }

        $create_data['taxonomy_id'] = $taxonomy->id;
        $item = Term::create($create_data);
        return new TermViewModel($item);
    }

    public function getTag(int $id)
    {
        $item = Term::find($id);

        if (is_null($item))
            throw new OperationException("Term not found");

        return new TermViewModel($item);
    }

    public function updateTag(int $id, UpdateTagRequest $request)
    {
        $item = Term::find($id);

        if (is_null($item))
            throw new OperationException("Term not found");

        $data = $request->validated();
        $update_data = collect($data)->only(['sorting'])->toArray();
        $localized_attrs = ['title', 'slug'];
        foreach ($localized_attrs as $attr) {
            foreach ($data[$attr] as $locale => $value) {
                if ($attr === "slug") {
                    if (empty($value)) {
                        $value = $data['title'][$locale];
                    }
                    $value = Str::slug($value, '-');
                }
                $update_data[$locale][$attr] = $value;
            }
        }

        $update_data['article_id'] = null;

        $article = $item->article;

        if ($article) {
            $article->delete();
        }

        return $item->update($update_data);
    }

    public function deleteTerm(int $id)
    {
        $item = Term::find($id);

        if (is_null($item))
            throw new OperationException("Term not found");

        return $item->delete();
    }

    // endregion
}
