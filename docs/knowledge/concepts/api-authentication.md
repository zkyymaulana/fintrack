---
type: API
title: FinTrack API authentication
description: API memakai Sanctum bearer token setelah proses register atau login.
tags: [api, auth, sanctum]
sources:
  - resource: ../../../routes/api.php
    title: API authentication routes and middleware
  - resource: ../../../app/Http/Controllers/Api/AuthController.php
    title: Registration, login, and logout implementation
  - resource: ../../../database/migrations/2026_06_22_043827_create_personal_access_tokens_table.php
    title: Sanctum token table migration
---

# Public endpoints

- `POST /api/register` membuat akun dan mengembalikan Sanctum bearer token.
- `POST /api/login` memeriksa email/password dan mengembalikan bearer token.
- `POST /api/forgot-password` dan `POST /api/reset-password` menangani alur reset password.

# Authenticated endpoints

Route lain dalam group `auth:sanctum` membutuhkan token pada header `Authorization: Bearer ...`. `POST /api/logout` menghapus token aktif. Endpoint `GET /api/user` mengembalikan profil pengguna terautentikasi.

# Web authentication

Web memakai sesi Laravel. Lihat [web authentication](/knowledge/concepts/web-authentication.md) untuk route dan alurnya. Logout web tidak menghapus token API milik aplikasi Flutter.
