<?php

namespace ZhenMu\Support\Traits;

trait ModelResultTrait
{
    public function registerResultToBuilder()
    {
        \Illuminate\Database\Eloquent\Builder::macro('result', $paginate = function () {
            $namespace = request()->route()->getAction('namespace');
        
            if (!request('page') || request('export')) {
                return $this->get(request('columns', ['*']));
            }
        
            if (\Illuminate\Support\Str::contains($namespace, 'App\Http\Controllers\Admin')) {
                return $this->paginate(request('per_page', 20) <= 100 ? request('per_page', 20) : 100, request('columns', ['*']));
            }
        
            return $this->paginate(request('per_page', 20) <= 100 ? request('per_page', 20) : 100, request('columns', ['*']));
        });
        \Illuminate\Database\Query\Builder::macro('result', $paginate);
    }
}
