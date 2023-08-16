<?php

namespace ZhenMu\Support\Utilities;

class ModelNoUtility
{
    public static function setCurrentIndex($model, ?string $field = null, &$params = [], $prefix = null, $orderByField = 'created_at', $indexLength = 4, $dateFormat = 'ymd')
    {
        if (!is_string($model)) {
            $model = get_class($model);
        }

        if (!defined("{$model}::CUSTOMER_NUMBER_PREFIX")) {
            throw new \RuntimeException("{$model}::CUSTOMER_NUMBER_PREFIX doesn't exist.");
        }

        $prefix = $model::CUSTOMER_NUMBER_PREFIX;
        $index = ModelNoUtility::getCurrentIndex($model, $field, $prefix, $orderByField, $indexLength);

        $customerNumber = ModelNoUtility::customerNumber($prefix, $index, $indexLength, $dateFormat);
        if ($field) {
            $params[$field] = $customerNumber;
        }

        return $customerNumber;
    }

    public static function setCurrentIndexByIndex($model, $index, ?string $field = null, &$params = [], $prefix = null, $indexLength = 4, $dateFormat = 'ymd')
    {
        if (!is_string($model)) {
            $model = get_class($model);
        }

        if (!defined("{$model}::CUSTOMER_NUMBER_PREFIX")) {
            throw new \RuntimeException("{$model}::CUSTOMER_NUMBER_PREFIX doesn't exist.");
        }

        $prefix = $model::CUSTOMER_NUMBER_PREFIX;

        $customerNumber = ModelNoUtility::customerNumber($prefix, $index, $indexLength, $dateFormat);
        if ($field) {
            $params[$field] = $customerNumber;
        }

        return $customerNumber;
    }

    public static function getNextCustonNumber($model, string $field, $prefix = null, $orderByField = 'created_at', $indexLength = 4, $dateFormat = 'ymd')
    {
        $nextIndex = ModelNoUtility::getCurrentIndex(...func_get_args());

        $tmp = [];
        $customerNumber = static::setCurrentIndexByIndex($model, $nextIndex, $field, $tmp, $prefix, $indexLength, $dateFormat);

        $exists = $model::where($field, $customerNumber)->count();
        if ($exists) {
            $nextIndex++;
            $customerNumber = static::setCurrentIndexByIndex($model, $nextIndex, $field, $tmp, $prefix, $indexLength, $dateFormat);
        }

        unset($tmp);

        return $customerNumber;
    }

    public static function getNextIndex($model, string $field, $prefix = null, $orderByField = 'created_at', $indexLength = 4)
    {
        $currentIndex = ModelNoUtility::getCurrentIndex(...func_get_args());

        return $currentIndex + 1;
    }

    public static function getCurrentIndex($model, string $field, $prefix = null, $orderByField = 'created_at', $indexLength = 4)
    {
        if (!is_string($model)) {
            $model = get_class($model);
        }

        if (!defined("{$model}::CUSTOMER_NUMBER_PREFIX")) {
            throw new \RuntimeException("{$model}::CUSTOMER_NUMBER_PREFIX doesn't exist.");
        }

        $orderByField = $orderByField ?? 'created_at';

        $date = now();
        $prefix = $model::CUSTOMER_NUMBER_PREFIX ?? $prefix;
        $batch_number = $model::whereDate($orderByField, $date)
            ->orderByDesc($orderByField)
            ->count() ?? 0;

        $index = 0;
        if ($batch_number) {
            $index = $batch_number;
        }

        return $index;
    }

    public static function customerNumber(?string $prefix = null, int $currentIndex = 0, $indexLength = 4, string $dateFormat = 'ymd')
    {
        $nextIndex = $currentIndex + 1;
        $nextIndexString = str_pad($nextIndex, $indexLength, '0', STR_PAD_LEFT);

        $date = date($dateFormat);

        $prefix = $prefix ?? '';

        return "{$prefix}{$date}{$nextIndexString}";
    }
}