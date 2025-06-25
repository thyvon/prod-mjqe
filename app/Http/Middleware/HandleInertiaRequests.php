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
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    // public function share(Request $request): array
    // {
    //     return [
    //         ...parent::share($request),
    //         'auth' => [
    //             'user' => $request->user(),
    //         ],
    //     ];
    // }

    public function share(Request $request): array
    {
        $user = $request->user();
        $approvals = $user ? $user->approvals()
        ->select('id', 'status', 'created_at', 'approval_name', 'status_type', 'docs_type', 'approval_id')
        ->where('status', 0) // Filter by status = 0
        ->orderBy('status_type', 'asc') // Sort by status_type
        ->get()
        ->values() // Reindex the collection
        ->toArray() : null;
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user,
                'approvals' => $approvals,
            ],
        ]);
    }

}
