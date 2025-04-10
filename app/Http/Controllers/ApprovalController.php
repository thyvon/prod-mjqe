<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Approval;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    public function index()
    {
        $approvals = Approval::with(['user', 'cashRequest:id,id as approval_id,ref_no', 'clearInvoice:id,id as approval_id,ref_no', 'clearStatment:id,id as approval_id,statement_number'])
            ->where('status', 0)
            ->where('user_id', Auth::id()) // Filter by authenticated user
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($approval) {
                // Add a column to show ref_no or statement_number
                if ($approval->cashRequest) {
                    $approval->reference = $approval->cashRequest->ref_no;
                } elseif ($approval->clearInvoice) {
                    $approval->reference = $approval->clearInvoice->ref_no;
                } elseif ($approval->clearStatment) {
                    $approval->reference = $approval->clearStatment->statement_number;
                } else {
                    $approval->reference = 'N/A';
                }
                return $approval;
            });

        return Inertia::render('Approvals/Index', [
            'approvals' => $approvals,
        ]);
    }
}
