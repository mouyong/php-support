<?php

namespace ZhenMu\Support\Traits;

trait ModelResultTrait
{
    public function registerResultToBuilder()
    {
        \Illuminate\Database\Eloquent\Builder::macro('result', $paginate = function ($perPage = null, $columns = ['*'], $pageName = 'page', $page = null) {
            $columns = request('columns', $columns);
        
            if (!$perPage && (!request($pageName) || request('export'))) {
                return $this->get($columns);
            }
        
            $perPage = request('per_page', $perPage) <= 100 ? request('per_page', $perPage) : 100;

            return $this->paginate($perPage, $columns);

        });
        \Illuminate\Database\Query\Builder::macro('result', $paginate);
    }
}
