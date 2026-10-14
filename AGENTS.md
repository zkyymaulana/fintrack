# Panduan Agent untuk finTrack-be

## Cakupan proyek

- Folder ini adalah backend FinTrack berbasis Laravel 12 dan PHP 8.2+, dengan API di `routes/api.php` dan `app/Http/Controllers/Api/`.
- Kerjakan perubahan backend di `finTrack-be`. Jangan mengubah repo Flutter `fintrack_app` kecuali pengguna secara eksplisit memintanya.
- Sebelum mengubah file, periksa implementasi dan perubahan lokal yang sudah ada. Pertahankan perubahan pengguna yang tidak terkait.
- Jangan menambahkan dependency atau mengubah kontrak API tanpa kebutuhan yang jelas. Jangan membaca, menyalin, atau menuliskan rahasia dari `.env`.

## Struktur dan pola implementasi

- Route API didefinisikan di `routes/api.php`; controller API berada di `app/Http/Controllers/Api/`.
- Model Eloquent berada di `app/Models/`; perubahan skema database dibuat sebagai migration baru di `database/migrations/`.
- Gunakan pola respons, autentikasi Sanctum, validasi, dan otorisasi yang sudah dipakai fitur terkait. Jangan mengubah format respons endpoint lain tanpa diminta.
- Ikuti gaya PHP proyek dan gunakan Laravel/Pest yang sudah terpasang. Jangan mengedit isi `vendor/`, `node_modules/`, cache, hasil build, atau file runtime sebagai bagian dari perubahan kode.

## Dokumentasi dan OKF

- Dokumentasi pengetahuan dan rencana proyek berada di `docs/`; format OKF mengikuti spesifikasi v0.2 yang dirujuk di `docs/index.md`.
- `docs/index.md` adalah indeks bundle OKF. Pertahankan `okf_version: "0.2"` di frontmatter-nya. Setiap folder dokumentasi memiliki `index.md` yang menautkan isi folder tersebut.
- `docs/log.md` menyimpan riwayat perubahan bundle; gunakan `YYYY-MM-DD` dan urutkan entri dari yang terbaru. Log khusus suatu subfolder boleh berada di level folder tersebut.
- Dokumen konsep di `docs/knowledge/concepts/` memakai YAML frontmatter `type`. Rencana aktif disimpan di `docs/plans/active/` dan ditautkan dari indeks folder terkait.
- Jika menambah atau memindahkan dokumen/folder, perbarui indeks terkait dan pastikan tautannya valid. Untuk folder sumber backend yang dikelola repo, pertahankan `index.md` sebagai navigasi isi folder; folder dependency, `.git`, cache/runtime, dan hasil build tidak memerlukan indeks.
- Jangan menghapus atau menimpa dokumentasi/rencana yang sudah ada tanpa alasan yang terkait langsung dengan permintaan pengguna.

## Verifikasi

- Jalankan tes yang relevan setelah perubahan perilaku backend. Perintah seluruh tes: `composer test` atau `php artisan test`.
- Untuk pemeriksaan gaya PHP, gunakan `vendor/bin/pint --test` bila tersedia.
- Jika tes tidak dapat dijalankan, jelaskan batasannya dan sebutkan pemeriksaan lain yang dilakukan. Jangan menyatakan tes lulus jika belum dijalankan.
