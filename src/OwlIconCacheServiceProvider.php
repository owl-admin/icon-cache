<?php

namespace Slowlyo\OwlIconCache;

use Slowlyo\OwlAdmin\Admin;
use Slowlyo\OwlAdmin\Extend\ServiceProvider;

class OwlIconCacheServiceProvider extends ServiceProvider
{
    public function customInitAfter()
    {
        $icons = IconCache::make()->toJson();

        Admin::scripts(<<<JS
(function (){
    for (let i = 0; i < localStorage.length; i++){
        let key = localStorage.key(i)

        if(key.startsWith('iconify') && !key.startsWith('iconify-')){
            localStorage.removeItem(key)
        }
    }

    let icons = {$icons}

    if(icons?.length){
        for (let i = 0; i < icons.length; i++){
            localStorage.setItem('iconify' + i, JSON.stringify({
                cached: 476119,
                data: icons[i],
                provider: ''
            }))
        }

        localStorage.setItem('iconify-count', icons.length)

        if(!localStorage.getItem('iconify-from-cache')){
            localStorage.setItem('iconify-from-cache', 1)

            window.location.reload()
        }
    }
})()

JS
        );
    }

    public function settingForm()
    {
        return $this->baseSettingForm()->body([
            amis()->Alert()->level('info')->body('保存后需要刷新 1~2 次页面才生效'),
            amis()->ArrayControl('extra_icons', '其他图标')->items(
                amis()->TextControl()->clearable()->required()->placeholder('图标名称')
            ),
        ]);
    }
}
