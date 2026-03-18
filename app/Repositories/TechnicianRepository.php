<?php

namespace App\Repositories;

use App\Helpers\ResponseHelper;
use App\Http\Resources\TechnicianResource;
use App\Models\Technician;
use Illuminate\Support\Facades\DB;

class TechnicianRepository extends BaseMasterDataRepository
{
    protected string $resourceClass = TechnicianResource::class;
    protected string $name = 'Technician';

    public function __construct(Technician $model)
    {
        $this->model = $model;
    }

    public function quickStore(array $data)
    {
        $item = $this->model->create([
            'nama' => $data['nama'],
            'no_hp' => $data['no_hp'] ?? null,
            'alamat' => $data['alamat'] ?? null,
            'specialist' => $data['specialist'] ?? null,
        ]);

        return ResponseHelper::success(
            new $this->resourceClass($item),
            'Technician created successfully',
            201
        );
    }
}
