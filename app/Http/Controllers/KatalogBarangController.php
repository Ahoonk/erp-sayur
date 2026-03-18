<?php
namespace App\Http\Controllers;
use App\Repositories\KatalogBarangRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class KatalogBarangController extends Controller implements HasMiddleware {
    public static function middleware(): array {
        return [
            new Middleware('permission:view master-data', only: ['index', 'show', 'all']),
            new Middleware('permission:create master-data', only: ['store']),
            new Middleware('permission:edit master-data', only: ['update']),
            new Middleware('permission:delete master-data', only: ['destroy']),
        ];
    }

    public function __construct(private KatalogBarangRepository $repository) {}

    public function index(Request $request) {
        return $this->repository->index(
            $request->query('per_page', 10),
            $request->query('search', ''),
            $request->query('category_id'),
        );
    }

    public function all(Request $request) {
        return $this->repository->all($request->query('search', ''), $request->query('category_id'));
    }

    public function show($id) {
        return $this->repository->show($id);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'category_id' => 'required|uuid|exists:categories,id',
            'unit_id' => 'required|uuid|exists:units,id',
        ]);
        return $this->repository->store($data);
    }

    public function update(Request $request, $id) {
        $data = $request->validate([
            'nama_barang' => 'sometimes|required|string|max:255',
            'category_id' => 'sometimes|required|uuid|exists:categories,id',
            'unit_id' => 'sometimes|required|uuid|exists:units,id',
        ]);
        return $this->repository->update($data, $id);
    }

    public function destroy($id) {
        return $this->repository->destroy($id);
    }
}
