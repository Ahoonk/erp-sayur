<?php

namespace App\Repositories;

use App\Http\Resources\TaxResource;
use App\Models\Tax;

class TaxRepository extends BaseMasterDataRepository
{
    protected string $resourceClass = TaxResource::class;
    protected string $name = 'Tax';

    public function __construct(Tax $model)
    {
        $this->model = $model;
    }

    /**
     * Override all() to only return active taxes
     */
    public function all()
    {
        $data = $this->model->newQuery()->active()->orderBy('nama')->get();

        return \App\Helpers\ResponseHelper::success(
            TaxResource::collection($data),
            "All active taxes retrieved successfully"
        );
    }
}
