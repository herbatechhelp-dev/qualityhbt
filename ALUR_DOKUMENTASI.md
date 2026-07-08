# Dokumentasi Alur Aplikasi QMS Terintegrasi

**Aplikasi:** Integrated Quality Management System (QMS) — HerbaTech  
**Stack:** Laravel 10 + Inertia.js + Vue 3 + Tailwind CSS  
**Database:** MySQL  

---

## 1. Alur Autentikasi

```
Pengunjung
  │
  ├─ Login (/login)
  │    └─ AuthenticatedSessionController
  │         └─ Validasi credentials
  │              ├─ Sukses → Redirect ke /dashboard
  │              └─ Gagal → Kembali ke login dengan error
  │
  ├─ Register (/register) — (opsional, bisa dimatikan)
  │    └─ RegisteredUserController → buat user baru role=initiator
  │
  └─ Forgot Password (/forgot-password)
       └─ PasswordResetLinkController → kirim email reset
```

**Session:** Menggunakan Laravel Breeze (Inertia stack) + Sanctum untuk token API.

---

## 2. Struktur Role & Hak Akses

| Role | Deskripsi |
|------|-----------|
| `initiator` | Pelapor/membuat Change Request, Deviation, CAPA |
| `qa` | Menyetujui/menolak, melakukan evaluasi |
| `head_of_quality` | Approval level 1 (CRA/CRB) |
| `operational_manager` | Approval level 2 (CRA/CRB) |
| `general_manager` | Approval level terakhir (CRA/CRB) |
| `superadmin` | Manajemen user & sistem, akses penuh |

Helper `isQaOrManagement()` mencakup: `qa`, `superadmin`, `head_of_quality`, `operational_manager`, `general_manager`.

---

## 3. Alur Modul - Master Dokumen

```
                   ┌─────────────────────────────┐
                   │   /documents (GET)           │
                   │   DocumentController@index   │
                   │   - List paginated           │
                   │   - Search by no dokumen,    │
                   │     judul, dll               │
                   │   - Filter by type           │
                   └──────────┬──────────────────┘
                              │
              ┌───────────────┴───────────────┐
              ▼                               ▼
   Import Excel (.xlsx/.csv)      Sync Google Sheets
   POST /documents/import         POST /documents/sync-sheets
   DocumentController@import      DocumentController@syncSheets
   ───────────────────────        ─────────────────────────
   - QA/Management only           - QA/Management only
   - Parse kolom A-S              - Ambil data dari Google
   - Map ke field database          Sheets API (service account)
   - updateOrCreate by            - Mapping kolom sama
     document_number              - updateOrCreate by
                                   document_number
```

### Mapping Kolom Spreadsheet → Database

| Spreadsheet | Field Database |
|-------------|---------------|
| A | excel_no |
| B (JUDUL) | title |
| C (NO DOKUMEN) | document_number |
| D+E | revision |
| F | no_perubahan_cc |
| G | effective_date |
| H | tgl_review |
| I | tgl_review_2 |
| K+L | pengganti_lampiran |
| M | no_catatan_mutu |
| N | dokumen_terkait |
| O | tgl_sosialisasi |
| P | distribusi |
| Q | no_pemusnahan |
| R | tgl_pemusnahan |
| S | tempat_penyimpanan |

---

## 4. Alur Modul - Change Request (CRA/CRB)

### 4.1 Status Workflow

```
                    ┌─────────┐
                    │  DRAFT  │ (hanya visible ke initiator)
                    └────┬────┘
                         │ Submit (store)
                         ▼
                    ┌─────────┐
                    │  OPEN   │
                    └────┬────┘
                         │ Evaluasi QA (evaluate)
                         ▼
                    ┌────────────┐
                    │ IN REVIEW  │
                    └────┬───────┘
                         │
           ┌─────────────┼─────────────┐
           ▼             ▼             ▼
    ┌──────────┐  ┌────────────┐  ┌────────┐
    │ APPROVED │  │ IN PROGRESS│  │ REJECT │
    └────┬─────┘  └─────┬──────┘  └────────┘
         │              │
         │              ▼
         │      ┌──────────┐
         └─────→│ COMPLETE │
                └──────────┘
```

### 4.2 Alur Approval (CRA & CRB)

```
Initiator submit → Status: OPEN
     │
     ▼
QA melakukan evaluasi (POST /change-requests/{cr}/evaluate)
     │
     ├─ Tahap 1: Head of Quality menyetujui
     │    └─ Data disimpan di qa_verification_data → qa_1
     │
     ├─ Tahap 2: Operational Manager menyetujui
     │    └─ Data disimpan di qa_verification_data → qa_2
     │
     ├─ Tahap 3: General Manager menyetujui
     │    └─ Data disimpan di qa_verification_data → qa_3
     │
     └─ Setiap tahap bisa REJECT → status REJECT
          └─ Alasan reject wajib diisi
```

**Logika approval:**
- Setiap role hanya bisa approve di level-nya masing-masing
- Head of Quality approve → status IN REVIEW
- Operational Manager approve → jika semua role sudah approve → status IN PROGRESS
- General Manager approve → status COMPLETE (jika sudah ada PIC)
- Jika ditolak oleh siapapun → status REJECT (semua approval sebelumnya dihapus)
- QA bisa melakukan QA Stage 3 (upload file implementasi)

### 4.3 Tipe Change Request

| Tipe | Deskripsi |
|------|-----------|
| **CRA** | Change Request with Risk Analysis — mencakup identifikasi risiko, severity, occurrence, detection, RPN (auto-calculate), risk control |
| **CRB** | Change Request Basic — tanpa risk analysis |

**RPN Formula:** `Severity × Occurrence × Detection`

### 4.4 Halaman

| Route | View | Fungsi |
|-------|------|--------|
| GET /change-requests | Index.vue | List CR, filter by role scoping |
| GET /change-requests/create | Create.vue | Form create (pilih CRA/CRB) |
| GET /change-requests/{cr} | Show.vue | Detail + panel evaluasi QA |
| GET /change-requests/{cr}/print | print-cra/crb.blade.php | Cetak PDF |

---

## 5. Alur Modul - Deviation Report

### 5.1 Status Workflow

```
         ┌─────────┐
         │  DRAFT  │ (hanya visible ke initiator)
         └────┬────┘
              │ Submit / Update
              ▼
         ┌─────────┐
         │  OPEN   │
         └────┬────┘
              │ QA decide (POST /deviations/{id}/decide)
              │
      ┌───────┴───────┐
      ▼               ▼
 ┌──────────┐   ┌──────────┐
 │ APPROVED │   │ REJECTED │
 └────┬─────┘   └──────────┘
      │              ▲
      │              │ (dengan alasan)
      ▼
 Auto-create CAPA
 (CapaController tersedia
  untuk diisi)
```

### 5.2 Alur Approval

```
Initiator membuat laporan deviation:
  - Pilih jenis penyimpangan
  - Upload attachment (multi-file)
  - Analisis risiko
  - Submit → OPEN

QA Review:
  - Lihat detail deviation
  - Decision:
      ├─ APPROVE → status APPROVED
      │    └─ Auto-generate CAPA record (status DRAFT)
      │         └─ CAPA number: CAPA/YYYY/XXXX
      └─ REJECT → status REJECTED
           └─ Wajib isi alasan penolakan
```

### 5.3 Halaman

| Route | View | Fungsi |
|-------|------|--------|
| GET /deviations | Index.vue | List deviation |
| GET /deviations/create | Create.vue | Form deviation baru |
| GET /deviations/{id} | Show.vue | Detail + keputusan QA |
| GET /deviations/{id}/edit | Edit.vue | Edit draft/rejected |
| GET /deviations/{id}/print-dr | print-dr.blade.php | Cetak DR |
| GET /deviations/{id}/print-investigation | print-investigation.blade.php | Cetak investigasi |

---

## 6. Alur Modul - CAPA (Corrective & Preventive Action)

### 6.1 Status Workflow

```
Auto-create dari Deviation APPROVED
         │
         ▼
    ┌─────────┐
    │  DRAFT  │
    └────┬────┘
         │ Isi tindakan CAPA, PIC, tanggal
         ▼
    ┌─────────────┐
    │ IN PROGRESS │
    └────┬────────┘
         │ Upload bukti lapangan
         ▼
    ┌──────────┐
    │ APPROVED │
    └────┬─────┘
         │ QA verifikasi
         ▼
    ┌─────────┐
    │  CLOSE  │
    └─────────┘
```

### 6.2 Alur Lengkap CAPA

```
Deviation disetujui → CAPA auto-created (DRAFT)
     │
     ├─ PIC mengisi (POST /capas/{capa}):
     │    - Tindakan CAPA
     │    - Target tanggal mulai & selesai
     │    - PIC
     │    → Status: IN PROGRESS
     │
     ├─ PIC upload bukti lapangan (POST /capas/{capa}/proof):
     │    - Upload file (pdf, jpg, png, doc, docx, zip, rar)
     │    → Status: APPROVED
     │
     └─ QA verifikasi (POST /capas/{capa}/verify):
          ├─ Verifikasi & CLOSE
          └─ Tolak → kembali IN PROGRESS (dengan catatan)
```

### 6.3 Halaman

| Route | View | Fungsi |
|-------|------|--------|
| GET /capas | Index.vue | List CAPA |
| GET /capas/{id} | Show.vue | Detail + upload proof + verifikasi QA |

---

## 7. Alur Super Admin

### 7.1 Manajemen User

```
/superadmin/users (GET)  → List semua user
POST /superadmin/users   → Tambah user baru
PUT /superadmin/users/{user} → Edit user (termasuk role)
DELETE /superadmin/users/{user} → Hapus user
```

### 7.2 Pengaturan Sistem

```
/superadmin/settings (GET)  → Form settings
POST /superadmin/settings   → Update settings
```

**Settings yang tersedia:**
- `app_name` — Nama aplikasi
- `app_logo_type` — `text` atau `image`
- `app_logo_text` — Teks logo (jika type=text)
- `app_logo_path` — Path file logo (jika type=image)
- `app_favicon_path` — Favicon
- `google_spreadsheet_id` — ID spreadsheet Google Sheets
- `google_spreadsheet_range` — Range data
- `google_service_account_json` — Credential JSON

---

## 8. Alur Global (Middleware & Shared Data)

### 8.1 Middleware Stack

```
Request Masuk
  │
  ├─ TrustProxies
  ├─ HandleCors
  ├─ PreventRequestsDuringMaintenance
  ├─ ValidatePostSize
  ├─ TrimStrings
  ├─ ConvertEmptyStringsToNull
  │
  └─ Web Group:
       ├─ EncryptCookies
       ├─ StartSession
       ├─ ShareErrorsFromSession
       ├─ VerifyCsrfToken
       ├─ SubstituteBindings
       ├─ HandleInertiaRequests (share auth, settings, flash)
       └─ AddLinkHeadersForPreloadedAssets
```

### 8.2 Shared Inertia Props (dari HandleInertiaRequests.php)

```javascript
// Tersedia di semua halaman Vue via usePage().props
{
  auth: {
    user: { id, name, email, role, signature_path }
  },
  settings: {
    app_name,
    app_logo_type,
    app_logo_text,
    app_logo_path,
    app_logo_url,      // computed
    app_favicon_path,
    app_favicon_url,   // computed
    google_*           // config spreadsheet
  },
  flash: {
    success, error
  }
}
```

---

## 9. Alur Cetak (PDF/Print)

Setiap modul memiliki halaman cetak berbasis Blade:

```
Change Request:
  CRA  → /change-requests/{cr}/print → views/change-requests/print-cra.blade.php
  CRB  → /change-requests/{cr}/print → views/change-requests/print-crb.blade.php

Deviation:
  DR           → /deviations/{id}/print-dr           → views/deviations/print-dr.blade.php
  Investigation → /deviations/{id}/print-investigation → views/deviations/print-investigation.blade.php
```

Data yang dikirim ke view cetak:
- Data record (CR/Deviation/CAPA)
- Signatures dari user terkait (jika ada file signature)
- Informasi approval (qa_verification_data untuk CR)

---

## 10. Diagram Ringkas Alur Bisnis

```
                        ┌─────────────────────────────┐
                        │        DASHBOARD            │
                        │  - Statistik per modul       │
                        │  - Recent items             │
                        └──────────┬──────────────────┘
                                   │
         ┌─────────────────────────┼─────────────────────────┐
         ▼                         ▼                         ▼
   ┌────────────┐          ┌──────────────┐          ┌──────────────┐
   │   MASTER   │          │ CHANGE       │          │  DEVIATION   │
   │  DOKUMEN   │          │ REQUEST      │          │   REPORT     │
   │            │          │ (CRA/CRB)    │          │              │
   │ Import     │          │ Initiate     │          │ Initiate     │
   │ Excel/CSV  │          │ Risk Analysis│          │ Attachment   │
   │ Sync Google│          │ (CRA only)   │          │ Risk Analysis│
   │ Sheets     │          │ Approval     │          │ QA Decide    │
   │ Search     │          │ (3 levels)   │          │              │
   └────────────┘          │ Print        │          │ APPROVED ────┼──→ REJECTED
                           └──────────────┘          └──────┬───────┘
                                                           │
                                                           ▼
                                                    ┌──────────────┐
                                                    │     CAPA     │
                                                    │              │
                                                    │ Action Plan  │
                                                    │ Upload Bukti │
                                                    │ QA Verify    │
                                                    │              │
                                                    │ CLOSE        │
                                                    └──────────────┘
```

---

## 11. Database Relationships

```
User (1) ──〈 Initiator 〉── (N) ChangeRequest
User (1) ──〈 PIC 〉──────── (N) ChangeRequest
User (1) ──〈 Initiator 〉── (N) Deviation
User (1) ──〈 Initiator 〉── (N) Capa
User (1) ──〈 PIC 〉──────── (N) Capa
Deviation (1) ──〈 〉────── (N) Capa
```

---

## 12. Catatan Teknis

- **Frontend:** Vue 3 Composition API (`<script setup>`), Inertia form (`useForm`), Tailwind CSS utility classes
- **Routing:** Semua route JavaScript via Ziggy (`route('name', params)`)
- **Dark Mode:** Implemented di AuthenticatedLayout.vue dengan localStorage persistence
- **Loading State:** Global FeedbackOverlay.vue untuk loading dan alert
- **File Upload:** Menggunakan POST multipart form, attachment disimpan di `storage/app/public/`
- **Google Sheets API:** Menggunakan service account dengan key file JSON
