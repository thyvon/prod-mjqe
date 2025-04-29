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
        ->orderBy('created_at', 'desc')
        ->orderBy('status_type') // Sort by status_type
        ->get()
        ->unique('approval_id') // Ensure uniqueness by approval_id
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
