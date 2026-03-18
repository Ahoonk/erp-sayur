<?php

namespace App\Http\Controllers;

use App\Repositories\PurchaseRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PurchaseController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view purchases', only: ['index', 'show', 'generateInvoiceNumber']),
            new Middleware('permission:create purchases', only: ['store', 'addItem']),
            new Middleware('permission:edit purchases', only: ['update', 'updateItem']),
            new Middleware('permission:delete purchases', only: ['destroy', 'removeItem']),
        ];
    }

    public function __construct(private PurchaseRepository $repository) {}

    public function index(Request $request)
    {
        return $this->repository->index(
            $request->query('per_page', 10),
            $request->query('search', ''),
            $request->query('start_date'),
            $request->query('end_date'),
            $request->query('supplier_id')
        );
    }

    public function show($id)
    {
        return $this->repository->show($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'no_invoice' => 'required|string|unique:purchases,no_invoice',
            'tanggal' => 'required|date',
            'supplier_id' => 'required|uuid|exists:suppliers,id',
            'keterangan' => 'nullable|string',
        ]);
        return $this->repository->store($data);
    }

    public function addItem(Request $request, $id)
    {
        $data = $request->validate([
            'katalog_barang_id' => 'required|uuid|exists:katalog_barang,id',
            'qty' => 'required|numeric|min:0.001',
            'harga_beli' => 'required|numeric|min:0',
            'expired_at' => 'nullable|date',
        ]);
        return $this->repository->addItem($id, $data);
    }

    public function updateItem(Request $request, $purchaseId, $itemId)
    {
        $data = $request->validate([
            'qty' => 'sometimes|required|numeric|min:0.001',
            'harga_beli' => 'sometimes|required|numeric|min:0',
        ]);
        return $this->repository->updateItem($purchaseId, $itemId, $data);
    }

    public function removeItem($purchaseId, $itemId)
    {
        return $this->repository->removeItem($purchaseId, $itemId);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'no_invoice' => 'sometimes|required|string|unique:purchases,no_invoice,' . $id,
            'tanggal' => 'sometimes|required|date',
            'supplier_id' => 'sometimes|required|uuid|exists:suppliers,id',
            'keterangan' => 'nullable|string',
        ]);
        return $this->repository->update($data, $id);
    }

    public function destroy($id)
    {
        return $this->repository->destroy($id);
    }

    public function generateInvoiceNumber()
    {
        return response()->json([
            'success' => true,
            'data' => ['no_invoice' => $this->repository->generateInvoiceNumber()],
        ]);
    }
}
