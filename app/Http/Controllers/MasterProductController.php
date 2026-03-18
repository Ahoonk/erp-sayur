<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Repositories\MasterProductRepository;
use App\Http\Resources\MasterProductResource;
use App\Helpers\ResponseHelper;

class MasterProductController extends Controller
{
    public function __construct(private MasterProductRepository $repository) {}

    public function index(Request $request)
    {
        $data = $this->repository->paginate(
            $request->query('per_page', 10),
            $request->query('search')
        );
        return ResponseHelper::success($data, 'Master products retrieved successfully');
    }

    public function all()
    {
        return ResponseHelper::success(
            MasterProductResource::collection($this->repository->all()),
            'All master products retrieved'
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'brand_id' => 'required|uuid|exists:brands,id',
        ]);

        $item = $this->repository->store($validated);
        return ResponseHelper::success(new MasterProductResource($item), 'Master product created successfully', 201);
    }

    public function show($id)
    {
        return ResponseHelper::success(new MasterProductResource($this->repository->find($id)), 'Master product found');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'brand_id' => 'required|uuid|exists:brands,id',
        ]);

        $item = $this->repository->update($id, $validated);
        return ResponseHelper::success(new MasterProductResource($item), 'Master product updated successfully');
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return ResponseHelper::success(null, 'Master product deleted successfully');
    }

    public function search(Request $request)
    {
        return ResponseHelper::success(
            MasterProductResource::collection($this->repository->search($request->query('q'))),
            'Master products search results'
        );
    }
}
