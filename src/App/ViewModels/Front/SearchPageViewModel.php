<?php

namespace Uzwebline\Linecms\App\ViewModels\Front;

class SearchPageViewModel extends PageViewModel
{
    protected $results;

    public function __construct(array $parameters = [])
    {
        parent::__construct($parameters);
        $view = "search.results";
        $this->view = "theme::$view";
    }

    public function setResults($results)
    {
        $this->results = $results;
    }

    public function getResults()
    {
        return $this->results;
    }
}
