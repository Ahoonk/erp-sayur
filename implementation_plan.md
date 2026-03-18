# 🏗️ Implementation Plan — App Kasir (POS Toko Ponsel)

Rebuild aplikasi POS CV DHAROES PUTRA menggunakan arsitektur Laravel 12 + Vue.js 3 yang sudah ada, dengan peningkatan fitur signifikan.

## User Review Required

> [!IMPORTANT]
> **Skala project ini sangat besar** (~80+ file baru, 14 tabel database). Saya merekomendasikan implementasi **bertahap per batch** agar bisa di-review dan di-test secara incremental. Berikut urutan batch yang saya usulkan:

| Batch | Modul                                                                 | Estimasi            |
| ----- | --------------------------------------------------------------------- | ------------------- |
| **1** | Database Migrations + Models + Seeders                                | Fondasi semua tabel |
| **2** | Master Data CRUD (Brand, Category, Grade, Unit, Sales, Supplier, Tax) | Backend + Frontend  |
| **3** | Purchase Invoice (Pembelian) + Auto Barcode                           | Backend + Frontend  |
| **4** | Sales Transaction (Penjualan) + Pajak + Diskon                        | Backend + Frontend  |
| **5** | Service HP (Repair)                                                   | Backend + Frontend  |
| **6** | Reports & Dashboard + Barcode Print                                   | Backend + Frontend  |
| **7** | Role permissions update (kasir role) + Polish                         | Finishing           |

> [!WARNING]
> **Breaking changes**: Role `user` akan diganti menjadi `kasir`. Semua permission akan di-reset via seeder baru. Data user lama yang memiliki role `user` perlu di-migrate manual.

> [!CAUTION]
> **`migrate:fresh` diperlukan** — Karena ini rebuild, saya akan menambah banyak migration baru. Seeder lama akan di-update. Data existing di database development akan hilang.

---

## Arsitektur & Pola yang Diikuti

Mengikuti pattern yang sudah ada di codebase:

- **Repository Pattern**: Interface → Repository → Controller
- **UUID** sebagai Primary Key (trait `UUID`)
- **Spatie Permission** untuk roles & permissions
- **`ResponseHelper`** untuk standardisasi JSON response
- **`PaginateResource`** untuk pagination format
- **Form Request** untuk validasi
- **API Resource** untuk transformasi data
- **Vue.js 3 + Pinia + TailwindCSS** untuk frontend

---

## Proposed Changes

### Batch 1: Database Foundation [✅ COMPLETED]

Semua tabel menggunakan UUID. Relasi menggunakan `foreignUuid()`.

---

#### [NEW] [2026_02_25_000001_create_brands_table.php](file:///c:/laragon/www/app-kasir/database/migrations/2026_02_25_000001_create_brands_table.php)

```php
Schema: id (uuid), nama (string), created_at, updated_at, deleted_at
```

#### [NEW] [2026_02_25_000002_create_categories_table.php](file:///c:/laragon/www/app-kasir/database/migrations/2026_02_25_000002_create_categories_table.php)

```php
Schema: id (uuid), nama (string), created_at, updated_at, deleted_at
```

#### [NEW] [2026_02_25_000003_create_grades_table.php](file:///c:/laragon/www/app-kasir/database/migrations/2026_02_25_000003_create_grades_table.php)

```php
Schema: id (uuid), nama (string), keterangan (text, nullable), created_at, updated_at, deleted_at
```

#### [NEW] [2026_02_25_000004_create_units_table.php](file:///c:/laragon/www/app-kasir/database/migrations/2026_02_25_000004_create_units_table.php)

Tabel satuan barang (Pcs, Unit, dll).

```php
Schema: id (uuid), nama (string), created_at, updated_at, deleted_at
```

#### [NEW] [2026_02_25_000005_create_sales_reps_table.php](file:///c:/laragon/www/app-kasir/database/migrations/2026_02_25_000005_create_sales_reps_table.php)

```php
Schema: id (uuid), nama (string), no_hp (string, nullable), alamat (text, nullable), created_at, updated_at, deleted_at
```

#### [NEW] [2026_02_25_000006_create_suppliers_table.php](file:///c:/laragon/www/app-kasir/database/migrations/2026_02_25_000006_create_suppliers_table.php)

```php
Schema: id (uuid), nama (string), phone (string, nullable), email (string, nullable), alamat (text, nullable), created_at, updated_at, deleted_at
```

#### [NEW] [2026_02_25_000007_create_taxes_table.php](file:///c:/laragon/www/app-kasir/database/migrations/2026_02_25_000007_create_taxes_table.php)

Master data pajak yang bisa dipilih saat transaksi.

```php
Schema: id (uuid), nama (string, e.g. "PPN"), persentase (decimal 5,2), is_default (boolean, default false), is_active (boolean, default true), created_at, updated_at, deleted_at
```

#### [NEW] [2026_02_25_000008_create_products_table.php](file:///c:/laragon/www/app-kasir/database/migrations/2026_02_25_000008_create_products_table.php)

```php
Schema: id (uuid), barcode (string, unique, auto-generated), nama (string),
    brand_id (foreignUuid → brands), category_id (foreignUuid → categories),
    grade_id (foreignUuid → grades, nullable), unit_id (foreignUuid → units),
    imei1 (string, nullable, unique), imei2 (string, nullable, unique),
    harga_modal (decimal 15,2), harga_jual (decimal 15,2),
    stok (integer, default 1), keterangan (text, nullable), foto (string, nullable),
    created_at, updated_at, deleted_at
```

#### [NEW] [2026_02_25_000009_create_purchases_table.php](file:///c:/laragon/www/app-kasir/database/migrations/2026_02_25_000009_create_purchases_table.php)

Header invoice pembelian.

```php
Schema: id (uuid), no_invoice (string, unique), tanggal (date),
    supplier_id (foreignUuid → suppliers), user_id (foreignUuid → users, pencatat),
    total (decimal 15,2, default 0), keterangan (text, nullable),
    created_at, updated_at, deleted_at
```

#### [NEW] [2026_02_25_000010_create_purchase_items_table.php](file:///c:/laragon/www/app-kasir/database/migrations/2026_02_25_000010_create_purchase_items_table.php)

Detail item per invoice pembelian. Saat item di-add, otomatis generate barcode & buat record di `products`.

```php
Schema: id (uuid), purchase_id (foreignUuid → purchases),
    product_id (foreignUuid → products),
    qty (integer, default 1), harga_beli (decimal 15,2), subtotal (decimal 15,2),
    created_at, updated_at
```

#### [NEW] [2026_02_25_000011_create_sales_transactions_table.php](file:///c:/laragon/www/app-kasir/database/migrations/2026_02_25_000011_create_sales_transactions_table.php)

```php
Schema: id (uuid), no_invoice (string, unique), tanggal (date),
    pelanggan (string, nullable), user_id (foreignUuid → users, kasir),
    sales_rep_id (foreignUuid → sales_reps, nullable),
    subtotal (decimal 15,2), diskon_persen (decimal 5,2, default 0),
    diskon_nominal (decimal 15,2, default 0),
    tax_id (foreignUuid → taxes, nullable), tax_persen (decimal 5,2, default 0),
    tax_nominal (decimal 15,2, default 0),
    grand_total (decimal 15,2),
    tipe (enum: 'penjualan','service', default 'penjualan'),
    created_at, updated_at, deleted_at
```

#### [NEW] [2026_02_25_000012_create_sale_items_table.php](file:///c:/laragon/www/app-kasir/database/migrations/2026_02_25_000012_create_sale_items_table.php)

```php
Schema: id (uuid), sales_transaction_id (foreignUuid → sales_transactions),
    product_id (foreignUuid → products),
    qty (integer, default 1), harga_satuan (decimal 15,2), subtotal (decimal 15,2),
    created_at, updated_at
```

#### [NEW] [2026_02_25_000013_create_service_orders_table.php](file:///c:/laragon/www/app-kasir/database/migrations/2026_02_25_000013_create_service_orders_table.php)

Tabel khusus untuk order service HP.

```php
Schema: id (uuid), sales_transaction_id (foreignUuid → sales_transactions),
    nama_pelanggan (string), no_hp_pelanggan (string, nullable),
    merk_hp (string), tipe_hp (string), kerusakan (text),
    imei_hp (string, nullable), kelengkapan (text, nullable),
    biaya_jasa (decimal 15,2, default 0),
    status (enum: 'pending','dikerjakan','selesai','diambil','batal', default 'pending'),
    tanggal_masuk (date), tanggal_selesai (date, nullable),
    catatan_teknisi (text, nullable),
    created_at, updated_at, deleted_at
```

#### [NEW] [2026_02_25_000014_create_service_parts_table.php](file:///c:/laragon/www/app-kasir/database/migrations/2026_02_25_000014_create_service_parts_table.php)

Sparepart yang dipakai dalam service.

```php
Schema: id (uuid), service_order_id (foreignUuid → service_orders),
    product_id (foreignUuid → products, nullable), nama_part (string),
    qty (integer, default 1), harga_satuan (decimal 15,2), subtotal (decimal 15,2),
    created_at, updated_at
```

---

### Batch 1: Models (14 model baru)

Setiap model menggunakan trait `UUID`, `SoftDeletes` (kecuali pivot), dan memiliki relasi Eloquent yang sesuai.

#### [NEW] Models

| Model              | File                                                                                     |
| ------------------ | ---------------------------------------------------------------------------------------- |
| `Brand`            | [Brand.php](file:///c:/laragon/www/app-kasir/app/Models/Brand.php)                       |
| `Category`         | [Category.php](file:///c:/laragon/www/app-kasir/app/Models/Category.php)                 |
| `Grade`            | [Grade.php](file:///c:/laragon/www/app-kasir/app/Models/Grade.php)                       |
| `Unit`             | [Unit.php](file:///c:/laragon/www/app-kasir/app/Models/Unit.php)                         |
| `SalesRep`         | [SalesRep.php](file:///c:/laragon/www/app-kasir/app/Models/SalesRep.php)                 |
| `Supplier`         | [Supplier.php](file:///c:/laragon/www/app-kasir/app/Models/Supplier.php)                 |
| `Tax`              | [Tax.php](file:///c:/laragon/www/app-kasir/app/Models/Tax.php)                           |
| `Product`          | [Product.php](file:///c:/laragon/www/app-kasir/app/Models/Product.php)                   |
| `Purchase`         | [Purchase.php](file:///c:/laragon/www/app-kasir/app/Models/Purchase.php)                 |
| `PurchaseItem`     | [PurchaseItem.php](file:///c:/laragon/www/app-kasir/app/Models/PurchaseItem.php)         |
| `SalesTransaction` | [SalesTransaction.php](file:///c:/laragon/www/app-kasir/app/Models/SalesTransaction.php) |
| `SaleItem`         | [SaleItem.php](file:///c:/laragon/www/app-kasir/app/Models/SaleItem.php)                 |
| `ServiceOrder`     | [ServiceOrder.php](file:///c:/laragon/www/app-kasir/app/Models/ServiceOrder.php)         |
| `ServicePart`      | [ServicePart.php](file:///c:/laragon/www/app-kasir/app/Models/ServicePart.php)           |

---

### Batch 1: Seeders

#### [MODIFY] [RoleSeeder.php](file:///c:/laragon/www/app-kasir/database/seeders/RoleSeeder.php)

Tambah role `kasir` (menggantikan `user`) dan permissions baru:

```
Permissions baru:
  - view products, create products, edit products, delete products
  - view purchases, create purchases, edit purchases, delete purchases
  - view sales, create sales, edit sales, delete sales
  - view services, create services, edit services, delete services
  - view reports
  - view master-data, create master-data, edit master-data, delete master-data
  - print barcode

Role mappings:
  super-admin → semua permission
  admin → semua kecuali manage roles/permissions
  kasir → view products, create sales, create services, view own reports, print barcode
```

#### [NEW] [MasterDataSeeder.php](file:///c:/laragon/www/app-kasir/database/seeders/MasterDataSeeder.php)

Seed data awal: beberapa brand, kategori, grade, unit, dan 1 default tax (PPN 11%).

---

### Batch 2: Master Data CRUD [✅ COMPLETED]

Setiap master data mengikuti pattern: **Interface → Repository → Controller → FormRequest → Resource**.

7 modul master data yang identik pattern-nya:

| Modul    | Controller           | Repository           | Interface                     | Request           | Resource           |
| -------- | -------------------- | -------------------- | ----------------------------- | ----------------- | ------------------ |
| Brand    | `BrandController`    | `BrandRepository`    | `BrandRepositoryInterface`    | `BrandRequest`    | `BrandResource`    |
| Category | `CategoryController` | `CategoryRepository` | `CategoryRepositoryInterface` | `CategoryRequest` | `CategoryResource` |
| Grade    | `GradeController`    | `GradeRepository`    | `GradeRepositoryInterface`    | `GradeRequest`    | `GradeResource`    |
| Unit     | `UnitController`     | `UnitRepository`     | `UnitRepositoryInterface`     | `UnitRequest`     | `UnitResource`     |
| SalesRep | `SalesRepController` | `SalesRepRepository` | `SalesRepRepositoryInterface` | `SalesRepRequest` | `SalesRepResource` |
| Supplier | `SupplierController` | `SupplierRepository` | `SupplierRepositoryInterface` | `SupplierRequest` | `SupplierResource` |
| Tax      | `TaxController`      | `TaxRepository`      | `TaxRepositoryInterface`      | `TaxRequest`      | `TaxResource`      |

Semua controller punya: `index` (paginated + search), `store`, `show`, `update`, `destroy`.

Semua endpoint ada **Quick Create** endpoint — `POST /api/{resource}/quick` — yang menerima hanya `nama` dan return data baru, untuk fitur **"add langsung tanpa ke master data"**.

#### [MODIFY] [api.php](file:///c:/laragon/www/app-kasir/routes/api.php)

Tambah route untuk semua modul baru.

#### [MODIFY] [RepositoryServiceProvider.php](file:///c:/laragon/www/app-kasir/app/Providers/RepositoryServiceProvider.php)

Bind semua interface baru ke implementasi repository-nya.

---

### Batch 2: Master Data CRUD (Frontend)

Setiap master data halaman Vue.js yang identik pattern-nya:

#### [NEW] Vue Pages (di `frontend/frontend-starter/src/views/masterdata/`)

| File                   | Deskripsi                       |
| ---------------------- | ------------------------------- |
| `BrandListView.vue`    | CRUD Merk + pagination + search |
| `CategoryListView.vue` | CRUD Kategori                   |
| `GradeListView.vue`    | CRUD Grade                      |
| `UnitListView.vue`     | CRUD Satuan                     |
| `SalesRepListView.vue` | CRUD Sales                      |
| `SupplierListView.vue` | CRUD Supplier                   |
| `TaxListView.vue`      | CRUD Pajak                      |

#### [NEW] Reusable Components (di `frontend/frontend-starter/src/components/`)

| File                  | Deskripsi                                                         |
| --------------------- | ----------------------------------------------------------------- |
| `DataTable.vue`       | Komponen tabel reusable dengan pagination, search, filter tanggal |
| `QuickAddModal.vue`   | Modal popup kecil untuk "add langsung" dari form lain             |
| `ConfirmDialog.vue`   | Dialog konfirmasi delete                                          |
| `DateRangeFilter.vue` | Filter tanggal rentang (seperti mutasi bank)                      |

---

### Batch 3: Purchase Invoice + Auto Barcode [✅ COMPLETED]

**Update 2026-02-25:**
Semua bug mayor di Batch 3 telah diselesaikan:

- Upload foto barang (fixed axios multipart issue)
- Image Lightbox modal untuk preview
- Backend validation menggunakan Form Requests (Security)
- Sinkronisasi waktu lokal (Date bug)
- Perbaikan alignment UI

#### [NEW] Backend Files

| File                          | Deskripsi                                                                   |
| ----------------------------- | --------------------------------------------------------------------------- |
| `PurchaseController`          | CRUD purchase invoice                                                       |
| `PurchaseRepository`          | Logic: create purchase → add items → auto-generate barcode → create product |
| `PurchaseRepositoryInterface` | Contract                                                                    |
| `PurchaseResource`            | Transform termasuk items & products                                         |
| `PurchaseRequest`             | Validasi: no_invoice, tanggal, supplier_id, items[]                         |
| `BarcodeService`              | Service untuk generate barcode unik (format: `BG-YYYYMMDD-XXXXX`)           |

**Flow auto-barcode**: Saat `PurchaseItem` ditambahkan → `BarcodeService` generate barcode unik → `Product` dibuat otomatis dengan barcode tersebut.

#### [NEW] Frontend Files (di `views/purchase/`)

| File                     | Deskripsi                                                                               |
| ------------------------ | --------------------------------------------------------------------------------------- |
| `PurchaseListView.vue`   | List invoce pembelian + pagination + filter tanggal                                     |
| `PurchaseCreateView.vue` | Form: input header invoice lalu add product satu-satu. Termasuk QuickAdd untuk supplier |
| `PurchaseDetailView.vue` | Detail invoice + tombol print barcode                                                   |
| `BarcodePrintView.vue`   | Halaman cetak semua barcode dari 1 invoice                                              |

---

### Batch 4: Sales Transaction + Pajak [🚧 IN PROGRESS]

#### [NEW] Backend Files

| File                                  | Deskripsi                                                                                            |
| ------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| `SalesTransactionController`          | CRUD transaksi penjualan                                                                             |
| `SalesTransactionRepository`          | Logic: create sale → add items (cari produk via barcode/IMEI) → hitung pajak & diskon → kurangi stok |
| `SalesTransactionRepositoryInterface` | Contract                                                                                             |
| `SalesTransactionResource`            | Transform termasuk items, kasir, sales rep                                                           |
| `SalesTransactionRequest`             | Validasi: cart items, pelanggan, sales_rep, tax, diskon                                              |
| `ProductSearchController`             | Endpoint pencarian produk via barcode / IMEI / nama                                                  |

#### [NEW] Frontend Files (di `views/sales/`)

| File                  | Deskripsi                                                     |
| --------------------- | ------------------------------------------------------------- |
| `SalesListView.vue`   | Riwayat transaksi + pagination + filter tanggal + filter tipe |
| `SalesCreateView.vue` | POS form: scan barcode → cart → pilih pajak → diskon → bayar  |
| `SalesDetailView.vue` | Detail transaksi + cetak nota                                 |

---

### Batch 5: Service HP (Backend)

#### [NEW] Backend Files

| File                              | Deskripsi                                             |
| --------------------------------- | ----------------------------------------------------- |
| `ServiceOrderController`          | CRUD service order                                    |
| `ServiceOrderRepository`          | Logic: create service → add sparepart → update status |
| `ServiceOrderRepositoryInterface` | Contract                                              |
| `ServiceOrderResource`            | Transform termasuk parts & transaction                |
| `ServiceOrderRequest`             | Validasi data HP, kerusakan, biaya jasa, dll          |

#### [NEW] Frontend Files (di `views/service/`)

| File                    | Deskripsi                                                        |
| ----------------------- | ---------------------------------------------------------------- |
| `ServiceListView.vue`   | List service orders + filter status + pagination                 |
| `ServiceCreateView.vue` | Form: data HP pelanggan → kerusakan → biaya jasa → add sparepart |
| `ServiceDetailView.vue` | Detail service + update status + print nota                      |

---

### Batch 6: Reports & Dashboard

#### [NEW] Backend Files

| File                  | Deskripsi                                                                    |
| --------------------- | ---------------------------------------------------------------------------- |
| `ReportController`    | Endpoint laporan: penjualan, pembelian, per sales, laba rugi                 |
| `DashboardController` | Statistik: total penjualan hari ini, bulan ini, stok rendah, service pending |

Semua report:

- Filter: tanggal mulai, tanggal akhir, bulan, tahun (seperti mutasi bank)
- Pagination
- Summary row (total modal, total penjualan, total laba)

#### [NEW] Frontend Files

| File                                  | Deskripsi                               |
| ------------------------------------- | --------------------------------------- |
| `views/report/SalesReportView.vue`    | Laporan penjualan + filter mutasi style |
| `views/report/PurchaseReportView.vue` | Laporan pembelian                       |
| `views/report/SalesPerRepView.vue`    | Penjualan per sales                     |
| `views/report/ProfitReportView.vue`   | Laporan laba rugi                       |

#### [MODIFY] [DashboardHome.vue](file:///c:/laragon/www/app-kasir/frontend/frontend-starter/src/views/DashboardHome.vue)

Redesign menjadi dashboard POS: statistik cards, chart penjualan, quick links.

#### [MODIFY] [DashboardView.vue](file:///c:/laragon/www/app-kasir/frontend/frontend-starter/src/views/DashboardView.vue)

Update sidebar navigation menjadi menu POS lengkap (dengan dropdown/collapsible).

---

### Batch 7: Role & Permission Update

#### [MODIFY] [RoleSeeder.php](file:///c:/laragon/www/app-kasir/database/seeders/RoleSeeder.php)

Final role configuration:

- **Super Admin**: Full access
- **Admin**: Semua kecuali manage roles/permissions
- **Kasir**: Create sales, create service, view products, print barcode, view own reports

#### [MODIFY] [DashboardView.vue](file:///c:/laragon/www/app-kasir/frontend/frontend-starter/src/views/DashboardView.vue)

Sidebar menu item visibility berdasarkan role.

#### [MODIFY] [router/index.js](file:///c:/laragon/www/app-kasir/frontend/frontend-starter/src/router/index.js)

Tambah semua route baru + meta permission guards.

---

## Verification Plan

### Automated Tests

Karena saat ini project belum memiliki test yang meaningful (hanya file template kosong di `tests/Feature` dan `tests/Unit`), saya akan menambahkan:

1. **Migration test** — Memastikan semua migration bisa `migrate:fresh` tanpa error:

```bash
php artisan migrate:fresh --seed
```

2. **Seeder test** — Memastikan role & permission terisi correct:

```bash
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=MasterDataSeeder
```

3. **API endpoint test** (per batch) — Menggunakan browser subagent untuk call API setelah setiap batch selesai.

### Manual Verification

Setelah setiap batch, saya akan:

1. **Jalankan `migrate:fresh --seed`** untuk memastikan database schema benar
2. **Test endpoint API** via browser/curl untuk memastikan CRUD berjalan
3. **Buka frontend** di browser untuk verifikasi visual halaman baru
4. **Test flow end-to-end** (terutama untuk Batch 3-5):
    - Buat purchase invoice → verify barcode auto-generated → print barcode
    - Buat transaksi penjualan → scan barcode → tambah pajak → verify stok berkurang
    - Buat service order → tambah sparepart → update status

> [!NOTE]
> Saya akan mulai implementasi dari **Batch 1** (database foundation) setelah plan ini disetujui. Setiap batch akan saya implementasi, test, dan tunjukkan hasilnya sebelum lanjut ke batch berikutnya.
