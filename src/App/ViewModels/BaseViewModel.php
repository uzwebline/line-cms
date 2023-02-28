<?php

namespace Uzwebline\Linecms\App\ViewModels;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Spatie\ViewModels\ViewModel;

abstract class BaseViewModel extends ViewModel
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse([
                "success" => true,
                "error" => null,
                "data" => $this->items()
            ]);
        }

        if ($this->view) {
            return response()->view($this->view, $this);
        }

        return new JsonResponse([
            "success" => true,
            "error" => null,
            "data" => $this->items()
        ]);
    }
}
