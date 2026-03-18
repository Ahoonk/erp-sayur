<?php

namespace App\Repositories;

use App\Http\Resources\GradeResource;
use App\Models\Grade;

class GradeRepository extends BaseMasterDataRepository
{
    protected string $resourceClass = GradeResource::class;
    protected string $name = 'Grade';

    public function __construct(Grade $model)
    {
        $this->model = $model;
    }
}
