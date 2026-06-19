<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'admin_permissions' => $request->user()?->isAdmin() 
                    ? ($request->user()->id === 1 ? ['*'] : ($request->user()->permissions ?? [])) 
                    : [],
            ],
            'hodContext' => function () use ($request) {
                $user = $request->user();
                if (! $user?->isHod()) {
                    return null;
                }

                $user->loadMissing('department.school');
                $department = $user->department;
                if (! $department) {
                    return [
                        'hasDepartment' => false,
                        'label' => null,
                    ];
                }

                $schoolName = $department->school?->name;
                $departmentName = $department->name;
                $label = $schoolName
                    ? "{$schoolName} · {$departmentName}"
                    : $departmentName;

                return [
                    'hasDepartment' => true,
                    'label' => $label,
                    'departmentName' => $departmentName,
                    'schoolName' => $schoolName,
                ];
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
