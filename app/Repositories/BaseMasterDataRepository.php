<?php

namespace App\Repositories;

use App\Helpers\ResponseHelper;
use App\Http\Resources\PaginateResource;
use App\Interfaces\MasterDataRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

abstract class BaseMasterDataRepository implements MasterDataRepositoryInterface
{
    protected Model $model;
    protected string $resourceClass;
    protected string $name;

    public function index($perPage, $search)
    {
        $query = $this->model->newQuery()->search($search)->latest();
        $data = $query->paginate($perPage);

        return ResponseHelper::success(
            new PaginateResource($data, $this->resourceClass),
            "{$this->name} retrieved successfully"
        );
    }

    public function all()
    {
        $data = $this->model->newQuery()->orderBy('nama')->get();

        return ResponseHelper::success(
            $this->resourceClass::collection($data),
            "All {$this->name} retrieved successfully"
        );
    }

    public function store(array $data)
    {
        $record = $this->model->create($data);

        return ResponseHelper::success(
            new $this->resourceClass($record),
            "{$this->name} created successfully",
            201
        );
    }

    public function quickStore(array $data)
    {
        $record = $this->model->create($data);

        return ResponseHelper::success(
            new $this->resourceClass($record),
            "{$this->name} created successfully",
            201
        );
    }

    public function show($id)
    {
        $record = $this->model->findOrFail($id);

        return ResponseHelper::success(
            new $this->resourceClass($record),
            "{$this->name} retrieved successfully"
        );
    }

    public function update(array $data, $id)
    {
        $record = $this->model->findOrFail($id);
        $record->update($data);

        return ResponseHelper::success(
            new $this->resourceClass($record),
            "{$this->name} updated successfully"
        );
    }

    public function destroy($id)
    {
        $record = $this->model->findOrFail($id);
        $record->delete();

        return ResponseHelper::success(
            null,
            "{$this->name} deleted successfully"
        );
    }
}
