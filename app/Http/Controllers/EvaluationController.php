<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\User;
use App\Models\Approval;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EvaluationController extends Controller
{
    public function index()
    {
        $evaluations = Evaluation::with('quotations.supplier', 'quotations.products')->get();

        return Inertia::render('Evaluations/Index', [
            'evaluations' => $evaluations,
        ]);
    }

    public function create()
    {
        return Inertia::render('Evaluations/Create', [
            'suppliers' => Supplier::all(),
            'products' => Product::all(),
            'users' => User::all(),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'products' => 'required|array|min:1',
                'products.*' => 'exists:products,id',
                'quantities' => 'required|array',
                'quantities.*' => 'required|numeric|min:0',
                'quotations' => 'required|array|min:1',
                'quotations.*.supplier_id' => 'required|exists:suppliers,id',
                'quotations.*.specifications' => 'required|array',
                'quotations.*.specifications.*' => 'required|string|max:255',
                'quotations.*.prices' => 'required|array',
                'quotations.*.prices.*' => 'required|numeric|min:0',
                'quotations.*.discounts' => 'nullable|array',
                'quotations.*.discounts.*' => 'nullable|numeric|min:0',
                'quotations.*.criteria' => 'required|array',
                'quotations.*.criteria.price' => 'nullable|string|max:255',
                'quotations.*.criteria.quality' => 'nullable|string|max:255',
                'quotations.*.criteria.lead_time' => 'nullable|string|max:255',
                'quotations.*.criteria.warranty' => 'nullable|string|max:255',
                'quotations.*.criteria.term_payment' => 'nullable|string|max:255',
                'recommendation' => 'required|string|max:1000',
                'reviewed_by' => 'required|exists:users,id',
                'approved_by' => 'required|exists:users,id',
                'acknowledged_by' => 'required|exists:users,id',
            ]);

            $evaluation = DB::transaction(function () use ($data, $request) {
                $evaluation = Evaluation::create([
                    'reference' => $this->generateReference(),
                    'recommendation' => $data['recommendation'],
                    'reviewed_by' => $data['reviewed_by'],
                    'approved_by' => $data['approved_by'],
                    'acknowledged_by' => $data['acknowledged_by'],
                ]);

                foreach ($data['quotations'] as $quoteData) {
                    $quotation = $evaluation->quotations()->create([
                        'supplier_id' => $quoteData['supplier_id'],
                        'criteria' => json_encode($quoteData['criteria']),
                    ]);

                    $productData = [];
                    foreach ($data['products'] as $productId) {
                        $productData[$productId] = [
                            'quantity' => $data['quantities'][$productId] ?? 0,
                            'specification' => $quoteData['specifications'][$productId] ?? '',
                            'price' => $quoteData['prices'][$productId] ?? 0,
                            'discount' => $quoteData['discounts'][$productId] ?? 0,
                        ];
                    }
                    $quotation->products()->sync($productData);
                }

                $this->storeApprovals($evaluation->id, $request);

                return $evaluation;
            });

            return response()->json([
                'message' => 'Evaluation created successfully!',
                'evaluation' => $this->transformEvaluationData($evaluation),
            ], 201);

        } catch (ValidationException $e) {
            Log::error('Validation failed in EvaluationController@store', [
                'errors' => $e->errors(),
                'user_id' => auth()->id(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error in EvaluationController@store', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            return redirect()->back()->with('error', 'An error occurred while creating the evaluation.');
        }
    }

    private function transformEvaluationData(Evaluation $evaluation)
    {
        $evaluation->load('quotations.supplier', 'quotations.products');
        return [
            'id' => $evaluation->id,
            'reference' => $evaluation->reference,
            'products' => $evaluation->quotations->flatMap->products->pluck('id')->unique()->values()->all(),
            'quantities' => $evaluation->quotations->first() ? collect($evaluation->quotations->first()->products)->pluck('pivot.quantity', 'id')->all() : [],
            'quotations' => $evaluation->quotations->map(function ($quotation) {
                return [
                    'id' => $quotation->id,
                    'supplier_id' => $quotation->supplier_id,
                    'specifications' => collect($quotation->products)->pluck('pivot.specification', 'id')->all(),
                    'prices' => collect($quotation->products)->pluck('pivot.price', 'id')->all(),
                    'discounts' => collect($quotation->products)->pluck('pivot.discount', 'id')->all(),
                    'criteria' => json_decode($quotation->criteria, true),
                ];
            })->all(),
            'recommendation' => $evaluation->recommendation,
            'reviewed_by' => $evaluation->reviewed_by,
            'approved_by' => $evaluation->approved_by,
            'acknowledged_by' => $evaluation->acknowledged_by,
        ];
    }

    public function show(Evaluation $evaluation)
    {
        try {
            // Load related data for the evaluation
            $evaluation->load('quotations.supplier', 'quotations.products');

            // Fetch associated approvals
            $approvals = Approval::where([
                'approval_id' => $evaluation->id,
                'docs_type' => 8,
            ])->get()->map(function ($approval) {
                return [
                    'status_type' => $approval->status_type,
                    'user_id' => $approval->user_id,
                    'approval_name' => $approval->approval_name,
                    'docs_type' => $approval->docs_type,
                    'name' => $approval->user ? $approval->user->name : 'N/A',
                    'position' => $approval->user ? ($approval->user->position ?? 'N/A') : 'N/A',
                    'status' => $approval->status ?? 0, // 0: pending, 1: approved, -1: rejected
                    'signature' => $approval->signature ?? null,
                    'click_date' => $approval->updated_at ? $approval->updated_at->format('Y-m-d') : null,
                ];
            })->all();

            // Log the access for auditing
            Log::info('Evaluation viewed', [
                'evaluation_id' => $evaluation->id,
                'user_id' => auth()->id(),
            ]);

            // Render Inertia view
            return Inertia::render('Evaluations/Show', [
                'evaluation' => $this->transformEvaluationData($evaluation),
                'approvals' => $approvals,
                'suppliers' => Supplier::all()->map(function ($supplier) {
                    return [
                        'id' => $supplier->id,
                        'name' => $supplier->name,
                        'address' => $supplier->address ?? '',
                        'phone' => $supplier->number ?? '',
                        'vat' => $supplier->vat ?? 0,
                    ];
                })->all(),
                'products' => Product::all()->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'code' => $product->sku ?? '',
                        'name' => $product->product_description ?? '',
                        'uom' => $product->uom ?? '',
                    ];
                })->all(),
                'users' => User::all()->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'position' => $user->position ?? 'N/A',
                        'signature' => $user->signature ?? null,
                    ];
                })->all(),
                'currentUser' => auth()->user() ? [
                    'id' => auth()->user()->id,
                    'name' => auth()->user()->name,
                    'position' => auth()->user()->position ?? 'N/A',
                ] : null,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in EvaluationController@show', [
                'message' => $e->getMessage(),
                'evaluation_id' => $evaluation->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->with('error', 'An error occurred while retrieving the evaluation.');
        }
    }

    public function edit(Evaluation $evaluation)
    {
        $approvals = Approval::where([
            'approval_id' => $evaluation->id,
            'docs_type' => 8,
        ])->get()->map(function ($approval) {
            return [
                'status_type' => $approval->status_type,
                'user_id' => $approval->user_id,
                'approval_name' => $approval->approval_name,
                'docs_type' => $approval->docs_type,
            ];
        })->all();

        Log::info('Approvals fetched for evaluation', [
            'evaluation_id' => $evaluation->id,
            'approvals' => $approvals,
        ]);

        return Inertia::render('Evaluations/Edit', [
            'evaluation' => $this->transformEvaluationData($evaluation),
            'suppliers' => Supplier::all(),
            'products' => Product::all(),
            'users' => User::all(),
            'approvals' => $approvals,
        ]);
    }

    public function update(Request $request, Evaluation $evaluation)
    {
        try {
            $data = $request->validate([
                'products' => 'required|array|min:1',
                'products.*' => 'exists:products,id',
                'quantities' => 'required|array',
                'quantities.*' => 'required|numeric|min:0',
                'quotations' => 'required|array|min:1',
                'quotations.*.id' => 'nullable|exists:quotations,id',
                'quotations.*.supplier_id' => 'required|exists:suppliers,id',
                'quotations.*.specifications' => 'required|array',
                'quotations.*.specifications.*' => 'required|string|max:255',
                'quotations.*.prices' => 'required|array',
                'quotations.*.prices.*' => 'required|numeric|min:0',
                'quotations.*.discounts' => 'nullable|array',
                'quotations.*.discounts.*' => 'nullable|numeric|min:0',
                'quotations.*.criteria' => 'required|array',
                'quotations.*.criteria.price' => 'nullable|string|max:255',
                'quotations.*.criteria.quality' => 'nullable|string|max:255',
                'quotations.*.criteria.lead_time' => 'nullable|string|max:255',
                'quotations.*.criteria.warranty' => 'nullable|string|max:255',
                'quotations.*.criteria.term_payment' => 'nullable|string|max:255',
                'recommendation' => 'required|string|max:1000',
                'reviewed_by' => 'required|exists:users,id',
                'approved_by' => 'required|exists:users,id',
                'acknowledged_by' => 'required|exists:users,id',
            ]);

            $evaluation = DB::transaction(function () use ($data, $request, $evaluation) {
                $evaluation->update([
                    'recommendation' => $data['recommendation'],
                    'reviewed_by' => $data['reviewed_by'],
                    'approved_by' => $data['approved_by'],
                    'acknowledged_by' => $data['acknowledged_by'],
                ]);

                $submittedQuotationIds = collect($data['quotations'])->pluck('id')->filter()->all();
                $evaluation->quotations()->whereNotIn('id', $submittedQuotationIds)->delete();

                foreach ($data['quotations'] as $quoteData) {
                    if (!empty($quoteData['id']) && $quotation = $evaluation->quotations()->find($quoteData['id'])) {
                        $quotation->update([
                            'supplier_id' => $quoteData['supplier_id'],
                            'criteria' => json_encode($quoteData['criteria']),
                        ]);
                    } else {
                        $quotation = $evaluation->quotations()->create([
                            'supplier_id' => $quoteData['supplier_id'],
                            'criteria' => json_encode($quoteData['criteria']),
                        ]);
                    }

                    $productData = [];
                    foreach ($data['products'] as $productId) {
                        $productData[$productId] = [
                            'quantity' => $data['quantities'][$productId] ?? 0,
                            'specification' => $quoteData['specifications'][$productId] ?? '',
                            'price' => $quoteData['prices'][$productId] ?? 0,
                            'discount' => $quoteData['discounts'][$productId] ?? 0,
                        ];
                    }
                    $quotation->products()->sync($productData);
                }

                $this->storeApprovals($evaluation->id, $request);

                return $evaluation;
            });

            return response()->json([
                'message' => 'Evaluation updated successfully!',
                'evaluation' => $this->transformEvaluationData($evaluation),
            ], 200);
            
        } catch (ValidationException $e) {
            Log::error('Validation failed in EvaluationController@update', [
                'errors' => $e->errors(),
                'evaluation_id' => $evaluation->id,
                'user_id' => auth()->id(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error in EvaluationController@update', [
                'message' => $e->getMessage(),
                'evaluation_id' => $evaluation->id,
                'user_id' => auth()->id(),
            ]);
            return redirect()->back()->with('error', 'An error occurred while updating the evaluation.');
        }
    }

    public function destroy(Evaluation $evaluation)
    {
        try {
            DB::transaction(function () use ($evaluation) {
                // Delete associated approvals
                Approval::where([
                    'approval_id' => $evaluation->id,
                    'docs_type' => 8,
                ])->delete();

                // Delete the evaluation (cascades to quotations and quotation_product)
                $evaluation->delete();
            });

            return response()->json([
                'message' => 'Evaluation deleted successfully!',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in EvaluationController@destroy', [
                'message' => $e->getMessage(),
                'evaluation_id' => $evaluation->id,
                'user_id' => auth()->id(),
            ]);
            return redirect()->back()->with('error', 'An error occurred while deleting the evaluation.');
        }
    }

    private function generateApprovalName($statusType)
    {
        return match ($statusType) {
            3 => 'Approve',
            7 => 'Review',
            2 => 'Acknowledge',
            default => 'Processed',
        };
    }

    private function storeApprovals($evaluationId, Request $request)
    {
        try {
            $docsType = 8;

            $approvalData = [
                ['status_type' => 7, 'user_id' => $request->reviewed_by],
                ['status_type' => 3, 'user_id' => $request->approved_by],
                ['status_type' => 2, 'user_id' => $request->acknowledged_by],
            ];

            foreach ($approvalData as $data) {
                if ($data['user_id']) {
                    $approval = Approval::where([
                        'approval_id' => $evaluationId,
                        'status_type' => $data['status_type'],
                        'docs_type' => $docsType,
                    ])->first();

                    $approvalName = $this->generateApprovalName($data['status_type']);

                    if ($approval) {
                        $approval->update([
                            'user_id' => $data['user_id'],
                            'docs_type' => $docsType,
                            'approval_name' => "Evaluation-$approvalName",
                        ]);
                    } else {
                        Approval::create([
                            'approval_id' => $evaluationId,
                            'status_type' => $data['status_type'],
                            'docs_type' => $docsType,
                            'user_id' => $data['user_id'],
                            'approval_name' => "Evaluation-$approvalName",
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in EvaluationController@storeApprovals', [
                'message' => $e->getMessage(),
                'evaluation_id' => $evaluationId,
                'user_id' => auth()->id(),
            ]);
            throw $e;
        }
    }

    private function generateReference()
    {
        $yearMonth = now()->format('Y') . now()->format('m');
        $latestEvaluation = Evaluation::where('reference', 'like', "EV-$yearMonth%")
            ->orderBy('reference', 'desc')
            ->first();
        
        $sequence = 1;
        if ($latestEvaluation && preg_match('/EV-\d{6}-(\d{4})/', $latestEvaluation->reference, $matches)) {
            $sequence = (int)$matches[1] + 1;
        }

        return sprintf("EV-%s-%04d", $yearMonth, $sequence);
    }
}