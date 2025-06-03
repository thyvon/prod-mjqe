<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\User;
use App\Models\Approval;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EvaluationController extends Controller
{
    // List all evaluations
    public function index()
    {
        $evaluations = Evaluation::with('quotations.supplier', 'quotations.products')->latest()->paginate(10);

        return Inertia::render('Evaluations/Index', [
            'evaluations' => $evaluations,
        ]);
    }

    // Show create form
    public function create()
    {
        $suppliers = Supplier::all();
        $products = Product::all();
        $users = User::all();
        return Inertia::render('Evaluations/Create', [
            'suppliers' => $suppliers,
            'products' => $products,
            'users' => $users,
        ]);
    }

    // Store new evaluation with quotations
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
                'quotations.*.prices' => 'required|array',
                'quotations.*.prices.*' => 'required|numeric|min:0',
                'quotations.*.discounts' => 'nullable|array',
                'quotations.*.discounts.*' => 'nullable|numeric|min:0',
                'quotations.*.vat' => 'nullable|array',
                'quotations.*.vat.*' => 'nullable|numeric|min:0',
                'quotations.*.criteria' => 'nullable|array',
                'quotations.*.criteria.price' => 'nullable|string|max:255',
                'quotations.*.criteria.quality' => 'nullable|string|max:255',
                'quotations.*.criteria.lead_time' => 'nullable|string|max:255',
                'quotations.*.criteria.warranty' => 'nullable|string|max:255',
                'quotations.*.criteria.term_payment' => 'nullable|string|max:255',
                'recommendation' => 'required|string|max:1000',
                'reviewed_by' => 'required|exists:users,id',
                'approved_by' => 'required|exists:users,id',
            ]);

            // Create the evaluation
            $evaluation = Evaluation::create([
                'recommendation' => $data['recommendation'],
                'reviewed_by' => $data['reviewed_by'],
                'approved_by' => $data['approved_by'],
            ]);

            // Process each quotation
            foreach ($data['quotations'] as $quoteData) {
                $quotation = $evaluation->quotations()->create([
                    'supplier_id' => $quoteData['supplier_id'],
                    'criteria' => json_encode($quoteData['criteria']),
                ]);

                // Sync products with quantities, prices, and discounts
                $productData = [];
                foreach ($data['products'] as $productId) {
                    $productData[$productId] = [
                        'quantity' => $data['quantities'][$productId] ?? 0,
                        'price' => $quoteData['prices'][$productId] ?? 0,
                        'discount' => $quoteData['discounts'][$productId] ?? 0,
                    ];
                }
                $quotation->products()->sync($productData);
            }

            // Store approval records
            $this->storeApprovals($evaluation->id, $request);

            return redirect()->route('evaluations.index')->with('success', 'Evaluation created successfully!');
        } catch (ValidationException $e) {
            // Log validation errors
            Log::error('Validation failed in EvaluationController@store', [
                'errors' => $e->errors(),
                'request_data' => $request->except(['password', 'password_confirmation']), // Exclude sensitive data
                'user_id' => auth()->id(),
            ]);
            throw $e; // Re-throw to return 422 response to client
        } catch (\Exception $e) {
            // Log unexpected errors
            Log::error('Error in EvaluationController@store', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password', 'password_confirmation']),
                'user_id' => auth()->id(),
            ]);
            return redirect()->back()->with('error', 'An error occurred while creating the evaluation.');
        }
    }

    public function show(Evaluation $evaluation)
    {
        $evaluation->load('quotations.supplier', 'quotations.products');

        // Transform the data to match the Vue form structure
        $evaluationData = [
            'id' => $evaluation->id,
            'products' => $evaluation->quotations->flatMap->products->pluck('id')->unique()->values()->all(),
            'quantities' => $evaluation->quotations->first() ? collect($evaluation->quotations->first()->products)->pluck('pivot.quantity', 'id')->all() : [],
            'quotations' => $evaluation->quotations->map(function ($quotation) {
                return [
                    'id' => $quotation->id,
                    'supplier_id' => $quotation->supplier_id,
                    'prices' => collect($quotation->products)->pluck('pivot.price', 'id')->all(),
                    'discounts' => collect($quotation->products)->pluck('pivot.discount', 'id')->all(),
                    'criteria' => json_decode($quotation->criteria, true),
                ];
            })->all(),
            'recommendation' => $evaluation->recommendation,
            'reviewed_by' => $evaluation->reviewed_by,
            'approved_by' => $evaluation->approved_by,
        ];

        return Inertia::render('Evaluations/Show', [
            'evaluation' => $evaluationData,
        ]);
    }

    // Show edit form with loaded relations
    public function edit(Evaluation $evaluation)
    {
        $evaluation->load('quotations.supplier', 'quotations.products');

        // Transform the data to match the Vue form structure
        $evaluationData = [
            'id' => $evaluation->id,
            'products' => $evaluation->quotations->flatMap->products->pluck('id')->unique()->values()->all(),
            'quantities' => $evaluation->quotations->first() ? collect($evaluation->quotations->first()->products)->pluck('pivot.quantity', 'id')->all() : [],
            'quotations' => $evaluation->quotations->map(function ($quotation) {
                return [
                    'id' => $quotation->id,
                    'supplier_id' => $quotation->supplier_id,
                    'prices' => collect($quotation->products)->pluck('pivot.price', 'id')->all(),
                    'discounts' => collect($quotation->products)->pluck('pivot.discount', 'id')->all(),
                    'criteria' => json_decode($quotation->criteria, true),
                ];
            })->all(),
            'recommendation' => $evaluation->recommendation,
            'reviewed_by' => $evaluation->reviewed_by,
            'approved_by' => $evaluation->approved_by,
        ];

        $suppliers = Supplier::all();
        $products = Product::all();
        $users = User::all();

        return Inertia::render('Evaluations/Edit', [
            'evaluation' => $evaluationData,
            'suppliers' => $suppliers,
            'products' => $products,
            'users' => $users,
        ]);
    }

    // Update evaluation and its quotations
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
                'quotations.*.prices' => 'required|array',
                'quotations.*.prices.*' => 'required|numeric|min:0',
                'quotations.*.discounts' => 'nullable|array',
                'quotations.*.discounts.*' => 'nullable|numeric|min:0',
                'quotations.*.vat' => 'nullable|array',
                'quotations.*.vat.*' => 'nullable|numeric|min:0',
                'quotations.*.criteria' => 'nullable|array',
                'quotations.*.criteria.price' => 'nullable|string|max:255',
                'quotations.*.criteria.quality' => 'nullable|string|max:255',
                'quotations.*.criteria.lead_time' => 'nullable|string|max:255',
                'quotations.*.criteria.warranty' => 'nullable|string|max:255',
                'quotations.*.criteria.term_payment' => 'nullable|string|max:255',
                'recommendation' => 'required|string|max:1000',
                'reviewed_by' => 'required|exists:users,id',
                'approved_by' => 'required|exists:users,id',
            ]);

            // Update the evaluation
            $evaluation->update([
                'recommendation' => $data['recommendation'],
                'reviewed_by' => $data['reviewed_by'],
                'approved_by' => $data['approved_by'],
            ]);

            // Collect IDs of submitted quotations
            $submittedQuotationIds = collect($data['quotations'])->pluck('id')->filter()->all();

            // Delete quotations that were removed
            $evaluation->quotations()->whereNotIn('id', $submittedQuotationIds)->delete();

            // Process each quotation
            foreach ($data['quotations'] as $quoteData) {
                if (!empty($quoteData['id'])) {
                    // Update existing quotation
                    $quotation = $evaluation->quotations()->find($quoteData['id']);
                    if ($quotation) {
                        $quotation->update([
                            'supplier_id' => $quoteData['supplier_id'],
                            'criteria' => json_encode($quoteData['criteria']),
                        ]);
                        // Sync products with quantities, prices, and discounts
                        $productData = [];
                        foreach ($data['products'] as $productId) {
                            $productData[$productId] = [
                                'quantity' => $data['quantities'][$productId] ?? 0,
                                'price' => $quoteData['prices'][$productId] ?? 0,
                                'discount' => $quoteData['discounts'][$productId] ?? 0,
                            ];
                        }
                        $quotation->products()->sync($productData);
                    }
                } else {
                    // Create new quotation
                    $quotation = $evaluation->quotations()->create([
                        'supplier_id' => $quoteData['supplier_id'],
                        'criteria' => json_encode($quoteData['criteria']),
                    ]);
                    // Sync products with quantities, prices, and discounts
                    $productData = [];
                    foreach ($data['products'] as $productId) {
                        $productData[$productId] = [
                            'quantity' => $data['quantities'][$productId] ?? 0,
                            'price' => $quoteData['prices'][$productId] ?? 0,
                            'discount' => $quoteData['discounts'][$productId] ?? 0,
                        ];
                    }
                    $quotation->products()->sync($productData);
                }
            }

            // Store approval records
            $this->storeApprovals($evaluation->id, $request);

            return redirect()->route('evaluations.index')->with('success', 'Evaluation updated successfully!');
        } catch (ValidationException $e) {
            // Log validation errors
            Log::error('Validation failed in EvaluationController@update', [
                'errors' => $e->errors(),
                'request_data' => $request->except(['password', 'password_confirmation']),
                'evaluation_id' => $evaluation->id,
                'user_id' => auth()->id(),
            ]);
            throw $e; // Re-throw to return 422 response to client
        } catch (\Exception $e) {
            // Log unexpected errors
            Log::error('Error in EvaluationController@update', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password', 'password_confirmation']),
                'evaluation_id' => $evaluation->id,
                'user_id' => auth()->id(),
            ]);
            return redirect()->back()->with('error', 'An error occurred while updating the evaluation.');
        }
    }

    // Delete evaluation + cascade quotations
    public function destroy(Evaluation $evaluation)
    {
        try {
            $evaluation->delete();
            return redirect()->route('evaluations.index')->with('success', 'Evaluation deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error in EvaluationController@destroy', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
            5 => 'Review',
            default => 'Processed',
        };
    }

    private function storeApprovals($evaluationId, Request $request)
    {
        try {
            $docsType = 10; // Set docs_type for evaluations

            $approvalData = [
                ['status_type' => 5, 'user_id' => $request->reviewed_by],
                ['status_type' => 3, 'user_id' => $request->approved_by],
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
                'trace' => $e->getTraceAsString(),
                'evaluation_id' => $evaluationId,
                'reviewed_by' => $request->reviewed_by,
                'approved_by' => $request->approved_by,
                'user_id' => auth()->id(),
            ]);
            throw $e; // Re-throw to propagate error to parent method
        }
    }
}