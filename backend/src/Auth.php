<?php

namespace App;





use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth
{
    private static $secret = null;

    private static function getSecret()
    {
        if (self::$secret === null) {
            self::$secret = $_ENV['JWT_SECRET'] ?? 'your_secret_key';
        }
        return self::$secret;
    }

    public static function generateToken($userId, $role)
    {
        $issuedAt = time();
        $expire = $issuedAt + (7 * 24 * 60 * 60); // 7 days

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'userId' => $userId,
            'role' => $role
        ];

        return JWT::encode($payload, self::getSecret(), 'HS256');
    }

    public static function verifyToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key(self::getSecret(), 'HS256'));
            return $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getTokenFromHeader()
    {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            return null;
        }

        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public static function getCurrentUser()
    {
        $token = self::getTokenFromHeader();
        if (!$token) {
            return null;
        }

        return self::verifyToken($token);
    }

    public static function requireAuth($role = null)
    {
        $user = self::getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        if ($role && $user->role !== $role) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        return $user;
    }

    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
}
