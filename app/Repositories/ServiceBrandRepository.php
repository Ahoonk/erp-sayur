<?php

namespace App\Repositories;

use App\Http\Resources\ServiceBrandResource;
use App\Models\ServiceBrand;

class ServiceBrandRepository extends BaseMasterDataRepository
{
    protected string $resourceClass = ServiceBrandResource::class;
    protected string $name = 'ServiceBrand';

    public function __construct(ServiceBrand $model)
    {
        $this->model = $model;
    }
}
