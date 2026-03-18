<?php

namespace App\Http\Controllers;

use App\Repositories\StockAdjustmentRepository;
use Illuminate\Http\Request;

class StockAdjustmentController extends Controller
{
    public function __construct(private StockAdjustmentRepository $repository) {}

    public function index(Request $request)
    {
        return $this->repository->index(
            $request->query('per_page', 10),
            $request->query('search', ''),
            $request->query('reason'),
            $request->query('start_date'),
            $request->query('end_date')
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'katalog_barang_id' => 'required|uuid|exists:katalog_barang,id',
            'tanggal' => 'required|date',
            'reason' => 'required|in:REJECT SUPPLIER,EXPIRED,BARANG HILANG/SUSUT',
            'qty' => 'required|numeric|gt:0',
            'keterangan' => 'nullable|string',
        ]);

        return $this->repository->store($validated, $request->user()?->id);
    }
}
