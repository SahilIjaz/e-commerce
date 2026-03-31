<?php

namespace App\Controllers;

use App\Database;
use App\Auth;
use MongoDB\BSON\ObjectId;

class AuthController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getCollection('users');
    }

    public function register()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validation
        if (!isset($data['email']) || !isset($data['password']) || !isset($data['name']) || !isset($data['role'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        // Check if user already exists
        $existing = $this->db->findOne(['email' => $data['email']]);
        if ($existing) {
            http_response_code(409);
            echo json_encode(['error' => 'Email already registered']);
            return;
        }

        // Validate role
        if (!in_array($data['role'], ['admin', 'client', 'buyer'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid role']);
            return;
        }

        // Create user
        $user = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Auth::hashPassword($data['password']),
            'role' => $data['role'],
            'profileImage' => $data['profileImage'] ?? null,
            'createdAt' => new \MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new \MongoDB\BSON\UTCDateTime(),
            'isActive' => true
        ];

        try {
            $result = $this->db->insertOne($user);
            $userId = (string)$result->getInsertedId();

            $token = Auth::generateToken($userId, $data['role']);

            echo json_encode([
                'success' => true,
                'message' => 'User registered successfully',
                'token' => $token,
                'user' => [
                    'id' => $userId,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => $data['role']
                ]
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
        }
    }

    public function login()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password are required']);
            return;
        }

        $user = $this->db->findOne(['email' => $data['email']]);

        if (!$user || !Auth::verifyPassword($data['password'], $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
            return;
        }

        $token = Auth::generateToken((string)$user['_id'], $user['role']);

        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => (string)$user['_id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'profileImage' => $user['profileImage'] ?? null
            ]
        ]);
    }

    public function logout()
    {
        Auth::requireAuth();

        echo json_encode([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }

    public function getCurrentUser()
    {
        $user = Auth::requireAuth();

        $userData = $this->db->findOne(['_id' => new ObjectId($user->userId)]);

        if (!$userData) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        echo json_encode([
            'success' => true,
            'user' => [
                'id' => (string)$userData['_id'],
                'name' => $userData['name'],
                'email' => $userData['email'],
                'role' => $userData['role'],
                'profileImage' => $userData['profileImage'] ?? null
            ]
        ]);
    }
}
