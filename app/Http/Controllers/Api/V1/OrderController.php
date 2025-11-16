<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Services\OrderService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    private $orderService;
    private $orderRepository;

    public function __construct(
        OrderService $orderService,
        OrderRepository $orderRepository
    ) {
        $this->orderService = $orderService;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders",
     *     summary="Get all orders",
     *     tags={"Orders"},
     *     security={{"bearer":{}}},
     *     @OA\Response(response=200, description="List of orders")
     * )
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->isCustomer()) {
            $orders = $this->orderRepository->getCustomerOrders($user->id, $request->get('per_page', 15));
        } else {
            // Admin and vendors can see all orders
            $orders = $this->orderRepository->paginate($request->get('per_page', 15));
        }

        return response()->json($orders);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders",
     *     summary="Create a new order",
     *     tags={"Orders"},
     *     security={{"bearer":{}}},
     *     @OA\Response(response=201, description="Order created successfully")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|string',
            'billing_address' => 'nullable|string',
            'shipping' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['customer_id'] = Auth::id();

        try {
            $order = $this->orderService->createOrder($data);

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders/{id}",
     *     summary="Get order by ID",
     *     tags={"Orders"},
     *     security={{"bearer":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Order details")
     * )
     */
    public function show($id)
    {
        $order = $this->orderRepository->getOrderWithItems($id);

        // Check authorization
        if (Auth::user()->isCustomer() && $order->customer_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($order);
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/orders/{id}/confirm",
     *     summary="Confirm order and deduct inventory",
     *     tags={"Orders"},
     *     security={{"bearer":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Order confirmed")
     * )
     */
    public function confirm($id)
    {
        try {
            $order = $this->orderService->confirmOrder($id);

            return response()->json([
                'message' => 'Order confirmed successfully',
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/orders/{id}/status",
     *     summary="Update order status",
     *     tags={"Orders"},
     *     security={{"bearer":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Order status updated")
     * )
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $order = $this->orderService->updateOrderStatus($id, $request->status);

            return response()->json([
                'message' => 'Order status updated successfully',
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/orders/{id}",
     *     summary="Cancel order",
     *     tags={"Orders"},
     *     security={{"bearer":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Order cancelled")
     * )
     */
    public function destroy($id)
    {
        try {
            $order = $this->orderService->cancelOrder($id);

            return response()->json([
                'message' => 'Order cancelled successfully',
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
