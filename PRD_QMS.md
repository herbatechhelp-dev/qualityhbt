# Product Requirement Document (PRD)
## Integrated Quality Management System (QMS)

---

## Daftar Isi

1. [Pendahuluan & Tujuan Sistem](#1-pendahuluan--tujuan-sistem)
2. [Arsitektur Aktor & Pengguna](#2-arsitektur-aktor--pengguna-user-roles)
3. [Spesifikasi Fungsional Modul](#3-spesifikasi-fungsional-modul)
   - 3.1 [Master List Dokumen](#31-modul-1--master-list-dokumen)
   - 3.2 [Change Request (CRA & CRB)](#32-modul-2--change-request-cra--crb)
   - 3.3 [Deviation Report](#33-modul-3--deviation-report-deviasi)
   - 3.4 [CAPA](#34-modul-4--capa-corrective-and-preventive-action)
4. [Aturan Bisnis & Integrasi Sistem](#4-aturan-bisnis--integrasi-sistem)

---

## 1. Pendahuluan & Tujuan Sistem

Tujuan dari pengembangan sistem ini adalah untuk **mendigitalisasi dan mengintegrasikan** proses pemastian mutu (Quality Assurance) ke dalam satu platform terpadu.

Sistem ini mencakup empat pilar utama:

| Pilar | Deskripsi |
|---|---|
| 📄 Manajemen Referensi Dokumen | Repositori terpusat seluruh dokumen mutu |
| 🔄 Change Request (CR) | Pengelolaan usulan perubahan dokumen atau proses |
| ⚠️ Deviation Report | Pelaporan ketidaksesuaian dan penyimpangan mutu |
| ✅ CAPA | Tindakan korektif dan preventif atas hasil deviasi |

> **Tujuan akhir:** Memastikan kepatuhan regulasi dan efisiensi operasional secara menyeluruh di seluruh departemen terkait.

---

## 2. Arsitektur Aktor & Pengguna (User Roles)

| Role | Identitas | Kewenangan |
|---|---|---|
| **Users / Inisiator** | Personel departemen terkait | Membuat usulan perubahan · Melaporkan deviasi · Tindak lanjut CAPA · Melihat riwayat data |
| **QA (Quality Assurance)** | Personel peninjau dengan otoritas penuh | Verifikasi dokumen · Edit rencana tindakan · Approve / Reject · Menutup (Close) siklus dokumen |

---

## 3. Spesifikasi Fungsional Modul

---

### 3.1 Modul 1 — Master List Dokumen

Modul dasar yang berfungsi sebagai **repositori terpusat** untuk semua data referensi dokumen mutu di dalam sistem.

#### Alur Proses

```
Administrator Unggah Spreadsheet  →  Sistem Membaca & Memetakan  →  Dokumen Tampil di Logbook
```

#### Spesifikasi CRUD

| Operasi | Tipe | Detail |
|---|---|---|
| **CREATE** | Bulk Import | Unggah file spreadsheet (`.xlsx` / `.csv`) untuk impor data massal |
| **READ** | Logbook Display | Menampilkan daftar logbook master dokumen secara terpusat |
| **UPDATE** | — | Tidak diakomodasi berdasarkan alur dokumen utama |
| **DELETE** | — | Tidak diakomodasi berdasarkan alur dokumen utama |

#### Data Output (READ)

- Masterlist Prosedur Tetap (PROTAP)
- Masterlist QMS / Masterlist
- Masterlist Instruksi Kerja (IK)
- Master List Spesifikasi
- Scan CRF / CRE
- Masterlist Protokol
- Masterlist Laporan Tahunan (Pengkajian Mutu Produk)

---

### 3.2 Modul 2 — Change Request (CRA & CRB)

Modul untuk mengelola usulan perubahan dokumen atau proses. Terbagi menjadi dua jalur:
- **CRA** — disertai analisis risiko dengan kalkulasi RPN otomatis
- **CRB** — tanpa analisis risiko

#### Alur Proses

```
Tambah Data Baru  →  Pilih Jalur CRA / CRB  →  Isi Formulir & Lampiran  →  Save & Submit  →  QA: Verifikasi & Action
```

1. Pengguna membuka Users Dashboard, memilih **"TAMBAH DATA BARU"**, lalu diarahkan ke formulir CRA atau CRB.
2. Pada formulir **CRA**, pengguna wajib mengisi parameter identifikasi risiko. Nilai **RPN dihitung otomatis** oleh sistem.
3. Pengguna mengunggah dokumen lampiran dan menekan `Save as draft` atau `Save and Submit`.
4. Dokumen yang di-submit masuk ke QA Dashboard dengan status awal **OPEN / IN REVIEW**.
5. QA membuka dokumen, melakukan modifikasi rencana kerja, mengisi timeline, menetapkan PIC, dan memberikan keputusan akhir.

#### CREATE — Input Form (oleh Users)

| Field | Tipe Input | Keterangan | Status |
|---|---|---|---|
| Sifat Perubahan | Dropdown | Permanen / Sementara | Wajib |
| Risk Identification *(Khusus CRA)* | Text | Uraian identifikasi risiko | Wajib |
| Potential Cause *(Khusus CRA)* | Text | Penyebab potensial risiko | Wajib |
| Risk Value *(Khusus CRA)* | Number | Memicu kalkulasi RPN otomatis | Wajib |
| Risk Control *(Khusus CRA)* | Text | Kontrol yang diterapkan | Wajib |
| Action *(Khusus CRA)* | Text | Tindakan mitigasi | Wajib |
| File Lampiran | File Upload | `.pdf`, `.xlsx`, dll. | Opsional |
| Keterangan Lampiran | Text | Deskripsi singkat file | Opsional |

> **Aksi:** `Save as Draft` · `Save and Submit`

#### READ — Status Sistem

| Status | Keterangan |
|---|---|
| `OPEN` | Dokumen baru masuk |
| `IN REVIEW` | Sedang ditinjau QA |
| `APPROVED` | Disetujui QA |
| `IN PROGRESS` | Tindakan sedang berjalan |
| `COMPLETE` | Selesai |
| `REJECT` | Ditolak QA |

> **Filter pencarian:** No CR · Inisiator Pengusul · Departemen · Jenis

#### UPDATE — Form Evaluasi (oleh QA)

| Field | Tipe Input | Status |
|---|---|---|
| Rencana Tindakan | Text Area | Wajib |
| PIC | Text / User Selection | Wajib |
| Time Line | Date Picker | Wajib |
| Hasil Verifikasi | Text Area | Wajib |

#### DELETE

Tombol **"Hapus"** tersedia pada baris file lampiran tertentu, hanya sebelum draf final dikirim.

---

### 3.3 Modul 3 — Deviation Report (Deviasi)

Modul untuk mendokumentasikan **laporan ketidaksesuaian atau penyimpangan mutu** yang terjadi di lapangan.

#### Alur Proses

```
Input Laporan Deviasi  →  Upload Lampiran & Submit  →  QA Review  →  QA Beri Keputusan  →  Approve → Buka CAPA
```

#### Keputusan QA

| Keputusan | Dampak pada Sistem |
|---|---|
| ❌ **REJECT** | Status berubah dengan alasan penolakan · Dokumen dikembalikan ke inisiator untuk direvisi · Input "Alasan Reject" **wajib** diisi |
| ✅ **APPROVE** | Sistem secara otomatis meneruskan data dan membuka lembaran baru di **Modul CAPA** |

#### CREATE — Input Form (oleh Users)

| Field | Tipe Input | Status |
|---|---|---|
| Form Deviasi Dasar | Form standar | Wajib |
| File Lampiran | File Upload | Opsional |
| Keterangan Lampiran | Text | Opsional |

#### READ — QA Dashboard

| Kolom Tabel | Keterangan |
|---|---|
| No | Nomor urut |
| Tanggal Request | Tanggal pengajuan |
| No Deviasi | Nomor dokumen deviasi |
| Departemen | Departemen pengaju |
| Deviasi Terkait | Deskripsi ketidaksesuaian |
| Action | Tombol `View Detail` |

> **Filter pencarian:** Cari Request Deviasi

#### UPDATE — Keputusan QA

| Field | Tipe Input | Status |
|---|---|---|
| Action | Dropdown: Approve / Reject | Wajib |
| Alasan Reject | Text Area | Wajib *(jika Reject dipilih)* |

#### DELETE

Tidak tersedia — data deviasi tidak dapat dihapus untuk **menjaga integritas audit log**.

---

### 3.4 Modul 4 — CAPA (Corrective and Preventive Action)

Modul penanganan tingkat lanjut untuk pelaksanaan **tindakan perbaikan dan pencegahan** berdasarkan hasil deviasi yang telah disetujui.

#### Alur Proses

```
Deviasi Approved → CAPA Auto-Generate  →  Users Lengkapi Form & Submit  →  Upload Bukti Lapangan  →  QA Review & Verifikasi  →  CLOSE
```

> Data CAPA **dibuat otomatis** saat laporan Deviasi mendapatkan status Approved dari QA, dengan membawa relasi nomor dokumen sumber.

#### CREATE — Input Form (oleh Users)

| Field | Tipe Input | Keterangan | Status |
|---|---|---|---|
| Sumber No CAPA | Auto-generate | Nomor referensi deviasi asal | Wajib |
| Tanggal Penyimpangan | Date Picker | — | Wajib |
| Type CAPA | Dropdown | Contoh: Deviasi | Wajib |
| Tindakan CAPA | Text Area | Rincian tindakan | Wajib |
| PIC | Text / User Selection | Pelaksana tindakan | Wajib |
| Tanggal Mulai | Date Picker | — | Wajib |
| Tanggal Selesai | Date Picker | Tidak boleh lebih lampau dari Tanggal Mulai | Wajib |

> **Aksi:** `Save as Draft` · `Save and Submit`

#### READ — Monitoring

| Komponen | Keterangan |
|---|---|
| Riwayat Sub CAPA | Daftar nomor sub CAPA terkait |
| Type CAPA | Jenis tindakan |
| PIC | Penanggung jawab |
| Batas Waktu | Deadline penyelesaian |
| Action | Tombol `Lihat Tindakan CAPA` |

> **Filter pencarian:** Cari Verifikasi CAPA / Cari Request CAPA berdasarkan Sumber No CAPA

#### UPDATE — Modifikasi & Verifikasi

| Field | Tipe Input | Keterangan | Status |
|---|---|---|---|
| Edit Tanggal / No Request | Input korektif | Modifikasi administratif jika diperlukan | Opsional |
| Upload Bukti Lapangan | File Upload | Bukti pelaksanaan tindakan di lapangan | Wajib |
| Hasil Verifikasi QA | Text Area | Resume peninjauan akhir QA | Wajib |

#### Siklus Status CAPA

```
DRAFT / OPEN  →  IN PROGRESS  →  APPROVED  →  CLOSE
```

#### DELETE

Tidak tersedia — data CAPA tidak dapat dihapus untuk **mematuhi regulasi ketertelusuran berkas mutu**.

---

## 4. Aturan Bisnis & Integrasi Sistem

| Kode | Aturan | Ketentuan Sistem |
|---|---|---|
| **BR-01** | Perhitungan RPN Otomatis | Sistem pada sub-modul CRA **wajib** menghitung nilai RPN secara otomatis segera setelah pengguna mengisi bobot nilai pada tabel penilaian risiko. |
| **BR-02** | Keterikatan Deviasi ke CAPA | Data CAPA **tidak dapat dibuat secara mandiri** tanpa referensi Dokumen Deviasi asal yang telah berstatus Approved oleh QA. |
| **BR-03** | Mekanisme Reject Deviasi | Jika QA memilih Reject, sistem **wajib memvalidasi** bahwa kolom "Alasan Reject" tidak kosong, lalu mengirimkan notifikasi pengembalian dokumen ke dasbor inisiator. |
| **BR-04** | Validasi Tanggal CAPA | Pada form CAPA, input **"Tanggal Selesai" tidak boleh** diatur lebih lampau dari "Tanggal Mulai". |

---

*QMS v1.0 · Confidential*
