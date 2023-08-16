<?php

namespace App\Utilities;

use Illuminate\Support\Arr;
use League\Fractal\Manager;
use App\Transformers\EmptyTransformer;
use Illuminate\Database\Eloquent\Model;
use League\Fractal\TransformerAbstract;
use Illuminate\Database\Eloquent\Collection;
use League\Fractal\Resource\Item as FractalItem;
use League\Fractal\Serializer\DataArraySerializer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;

class Transform
{
    protected mixed $data;
    
    /**
     * Fractal manager.
     *
     * @var Manager
     */
    protected Manager $fractal;

    /**
     * Create a new class instance.
     *
     * @param mixed $data
     * @param Manager $fractal
     */
    public function __construct(mixed $data = null, ?Manager $fractal = null)
    {
        $this->data = $data;

        $this->fractal = $fractal ?? new Manager();

        if (\request()->has('include')) {
            $this->fractal->parseIncludes(\request()->query('include'));
        }
    }

    /**
     * Make a new class instance.
     *
     * @param Manager $fractal
     */
    public static function make()
    {
        $instance = new static(...func_get_args());

        return $instance->withData($instance->getData());
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * Make a JSON response with the transformed items.
     *
     * @param $data
     * @param  TransformerAbstract|null|boolean  $transformer
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function withData($data, $transformer = null, $meta = [])
    {
        if (!$data) {
            return null;
        }

        if ($data instanceof Model) {
            $result = $this->item($data, $transformer);
        } else {
            $result = $this->collection($data, $transformer);
            if ($meta) {
                $result['meta'] = isset($result['meta']) ? array_merge($result['meta'], $meta) : $meta;
            }
        }

        return $result;
    }

    /**
     * Transform a collection of data.
     *
     * @param $data
     * @param TransformerAbstract|null|boolean $transformer
     * @return array
     * @throws \Exception
     */
    public function collection($data, $transformer = null)
    {
        $transformer = ($transformer instanceof TransformerAbstract) ? $transformer : $this->fetchDefaultTransformer($data);

        if ($data instanceof LengthAwarePaginator) {
            $collection = new FractalCollection($data->getCollection(), $transformer);
            $collection->setPaginator(new IlluminatePaginatorAdapter($data));
        } else {
            $collection = new FractalCollection($data, $transformer);
        }

        $result = $this->fractal->createData($collection)->toArray();

        return $result['data'] ?? $result;
    }

    /**
     * Transform a single data.
     *
     * @param $data
     * @param TransformerAbstract|null $transformer
     * @return array
     * @throws \Exception
     */
    public function item($data, $transformer = null)
    {
        $transformer = ($transformer instanceof TransformerAbstract) ? $transformer : $this->fetchDefaultTransformer($data);

        $result = $this->fractal->createData(
            new FractalItem($data, $transformer)
        )->toArray();

        return $result['data'] ?? $result;
    }

    /**
     * Tries to fetch a default transformer for the given data.
     *
     * @param $data
     *
     * @return EmptyTransformer
     * @throws \Exception
     */
    protected function fetchDefaultTransformer($data)
    {
        if(($data instanceof LengthAwarePaginator || $data instanceof Collection) && $data->isEmpty()) {
            $emptyTransformer = new class extends TransformerAbstract
            {
                public function transform()
                {
                    return [];
                }
            };

            return $emptyTransformer;
        }

        $className = $this->getClassName($data);

        if ($this->hasDefaultTransformer($className)) {
            $transformer = config('api.transformers.' . $className);
        } else {
            $classBasename = class_basename($className);

            if(!class_exists($transformer = "App\\Transformers\\{$classBasename}Transformer")) {
                throw new \Exception("No transformer for {$className}");
            }
        }

        return new $transformer;
    }

    /**
     * Check if the class has a default transformer.
     *
     * @param $className
     *
     * @return bool
     */
    protected function hasDefaultTransformer($className)
    {
        return ! is_null(config('api.transformers.' . $className));
    }

    /**
     * Get the class name from the given object.
     *
     * @param $object
     *
     * @return string
     * @throws \Exception
     */
    protected function getClassName($object)
    {
        if ($object instanceof LengthAwarePaginator || $object instanceof Collection) {
            return get_class(Arr::first($object));
        }

        if (!is_string($object) && !is_object($object)) {
            throw new \Exception("No transformer of \"{$object}\" found.");
        }

        return get_class($object);
    }
}
