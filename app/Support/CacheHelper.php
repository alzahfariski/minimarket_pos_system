<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    public static function invalidateProductsCache(): void
    {
        Cache::forget('products:list');
    }
}
