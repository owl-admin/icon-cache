<?php

namespace Slowlyo\OwlIconCache;

use Slowlyo\OwlAdmin\Admin;
use Iconify\IconsJSON\Finder;

class IconCache
{
    public array $icons = [
        'ri:wifi-off-line',
        'material-symbols:light-mode-outline',
        'material-symbols:dark-mode-outline',
        'ant-design:fullscreen-exit-outlined',
        'ant-design:fullscreen-outlined',
        'ant-design:reload-outlined',
        'ant-design:setting-outlined',
        'line-md:menu-fold-right',
        'line-md:menu-fold-left',
        'ant-design:key-outlined',
        'ant-design:user-outlined',
    ];

    public static function make()
    {
        return new self();
    }

    public function __construct()
    {
        $devToolMenus = collect(Admin::menu()->devToolMenus())->map(function ($item) {
            return collect($item['meta']['icon'])->merge(collect($item['children'])->pluck('meta')->pluck('icon'));
        })->flatten();

        $extraIcons = OwlIconCacheServiceProvider::setting('extra_icons', []);

        $this->icons = collect($this->icons)
            ->merge(Admin::adminMenuModel()::pluck('icon')->toArray())
            ->merge($devToolMenus)
            ->merge($extraIcons)
            ->toArray();
    }

    public function all()
    {
        return $this->toArray();
    }

    public function getJsonFile($name)
    {
        $jsonFile = Finder::locate($name);

        if (!file_exists($jsonFile)) {
            return [];
        }

        return json_decode(file_get_contents($jsonFile), true);
    }

    public function getIcon($name)
    {
        $arr = explode(':', $name);

        $json = $this->getJsonFile($arr[0]);

        return data_get($json, 'icons.' . $arr[1] . '.body');
    }

    public function getIconWH($prefix)
    {
        $json = $this->getJsonFile($prefix);

        return [
            'width'  => data_get($json, 'width', 24),
            'height' => data_get($json, 'height', 24),
        ];
    }

    public function toArray()
    {
        return collect($this->icons)->unique()->map(fn($key) => [
            'prefix' => explode(':', $key)[0],
            'name'   => explode(':', $key)[1],
            'svg'    => $this->getIcon($key),
        ])->groupBy('prefix')
            ->map(fn($items) => collect($items)->pluck('svg', 'name')->map(fn($i) => ['body' => $i])->toArray())
            ->map(fn($icons, $prefix) => array_merge([
                'prefix'       => $prefix,
                'icons'        => $icons,
                'aliases'      => [],
                'lastModified' => time(),
            ], $this->getIconWH($prefix)))
            ->values()
            ->toArray();
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }
}
