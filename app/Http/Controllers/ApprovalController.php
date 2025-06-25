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
        Log::debug('Fetching approvals for user_id: ' . Auth::id());

        $currentUserId = Auth::id();

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
            ->select(
                'id',
                'user_id',
                'docs_type',
                'status',
                'created_at',
                'approval_name',
                'status_type',
                'approval_id',
                'click_date'
            )
            ->where('user_id', $currentUserId)
            ->orderBy('docs_type', 'asc')
            ->orderBy('status_type', 'asc')
            ->get();

        Log::debug('Raw approvals count: ' . $approvals->count());

        $approvals = $approvals->map(function ($approval) use ($currentUserId) {
            // Add reference based on docs_type
            if (in_array($approval->docs_type, [1, 2]) && $approval->cashRequest) {
                $approval->reference = $approval->cashRequest->ref_no;
            } elseif (in_array($approval->docs_type, [3, 4]) && $approval->clearInvoice) {
                $approval->reference = $approval->clearInvoice->ref_no;
            } elseif ($approval->docs_type == 5 && $approval->clearStatment) {
                $approval->reference = $approval->clearStatment->statement_number;
            } elseif ($approval->docs_type == 6 && $approval->cancellation) {
                $approval->reference = $approval->cancellation->cancellation_no .
                    ($approval->cancellation->purchaseRequest
                        ? ' (' . $approval->cancellation->purchaseRequest->pr_number . ')'
                        : '');
            } elseif ($approval->docs_type == 7 && $approval->cancellation) {
                $approval->reference = $approval->cancellation->cancellation_no .
                    ($approval->cancellation->purchaseOrder
                        ? ' (' . $approval->cancellation->purchaseOrder->po_number . ')'
                        : '');
            } elseif ($approval->docs_type == 8 && $approval->evaluation) {
                $approval->reference = $approval->evaluation->reference;
            } else {
                $approval->reference = 'N/A';
                Log::debug('No reference found for docs_type: ' . $approval->docs_type . ', id: ' . $approval->id);
            }

            // Define approval step based on docs_type and status_type
            $stepMapping = [];
            if ($approval->docs_type == 1) {
                // Petty Cash Request
                $stepMapping = [
                    1 => 'Check',
                    3 => 'Approve',
                    4 => 'Receive'
                ];
            } elseif ($approval->docs_type == 2) {
                // Advance Payment Request
                $stepMapping = [
                    1 => 'Check',
                    2 => 'Acknowledge',
                    3 => 'Approve',
                    4 => 'Receive'
                ];
            } elseif ($approval->docs_type == 3) {
                // Clear Invoice
                $stepMapping = [
                    1 => 'Check',
                    3 => 'Approve',
                ];
            } elseif ($approval->docs_type == 5) {
                // Clear Statement
                $stepMapping = [
                    1 => 'Check',
                    3 => 'Approve',
                ];
            } elseif ($approval->docs_type == 6) {
                // PR cancellation
                $stepMapping = [
                    3 => 'Approve',
                    5 => 'Authorize',
                ];
            } elseif ($approval->docs_type == 7) {
                // PO cancellation
                $stepMapping = [
                    3 => 'Approve'
                ];
            } elseif ($approval->docs_type == 8) {
                $stepMapping = [
                    2 => 'Acknowledge',
                    3 => 'Approve',
                    7 => 'Review'
                ];
            }
            $approval->approval_step = $stepMapping[$approval->status_type] ?? 'Unknown';
            if ($approval->approval_step === 'Unknown') {
                Log::debug('Unknown status_type: ' . $approval->status_type . ' for docs_type: ' . $approval->docs_type . ', id: ' . $approval->id);
            }

            // Filter based on workflow dependencies for all docs_type 1-8
            $shouldShow = true;
            if (in_array($approval->docs_type, range(1, 8))) {
                // Determine the previous status_type based on the current step
                $previousStatusType = null;
                if ($approval->docs_type == 1) {
                    $statusOrder = [1, 3, 4]; // Check -> Approve -> Receive
                } elseif ($approval->docs_type == 2) {
                    $statusOrder = [1, 2, 3, 4]; // Check -> Acknowledge -> Approve -> Receive
                } elseif ($approval->docs_type == 3) {
                    $statusOrder = [1, 3]; // Check -> Approve
                } elseif ($approval->docs_type == 5) {
                    $statusOrder = [1, 3]; // Check -> Approve
                } elseif ($approval->docs_type == 6) {
                    $statusOrder = [3, 5]; // Approve -> Authorize
                } elseif ($approval->docs_type == 7) {
                    $statusOrder = [3]; // Only Approve step (adjust if more steps)
                } elseif ($approval->docs_type == 8) {
                    $statusOrder = [2, 7, 3]; // Acknowledge -> Review -> Approve
                }

                $currentIndex = array_search($approval->status_type, $statusOrder);
                if ($currentIndex !== false && $currentIndex > 0) {
                    $previousStatusType = $statusOrder[$currentIndex - 1];
                    $previousApproval = Approval::where('approval_id', $approval->approval_id)
                        ->where('docs_type', $approval->docs_type)
                        ->where('status_type', $previousStatusType)
                        ->first();
                    $shouldShow = $previousApproval && $previousApproval->status == 1;
                }
            }

            return $shouldShow ? $approval : null;
        })
        ->filter() // Remove null entries
        ->values(); // Reindex the collection

        Log::debug('Final approvals count: ' . $approvals->count());
        Log::debug('Final approvals data: ', $approvals->all());

        return Inertia::render('Approvals/Index', [
            'approvals' => $approvals->all(),
        ]);
    }
}