<?php

namespace Uzwebline\Linecms\App\ViewModels\Front;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class CabinetPageViewModel extends PageViewModel
{
    public $f_name;
    public $l_name;
    public $phone;
    public $username;
    public $sex;
    public $region;
    public $birth_date;
    public $h_sex;
    public $h_region;
    public $h_birth_date;

    public function __construct(array $parameters = [])
    {
        parent::__construct($parameters);
        $view       = "cabinet.index";
        $this->view = "theme::$view";

        $this->f_name   = auth()->user()->f_name;
        $this->l_name   = auth()->user()->l_name;
        $this->phone    = (new Phone(auth()->user()->phone))->getFormatted();
        $this->username = auth()->user()->username;

        $data = auth()->user()->data;

        if (!is_null($data)) {
            $this->sex        = $data->sex;
            $this->region     = $data->region_id;
            $this->birth_date = $data->birth_date;

            $this->h_sex        = ($this->sex === 1 ? trans('theme::all.sex_male') : trans('theme::all.sex_female'));
            $this->h_region     = $data->region->{"title_" . app()->getLocale()} ?? "";
            $this->h_birth_date = Carbon::parse($this->birth_date)->format('d.m.Y');
        }
    }

    public function paginateCodes()
    {
        $page  = request('page', 1);
        $limit = 10;

        return $this->getCodes($this->username, $page, $limit, request()->url(), request()->query());
    }

    protected function getCodes(
        string $phone,
        int $page = 1,
        int $limit = 10,
        string $path,
        array $query = []
    ): LengthAwarePaginator {
        $client   = new Client();
        $url      = "http://service.flashup-promo.uz/api/data/history/{$phone}?page={$page}";
        $username = 'flashup_promo_api_user';
        $password = 'q]5=\P8M~Y9k"t<j]nj)!g36YT</sC?]';

        try {
            $request_result = $client->get($url, [
                'auth' => [$username, $password],
            ]);
            $status_code    = $request_result->getStatusCode();
            if ($status_code === 200) {
                $response = json_decode($request_result->getBody()->getContents(), true);

                return new LengthAwarePaginator(collect($response['data']), $response['total'], $response['per_page'], $response['current_page'], [
                    'path'  => $path,
                    'query' => $query,
                ]);
            }
        } catch (\Exception $ex) {
            Log::error($ex);
        }

        return new LengthAwarePaginator(collect([]), 0, $limit, $page);
    }
}
