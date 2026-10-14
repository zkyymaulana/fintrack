<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile', [
            'profile' => $request->user('web')->only('name', 'email'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user('web');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);
        $user->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', Password::min(8), 'confirmed'],
        ]);
        $request->user('web')->update(['password' => $data['password']]);

        return back()->with('success', 'Kata sandi berhasil diperbarui.');
    }

    public function showAvatar(Request $request): StreamedResponse
    {
        $path = $request->user('web')->avatar_path;
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, ['Cache-Control' => 'private, no-store']);
    }

    public function avatar(Request $request): RedirectResponse
    {
        $request->validate(['avatar' => ['required', 'image', 'mimes:jpeg,png', 'max:2048']]);
        $user = $request->user('web');
        $oldPath = $user->avatar_path;
        $newPath = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar_path' => $newPath]);
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return back()->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function deleteAvatar(Request $request): RedirectResponse
    {
        $user = $request->user('web');
        $oldPath = $user->avatar_path;
        $user->update(['avatar_path' => null]);
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return back()->with('success', 'Foto profil berhasil dihapus.');
    }
}
