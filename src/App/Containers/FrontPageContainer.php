<?php

namespace Uzwebline\Linecms\App\Containers;

use Uzwebline\Linecms\App\Services\FrontService;
use Uzwebline\Linecms\App\ViewModels\Front\PageViewModel;

class FrontPageContainer
{
    /**
     * @var PageViewModel
     */
    protected PageViewModel $pageViewModel;
    /**
     * @var FrontService
     */
    protected FrontService $frontService;

    /**
     * @param FrontService $frontService
     *
     * @return FrontService
     */
    public function setFrontService(FrontService $frontService): FrontService
    {
        $this->frontService = $frontService;

        return $frontService;
    }

    /**
     * @return FrontService
     */
    public function getFrontService(): FrontService
    {
        return $this->frontService;
    }

    /**
     * @param PageViewModel $pageViewModel
     *
     * @return PageViewModel
     */
    public function setPage(PageViewModel $pageViewModel): PageViewModel
    {
        $this->pageViewModel = $pageViewModel;

        return $pageViewModel;
    }

    /**
     * @return PageViewModel
     */
    public function getPage(): PageViewModel
    {
        return $this->pageViewModel;
    }
}
