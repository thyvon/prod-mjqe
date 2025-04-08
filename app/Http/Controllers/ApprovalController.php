<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Approval;
use Inertia\Inertia;

class ApprovalController extends Controller
{
    public function index()
    {
        $approvals = Approval::with(['user', 'cashRequest', 'clearInvoice', 'clearStatment'])
            ->where('status', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Approvals/Index', [
            'approvals' => $approvals,
        ]);
    }
}
