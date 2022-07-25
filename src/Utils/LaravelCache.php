<?php

namespace ZhenMu\Support\Utils;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class LaravelCache
{
    /**
     * 单位 秒
     */
    const DEFAULT_CACHE_TIME = 3600;

    /**
     * 执行指定函数并缓存指定时长
     *
     * @param  string               $cacheKey
     * @param  callable|Carbon|null $cacheTime
     * @param  callable|null        $callable
     * @param  boolean              $forever
     * @return void
     */
    public static function remember(string $cacheKey, callable|Carbon $cacheTime = null, callable $callable = null, $forever = false)
    {
        if (!is_callable($callable)) {
            return null;
        }

        // 使用默认缓存时间
        if (is_callable($cacheTime)) {
            $callable = $cacheTime;
            $cacheTime = now()->addSeconds(LaravelCache::DEFAULT_CACHE_TIME);
        }

        if ($forever) {
            $data = Cache::rememberForever($cacheKey, $callable);
        } else {
            $data = Cache::remember($cacheKey, $cacheTime, $callable);
        }

        if (!$data) {
            Cache::pull($cacheKey);
        }

        return $data;
    }

    /**
     * 执行指定函数并永久缓存
     *
     * @param  string               $cacheKey
     * @param  callable|Carbon|null $cacheTime
     * @param  callable|null        $callable
     * @return void
     */
    public static function rememberForever(string $cacheKey, callable|Carbon $cacheTime = null, callable $callable = null)
    {
        return LaravelCache::remember($cacheKey, $cacheTime, $callable, true);
    }
}
