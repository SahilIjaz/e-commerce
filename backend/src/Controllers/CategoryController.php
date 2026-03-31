<?php

namespace App\Controllers;

use App\Database;
use App\Auth;
use MongoDB\BSON\ObjectId;

class CategoryController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getCollection('categories');
    }

    public function getAll()
    {
        try {
            $categories = $this->db->find()->toArray();

            echo json_encode([
                'success' => true,
                'categories' => array_map(function($c) {
                    return [
                        'id' => (string)$c['_id'],
                        'name' => $c['name'],
                        'description' => $c['description'] ?? null,
                        'icon' => $c['icon'] ?? null
                    ];
                }, $categories)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function create()
    {
        Auth::requireAuth('admin');

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Category name is required']);
            return;
        }

        try {
            $category = [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'icon' => $data['icon'] ?? null,
                'createdAt' => new \MongoDB\BSON\UTCDateTime()
            ];

            $result = $this->db->insertOne($category);

            echo json_encode([
                'success' => true,
                'message' => 'Category created successfully',
                'categoryId' => (string)$result->getInsertedId()
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create category: ' . $e->getMessage()]);
        }
    }

    public function update()
    {
        Auth::requireAuth('admin');

        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Category ID is required']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        try {
            $updateData = [];
            if (isset($data['name'])) $updateData['name'] = $data['name'];
            if (isset($data['description'])) $updateData['description'] = $data['description'];
            if (isset($data['icon'])) $updateData['icon'] = $data['icon'];

            $result = $this->db->updateOne(
                ['_id' => new ObjectId($id)],
                ['$set' => $updateData]
            );

            if ($result->getMatchedCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Category not found']);
                return;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Category updated successfully'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update category: ' . $e->getMessage()]);
        }
    }

    public function delete()
    {
        Auth::requireAuth('admin');

        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Category ID is required']);
            return;
        }

        try {
            $result = $this->db->deleteOne(['_id' => new ObjectId($id)]);

            if ($result->getDeletedCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Category not found']);
                return;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete category: ' . $e->getMessage()]);
        }
    }
}
