<?php
namespace App\Http\Controllers;
use App\Repositories\StockRepository;
use Illuminate\Http\Request;

class StockController extends Controller {
    public function __construct(private StockRepository $repository) {}

    public function summary(Request $request) {
        return $this->repository->summary(
            $request->query('per_page', 10),
            $request->query('search', ''),
            $request->query('category_id'),
        );
    }

    public function mutasi(Request $request) {
        return $this->repository->mutasi(
            $request->query('per_page', 10),
            $request->query('search', ''),
            $request->query('start_date'),
            $request->query('end_date'),
            $request->query('category_id'),
            $request->query('katalog_barang_id'),
        );
    }

    public function expiring(Request $request) {
        return $this->repository->expiring(
            $request->query('per_page', 10),
            $request->query('search', ''),
            $request->query('start_date'),
            $request->query('end_date'),
        );
    }
}
