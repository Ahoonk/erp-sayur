# App Kasir Walkthrough

Last update: 2026-02-27 (WIB, malam lanjut)

## 1. Product Direction
App Kasir is a retail + service system for gadget stores:
- Purchase and stock management
- POS sales and invoice
- Service intake, sparepart usage, payment finalization, and service invoice
- Role-based operations (super admin, admin/owner, kasir)

## 2. Current Architecture State
- Backend: Laravel repositories + resources + permission middleware
- Frontend: Vue 3 + Pinia + route-based module pages
- Stock logic: FIFO for non-serialized products, per-unit handling for serialized products

## 3. What Has Been Delivered

### Master and Catalog
- Relational master data for product catalog
- Quick-add references from forms to avoid context switching

### Purchase and Inventory
- Purchase invoice flow with item-level stock entries
- Barcode handling aligned to product type
- Stock summary grouped by SKU
- SKU detail modal to inspect underlying lots

### Sales
- POS input with payment and discount
- Sales transaction listing and detailed modal
- Invoice print flow
- HPP allocation and subtotal_hpp tracking

### Service
- Service intake and progress states
- Sparepart stock deduction/restoration rules
- Cancel process with explicit stock restoration option
- Final payment flow:
  1) set status selesai
  2) input payment (discount + method + paid amount)
  3) system creates/updates service transaction
  4) system auto-sets delivered status
  5) invoice auto-opens for print
  6) payment panel locked after finalization

- Service transaction page:
  - separated in sidebar
  - uses service-only data
  - shows sparepart usage summary
  - detail modal adapted for service
  - detail modal no longer forces IMEI for non-serialized items
  - service jasa row shown without qty
  - data service list table alignment fixed (aksi and estimasi column)
  - date range filter now blocks end date earlier than start date

### Date and Time Consistency
- Backend timezone source is now env-driven (`APP_TIMEZONE`, currently `Asia/Jakarta`).
- Service create default date now follows local browser date, not UTC string conversion.
- Sales invoice print date now formatted from raw `YYYY-MM-DD` string, avoiding timezone shifts.

### Invoice Item Identifier Rules
- Invoice/detail rendering now follows product `identifier_type`:
  - `none`: no IMEI/SN line
  - `imei1`: show one IMEI line
  - `imei2`: show IMEI 1 and IMEI 2 if available
  - `serial`: show serial number line

### Reporting (Implemented)
- API report endpoints now available:
  - `/api/reports/sales` (includes POS + service, with `tipe` filter)
  - `/api/reports/purchases`
  - `/api/reports/profit`
- Common capabilities per report:
  - date range filter with validation (`end_date >= start_date`)
  - search
  - pagination (10/50/100)
  - excel export (`export=excel`, CSV)
- Frontend report pages are now functional:
  - table list with live filters
  - export PDF via print layout
  - export Excel button
- Revisi terbaru laporan penjualan & service:
  - added `Sales` filter in backend and frontend (`sales_rep_id`)
  - table structure changed to match requested layout:
    - No, No.Invoice, Tanggal, Pelanggan, Kasir, Sales, Qty, Total Bayar, Modal, Laba
  - removed subtotal column from list
  - added dynamic bottom `TOTAL` row (qty, total bayar, modal/hpp, laba)
  - totals recalculate based on active filters (tanggal, search, tipe, sales)
  - export outputs (Excel/PDF) aligned with the new report structure

### Dashboard
- Backend summary endpoint and frontend dashboard cards/charts
- Store branding in sidebar (logo + store name)

## 4. Open Items
- Formal testing pass for edge cases:
  - overpayment and change calculation
  - service cancel after stock operations
  - report export validation on large datasets (performance and completeness)
- Finish full table visual consistency pass on legacy pages that still mix old classes and new table classes

## 5. Suggested Next Focus
1. Lock in report pages (sales, purchase, laba rugi/HPP) with verified service contribution.
2. Add a compact QA checklist and run through all critical transaction states.
3. Standardize shared currency input behavior as a reusable component.
