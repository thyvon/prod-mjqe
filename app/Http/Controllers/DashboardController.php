<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseRequest;
use App\Models\PurchaseOrder;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    private function validateDateRange(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        return [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];
    }

    private function countPRsByStatus($startDate, $endDate, $status = null)
    {
        $query = PurchaseRequest::whereBetween('request_date', [$startDate, $endDate]);
        if ($status) {
            $query->where('status', $status);
        }
        return $query->count();
    }

    private function countPOsByStatus($startDate, $endDate, $status = null)
    {
        $query = PurchaseOrder::whereBetween('date', [$startDate, $endDate]); // Ensure 'date' is correct
        if ($status) {
            $query->where('status', $status);
        }
        return $query->count();
    }

    private function calculatePercentage($part, $total)
    {
        return $total > 0 ? ($part / $total) * 100 : 0;
    }

    public function countPRByDateRange(Request $request)
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->validateDateRange($request);
        $count = $this->countPRsByStatus($startDate, $endDate);
        return response()->json(['count' => $count]);
    }

    public function countPRCompleted(Request $request)
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->validateDateRange($request);
        $count = $this->countPRsByStatus($startDate, $endDate, 'Closed');
        return response()->json(['count' => $count]);
    }

    public function countPRPending(Request $request)
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->validateDateRange($request);
        $count = $this->countPRsByStatus($startDate, $endDate, 'Pending');
        return response()->json(['count' => $count]);
    }

    public function countPRPartial(Request $request)
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->validateDateRange($request);
        $count = $this->countPRsByStatus($startDate, $endDate, 'Partial');
        return response()->json(['count' => $count]);
    }

    public function countPRVoid(Request $request)
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->validateDateRange($request);
        $count = $this->countPRsByStatus($startDate, $endDate, 'Void');
        return response()->json(['count' => $count]);
    }


    // PO Dashboard
    public function countPOByDateRange(Request $request)
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->validateDateRange($request);
        $count = $this->countPOsByStatus($startDate, $endDate);
        return response()->json(['count' => $count]);
    }

    public function countPOCompleted(Request $request)
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->validateDateRange($request);
        $count = $this->countPOsByStatus($startDate, $endDate, 'Closed');
        return response()->json(['count' => $count]);
    }

    public function countPOPending(Request $request)
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->validateDateRange($request);
        $count = $this->countPOsByStatus($startDate, $endDate, 'Pending');
        return response()->json(['count' => $count]);
    }

    public function countPOPartial(Request $request)
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->validateDateRange($request);
        $count = $this->countPOsByStatus($startDate, $endDate, 'Partial');
        return response()->json(['count' => $count]);
    }

    public function countPOVoid(Request $request)
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->validateDateRange($request);
        $count = $this->countPOsByStatus($startDate, $endDate, 'Void');
        return response()->json(['count' => $count]);
    }


    //PR Dashboard Percentage
    public function getCompletedPercentage(Request $request)
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->validateDateRange($request);
        $totalPRs = $this->countPRsByStatus($startDate, $endDate);
        $completedPRs = $this->countPRsByStatus($startDate, $endDate, 'Closed');
        $percentage = $this->calculatePercentage($completedPRs, $totalPRs);
        return response()->json(['completed_percentage' => $percentage]);
    }

    public function getPendingPercentage(Request $request)
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->validateDateRange($request);
        $totalPRs = $this->countPRsByStatus($startDate, $endDate);
        $pendingPRs = $this->countPRsByStatus($startDate, $endDate, 'Pending');
        $percentage = $this->calculatePercentage($pendingPRs, $totalPRs);
        return response()->json(['pending_percentage' => $percentage]);
    }

    public function getPartialPercentage(Request $request)
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->validateDateRange($request);
        $totalPRs = $this->countPRsByStatus($startDate, $endDate);
        $partialPRs = $this->countPRsByStatus($startDate, $endDate, 'Partial');
        $percentage = $this->calculatePercentage($partialPRs, $totalPRs);
        return response()->json(['partial_percentage' => $percentage]);
    }

    public function getVoidPercentage(Request $request)
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->validateDateRange($request);
        $totalPRs = $this->countPRsByStatus($startDate, $endDate);
        $voidPRs = $this->countPRsByStatus($startDate, $endDate, 'Void');
        $percentage = $this->calculatePercentage($voidPRs, $totalPRs);
        return response()->json(['void_percentage' => $percentage]);
    }


    //PO Dashboard Percentage
    public function getCompletedPOPercentage(Request $request)
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->validateDateRange($request);
        $totalPOs = $this->countPOsByStatus($startDate, $endDate);
        $completedPOs = $this->countPOsByStatus($startDate, $endDate, 'Closed');
        $percentage = $this->calculatePercentage($completedPOs, $totalPOs);
        return response()->json(['completed_po_percentage' => $percentage]);
    }

    public function getPendingPOPercentage(Request $request)
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->validateDateRange($request);
        $totalPOs = $this->countPOsByStatus($startDate, $endDate);
        $pendingPOs = $this->countPOsByStatus($startDate, $endDate, 'Pending');
        $percentage = $this->calculatePercentage($pendingPOs, $totalPOs);
        return response()->json(['pending_po_percentage' => $percentage]); // Ensure correct key and value
    }

    public function getPartialPOPercentage(Request $request)
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->validateDateRange($request);
        $totalPOs = $this->countPOsByStatus($startDate, $endDate);
        $partialPOs = $this->countPOsByStatus($startDate, $endDate, 'Partial');
        $percentage = $this->calculatePercentage($partialPOs, $totalPOs);
        return response()->json(['partial_po_percentage' => $percentage]);
    }

    public function getVoidPOPercentage(Request $request)
    {
        ['start_date' => $startDate, 'end_date' => $endDate] = $this->validateDateRange($request);
        $totalPOs = $this->countPOsByStatus($startDate, $endDate);
        $voidPOs = $this->countPOsByStatus($startDate, $endDate, 'Void');
        $percentage = $this->calculatePercentage($voidPOs, $totalPOs);
        return response()->json(['void_po_percentage' => $percentage]);
    }

    public function index()
    {
        return Inertia::render('Dashboard');
    }
}
