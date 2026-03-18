<?php
namespace App\Http\Controllers;
use App\Repositories\MitraRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class MitraController extends Controller implements HasMiddleware {
    public static function middleware(): array {
        return [
            new Middleware('permission:view master-data', only: ['index', 'show', 'all']),
            new Middleware('permission:create master-data', only: ['store', 'quickStore']),
            new Middleware('permission:edit master-data', only: ['update']),
            new Middleware('permission:delete master-data', only: ['destroy']),
        ];
    }

    public function __construct(private MitraRepository $repository) {}

    public function index(Request $request) {
        return $this->repository->index($request->query('per_page', 10), $request->query('search', ''));
    }

    public function all(Request $request) {
        return $this->repository->all($request->query('search', ''));
    }

    public function show($id) {
        return $this->repository->show($id);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'telepon' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'keterangan' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        return $this->repository->store($data);
    }

    public function quickStore(Request $request) {
        $data = $request->validate(['nama' => 'required|string|max:255']);
        return $this->repository->store($data);
    }

    public function update(Request $request, $id) {
        $data = $request->validate([
            'nama' => 'sometimes|required|string|max:255',
            'telepon' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'keterangan' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        return $this->repository->update($data, $id);
    }

    public function destroy($id) {
        return $this->repository->destroy($id);
    }
}
