# App Kasir Task Tracker

Last update: 2026-02-27 (WIB, malam lanjut)
Current phase: Reporting refinement and UI consistency hardening

## Completed

### Core and Master Data
- Database foundations (UUID models, roles, permissions, master tables)
- Master data CRUD modules (brand, category, grade, unit, supplier, sales rep, tax)
- Quick-add modal flow for master references

### Purchase and Stock
- Purchase header and item flow with invoice generation
- Catalog-based purchase input (category -> brand -> grade -> product)
- Barcode generation strategy by product type
- Stock summary by SKU (grouped)
- SKU detail modal in stock summary (lot-level list, invoice, supplier, grade, buy/sell price)

### Sales and POS
- POS transaction flow with discount, payment method, cashier handling
- Sales list, filters, stats, and invoice print
- FIFO HPP allocation for non-serialized stock

### Dashboard and Sidebar
- Dashboard home cards and charts from backend summary endpoint
- Sidebar logo and store name loaded from store settings

### Service Module
- Service create/list/detail/edit/print flows
- Sparepart suggestion and add/remove improvements
- Cancel flow with modal option to restore stock or not
- Status safeguard: cannot move from selesai back to dikerjakan
- Service payment panel with discount and payment method
- Discount mode supports percentage or nominal
- Numeric inputs formatted with Indonesian thousands separator
- Final payment locks transaction, auto marks as delivered, and auto opens invoice print
- Detail page print button hidden when service already selesai
- Service transaction menu added
- Service transaction list uses sales endpoint with tipe=service
- Service transaction list removes sales filter/column, shows sparepart summary
- Service transaction detail modal enlarged and adapted for service context
- Service invoice aligned to sales invoice template and includes service-specific rows
- No service display standardized to short UUID (8 chars)
- Service list width aligned with transaction list container style
- Service print invoice: IMEI block removed from sparepart rows
- Service print/detail invoice: biaya jasa no longer uses qty
- Service list table alignment fix:
  - kolom `Aksi` dirapikan agar icon tidak tumpang tindih
  - kolom `Estimasi` header dan isi disejajarkan
  - filter tanggal `sampai` tidak bisa lebih kecil dari `mulai`

### Timezone and Date Consistency
- Laravel app timezone now reads `APP_TIMEZONE` from environment
- Frontend default `tanggal_masuk` on Service Create now uses local date (non-UTC)
- Sales invoice print date rendering no longer uses JS `Date` parsing (prevents UTC day-shift)

### Invoice Identifier Logic
- Sales API now includes `identifier_type` in item product payload
- Sales invoice and transaction detail only show IMEI/SN when product type requires it

### Reporting Module (NEW)
- Backend endpoint laporan:
  - `GET /api/reports/sales`
  - `GET /api/reports/purchases`
  - `GET /api/reports/profit`
- Semua endpoint support:
  - filter tanggal (`start_date`, `end_date`)
  - validasi tanggal akhir `after_or_equal` tanggal mulai
  - search
  - pagination (`per_page`: 10/50/100)
  - export excel (`export=excel`, CSV UTF-8 BOM)
- Frontend pages laporan sekarang aktif penuh (bukan placeholder):
  - [Laporan Penjualan & Service] dengan kolom tipe transaksi
  - Laporan Pembelian
  - Laba Rugi / HPP
- Tiap halaman laporan sudah ada:
  - filter tanggal + search + pagination
  - Export PDF (print layout dari data terfilter)
  - Export Excel (CSV)
- Revisi khusus `Laporan Penjualan & Service` (mengikuti contoh user):
  - tambah filter `Sales` (`sales_rep_id`)
  - kolom tabel diubah ke: `No`, `No.Invoice`, `Tanggal`, `Pelanggan`, `Kasir`, `Sales`, `Qty`, `Total Bayar`, `Modal`, `Laba`
  - `Subtotal` dihapus dari list
  - baris `TOTAL` ditambahkan di bawah tabel
  - nilai `TOTAL` berubah dinamis sesuai filter tanggal/search/tipe/sales
  - summary total juga ikut ke export Excel dan PDF

## In Progress
- Audit konsistensi alignment semua tabel halaman utama (header vs body, nominal, kolom aksi sticky)
- Penyelarasan style list lama yang belum full pakai kelas table global

## Next
- Add regression test checklist for service final payment and lock behavior
- Revisi lanjutan tampilan laporan (visual parity 1:1 sesuai contoh: spacing, density, warna baris total)
- Upgrade export PDF dari print layout browser ke generator PDF server-side (opsional, jika diperlukan produksi)
- Optional UI polish: unify currency input component across POS, service, purchase
