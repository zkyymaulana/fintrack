---
type: Web Authentication
title: FinTrack web session authentication
description: Login, pendaftaran, dan logout browser memakai sesi Laravel dalam halaman Inertia.
tags: [web, auth, session, inertia]
sources:
  - resource: ../../../routes/web.php
    title: Web authentication routes
  - resource: ../../../app/Http/Controllers/Web/Auth/SessionController.php
    title: Login and logout implementation
  - resource: ../../../app/Http/Controllers/Web/Auth/RegisteredUserController.php
    title: Web registration implementation
  - resource: ../../../app/Http/Middleware/HandleInertiaRequests.php
    title: Shared Inertia authentication props
---

# Web authentication

- Tamu dapat membuka `GET /login` dan `GET /register`; route POST terkait membuat sesi dengan guard `web`.
- Login memvalidasi email dan kata sandi, membatasi frekuensi percobaan, lalu meregenerasi session ID setelah berhasil.
- Registrasi memvalidasi nama, email unik, kata sandi minimal delapan karakter, dan konfirmasi; user baru langsung login ke sesi web.
- `GET /dashboard` memerlukan `auth:web` dan menampilkan halaman awal Inertia.
- `POST /logout` menghapus sesi browser, menginvalidasi sesi, dan meregenerasi token CSRF.
- Props bersama Inertia hanya memuat `id`, `name`, `email`, dan URL avatar terotorisasi dari user aktif.

Autentikasi API Flutter tetap memakai Sanctum bearer token. Lihat [API authentication](/knowledge/concepts/api-authentication.md).
