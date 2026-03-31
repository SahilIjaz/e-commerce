<?php

namespace App\Controllers;

use App\Database;
use App\Auth;
use MongoDB\BSON\ObjectId;

class CartController
{
    private $cartDb;
    private $productDb;

    public function __construct()
    {
        $this->cartDb = Database::getInstance()->getCollection('carts');
        $this->productDb = Database::getInstance()->getCollection('products');
    }

    public function getCart()
    {
        $user = Auth::requireAuth();

        try {
            $cart = $this->cartDb->findOne(['userId' => $user->userId]);

            if (!$cart) {
                echo json_encode([
                    'success' => true,
                    'cart' => [
                        'items' => [],
                        'total' => 0,
                        'itemCount' => 0
                    ]
                ]);
                return;
            }

            $total = 0;
            foreach ($cart['items'] as $item) {
                $total += $item['price'] * $item['quantity'];
            }

            echo json_encode([
                'success' => true,
                'cart' => [
                    'items' => $cart['items'],
                    'total' => $total,
                    'itemCount' => count($cart['items'])
                ]
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function addItem()
    {
        $user = Auth::requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['productId']) || !isset($data['quantity'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Product ID and quantity are required']);
            return;
        }

        try {
            $product = $this->productDb->findOne(['_id' => new ObjectId($data['productId'])]);

            if (!$product) {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found']);
                return;
            }

            if ($product['stock'] < $data['quantity']) {
                http_response_code(400);
                echo json_encode(['error' => 'Insufficient stock']);
                return;
            }

            $item = [
                'id' => (string)$product['_id'],
                'productId' => (string)$product['_id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => (int)$data['quantity'],
                'image' => $product['images'][0] ?? null,
                'addedAt' => new \MongoDB\BSON\UTCDateTime()
            ];

            $cart = $this->cartDb->findOne(['userId' => $user->userId]);

            if ($cart) {
                // Check if item already exists
                $itemExists = false;
                foreach ($cart['items'] as $key => $cartItem) {
                    if ($cartItem['productId'] === $item['productId']) {
                        $this->cartDb->updateOne(
                            ['userId' => $user->userId],
                            ['$set' => ['items.' . $key . '.quantity' => $cartItem['quantity'] + $item['quantity']]]
                        );
                        $itemExists = true;
                        break;
                    }
                }

                if (!$itemExists) {
                    $this->cartDb->updateOne(
                        ['userId' => $user->userId],
                        ['$push' => ['items' => $item]]
                    );
                }
            } else {
                $this->cartDb->insertOne([
                    'userId' => $user->userId,
                    'items' => [$item],
                    'createdAt' => new \MongoDB\BSON\UTCDateTime()
                ]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Item added to cart'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function updateItem()
    {
        $user = Auth::requireAuth();
        $itemId = $_GET['itemId'] ?? null;
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$itemId || !isset($data['quantity'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Item ID and quantity are required']);
            return;
        }

        try {
            $cart = $this->cartDb->findOne(['userId' => $user->userId]);

            if (!$cart) {
                http_response_code(404);
                echo json_encode(['error' => 'Cart not found']);
                return;
            }

            foreach ($cart['items'] as $key => $item) {
                if ($item['productId'] === $itemId) {
                    $this->cartDb->updateOne(
                        ['userId' => $user->userId],
                        ['$set' => ['items.' . $key . '.quantity' => (int)$data['quantity']]]
                    );

                    echo json_encode([
                        'success' => true,
                        'message' => 'Item updated'
                    ]);
                    return;
                }
            }

            http_response_code(404);
            echo json_encode(['error' => 'Item not found in cart']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function removeItem()
    {
        $user = Auth::requireAuth();
        $itemId = $_GET['itemId'] ?? null;

        if (!$itemId) {
            http_response_code(400);
            echo json_encode(['error' => 'Item ID is required']);
            return;
        }

        try {
            $this->cartDb->updateOne(
                ['userId' => $user->userId],
                ['$pull' => ['items' => ['productId' => $itemId]]]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Item removed from cart'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function clearCart()
    {
        $user = Auth::requireAuth();

        try {
            $this->cartDb->deleteOne(['userId' => $user->userId]);

            echo json_encode([
                'success' => true,
                'message' => 'Cart cleared'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
