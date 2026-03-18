<?php

namespace App\Repositories;

use App\Http\Resources\UnitResource;
use App\Models\Unit;

class UnitRepository extends BaseMasterDataRepository
{
    protected string $resourceClass = UnitResource::class;
    protected string $name = 'Unit';

    public function __construct(Unit $model)
    {
        $this->model = $model;
    }
}
