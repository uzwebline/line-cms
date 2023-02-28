<?php

namespace Uzwebline\Linecms\App\Services;

use Uzwebline\Linecms\App\Entities\Management;
use Uzwebline\Linecms\App\Exceptions\OperationException;
use Uzwebline\Linecms\App\Requests\Management\CreateManagementRequest;
use Uzwebline\Linecms\App\ViewModels\Management\ManagementViewModel;
use Illuminate\Pagination\LengthAwarePaginator;

class ManagementService
{

    public function paginate($limit = 25): LengthAwarePaginator
    {
        $pagination = Management::query()->paginate($limit);
        $pagination->getCollection()->transform(function ($value) {
            return new ManagementViewModel($value);
        });
        return $pagination;
    }

    public function delete($id)
    {
        $item = Management::find($id);
        if (is_null($item)) {
            throw new OperationException("Not found");
        }
        return $item->delete();
    }

    public function store(CreateManagementRequest $request)
    {
        $data = $request->validated();
        return Management::query()->create($data);
    }
}
