<?php

namespace App\Repositories;

use App\Http\Resources\SalesRepResource;
use App\Models\SalesRep;

class SalesRepRepository extends BaseMasterDataRepository
{
    protected string $resourceClass = SalesRepResource::class;
    protected string $name = 'Sales Rep';

    public function __construct(SalesRep $model)
    {
        $this->model = $model;
    }
}
