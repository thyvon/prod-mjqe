<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Models\Documents;
use App\Models\DocumentsItems;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // First, retrieve the documents with their items
        $documents = Documents::with('items')->get();
    
        // Set a default icon class if not already set for each document
        foreach ($documents as $document) {
            if (!$document->icon_class) {
                $document->icon_class = 'fas fa-map-signs'; // Default icon
            }
        }
    
        // Pass the documents to the view
        return view('Documents.Index', compact('documents'));
    }

    public function showAllItems()
    {
        $items = DocumentsItems::with('documentation')->get();
    
        return Inertia::render('Documents/ItemList', [
            'items' => $items,
        ]);
    }

    public function backend ()
    {
        $documents = Documents::with('items')->get();
        return Inertia::render('Documents/Index', [
            'documents' => $documents,
        ]);
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_name' => 'required|string|max:255',
            'icon_class' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'items' => 'array',
            'items.*.article_name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.file_name' => 'nullable|string',
            'items.*.file_path' => 'nullable|string',
        ]);
    
        $document = Documents::create([
            'section_name' => $validated['section_name'],
            'icon_class' => $validated['icon_class'],
            'description' => $validated['description'],
        ]);
    
        foreach ($validated['items'] as $item) {
            $document->items()->create([
                'article_name' => $item['article_name'],
                'description' => $item['description'],
                'file_name' => $item['file_name'] ?? null,
                'file_path' => $item['file_path'] ?? null,
            ]);
        }
    
        return response()->json(['message' => 'Document created successfully.']);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Retrieve the document with its associated items by ID
        $document = Documents::with('items')->findOrFail($id);
    
        // Return the document and its associated items as a JSON response
        return response()->json([
            'document' => $document,
        ]);
    }
    

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'section_name' => 'required|string|max:255',
            'icon_class' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'items' => 'array',
            'items.*.id' => 'nullable|exists:documents_article,id', // Validate that item id exists if provided
            'items.*.article_name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.file_name' => 'nullable|string',
            'items.*.file_path' => 'nullable|string',
        ]);
        
        // Find the document by its ID
        $document = Documents::findOrFail($id);
        $document->update([
            'section_name' => $validated['section_name'],
            'icon_class' => $validated['icon_class'],
            'description' => $validated['description'],
        ]);
    
        $existingItemIds = $document->items()->pluck('id')->toArray();
        $submittedItemIds = [];
    
        foreach ($validated['items'] as $itemData) {
            if (isset($itemData['id']) && in_array($itemData['id'], $existingItemIds)) {
                // Update existing item
                $item = $document->items()->find($itemData['id']);
                $item->update([
                    'article_name' => $itemData['article_name'],
                    'description' => $itemData['description'],
                    'file_name' => $itemData['file_name'] ?? null,
                    'file_path' => $itemData['file_path'] ?? null,
                ]);
                $submittedItemIds[] = $item->id;
            } else {
                // Create new item
                $item = $document->items()->create([
                    'article_name' => $itemData['article_name'],
                    'description' => $itemData['description'],
                    'file_name' => $itemData['file_name'] ?? null,
                    'file_path' => $itemData['file_path'] ?? null,
                ]);
                $submittedItemIds[] = $item->id;
            }
        }
    
        // Delete items that were not in the submitted data (items removed from the form)
        $toDelete = array_diff($existingItemIds, $submittedItemIds);
        if (!empty($toDelete)) {
            $document->items()->whereIn('id', $toDelete)->delete();
        }
    
        return response()->json(['message' => 'Document updated successfully.']);
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $document = Documents::findOrFail($id);
    
        // Delete related items
        foreach ($document->items as $item) {
            if ($item->file_path && Storage::exists($item->file_path)) {
                Storage::delete($item->file_path);
            }
        }
    
        $document->items()->delete();
        $document->delete();
    
        return response()->json(['message' => 'Document deleted successfully.']);
    }

    public function uploadArticleImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120', // 5MB max
        ]);
    
        $file = $request->file('image');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('public/article-images', $filename);
    
        $url = Storage::url($path);
    
        return response()->json([
            'url' => $url,
            'file_name' => $filename,
            'file_path' => $path,
        ]);
    }

    public function updateItemStatus(Request $request, $itemId)
    {
        $request->validate([
            'status' => 'required|string|max:255', // Adjust as needed (e.g. enum validation)
        ]);

        $item = DocumentsItems::findOrFail($itemId);

        $item->status = $request->status;
        $item->save();

        return response()->json(['message' => 'Item status updated successfully.']);
    }
}
