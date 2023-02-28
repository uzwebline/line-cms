<?php

namespace Uzwebline\Linecms\App\Services;

use Uzwebline\Linecms\App\Entities\{Requests, Resumes};
use Uzwebline\Linecms\App\Exceptions\OperationException;
use Uzwebline\Linecms\App\ViewModels\Requests\{RequestsViewModel, ResumesViewModel};
use Illuminate\Pagination\LengthAwarePaginator;

class RequestService
{

    public function paginate($limit = 25): LengthAwarePaginator
    {
        $pagination = Requests::query()->orderBy('id', 'desc')->paginate($limit);
        $pagination->getCollection()->transform(function ($value) {
            return new RequestsViewModel($value);
        });

        return $pagination;
    }

    public function delete($id)
    {
        $item = Requests::find($id);
        if (is_null($item)) {
            throw new OperationException("Not found");
        }

        return $item->delete();
    }

    public function get($id)
    {
        $item = Requests::find($id);
        if (is_null($item)) {
            throw new OperationException("Not found");
        }

        return $item;
    }

    public function paginateResumes($limit = 25): LengthAwarePaginator
    {
        $pagination = Resumes::query()->paginate($limit);
        $pagination->getCollection()->transform(function ($value) {
            return new ResumesViewModel($value);
        });

        return $pagination;
    }

    public function deleteResume($id)
    {
        $item = Resumes::find($id);
        if (is_null($item)) {
            throw new OperationException("Not found");
        }

        return $item->delete();
    }

    public function getResume($id)
    {
        $item = Resumes::find($id);
        if (is_null($item)) {
            throw new OperationException("Not found");
        }

        return $item;
    }
}
