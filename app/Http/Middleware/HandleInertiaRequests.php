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

        $approvals = null;
        if ($user) {
            $approvals = $user->approvals()
                ->select('id', 'status', 'created_at', 'approval_name', 'status_type', 'docs_type', 'approval_id')
                ->where('status', 0)
                ->orderBy('status_type', 'asc')
                ->get()
                ->map(function ($approval) {
                    // Workflow dependency logic
                    $statusOrders = [
                        1 => [1, 3, 4],
                        2 => [1, 2, 3, 4],
                        3 => [1, 3],
                        5 => [1, 3],
                        6 => [3, 5],
                        7 => [3],
                        8 => [2, 7, 3],
                    ];
                    $shouldShow = true;
                    $statusOrder = $statusOrders[$approval->docs_type] ?? [];
                    $currentIndex = array_search($approval->status_type, $statusOrder);
                    if ($currentIndex !== false && $currentIndex > 0) {
                        $previousStatusType = $statusOrder[$currentIndex - 1];
                        $previousApproval = \App\Models\Approval::where('approval_id', $approval->approval_id)
                            ->where('docs_type', $approval->docs_type)
                            ->where('status_type', $previousStatusType)
                            ->first();
                        $shouldShow = $previousApproval && $previousApproval->status == 1;
                    }
                    return $shouldShow ? $approval : null;
                })
                ->filter()
                ->values()
                ->toArray();
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user,
                'approvals' => $approvals,
            ],
        ]);
    }

}
