---
title: TriasePublik Makassar
emoji: 🏛️
colorFrom: blue
colorTo: slate
sdk: docker
app_port: 7860
pinned: false
---

# TriasePublik Makassar — Sistem Triase & Disposisi Aduan Publik

Purwarupa sistem klasifikasi otomatis kategori layanan infrastruktur perkotaan dan skala urgensi aduan warga Kota Makassar berbasis **Laravel 13**, **SQLite**, dan mesin inferensi **Linear SVM + TF-IDF N-Gram** yang diekspor langsung dari hasil pelatihan model skripsi.

## Fitur Utama
- **Portal & Uji Triase (`/`)**: Form pengaduan warga dengan normalisasi slang & partikel dialek Bugis-Makassar (`-x` -> `-nya`, `ki`, `ji`, `mi`, `tawwa`), klasifikasi kategori 5 sektor (Lampu Jalan, Drainase, Pangkas/Tebang Pohon, Sampah, Jalan Rusak), penilaian skala urgensi (heuristik keselamatan publik + SVM), disposisi OPD otomatis (Dishub, DPU, DLH) beserta SLA, serta daftar n-gram penentu keputusan.
- **Meja Kerja Antrean OPD (`/antrean`)**: Terisi otomatis dengan **1.508 sampel set uji riil Pemkot Makassar** dari `data_clean_test.csv`, mendukung pencarian, filter kecamatan/kategori/urgensi/status, audit tiket (`/antrean/{id}`), dan pembaruan status tindak lanjut.
- **Evaluasi & Riset (`/riset`)**: Ringkasan benchmark empiris Dual-Track (Baseline Linear SVM vs Fine-Tuned IndoBERT), analisis ketimpangan kelas 13:1, studi kasus *error analysis* kalimat majemuk, serta penguji normalisasi dialek Makassar interaktif.

## Deploy Gratis (Docker)
1. **Hugging Face Spaces (Gratis, 16 GB RAM, 2 vCPU, Tanpa Kartu Kredit):**
   - Buat Space baru dengan SDK **Docker**, lalu push repositori ini. Port `7860` dan auto-migrasi SQLite sudah dikonfigurasi di `Dockerfile` dan `docker/entrypoint.sh`.
2. **Render.com (Gratis via `render.yaml`):**
   - Push folder ini ke GitHub, lalu hubungkan repo tersebut melalui **New + -> Blueprint** di dashboard Render.
