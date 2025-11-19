<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Services\ProductService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    private $productService;
    private $productRepository;

    public function __construct(
         ProductService $productService,
         ProductRepository $productRepository
    ) {
        $this->productService = $productService;
        $this->productRepository = $productRepository;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'vendor_id', 'min_price', 'max_price']);
        $products = $this->productRepository->searchProducts($filters, $request->get('per_page', 15));

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sku' => 'required|string|unique:products',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if(Auth::user()->role == 'customer'){
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Create the product
        $data = $request->all();
        $data['vendor_id'] = Auth::id();

        $product = $this->productService->createProduct($data);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product,
        ], 201);
    }

    public function show($id)
    {
        $product = $this->productRepository->getProductWithVariants($id);
        return response()->json($product);
    }

    public function update(Request $request, $id)
    {
        // Get the product first
        $product = $this->productRepository->find($id);
        
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        
        // Check if user is a vendor and owns the product
        if (Auth::user()->role === 'vendor' && $product->vendor_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized. You can only update your own products.'], 403);
        }
        
        // Check if user is a customer
        if (Auth::user()->role === 'customer') {
            return response()->json(['message' => 'Unauthorized. Customers cannot update products.'], 403);
        }
        
        $product = $this->productService->updateProduct($id, $request->all());

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product,
        ]);
    }

    public function destroy($id)
    {
        // Get the product first
        $product = $this->productRepository->find($id);
        
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        
        // Check if user is a vendor and owns the product
        if (Auth::user()->role === 'vendor' && $product->vendor_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized. You can only delete your own products.'], 403);
        }
        
        // Check if user is a customer
        if (Auth::user()->role === 'customer') {
            return response()->json(['message' => 'Unauthorized. Customers cannot delete products.'], 403);
        }
        
        $this->productService->deleteProduct($id);

        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function importCsv(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $file = $request->file('file');
        
        // Ensure imports directory exists
        $importsPath = storage_path('app/imports');
        if (!is_dir($importsPath)) {
            mkdir($importsPath, 0755, true);
        }
        
        $path = $file->storeAs('imports', 'products_' . time() . '.csv');

        $result = $this->productService->importProductsFromCsv(Storage::disk('local')->path($path));

        return response()->json([
            'message' => 'Import completed',
            'result' => $result,
        ]);
    }

    public function inventoryReport(Request $request)
    {
        $request->validate([
            'product_id' => 'nullable|integer|exists:products,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $filters = $request->only(['product_id', 'start_date', 'end_date']);
        $perPage = $request->get('per_page', 15);

        // Get the authenticated user
        $user = Auth::user();

        // If user is a vendor, only show their products
        if ($user->role === 'vendor') {
            $filters['vendor_id'] = $user->id;
        }

        $report = $this->productService->generateInventoryReport($filters, $perPage);

        return response()->json($report);
    }

    public function stockReport(Request $request)
    {
        $request->validate([
            'product_id' => 'nullable|integer|exists:products,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'per_page' => 'nullable|integer|min:1|max:100',
            'include_variants' => 'nullable|boolean',
        ]);

        $filters = $request->only(['product_id', 'start_date', 'end_date']);
        $perPage = $request->get('per_page', 15);
        $includeVariants = $request->get('include_variants', false);

        // Get the authenticated user
        $user = Auth::user();

        // If user is a vendor, only show their products
        if ($user->role === 'vendor') {
            $filters['vendor_id'] = $user->id;
        }

        $report = $this->productService->generateStockReport($filters, $perPage, $includeVariants);

        return response()->json($report);
    }
}
