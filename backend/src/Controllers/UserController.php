<?php

namespace App\Controllers;

use App\Database;
use App\Auth;
use MongoDB\BSON\ObjectId;

class UserController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getCollection('users');
    }

    public function getAll()
    {
        Auth::requireAuth('admin');

        try {
            $users = $this->db->find()->toArray();

            echo json_encode([
                'success' => true,
                'count' => count($users),
                'users' => array_map(function($u) {
                    return $this->formatUser($u);
                }, $users)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getById()
    {
        $user = Auth::requireAuth();
        $id = $_GET['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID is required']);
            return;
        }

        try {
            $userData = $this->db->findOne(['_id' => new ObjectId($id)]);

            if (!$userData) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                return;
            }

            echo json_encode([
                'success' => true,
                'user' => $this->formatUser($userData)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function update()
    {
        $user = Auth::requireAuth();
        $id = $_GET['id'] ?? null;
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID is required']);
            return;
        }

        // Users can only update their own profile unless they're admin
        if ($user->userId !== $id && $user->role !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        try {
            $updateData = [];
            if (isset($data['name'])) $updateData['name'] = $data['name'];
            if (isset($data['profileImage'])) $updateData['profileImage'] = $data['profileImage'];
            if (isset($data['password'])) $updateData['password'] = Auth::hashPassword($data['password']);

            $updateData['updatedAt'] = new \MongoDB\BSON\UTCDateTime();

            $result = $this->db->updateOne(
                ['_id' => new ObjectId($id)],
                ['$set' => $updateData]
            );

            if ($result->getMatchedCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                return;
            }

            echo json_encode([
                'success' => true,
                'message' => 'User updated successfully'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update user: ' . $e->getMessage()]);
        }
    }

    public function delete()
    {
        Auth::requireAuth('admin');

        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID is required']);
            return;
        }

        try {
            $result = $this->db->deleteOne(['_id' => new ObjectId($id)]);

            if ($result->getDeletedCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                return;
            }

            echo json_encode([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete user: ' . $e->getMessage()]);
        }
    }

    private function formatUser($user)
    {
        return [
            'id' => (string)$user['_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'profileImage' => $user['profileImage'] ?? null,
            'isActive' => $user['isActive'] ?? true,
            'createdAt' => $user['createdAt']
        ];
    }
}
