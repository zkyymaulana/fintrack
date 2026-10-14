<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => function () use ($request): ?array {
                    $user = $request->user('web');
                    if (! $user) {
                        return null;
                    }

                    return [
                        ...$user->only('id', 'name', 'email'),
                        'avatar_url' => $user->avatar_path ? route('profile.avatar.show', ['v' => basename($user->avatar_path)]) : null,
                    ];
                },
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
        ];
    }
}
