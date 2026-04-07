<?php

namespace App\Controllers;

use App\Database;
use App\Auth;
use MongoDB\BSON\ObjectId;

class OrderController
{
    private $orderDb;
    private $cartDb;
    private $productDb;

    public function __construct()
    {
        $this->orderDb = Database::getInstance()->getCollection('orders');
        $this->cartDb = Database::getInstance()->getCollection('carts');
        $this->productDb = Database::getInstance()->getCollection('products');
    }

    public function getOrders()
    {
        $user = Auth::requireAuth();

        try {
            $filter = ['buyerId' => $user->userId];

            // Admin can see all orders
            if ($user->role === 'admin') {
                $filter = [];
            } elseif ($user->role === 'client') {
                $filter = ['sellerId' => $user->userId];
            }

            $orders = $this->orderDb->find($filter)->toArray();

            echo json_encode([
                'success' => true,
                'orders' => array_map(function($o) {
                    return $this->formatOrder($o);
                }, $orders)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getOrderById()
    {
        $user = Auth::requireAuth();
        $id = $_GET['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Order ID is required']);
            return;
        }

        try {
            // Try to find by direct ID match (since we use string IDs in file-based DB)
            $order = $this->orderDb->findOne(['_id' => $id]);

            if (!$order) {
                http_response_code(404);
                echo json_encode(['error' => 'Order not found']);
                return;
            }

            // Check authorization
            if ($user->role === 'buyer' && $order['buyerId'] !== $user->userId) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }

            if ($user->role === 'client' && $order['sellerId'] !== $user->userId && $user->role !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }

            echo json_encode([
                'success' => true,
                'order' => $this->formatOrder($order)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function createOrder()
    {
        $user = Auth::requireAuth('buyer');
        $data = json_decode(file_get_contents('php://input'), true);

        try {
            $cart = $this->cartDb->findOne(['userId' => $user->userId]);

            if (!$cart || empty($cart['items'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Cart is empty']);
                return;
            }

            $orderItems = [];
            $total = 0;

            foreach ($cart['items'] as $item) {
                // Try to find product - productId should be stored as string from frontend
                $product = $this->productDb->findOne(['_id' => $item['productId']]);

                if (!$product) {
                    // If not found by direct match, it's an error
                    http_response_code(400);
                    echo json_encode(['error' => 'Product not found']);
                    return;
                }

                if (!isset($product['stock']) || $product['stock'] < $item['quantity']) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Insufficient stock for ' . ($product['name'] ?? 'product')]);
                    return;
                }

                $itemTotal = $item['price'] * $item['quantity'];
                $total += $itemTotal;

                $orderItems[] = [
                    'productId' => $item['productId'],
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'image' => $item['image']
                ];
            }

            $order = [
                'buyerId' => $user->userId,
                'sellerId' => $cart['items'][0]['sellerId'] ?? null,
                'items' => $orderItems,
                'total' => $total,
                'status' => 'pending',
                'shippingAddress' => $data['shippingAddress'] ?? null,
                'paymentMethod' => $data['paymentMethod'] ?? 'card',
                'trackingNumber' => null,
                'createdAt' => new \MongoDB\BSON\UTCDateTime(),
                'updatedAt' => new \MongoDB\BSON\UTCDateTime()
            ];

            $result = $this->orderDb->insertOne($order);

            // Clear cart
            $this->cartDb->deleteOne(['userId' => $user->userId]);

            // Reduce product stock
            foreach ($cart['items'] as $item) {
                $product = $this->productDb->findOne(['_id' => $item['productId']]);
                if ($product) {
                    $newStock = ($product['stock'] ?? 0) - $item['quantity'];
                    $this->productDb->updateOne(
                        ['_id' => $item['productId']],
                        ['$set' => ['stock' => max(0, $newStock)]]
                    );
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Order created successfully',
                'orderId' => (string)$result->getInsertedId()
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create order: ' . $e->getMessage()]);
        }
    }

    public function updateOrderStatus()
    {
        $user = Auth::requireAuth();
        $id = $_GET['id'] ?? null;
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$id || !isset($data['status'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Order ID and status are required']);
            return;
        }

        try {
            // Try to find by direct ID match (since we use string IDs in file-based DB)
            $order = $this->orderDb->findOne(['_id' => $id]);

            if (!$order) {
                http_response_code(404);
                echo json_encode(['error' => 'Order not found']);
                return;
            }

            // Only client/admin can update order status
            if ($user->role !== 'admin' && $user->role !== 'client') {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }

            $this->orderDb->updateOne(
                ['_id' => $id],
                ['$set' => [
                    'status' => $data['status'],
                    'trackingNumber' => $data['trackingNumber'] ?? null,
                    'updatedAt' => new \MongoDB\BSON\UTCDateTime()
                ]]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Order status updated'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function formatOrder($order)
    {
        return [
            'id' => (string)$order['_id'],
            'buyerId' => $order['buyerId'],
            'items' => $order['items'],
            'total' => $order['total'],
            'status' => $order['status'],
            'shippingAddress' => $order['shippingAddress'],
            'paymentMethod' => $order['paymentMethod'],
            'trackingNumber' => $order['trackingNumber'] ?? null,
            'createdAt' => $order['createdAt'],
            'updatedAt' => $order['updatedAt']
        ];
    }
}
