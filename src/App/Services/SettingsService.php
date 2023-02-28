<?php

namespace Uzwebline\Linecms\App\Services;

use Uzwebline\Linecms\App\Entities\Setting;
use Illuminate\Support\{Facades\Cache, Fluent};
use Ramsey\Uuid\Uuid;

class SettingsService
{
    public static function getThemeConfig(string $section)
    {
        $theme       = config('app.theme');
        $config_path = base_path("themes/$theme/config.json");
        $config      = json_decode(file_get_contents($config_path), true);

        return collect($config[$section] ?? []);
    }

    public static function locales()
    {
        $res = collect();

        foreach (app('translatable.locales')->all() as $locale) {
            $res->push(new Fluent([
                                      "code" => $locale,
                                      "name" => mb_convert_case($locale, MB_CASE_TITLE, "UTF-8"),
                                  ]));
        }

        return $res;
    }

    public static function getSetting(string $slug, string $locale)
    {
        $setting = Setting::query()->firstWhere('slug', $slug);
        if (is_null($setting)) {
            return null;
        }

        return $setting->value[$locale] ?? null;
    }

    public function listAll()
    {
        return Setting::query()->orderBy('sort')->get()->map(function ($item) {
            $tmp = [
                'id'   => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
            ];
            foreach ($item->value as $key => $value) {
                $tmp[$key] = $value;
            }

            return new Fluent($tmp);
        });
    }

    public function store(array $data)
    {
        Setting::query()->truncate();
        $insert_data = [];
        $index       = 0;
        foreach ($data as $item) {
            $value = $item;
            unset($value['name']);
            unset($value['slug']);
            $insert_data[] = [
                'id'    => Uuid::uuid4()->getHex()->toString(),
                'name'  => $item['name'],
                'slug'  => $item['slug'],
                'value' => json_encode($value),
                'sort'  => $index++,
            ];
        }
        Setting::insert($insert_data);

        $cache_monitor = Cache::get('cache_monitor:settings', []);
        foreach ($cache_monitor as $monitoring_item) {
            Cache::forget('settings:' . $monitoring_item);
        }
    }
}
