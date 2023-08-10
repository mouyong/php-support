<?php

namespace ZhenMu\Support\Utilities;

class ModelUtility
{
    public static function isMethodInClassChain($className, $methodName)
    {
        $refClass = new \ReflectionClass($className);

        while ($refClass) {
            if ($refClass->hasMethod($methodName)) {
                return true;
            }
            $refClass = $refClass->getParentClass();
        }

        return false;
    }

    /**
        $relations = [];
        // 定义查询条件与执行方式
        // => Model::where('where_field', 'where_field_value')
        // => ->whereIn('where_field_in', ['where_field_value_1', 'where_field_value_2'])
        // => ->get()
        $relations['relationNameInUse']['wheres'][] = [Model::class, 'where_field', 'where_field_value']; 
        $relations['relationNameInUse']['wheres']['whereIn'] = [Model::class, 'where_field_in', ['where_field_value_1', 'where_field_value_2']];
        $relations['relationNameInUse']['page'] = 1;
        $relations['relationNameInUse']['perPage'] = 20;
        $relations['relationNameInUse']['performMethod'] = 'get';

        $relations['tests']['wheres'][] = [Test::class, 'test_number', $params['test_numbers']];
        $relations['tests']['performMethod'] = 'get';

        $relations['experiment_records']['wheres'][] = [CsimExperiment::class, 'experiment_batch_number', $csim_experiment['experiment_batch_number']];
        $relations['experiment_records']['performMethod'] = 'first';
        $relations['experiment_records']['params'] = [['id', 'experiment_batch_number']];

        $relations['user']['wheres'][] = [User::class, 'experiment_batch_number', $csim_experiment['experiment_batch_number']];
        $relations['user']['performMethod'] = 'value';
        $relations['user']['params'] = ['username'];

        // 查询关联数据
        $relationData = RelationUtility::getRelations($relations);
        dd($relationData);
     */
    public static function getRelationData(array $relationsWhereList = [])
    {
        $relations = [];

        foreach ($relationsWhereList as $relationName => $relationsWheres) {
            $relations[$relationName] = static::getData(...$relationsWheres);
        }

        $filterRelations = array_filter($relations);
        return $filterRelations;
    }

    protected static function getData(array $wheres = [], $performMethod = null, $page = null, $perPage = null, bool $toArray = true, array $params = [['*']])
    {
        if (empty($wheres)) {
            return null;
        }

        $model = null;
        $query = null;

        foreach ($wheres as $whereKey => $where) {
            if (!$model) {
                $model = $where[0];
            }
            unset($where[0]);

            if (!$model) {
                continue;
            }

            $whereMethod = 'where';
            if (is_string($whereKey)) {
                $whereMethod = $whereKey;
            }

            $query = $model::{$whereMethod}(...$where);
        }

        if ($page && $perPage) {
            $query->skip($perPage * $page - $perPage)->limit($perPage);
        }

        $result = $query->{$performMethod}(...$params);
        if ($toArray && is_object($result) && method_exists($result, 'toArray')) {
            return $result?->toArray();
        }

        return $result;
    }

    /**
        // 关联关系 experiment => experiment_records
        $data['experiment_records'] = ModelUtility::formatRecords($relations, 'experiment_records', function ($item, $relations) {
            return CsimExperimentDataFormat::getExperimentInfo($item, $relations);
        });
     */
    public static function formatRecords($relations, $relationName, $callable)
    {
        if (empty($relations[$relationName])) {
            return [];
        }

        $data = [];
        foreach ($relations[$relationName] as $item) {
            $data[] = $callable($item, $relations);
        }

        return $data;
    }

    /**
        $data['analysis_records'] = ModelUtility::formatRecordsByWhere($relations, 'analysis_records', 'analysis_batch_number', $params['analysis_batch_number'], function ($data, $relations) {
            return ModelUtility::formatDataItem($data, $relations, function ($item, $relations) {
                return AnalysisDataFormat::getAnalysisInfo($item, $relations);
            });
        })
     */
    public static function formatDataItem($data, $relations, $callable)
    {
        $result = [];
        foreach($data as $item) {
            $result[] = $callable($item, $relations);
        }
        return $result;
    }

    /**
        // 关联关系 test_experiments => tests
        $data['test_number'] = $params['test_number'];
        $data['test'] = static::formatRecordsByWhere($relations, 'tests', 'test_number', $params['test_number'], function ($item, $relations) {
            return Test::getTestInfo($item, $relations);
        });
     */
    public static function formatRecordsByWhere($relations, $relationName, $whereField, $whereValue, $callable, $performMethod = 'where', $toArray = true)
    {
        if (empty($relations[$relationName])) {
            return [];
        }

        $relationCollection = is_array($relations[$relationName]) ? collect($relations[$relationName]) : $relations[$relationName];
        $result = $relationCollection->{$performMethod}($whereField, $whereValue);

        if ($toArray && is_object($result) && method_exists($result, 'toArray')) {
            $result = $result?->toArray();
        }

        return $callable($result, $relations);
    }
}
