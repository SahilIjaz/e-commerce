<?php

namespace App\Controllers;

use App\Database;
use App\Auth;
use MongoDB\BSON\ObjectId;
use GuzzleHttp\Client;

class ProductController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getCollection('products');
    }

    public function getAll()
    {
        try {
            $filters = [];

            // Category filter
            if (isset($_GET['category'])) {
                $filters['category'] = $_GET['category'];
            }

            // Search filter
            if (isset($_GET['search'])) {
                $filters['$text'] = ['$search' => $_GET['search']];
            }

            // Price range filter
            if (isset($_GET['minPrice']) && isset($_GET['maxPrice'])) {
                $filters['price'] = [
                    '$gte' => (float)$_GET['minPrice'],
                    '$lte' => (float)$_GET['maxPrice']
                ];
            }

            $products = $this->db->find($filters)->toArray();

            echo json_encode([
                'success' => true,
                'count' => count($products),
                'products' => array_map(function($p) {
                    return $this->formatProduct($p);
                }, $products)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getById()
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Product ID is required']);
                return;
            }

            // Try to find by direct ID match (since we use string IDs in file-based DB)
            $product = $this->db->findOne(['_id' => $id]);

            if (!$product) {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found']);
                return;
            }

            echo json_encode([
                'success' => true,
                'product' => $this->formatProduct($product)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function create()
    {
        $user = Auth::getCurrentUser();
        $sellerId = $user ? $user->userId : 'anonymous';

        $data = json_decode(file_get_contents('php://input'), true);

        // Validation
        $required = ['name', 'description', 'price', 'stock', 'category'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: $field"]);
                return;
            }
        }

        try {
            $product = [
                'name' => $data['name'],
                'description' => $data['description'],
                'price' => (float)$data['price'],
                'stock' => (int)$data['stock'],
                'category' => $data['category'],
                'images' => $data['images'] ?? [],
                'seller' => $sellerId,
                'rating' => 0,
                'reviews' => [],
                'isActive' => true,
                'createdAt' => new \MongoDB\BSON\UTCDateTime(),
                'updatedAt' => new \MongoDB\BSON\UTCDateTime()
            ];

            $result = $this->db->insertOne($product);

            // Add _id to product for response
            $product['_id'] = $result->getInsertedId();

            echo json_encode([
                'success' => true,
                'message' => 'Product created successfully',
                'product' => $this->formatProduct($product)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create product: ' . $e->getMessage()]);
        }
    }

    public function update()
    {
        Auth::requireAuth('client');

        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Product ID is required']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        try {
            $updateData = [];
            if (isset($data['name'])) $updateData['name'] = $data['name'];
            if (isset($data['description'])) $updateData['description'] = $data['description'];
            if (isset($data['price'])) $updateData['price'] = (float)$data['price'];
            if (isset($data['stock'])) $updateData['stock'] = (int)$data['stock'];
            if (isset($data['category'])) $updateData['category'] = $data['category'];
            if (isset($data['images'])) $updateData['images'] = $data['images'];

            $updateData['updatedAt'] = new \MongoDB\BSON\UTCDateTime();

            $result = $this->db->updateOne(
                ['_id' => $id],
                ['$set' => $updateData]
            );

            if ($result->getMatchedCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found']);
                return;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Product updated successfully'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update product: ' . $e->getMessage()]);
        }
    }

    public function delete()
    {
        Auth::requireAuth('client');

        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Product ID is required']);
            return;
        }

        try {
            $result = $this->db->deleteOne(['_id' => $id]);

            if ($result->getDeletedCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found']);
                return;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete product: ' . $e->getMessage()]);
        }
    }

    public function uploadImage()
    {
        Auth::requireAuth('client');

        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Product ID is required']);
            return;
        }

        if (!isset($_FILES['image'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Image file is required']);
            return;
        }

        try {
            $file = $_FILES['image'];
            $fileTmpPath = $file['tmp_name'];

            $cloudinaryUrl = $this->uploadToCloudinary($fileTmpPath);

            $this->db->updateOne(
                ['_id' => $id],
                ['$set' => ['images' => array_merge(
                    $this->db->findOne(['_id' => $id])['images'] ?? [],
                    [$cloudinaryUrl]
                )]]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'imageUrl' => $cloudinaryUrl
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    public function uploadImageForCreate()
    {
        Auth::requireAuth('client');

        if (!isset($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Image file is required']);
            return;
        }

        try {
            $file = $_FILES['file'];
            $fileTmpPath = $file['tmp_name'];

            $cloudinaryUrl = $this->uploadToCloudinary($fileTmpPath);

            echo json_encode([
                'success' => true,
                'imageUrl' => $cloudinaryUrl
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    private function uploadToCloudinary($filePath)
    {
        $cloudName = $_ENV['CLOUDINARY_CLOUD_NAME'];
        $apiKey = $_ENV['CLOUDINARY_API_KEY'];
        $apiSecret = $_ENV['CLOUDINARY_API_SECRET'];

        $client = new \GuzzleHttp\Client();

        try {
            // Use basic authentication with API key and secret
            $response = $client->post(
                "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload",
                [
                    'auth' => [$apiKey, $apiSecret],
                    'multipart' => [
                        [
                            'name' => 'file',
                            'contents' => fopen($filePath, 'r')
                        ]
                    ],
                    'timeout' => 30
                ]
            );

            $result = json_decode($response->getBody(), true);

            if (isset($result['secure_url'])) {
                return $result['secure_url'];
            } else {
                throw new \Exception('No secure_url in Cloudinary response');
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $errorBody = $e->getResponse()->getBody()->getContents();
            throw new \Exception('Cloudinary API Error: ' . $errorBody);
        }
    }

    private function buildMultipartData($data)
    {
        $multipart = [];
        foreach ($data as $key => $value) {
            if (is_resource($value)) {
                $multipart[] = [
                    'name' => $key,
                    'contents' => $value
                ];
            } else {
                $multipart[] = [
                    'name' => $key,
                    'contents' => $value
                ];
            }
        }
        return $multipart;
    }

    private function formatProduct($product)
    {
        return [
            'id' => (string)$product['_id'],
            'name' => $product['name'],
            'description' => $product['description'],
            'price' => $product['price'],
            'stock' => $product['stock'],
            'category' => $product['category'],
            'images' => $product['images'] ?? [],
            'rating' => $product['rating'] ?? 0,
            'seller' => $product['seller'] ?? null,
            'isActive' => $product['isActive'] ?? true,
            'createdAt' => $product['createdAt']
        ];
    }
}
