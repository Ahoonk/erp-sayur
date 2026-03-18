<?php

namespace App\Repositories;

use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryRepository extends BaseMasterDataRepository
{
    protected string $resourceClass = CategoryResource::class;
    protected string $name = 'Category';

    public function __construct(Category $model)
    {
        $this->model = $model;
    }
}
