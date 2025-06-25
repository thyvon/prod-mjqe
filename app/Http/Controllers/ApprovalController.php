<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Approval;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ApprovalController extends Controller
{
    public function index()
    {
        $approvals = Approval::with([
            'user:id,name',
            'cashRequest:id,id as approval_id,ref_no',
            'clearInvoice:id,id as approval_id,ref_no',
            'evaluation:id,id as approval_id,reference',
            'clearStatment:id,id as approval_id,statement_number',
            'cancellation:id,id as approval_id,cancellation_no,pr_po_id',
            'cancellation.purchaseRequest:id,id as pr_po_id,pr_number',
            'cancellation.purchaseOrder:id,id as pr_po_id,po_number',
        ])
            ->select('id', 'user_id', 'docs_type', 'status', 'created_at', 'approval_name', 'status_type', 'approval_id','click_date')
            ->where('user_id', Auth::id()) // Filter by authenticated user
            ->orderBy('status', 'asc') // Sort by status
            ->orderBy('created_at', 'desc') // Sort by created_at
            ->orderBy('status_type', 'asc') // Sort by status_type
            ->get()
            ->map(function ($approval) {
                // Add a column to show ref_no or statement_number based on docs_type
                if (in_array($approval->docs_type, [1, 2]) && $approval->cashRequest) {
                    $approval->reference = $approval->cashRequest->ref_no;
                } elseif (in_array($approval->docs_type, [3, 4]) && $approval->clearInvoice) {
                    $approval->reference = $approval->clearInvoice->ref_no;
                } elseif ($approval->docs_type == 5 && $approval->clearStatment) {
                    $approval->reference = $approval->clearStatment->statement_number;
                } elseif ($approval->docs_type == 6 && $approval->cancellation) {
                    $approval->reference = $approval->cancellation->cancellation_no . 
                        ($approval->cancellation->purchaseRequest ? ' (' . $approval->cancellation->purchaseRequest->pr_number . ')' : '');
                } elseif ($approval->docs_type == 7 && $approval->cancellation) {
                    $approval->reference = $approval->cancellation->cancellation_no .
                        ($approval->cancellation->purchaseOrder ? ' (' . $approval->cancellation->purchaseOrder->po_number . ')' : '');
                } elseif ($approval->docs_type == 8 && $approval->evaluation) {
                    $approval->reference = $approval->evaluation->reference;
                } else {
                    $approval->reference = 'N/A';
                }

                return $approval;
            });

        //         Log::info('Approvals fetched for user: ' . Auth::id(), [
        //     'count' => $approvals->count(),
        //     'approvals' => $approvals->toArray(),
        // ]);
        return Inertia::render('Approvals/Index', [
            'approvals' => $approvals->values()->all(), // ✅ Ensures a pure array
        ]);
    }

}
