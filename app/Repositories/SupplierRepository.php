<?php

namespace App\Repositories;

use App\Http\Resources\SupplierResource;
use App\Models\Supplier;

class SupplierRepository extends BaseMasterDataRepository
{
    protected string $resourceClass = SupplierResource::class;
    protected string $name = 'Supplier';

    public function __construct(Supplier $model)
    {
        $this->model = $model;
    }
}
