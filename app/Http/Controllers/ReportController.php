<?php

namespace App\Http\Controllers;

use App\Exports\SimpleArrayExport;
use App\Helpers\ResponseHelper;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use App\Models\SalesTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view reports|view all reports', only: ['sales', 'purchases', 'profit', 'salesDetail', 'purchasesDetail']),
        ];
    }

    public function sales(Request $request)
    {
        $validated = $this->validateReportRequest($request, true);
        $perPage = $this->resolvePerPage($request);

        $query = SalesTransaction::query()
            ->with(['user:id,name', 'salesRep:id,nama'])
            ->withSum('items as hpp_total', 'hpp_total')
            ->withSum('items as qty_total', 'qty');
        $this->applySalesReportFilters($query, $validated);
        $query
            ->latest('tanggal')
            ->latest('created_at')
            ->latest('id');

        $summary = $this->buildSalesSummary($validated);

        if (($validated['export'] ?? null) === 'excel') {
            return $this->exportSalesCsv($query->get(), $summary);
        }

        $data = $query->paginate($perPage);
        $rows = $data->getCollection()->map(function ($item) {
            $hppTotal = (float) ($item->hpp_total ?? 0);

            return [
                'id' => $item->id,
                'no_invoice' => $item->no_invoice,
                'tanggal' => optional($item->tanggal)->format('Y-m-d'),
                'tipe' => $item->tipe ?? 'penjualan',
                'pelanggan' => $item->pelanggan,
                'kasir' => $item->user?->name,
                'sales' => $item->salesRep?->nama,
                'qty_total' => (int) ($item->qty_total ?? 0),
                'grand_total' => (float) $item->grand_total,
                'metode_pembayaran' => $item->metode_pembayaran,
                'hpp_total' => $hppTotal,
                'laba_kotor' => (float) $item->grand_total - $hppTotal,
            ];
        })->values();
        $data->setCollection($rows);

        return ResponseHelper::success([
            'data' => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'per_page' => $data->perPage(),
            'total' => $data->total(),
            'from' => $data->firstItem(),
            'to' => $data->lastItem(),
            'summary' => $summary,
        ], 'Sales report retrieved');
    }

    public function purchases(Request $request)
    {
        $validated = $this->validateReportRequest($request);
        $perPage = $this->resolvePerPage($request);

        $query = Purchase::query()
            ->with(['supplier:id,nama', 'user:id,name'])
            ->withCount('items')
            ->search($validated['search'] ?? null)
            ->dateRange($validated['start_date'] ?? null, $validated['end_date'] ?? null)
            ->when($validated['supplier_id'] ?? null, fn($q, $supplierId) => $q->where('supplier_id', $supplierId))
            ->latest('tanggal')
            ->latest('created_at');

        if (($validated['export'] ?? null) === 'excel') {
            return $this->exportPurchasesCsv($query->get());
        }

        $data = $query->paginate($perPage);

        // Get summary totals
        $summaryQuery = Purchase::query()
            ->withCount('items')
            ->search($validated['search'] ?? null)
            ->dateRange($validated['start_date'] ?? null, $validated['end_date'] ?? null)
            ->when($validated['supplier_id'] ?? null, fn($q, $supplierId) => $q->where('supplier_id', $supplierId));

        $rows = $data->getCollection()->map(function ($item) {
            return [
                'id' => $item->id,
                'no_invoice' => $item->no_invoice,
                'tanggal' => optional($item->tanggal)->format('Y-m-d'),
                'supplier' => $item->supplier?->nama,
                'kasir' => $item->user?->name,
                'items_count' => (int) $item->items_count,
                'total' => (float) $item->total,
                'keterangan' => $item->keterangan,
            ];
        })->values();
        $data->setCollection($rows);

        return ResponseHelper::success([
            'data' => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'per_page' => $data->perPage(),
            'total' => $data->total(),
            'from' => $data->firstItem(),
            'to' => $data->lastItem(),
            'summary' => [
                'items_count' => (int) $summaryQuery->get()->sum('items_count'),
                'total' => (float) $summaryQuery->sum('total'),
            ],
        ], 'Purchase report retrieved');
    }

    public function profit(Request $request)
    {
        $validated = $this->validateReportRequest($request, true);
        $perPage = $this->resolvePerPage($request);
        $tipe = $validated['tipe'] ?? 'all';

        $query = SalesTransaction::query()
            ->with(['user:id,name'])
            ->withSum('items as hpp_total', 'hpp_total')
            ->search($validated['search'] ?? null)
            ->dateRange($validated['start_date'] ?? null, $validated['end_date'] ?? null)
            ->when($tipe !== 'all', fn($q) => $q->where('tipe', $tipe))
            ->when($validated['sales_rep_id'] ?? null, fn($q, $salesRepId) => $q->where('sales_rep_id', $salesRepId))
            ->latest('tanggal')
            ->latest('created_at');

        if (($validated['export'] ?? null) === 'excel') {
            return $this->exportProfitCsv($query->get());
        }

        // Get summary for all data
        $summaryQuery = SalesTransaction::query()
            ->withSum('items as hpp_total', 'hpp_total')
            ->search($validated['search'] ?? null)
            ->dateRange($validated['start_date'] ?? null, $validated['end_date'] ?? null)
            ->when($tipe !== 'all', fn($q) => $q->where('tipe', $tipe))
            ->when($validated['sales_rep_id'] ?? null, fn($q, $salesRepId) => $q->where('sales_rep_id', $salesRepId));

        $data = $query->paginate($perPage);
        $rows = $data->getCollection()->map(function ($item) {
            $hppTotal = (float) ($item->hpp_total ?? 0);
            $pendapatan = (float) $item->grand_total;

            return [
                'id' => $item->id,
                'no_invoice' => $item->no_invoice,
                'tanggal' => optional($item->tanggal)->format('Y-m-d'),
                'tipe' => $item->tipe ?? 'penjualan',
                'kasir' => $item->user?->name,
                'pendapatan' => $pendapatan,
                'hpp_total' => $hppTotal,
                'laba_kotor' => $pendapatan - $hppTotal,
            ];
        })->values();
        $data->setCollection($rows);

        $allData = $summaryQuery->get();
        $totalPendapatan = $allData->sum('grand_total');
        $totalHpp = $allData->sum('hpp_total');

        return ResponseHelper::success([
            'data' => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'per_page' => $data->perPage(),
            'total' => $data->total(),
            'from' => $data->firstItem(),
            'to' => $data->lastItem(),
            'summary' => [
                'pendapatan' => (float) $totalPendapatan,
                'hpp_total' => (float) $totalHpp,
                'laba_kotor' => (float) ($totalPendapatan - $totalHpp),
            ],
        ], 'Profit report retrieved');
    }

    public function salesDetail(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
            'search'     => ['nullable', 'string'],
            'export'     => ['nullable', 'in:excel,pdf'],
            'per_page'   => ['nullable', 'integer'],
        ]);

        $perPage = $this->resolvePerPage($request);

        $query = SaleItem::query()
            ->join('sales_transactions', 'sale_items.sales_transaction_id', '=', 'sales_transactions.id')
            ->whereNull('sales_transactions.deleted_at')
            ->with([
                'salesTransaction:id,no_invoice,tanggal,pelanggan,user_id,sales_rep_id',
                'salesTransaction.user:id,name',
                'salesTransaction.salesRep:id,nama',
                'product:id,barcode,imei1,imei2,master_product_id,grade_id,unit_id',
                'product.masterProduct:id,nama,brand_id',
                'product.masterProduct.brand:id,nama',
                'product.grade:id,nama',
                'product.unit:id,nama',
            ])
            ->when(!empty($validated['start_date']), fn($q) => $q->where('sales_transactions.tanggal', '>=', $validated['start_date']))
            ->when(!empty($validated['end_date']), fn($q) => $q->where('sales_transactions.tanggal', '<=', $validated['end_date']))
            ->when(!empty($validated['search']), fn($q) => $q->where(function ($sq) use ($validated) {
                $sq->where('sales_transactions.no_invoice', 'like', '%' . $validated['search'] . '%')
                    ->orWhere('sales_transactions.pelanggan', 'like', '%' . $validated['search'] . '%')
                    ->orWhereHas('product', fn($pq) => $pq->where('imei1', 'like', '%' . $validated['search'] . '%')
                        ->orWhere('imei2', 'like', '%' . $validated['search'] . '%'));
            }))
            ->select('sale_items.*')
            ->orderBy('sales_transactions.tanggal', 'desc')
            ->orderBy('sale_items.created_at', 'desc');

        // Summary (unfiltered count but date-filtered)
        $summaryBase = SaleItem::query()
            ->join('sales_transactions', 'sale_items.sales_transaction_id', '=', 'sales_transactions.id')
            ->whereNull('sales_transactions.deleted_at')
            ->when(!empty($validated['start_date']), fn($q) => $q->where('sales_transactions.tanggal', '>=', $validated['start_date']))
            ->when(!empty($validated['end_date']), fn($q) => $q->where('sales_transactions.tanggal', '<=', $validated['end_date']))
            ->when(!empty($validated['search']), fn($q) => $q->where(function ($sq) use ($validated) {
                $sq->where('sales_transactions.no_invoice', 'like', '%' . $validated['search'] . '%')
                    ->orWhere('sales_transactions.pelanggan', 'like', '%' . $validated['search'] . '%')
                    ->orWhereHas('product', fn($pq) => $pq->where('imei1', 'like', '%' . $validated['search'] . '%')
                        ->orWhere('imei2', 'like', '%' . $validated['search'] . '%'));
            }));

        $totalModal     = (float) (clone $summaryBase)->sum('sale_items.hpp_total');
        $totalHargaJual = (float) (clone $summaryBase)->sum('sale_items.subtotal');

        $summary = [
            'total_modal'      => $totalModal,
            'total_harga_jual' => $totalHargaJual,
            'total_laba'       => $totalHargaJual - $totalModal,
        ];

        if (($validated['export'] ?? null) === 'excel') {
            return $this->exportSalesDetailCsv($query->get(), $summary);
        }

        if (($validated['export'] ?? null) === 'pdf') {
            return $this->exportSalesDetailHtml($query->get(), $summary, $validated);
        }

        $data = $query->paginate($perPage);
        $rows = $data->getCollection()->map(fn($item) => $this->formatSaleDetailRow($item))->values();
        $data->setCollection($rows);

        return ResponseHelper::success([
            'data'         => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page'    => $data->lastPage(),
            'per_page'     => $data->perPage(),
            'total'        => $data->total(),
            'from'         => $data->firstItem(),
            'to'           => $data->lastItem(),
            'summary'      => $summary,
        ], 'Sales detail retrieved');
    }

    public function purchasesDetail(Request $request)
    {
        $validated = $request->validate([
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date'],
            'search'      => ['nullable', 'string'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'export'      => ['nullable', 'in:excel,pdf'],
            'per_page'    => ['nullable', 'integer'],
        ]);

        $perPage = $this->resolvePerPage($request);

        $query = PurchaseItem::query()
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->whereNull('purchases.deleted_at')
            ->with([
                'purchase:id,no_invoice,tanggal,supplier_id,user_id',
                'purchase.supplier:id,nama',
                'purchase.user:id,name',
                'product:id,barcode,imei1,imei2,master_product_id,grade_id,unit_id,stok,harga_jual',
                'product.masterProduct:id,nama,brand_id',
                'product.masterProduct.brand:id,nama',
                'product.grade:id,nama',
                'product.unit:id,nama',
                'product.saleItems:id,product_id,harga_satuan',
            ])
            ->when(!empty($validated['start_date']), fn($q) => $q->where('purchases.tanggal', '>=', $validated['start_date']))
            ->when(!empty($validated['end_date']), fn($q) => $q->where('purchases.tanggal', '<=', $validated['end_date']))
            ->when(!empty($validated['supplier_id']), fn($q) => $q->where('purchases.supplier_id', $validated['supplier_id']))
            ->when(!empty($validated['search']), fn($q) => $q->where(function ($sq) use ($validated) {
                $sq->where('purchases.no_invoice', 'like', '%' . $validated['search'] . '%')
                    ->orWhereHas('product', fn($pq) => $pq->whereHas('masterProduct', fn($mq) => $mq->where('nama', 'like', '%' . $validated['search'] . '%')))
                    ->orWhereHas('product', fn($pq) => $pq->where('barcode', 'like', '%' . $validated['search'] . '%'));
            }))
            ->select('purchase_items.*')
            ->orderBy('purchases.tanggal', 'desc')
            ->orderBy('purchase_items.created_at', 'desc');

        $summaryBase = PurchaseItem::query()
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->whereNull('purchases.deleted_at')
            ->when(!empty($validated['start_date']), fn($q) => $q->where('purchases.tanggal', '>=', $validated['start_date']))
            ->when(!empty($validated['end_date']), fn($q) => $q->where('purchases.tanggal', '<=', $validated['end_date']))
            ->when(!empty($validated['supplier_id']), fn($q) => $q->where('purchases.supplier_id', $validated['supplier_id']));

        $allItems   = (clone $summaryBase)->with(['product:id,stok,harga_jual', 'product.saleItems:id,product_id,harga_satuan'])->get();
        $totalStok  = $allItems->sum(fn($i) => (int) ($i->product?->stok ?? 0));
        $totalModal = (float) (clone $summaryBase)->sum('purchase_items.harga_beli');
        $totalHJ    = $allItems->sum(fn($i) => $this->resolveHargaJual($i->product));

        $summary = [
            'total_stok'       => $totalStok,
            'total_modal'      => $totalModal,
            'total_harga_jual' => $totalHJ,
        ];

        if (($validated['export'] ?? null) === 'excel') {
            return $this->exportPurchasesDetailCsv($query->get(), $summary);
        }

        if (($validated['export'] ?? null) === 'pdf') {
            return $this->exportPurchasesDetailHtml($query->get(), $summary, $validated);
        }

        $data = $query->paginate($perPage);
        $rows = $data->getCollection()->map(fn($item) => $this->formatPurchaseDetailRow($item))->values();
        $data->setCollection($rows);

        return ResponseHelper::success([
            'data'         => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page'    => $data->lastPage(),
            'per_page'     => $data->perPage(),
            'total'        => $data->total(),
            'from'         => $data->firstItem(),
            'to'           => $data->lastItem(),
            'summary'      => $summary,
        ], 'Purchases detail retrieved');
    }

    private function resolveHargaJual($product): float
    {
        if (!$product) return 0;
        $saleItem = $product->saleItems?->first();
        return $saleItem ? (float) $saleItem->harga_satuan : (float) $product->harga_jual;
    }

    private function formatSaleDetailRow(SaleItem $item): array
    {
        $modal     = (float) $item->hpp_total;
        $hargaJual = (float) $item->subtotal;
        return [
            'id'                   => $item->id,
            'sales_transaction_id' => $item->sales_transaction_id,
            'no_invoice'           => $item->salesTransaction?->no_invoice,
            'kode'         => $item->product?->barcode,
            'tanggal'      => $item->salesTransaction?->tanggal?->format('Y-m-d'),
            'nama_produk'  => $item->product?->masterProduct?->nama,
            'merk'         => $item->product?->masterProduct?->brand?->nama,
            'grade'        => $item->product?->grade?->nama,
            'satuan'       => $item->product?->unit?->nama,
            'imei1'        => $item->product?->imei1,
            'imei2'        => $item->product?->imei2,
            'pelanggan'    => $item->salesTransaction?->pelanggan,
            'kasir'        => $item->salesTransaction?->user?->name,
            'sales'        => $item->salesTransaction?->salesRep?->nama,
            'modal'        => $modal,
            'harga_jual'   => $hargaJual,
            'laba'         => $hargaJual - $modal,
        ];
    }

    private function formatPurchaseDetailRow(PurchaseItem $item): array
    {
        $hargaJual = $this->resolveHargaJual($item->product);
        return [
            'id'          => $item->id,
            'no_invoice'  => $item->purchase?->no_invoice,
            'supplier'    => $item->purchase?->supplier?->nama,
            'kode'        => $item->product?->barcode,
            'tanggal'     => $item->purchase?->tanggal?->format('Y-m-d'),
            'nama_produk' => $item->product?->masterProduct?->nama,
            'merk'        => $item->product?->masterProduct?->brand?->nama,
            'grade'       => $item->product?->grade?->nama,
            'satuan'      => $item->product?->unit?->nama,
            'imei1'       => $item->product?->imei1,
            'imei2'       => $item->product?->imei2,
            'stok'        => (int) ($item->product?->stok ?? 0),
            'modal'       => (float) $item->harga_beli,
            'harga_jual'  => $hargaJual,
            'is_sold'     => $item->product?->saleItems?->isNotEmpty() ?? false,
        ];
    }

    private function exportSalesDetailCsv($rows, array $summary)
    {
        $headings = ['No Invoice', 'Kode', 'Tanggal', 'Nama Produk', 'Merk', 'Grade', 'Satuan', 'IMEI 1', 'IMEI 2', 'Pelanggan', 'Kasir', 'Sales', 'Modal', 'Harga Jual', 'Laba'];
        $data = [];
        foreach ($rows as $item) {
            $modal     = (float) ($item->hpp_total ?? 0);
            $hargaJual = (float) ($item->subtotal ?? 0);
            $data[] = [
                $item->salesTransaction?->no_invoice,
                $item->product?->barcode,
                $item->salesTransaction?->tanggal?->format('d-m-Y'),
                $item->product?->masterProduct?->nama,
                $item->product?->masterProduct?->brand?->nama,
                $item->product?->grade?->nama,
                $item->product?->unit?->nama,
                $item->product?->imei1,
                $item->product?->imei2,
                $item->salesTransaction?->pelanggan,
                $item->salesTransaction?->user?->name,
                $item->salesTransaction?->salesRep?->nama,
                $modal,
                $hargaJual,
                $hargaJual - $modal,
            ];
        }
        $data[] = [];
        $data[] = ['TOTAL', '', '', '', '', '', '', '', '', '', '', '', $summary['total_modal'], $summary['total_harga_jual'], $summary['total_laba']];

        return Excel::download(new SimpleArrayExport($headings, $data), 'rekap-penjualan-detail.xlsx');
    }

    private function exportPurchasesDetailCsv($rows, array $summary)
    {
        $headings = ['No Invoice', 'Supplier', 'Kode', 'Tanggal', 'Nama Produk', 'Merk', 'Grade', 'Satuan', 'IMEI 1', 'IMEI 2', 'Modal', 'Harga Jual'];
        $data = [];
        foreach ($rows as $item) {
            $hargaJual = $this->resolveHargaJual($item->product);
            $data[] = [
                $item->purchase?->no_invoice,
                $item->purchase?->supplier?->nama,
                $item->product?->barcode,
                $item->purchase?->tanggal?->format('d-m-Y'),
                $item->product?->masterProduct?->nama,
                $item->product?->masterProduct?->brand?->nama,
                $item->product?->grade?->nama,
                $item->product?->unit?->nama,
                $item->product?->imei1,
                $item->product?->imei2,
                (float) $item->harga_beli,
                $hargaJual,
            ];
        }
        $data[] = [];
        $data[] = ['TOTAL', '', '', '', '', '', '', '', '', '', $summary['total_modal'], $summary['total_harga_jual']];

        return Excel::download(new SimpleArrayExport($headings, $data), 'rekap-pembelian-detail.xlsx');
    }

    private function exportSalesDetailHtml($rows, array $summary, array $filters)
    {
        $dateLabel = '';
        if (!empty($filters['start_date']) || !empty($filters['end_date'])) {
            $dateLabel = ' | ' . ($filters['start_date'] ?? '') . ' s/d ' . ($filters['end_date'] ?? '');
        }

        $displayKeys = ['no_invoice', 'kode', 'tanggal', 'nama_produk', 'merk', 'grade', 'satuan', 'imei1', 'imei2', 'pelanggan', 'kasir', 'sales', 'modal', 'harga_jual', 'laba'];
        $displayRows = $rows->map(function ($item) use ($displayKeys) {
            $row = $this->formatSaleDetailRow($item);
            return array_map(fn($k) => $row[$k] ?? '-', $displayKeys);
        })->toArray();

        $html = $this->buildDetailHtml(
            'Rekap Penjualan Detail' . $dateLabel,
            ['No Invoice', 'Kode', 'Tanggal', 'Nama Produk', 'Merk', 'Grade', 'Satuan', 'IMEI 1', 'IMEI 2', 'Pelanggan', 'Kasir', 'Sales', 'Modal', 'Harga Jual', 'Laba'],
            $displayRows,
            [number_format($summary['total_modal'], 0, ',', '.'), number_format($summary['total_harga_jual'], 0, ',', '.'), number_format($summary['total_laba'], 0, ',', '.')]
        );

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function exportPurchasesDetailHtml($rows, array $summary, array $filters)
    {
        $dateLabel = '';
        if (!empty($filters['start_date']) || !empty($filters['end_date'])) {
            $dateLabel = ' | ' . ($filters['start_date'] ?? '') . ' s/d ' . ($filters['end_date'] ?? '');
        }

        $displayKeys = ['no_invoice', 'supplier', 'kode', 'tanggal', 'nama_produk', 'merk', 'grade', 'satuan', 'imei1', 'imei2', 'modal', 'harga_jual'];
        $displayRows = $rows->map(function ($item) use ($displayKeys) {
            $row = $this->formatPurchaseDetailRow($item);
            return array_map(fn($k) => $row[$k] ?? '-', $displayKeys);
        })->toArray();

        $html = $this->buildDetailHtml(
            'Rekap Pembelian Detail' . $dateLabel,
            ['No Invoice', 'Supplier', 'Kode', 'Tanggal', 'Nama Produk', 'Merk', 'Grade', 'Satuan', 'IMEI 1', 'IMEI 2', 'Modal', 'Harga Jual'],
            $displayRows,
            [number_format($summary['total_modal'], 0, ',', '.'), number_format($summary['total_harga_jual'], 0, ',', '.')]
        );

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function buildDetailHtml(string $title, array $headers, array $rows, array $totalValues): string
    {
        $colCount = count($headers);
        $headHtml = implode('', array_map(fn($h) => "<th>{$h}</th>", $headers));
        $bodyHtml = '';
        foreach ($rows as $idx => $row) {
            $tds = implode('', array_map(fn($v) => '<td>' . htmlspecialchars((string) ($v ?? '-')) . '</td>', $row));
            $bodyHtml .= "<tr class=\"" . ($idx % 2 === 0 ? 'even' : '') . "\">{$tds}</tr>";
        }
        $footCols = $colCount - count($totalValues);
        $footHtml = "<td colspan=\"{$footCols}\" class=\"total-label\">TOTAL</td>";
        foreach ($totalValues as $v) {
            $footHtml .= "<td class=\"total-val\">{$v}</td>";
        }

        return "<!DOCTYPE html><html lang=\"id\"><head><meta charset=\"UTF-8\">
<title>{$title}</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Arial,sans-serif;font-size:9px;color:#111}
h1{font-size:13px;margin-bottom:4px}
.sub{font-size:9px;color:#555;margin-bottom:10px}
table{width:100%;border-collapse:collapse}
th,td{border:1px solid #ccc;padding:2px 4px;white-space:nowrap}
th{background:#1e3a5f;color:#fff;font-size:8px}
tr.even td{background:#f4f7fb}
tfoot td{background:#e8f0fe;font-weight:bold}
.total-label{text-align:right}
.total-val{text-align:right}
@media print{@page{size:A4 landscape;margin:8mm}button{display:none}}
</style></head><body>
<h1>{$title}</h1>
<p class=\"sub\">Dicetak: " . now()->format('d-m-Y H:i') . " | Total: " . count($rows) . " baris</p>
<button onclick=\"window.print()\" style=\"margin-bottom:8px;padding:4px 10px;background:#1e3a5f;color:white;border:none;cursor:pointer;border-radius:3px\">Cetak / Simpan PDF</button>
<table><thead><tr>{$headHtml}</tr></thead><tbody>{$bodyHtml}</tbody>
<tfoot><tr>{$footHtml}</tr></tfoot></table>
</body></html>";
    }

    private function validateReportRequest(Request $request, bool $withType = false): array
    {
        $rules = [
            'search' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'export' => ['nullable', 'in:excel,pdf'],
            'sales_rep_id' => ['nullable', 'exists:sales_reps,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'per_page' => ['nullable', 'integer'],
        ];

        if ($withType) {
            $rules['tipe'] = ['nullable', 'in:all,penjualan,service'];
        }

        return $request->validate($rules);
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 10);
        // Allow -1 for export (get all data)
        if ($perPage === -1) {
            return 100000;
        }
        if (!in_array($perPage, [10, 50, 100], true)) {
            $perPage = 10;
        }
        return $perPage;
    }

    private function applySalesReportFilters($query, array $filters): void
    {
        $tipe = $filters['tipe'] ?? 'all';
        $salesRepId = $filters['sales_rep_id'] ?? null;

        $query
            ->search($filters['search'] ?? null)
            ->dateRange($filters['start_date'] ?? null, $filters['end_date'] ?? null)
            ->when($tipe !== 'all', fn($q) => $q->where('tipe', $tipe))
            ->when($salesRepId, fn($q) => $q->where('sales_rep_id', $salesRepId));
    }

    private function buildSalesSummary(array $filters): array
    {
        $base = SalesTransaction::query();
        $this->applySalesReportFilters($base, $filters);
        $grandTotal = (float) (clone $base)->sum('grand_total');

        $qtyTotal = (int) SaleItem::query()
            ->whereHas('salesTransaction', fn($q) => $this->applySalesReportFilters($q, $filters))
            ->sum('qty');

        $hppTotal = (float) SaleItem::query()
            ->whereHas('salesTransaction', fn($q) => $this->applySalesReportFilters($q, $filters))
            ->sum('hpp_total');

        return [
            'qty_total' => $qtyTotal,
            'grand_total' => $grandTotal,
            'hpp_total' => $hppTotal,
            'laba_kotor' => $grandTotal - $hppTotal,
        ];
    }

    private function exportSalesCsv($rows, array $summary)
    {
        $headings = ['No Invoice', 'Tanggal', 'Pelanggan', 'Kasir', 'Sales', 'Qty', 'Total Bayar', 'Modal (HPP)', 'Laba'];
        $data = [];
        foreach ($rows as $item) {
            $hpp        = (float) ($item->hpp_total ?? 0);
            $grandTotal = (float) $item->grand_total;
            $data[] = [
                $item->no_invoice,
                optional($item->tanggal)->format('d-m-Y'),
                $item->pelanggan,
                $item->user?->name,
                $item->salesRep?->nama,
                (int) ($item->qty_total ?? 0),
                $grandTotal,
                $hpp,
                $grandTotal - $hpp,
            ];
        }
        $data[] = [];
        $data[] = ['TOTAL', '', '', '', '', (int) ($summary['qty_total'] ?? 0), (float) ($summary['grand_total'] ?? 0), (float) ($summary['hpp_total'] ?? 0), (float) ($summary['laba_kotor'] ?? 0)];

        return Excel::download(new SimpleArrayExport($headings, $data), 'laporan-penjualan.xlsx');
    }

    private function exportPurchasesCsv($rows)
    {
        $headings = ['No Invoice', 'Tanggal', 'Supplier', 'Kasir', 'Jumlah Item', 'Total', 'Keterangan'];
        $data = [];
        foreach ($rows as $item) {
            $data[] = [
                $item->no_invoice,
                optional($item->tanggal)->format('d-m-Y'),
                $item->supplier?->nama,
                $item->user?->name,
                (int) ($item->items_count ?? 0),
                (float) $item->total,
                $item->keterangan,
            ];
        }

        return Excel::download(new SimpleArrayExport($headings, $data), 'laporan-pembelian.xlsx');
    }

    private function exportProfitCsv($rows)
    {
        $headings = ['No Invoice', 'Tanggal', 'Tipe', 'Kasir', 'Pendapatan', 'HPP', 'Laba Kotor'];
        $data = [];
        foreach ($rows as $item) {
            $hpp        = (float) ($item->hpp_total ?? 0);
            $pendapatan = (float) $item->grand_total;
            $data[] = [
                $item->no_invoice,
                optional($item->tanggal)->format('d-m-Y'),
                $item->tipe ?? 'penjualan',
                $item->user?->name,
                $pendapatan,
                $hpp,
                $pendapatan - $hpp,
            ];
        }

        return Excel::download(new SimpleArrayExport($headings, $data), 'laporan-laba-rugi-hpp.xlsx');
    }
}
