<?php

namespace App\Repositories;

use App\Http\Resources\BrandResource;
use App\Models\Brand;

class BrandRepository extends BaseMasterDataRepository
{
    protected string $resourceClass = BrandResource::class;
    protected string $name = 'Brand';

    public function __construct(Brand $model)
    {
        $this->model = $model;
    }
}
