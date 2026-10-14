---
type: System Architecture
title: FinTrack backend architecture
description: Laravel backend menyediakan API keuangan dan fondasi UI web berbasis Inertia.
tags: [backend, laravel, architecture]
sources:
  - resource: ../../../routes/api.php
    title: API route definitions
  - resource: ../../../routes/web.php
    title: Web route definitions
  - resource: ../../../composer.json
    title: Backend dependencies
---

# Overview

`finTrack-be` adalah aplikasi Laravel 12 dengan PHP 8.2. Aplikasi menyediakan route API di `routes/api.php`, route web di `routes/web.php`, controller di `app/Http/Controllers`, model Eloquent di `app/Models`, dan skema basis data melalui migration.

Aplikasi Flutter berada di repo sejajar `fintrack_app`. Flutter memanggil API Laravel; API dan database tetap menjadi sumber data bersama.

# Current web entry point

Route web mengarahkan `/` ke `/dashboard`; tamu diarahkan ke `/login`. Halaman login, daftar akun, dashboard, dompet, transaksi, anggaran, dan analitik menggunakan Inertia/Vue melalui root view `resources/views/app.blade.php`. Browser menggunakan sesi Laravel, sementara API Flutter tetap menggunakan token Sanctum. Controller web membaca data milik user langsung melalui Eloquent dan mengirim props yang diperlukan halaman.

Vite membangun aset TypeScript dan Vue dari `resources/js/app.ts`. Middleware `app/Http/Middleware/HandleInertiaRequests.php` membagikan ID, nama, dan email user web serta pesan sukses sesi kepada halaman Inertia.

# Related concepts

Lihat [API authentication](/knowledge/concepts/api-authentication.md), [web authentication](/knowledge/concepts/web-authentication.md), [financial data model](/knowledge/concepts/financial-data-model.md), dan [external integrations](/knowledge/concepts/external-integrations.md).
