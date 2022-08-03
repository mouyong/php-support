<?php

namespace ZhenMu\Support\Utils;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class LaravelCache
{
    /**
     * 单位 秒
     */
    const DEFAULT_CACHE_TIME = [1800, 3600, 7200, 14400, 28800, 57600, 86400];

    /**
     * 执行指定函数并缓存指定时长
     *
     * @param  string               $cacheKey
     * @param  callable|Carbon|null $cacheTime
     * @param  callable|null        $callable
     * @param  boolean              $forever
     * @return mixed
     */
    public static function remember(string $cacheKey, callable|Carbon|null $cacheTime = null, callable $callable = null, $forever = false)
    {
        // 使用默认缓存时间
        if (is_callable($cacheTime)) {
            $callable = $cacheTime;

            // 防止缓存雪崩，对不同数据随机缓存时间。从半小时到 1天
            $cacheSeconds = rand(0, 100) % count(LaravelCache::DEFAULT_CACHE_TIME);
            $cacheTime = now()->addSeconds($cacheSeconds);
        }

        if (!is_callable($callable)) {
            return null;
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
     * @return mixed
     */
    public static function rememberForever(string $cacheKey, callable|Carbon|null $cacheTime = null, callable $callable = null)
    {
        return LaravelCache::remember($cacheKey, $cacheTime, $callable, true);
    }

    public static function get(string $cacheKey): mixed
    {
        return Cache::get($cacheKey);
    }

    public static function forget(string $cacheKey): bool
    {
        return Cache::forget($cacheKey);
    }

    public static function pull(string $cacheKey): mixed
    {
        return Cache::pull($cacheKey);
    }
}
