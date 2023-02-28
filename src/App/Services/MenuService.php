<?php

namespace Uzwebline\Linecms\App\Services;


use Uzwebline\Linecms\App\Entities\Menu;
use Uzwebline\Linecms\App\Entities\MenuItem;
use Uzwebline\Linecms\App\Exceptions\OperationException;
use Uzwebline\Linecms\App\Requests\Menu\CreateOrUpdateMenuRequest;
use Uzwebline\Linecms\App\ViewModels\Menu\MenuViewModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use Ramsey\Uuid\Uuid;

class MenuService
{
    // region Menu
    public function paginateMenus($limit = 25): LengthAwarePaginator
    {
        $pagination = Menu::paginate($limit);
        $pagination->getCollection()->transform(function ($value) {
            return new MenuViewModel($value);
        });
        return $pagination;
    }

    /**
     * @param int $id
     * @return MenuViewModel
     * @throws OperationException
     */

    public function getMenu(int $id): MenuViewModel
    {
        $item = Menu::find($id);

        if (is_null($item))
            throw new OperationException("Menu not found");

        return new MenuViewModel($item);
    }

    /**
     * @param CreateOrUpdateMenuRequest $request
     * @return MenuViewModel
     */
    public function createMenu(CreateOrUpdateMenuRequest $request): MenuViewModel
    {
        $data = $request->validated();

        if (!isset($data['status'])) {
            $data['status'] = false;
        }

        $item = Menu::create($data);

        return new MenuViewModel($item);
    }

    /**
     * @param int $id
     * @param CreateOrUpdateMenuRequest $request
     * @return mixed
     * @throws OperationException
     */
    public function updateMenu(int $id, CreateOrUpdateMenuRequest $request)
    {
        $data = $request->validated();

        $item = Menu::find($id);

        if (is_null($item))
            throw new OperationException("Menu not found");

        if (!isset($data['status'])) {
            $data['status'] = false;
        }

        return $item->update($data);
    }

    /**
     * @param int $id
     * @return mixed
     * @throws OperationException
     */
    public function deleteMenu(int $id)
    {
        $item = Menu::find($id);

        if (is_null($item))
            throw new OperationException("Menu not found");

        $item->items()->delete();

        return $item->delete();
    }

    // endregion

    public function getMenuItemsBySlug(string $slug, ?string $locale = null): array
    {
        $model = Menu::query();

        if ($locale) {
            $model->where('locale', $locale);
        }

        $menu = $model->where('slug', $slug)->first();

        if (is_null($menu))
            throw new OperationException("Menu not found");

        return $this->getMenuItemChildrenHierarchical($menu->items, null);
    }

    public function getMenuItems(int $id): array
    {
        $menu = Menu::find($id);

        if (is_null($menu))
            throw new OperationException("Menu not found");

        return $this->getMenuItemChildrenHierarchical($menu->items, null);
    }

    protected function getMenuItemChildrenHierarchical(Collection $items, ?string $parent_id = null): array
    {
        $index = 0;
        return $items->where('parent_id', $parent_id)->sortBy('sort')->mapWithKeys(function ($item) use (&$index, $items) {
            return [$index++ => new Fluent([
                'id' => $item->id,
                'parent_id' => $item->parent_id,
                'title' => $item->title,
                'link' => $item->link,
                'class' => $item->class,
                'icon'=>$item->icon,
                'children' => $this->getMenuItemChildrenHierarchical($items, $item->id)
            ])];
        })->toArray();
    }

    public function storeMenuItems(int $menu_id, array $items)
    {

        $menu = Menu::find($menu_id);

        if (is_null($menu))
            throw new OperationException("Menu not found");

        $menu->items()->delete();

        $insertData = [];

        $this->populateMenuItemsForInsert($menu_id, $insertData, $items, null);

        MenuItem::insert($insertData);
    }

    protected function populateMenuItemsForInsert(int $menu_id, array &$insertData, array $items, $parent_id = null)
    {
        $sort = 0;
        foreach ($items as $item) {
            $id = Uuid::uuid4()->getHex()->toString();
            $insertData[] = [
                'id' => $id,
                'menu_id' => $menu_id,
                'parent_id' => $parent_id,
                'title' => $item['title'] ?? "",
                'link' => $item['link'] ?? "",
                'sort' => $sort++,
                'class' => $item['class'] ?? "",
                'icon' => $item['icon'] ?? ""
            ];
            $this->populateMenuItemsForInsert($menu_id, $insertData, $item['children'], $id);
        }
    }
}
