<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Services\ProductService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ProductRepository;
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
        $product = $this->productService->updateProduct($id, $request->all());

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product,
        ]);
    }

    public function destroy($id)
    {
        $this->productService->deleteProduct($id);

        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function importCsv(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $file = $request->file('file');
        $path = $file->storeAs('imports', 'products_' . time() . '.csv');

        $result = $this->productService->importProductsFromCsv(storage_path('app/' . $path));

        return response()->json([
            'message' => 'Import completed',
            'result' => $result,
        ]);
    }
}
