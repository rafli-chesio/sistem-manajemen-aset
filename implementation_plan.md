# Rancangan Overhaul Sistem Manajemen Aset Sekolah

Berdasarkan feedback Kaprodi, sistem akan dirombak secara menyeluruh. Fokus utama adalah memperbaiki konsistensi data, logika bisnis yang realistis, dan kualitas UI/UX agar sesuai standar aplikasi sekolah yang sesungguhnya.

## Analisis Kondisi Saat Ini vs. Target

| Aspek | Kondisi Saat Ini | Target |
|---|---|---|
| Role naming | `kajur`, `super_admin` (via Spatie) | `ADMIN`, `KAJUR`, `VIEWER` (simplified) |
| Asset condition | `GOOD`, `FAIR`, `POOR`, `DAMAGED` | `BAIK`, `RUSAK_RINGAN`, `RUSAK_BERAT` |
| Asset status | `AVAILABLE`, `BORROWED`, `DAMAGED`, `LOST` | `AVAILABLE`, `BORROWED`, `MAINTENANCE`, `LOST`, `ARCHIVED` |
| Asset type | `UNIQUE`, `CONSUMABLE` | `FIXED`, `CONSUMABLE` |
| Transaksi consumable | Tidak terpisah | Tabel `stock_usages` sendiri |
| Quick Search | Tidak ada di dashboard | Search bar di dashboard |
| QR Code | Tidak ada | Generate & scan QR per aset |
| Import Excel | Tidak ada | Upload & bulk import |
| Laporan | Basic | Lengkap dengan export PDF & Excel |

## Open Questions

> [!IMPORTANT]
> **Database Production**: Feedback poin 7 menyarankan PostgreSQL. Namun `.env` Anda saat ini menggunakan MySQL. Apakah kita tetap pakai **MySQL** (sudah jalan, lebih familiar) atau pindah ke **PostgreSQL**? Saya rekomendasikan tetap MySQL agar tidak menambah kompleksitas.

> [!IMPORTANT]
> **Scope Demo Berikutnya**: Apakah semua 25 poin feedback ini perlu selesai sebelum demo ulang? Atau ada **fitur prioritas utama** yang paling krusial untuk Kaprodi (misalnya: perbaiki schema + UI + QR Code saja)? Ini akan menentukan urutan pekerjaan.

> [!WARNING]
> **Data Lama di Database**: Perubahan nilai enum (contoh: `GOOD` → `BAIK`, `UNIQUE` → `FIXED`) akan membutuhkan migrasi data. Apakah database lokal Anda **boleh di-reset** (`php artisan migrate:fresh --seed`) atau harus dipertahankan datanya?

---

## Prioritas Eksekusi (3 Fase)

### 🔴 FASE 1 — Fondasi (Database & Backend) — KRITIS
*Harus diselesaikan lebih dahulu karena semua fitur lain bergantung padanya.*

---

### Schema & Database

#### [MODIFY] Migration — Standardisasi Role, Condition, Status, Type

Buat **3 migration baru** (ALTER TABLE) untuk memperbarui nilai enum:

**1. `alter_users_add_role_column`**
```sql
-- Hapus ketergantungan Spatie untuk role sederhana
-- Tambah kolom role langsung di tabel users
ALTER TABLE users ADD COLUMN role ENUM('ADMIN', 'KAJUR', 'VIEWER') DEFAULT 'VIEWER';
```

**2. `alter_assets_standardize_enums`**
```sql
-- condition: GOOD→BAIK, DAMAGED→RUSAK_BERAT (hilangkan FAIR, POOR)
ALTER TABLE assets MODIFY condition ENUM('BAIK','RUSAK_RINGAN','RUSAK_BERAT') DEFAULT 'BAIK';
-- status: tambah MAINTENANCE, ARCHIVED
ALTER TABLE assets MODIFY status ENUM('AVAILABLE','BORROWED','MAINTENANCE','LOST','ARCHIVED') DEFAULT 'AVAILABLE';
-- type: UNIQUE→FIXED
ALTER TABLE assets MODIFY type ENUM('FIXED','CONSUMABLE');
```

**3. `create_stock_usages_table`** (NEW — untuk CONSUMABLE)
```sql
CREATE TABLE stock_usages (
    id, asset_id, used_by (user_id), quantity_used,
    purpose (text), performed_at, notes, created_at, updated_at
)
```

**4. `alter_borrow_items_add_fields`**
```sql
ALTER TABLE borrow_items ADD COLUMN condition_after ENUM('BAIK','RUSAK_RINGAN','RUSAK_BERAT') NULLABLE;
ALTER TABLE borrow_items ADD COLUMN returned_at TIMESTAMP NULLABLE;
ALTER TABLE borrow_items ADD COLUMN returned_by BIGINT NULLABLE (FK ke users);
```

#### [MODIFY] Model Updates
- `Asset.php` — perbarui konstanta kondisi, status, type
- `User.php` — hapus HasRoles (Spatie), tambah `role` kolom langsung
- `BorrowItem.php` — tambah relasi `returnedBy`
- `StockUsage.php` — [NEW] model baru

---

### RBAC Simplification

#### [MODIFY] Middleware & Authorization
- Ganti seluruh `$user->hasRole('kajur')` → `$user->role === 'KAJUR'`
- Ganti `$this->authorize('asset.view')` → gunakan `Gate::define` sederhana berbasis `role`
- Update `AuthServiceProvider` atau `AppServiceProvider`
- Update `RolesAndPermissionsSeeder` → `UserSeeder` sederhana

---

### 🟡 FASE 2 — Fitur Utama (Backend + Frontend)

---

### Quick Availability Search (Dashboard)

#### [MODIFY] `DashboardController.php`
- Tambah query: cari aset berdasarkan nama/kode, filter status `AVAILABLE`
- Return `availableAssets` ke view

#### [MODIFY] `Dashboard/Index.vue`
- Tambah komponen search bar di bagian atas dashboard
- Tampilkan hasil pencarian secara real-time (debounce 300ms)
- Tunjukkan status aset langsung (badge berwarna: Tersedia, Dipinjam, Maintenance)

---

### QR Code Generator & Scanner

#### [NEW] `QrCodeController.php`
- `generate(Asset $asset)` — generate QR berisi URL ke halaman detail aset
- Gunakan package `simplesoftwareio/simple-qrcode`

#### [MODIFY] `Assets/Show.vue`
- Tambah tombol "Cetak QR Code" di halaman detail aset
- QR Code bisa di-download sebagai PNG

#### [NEW] `Assets/Scan.vue`
- Halaman scanner menggunakan kamera (library `html5-qrcode`)
- Setelah scan sukses → redirect ke detail aset

---

### Import Excel

#### [NEW] `ImportController.php`
- Method `template()` — download template Excel kosong
- Method `import(Request $request)` — upload & proses file Excel
- Gunakan package `maatwebsite/excel`

#### [NEW] `Assets/Import.vue`
- Form upload file Excel
- Preview tabel data sebelum di-import
- Tampilkan baris yang error (validasi) dengan highlight merah

---

### Search & Filter Lengkap (Asset Index)

#### [MODIFY] `AssetController.php` (sudah ada, tambah filter)
- Tambah filter: `condition`, `year`
- Sudah ada: search (nama, kode, merk), type, status, category, location, department ✓

#### [MODIFY] `Assets/Index.vue`
- Tambah UI filter kondisi & tahun pengadaan
- Buat panel filter yang bisa di-collapse (accordion)

---

### Laporan Lengkap (Reports)

#### [MODIFY] `ReportController.php`
- Tambah laporan: aset rusak, barang paling sering dipinjam, pengeluaran stok consumable
- Export PDF: gunakan `barryvdh/laravel-dompdf`
- Export Excel: gunakan `maatwebsite/excel`

#### [MODIFY] `Reports/Index.vue`
- Tambah 3 jenis laporan baru
- Tambah tombol "Export PDF" dan "Export Excel" di setiap laporan

---

### 🟢 FASE 3 — Polish UI/UX

---

### Empty State & Error State

#### [NEW] `Components/EmptyState.vue`
```
Props: icon, title, description, actionLabel, actionRoute
```
- Dipakai di semua halaman tabel ketika data kosong

#### [NEW] `Components/ErrorState.vue`
- Ditampilkan jika terjadi error server

---

### UX Perbaikan Menyeluruh

#### [MODIFY] Semua halaman Index (Assets, Borrows, Returns, Users)
- Pastikan ada empty state
- Pastikan pagination terlihat jelas
- Tambah tooltips di tombol-tombol icon
- Sederhanakan navigasi sidebar — kurangi menu yang membingungkan

#### [MODIFY] Form (Create & Edit)
- Tambah validasi visual real-time (border merah saat error)
- Perindah pesan error agar tidak teknikal
- Tambah konfirmasi sebelum hapus (modal, bukan `window.confirm()`)

---

## Urutan Eksekusi Teknis

```
1. Fase 1: Migration baru → php artisan migrate
2. Fase 1: Update Models & Hapus Spatie dependency
3. Fase 1: Update semua Controller (ganti hasRole → role check)
4. Fase 1: Update Seeder
5. Fase 2: Quick Search Dashboard
6. Fase 2: QR Code (install package + controller + UI)
7. Fase 2: Import Excel (install package + controller + UI)
8. Fase 2: Filter & Search lengkap di Assets
9. Fase 2: Laporan + Export
10. Fase 3: Empty States
11. Fase 3: UI Polish menyeluruh
```

## Paket Composer Baru yang Dibutuhkan

```bash
composer require simplesoftwareio/simple-qrcode
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf
```

## Verification Plan

### Automated Tests
- `php artisan test` — pastikan semua 21 tes tetap hijau setelah refaktor RBAC
- Tambah test case baru untuk `StockUsage` dan `QrCodeController`

### Manual Verification
- Login sebagai ADMIN → bisa akses semua menu
- Login sebagai KAJUR → hanya lihat aset jurusannya sendiri, tidak bisa hapus
- Login sebagai VIEWER → hanya bisa lihat, tidak bisa edit/hapus
- Test import Excel dengan file valid dan file berisi error
- Test scan QR Code dari HP
- Test export PDF laporan
