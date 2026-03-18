<?php

namespace App\Http\Controllers;

use App\Interfaces\MasterDataRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class MasterDataController extends Controller implements HasMiddleware
{
    private MasterDataRepositoryInterface $repository;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:view master-data', only: ['index', 'show', 'all']),
            new Middleware('permission:create master-data', only: ['store', 'quickStore']),
            new Middleware('permission:edit master-data', only: ['update']),
            new Middleware('permission:delete master-data', only: ['destroy']),
        ];
    }

    public function __construct(MasterDataRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Set the repository to use (called by route binding)
     */
    public function setRepository(MasterDataRepositoryInterface $repository): self
    {
        $this->repository = $repository;
        return $this;
    }

    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $search = $request->query('search', '');

        return $this->repository->index($perPage, $search);
    }

    public function all()
    {
        return $this->repository->all();
    }

    public function store(Request $request)
    {
        return $this->repository->store($request->validated());
    }

    public function quickStore(Request $request)
    {
        $data = $request->validate(['nama' => 'required|string|max:255']);
        return $this->repository->quickStore($data);
    }

    public function show($id)
    {
        return $this->repository->show($id);
    }

    public function update(Request $request, $id)
    {
        return $this->repository->update($request->validated(), $id);
    }

    public function destroy($id)
    {
        return $this->repository->destroy($id);
    }
}
