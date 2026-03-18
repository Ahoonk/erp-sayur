<?php

namespace App\Repositories;

use App\Models\MasterProduct;
use App\Helpers\ResponseHelper;

class MasterProductRepository
{
    public function __construct(private MasterProduct $model) {}

    public function all()
    {
        return $this->model->with(['brand'])->orderBy('nama')->get();
    }

    public function paginate($perPage = 10, $search = null)
    {
        return $this->model->with(['brand'])
            ->when($search, function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            })
            ->orderBy('nama')
            ->paginate($perPage);
    }

    public function store(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $item = $this->model->findOrFail($id);
        $item->update($data);
        return $item;
    }

    public function delete($id)
    {
        $item = $this->model->findOrFail($id);
        return $item->delete();
    }

    public function find($id)
    {
        return $this->model->with(['brand'])->findOrFail($id);
    }

    public function search($keyword)
    {
        return $this->model->with(['brand'])
            ->where('nama', 'like', "%{$keyword}%")
            ->limit(10)
            ->get();
    }
}
