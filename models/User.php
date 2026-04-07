<?php
// models/User.php
require_once __DIR__ . '/../core/Model.php';

class User extends Model {

    public function findByEmail(string $email): array|false {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function findById(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findByVerificationToken(string $token): array|false {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE verification_token = ? AND is_active = 1 LIMIT 1"
        );
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Standard create (auto-login flow — no verification token)
     */
    public function create(array $data): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO users (email, password_hash, full_name, phone, role, email_verified) VALUES (?, ?, ?, ?, ?, 0)"
        );
        return $stmt->execute([
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['full_name'],
            $data['phone'] ?? null,
            'customer',
        ]);
    }

    /**
     * Create user with a verification token set (email verification flow)
     */
    public function createWithVerification(array $data, string $token): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO users (email, password_hash, full_name, phone, role, email_verified, verification_token) VALUES (?, ?, ?, ?, 'customer', 0, ?)"
        );
        return $stmt->execute([
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['full_name'],
            $data['phone'] ?? null,
            $token,
        ]);
    }

    /**
     * Mark a user's email as verified and clear the token.
     */
    public function verify(string $token): bool {
        $stmt = $this->db->prepare(
            "UPDATE users SET email_verified = 1, verification_token = NULL WHERE verification_token = ?"
        );
        return $stmt->execute([$token]);
    }

    /**
     * Set or refresh a user's verification token.
     */
    public function setVerificationToken(int $userId, string $token): bool {
        $stmt = $this->db->prepare(
            "UPDATE users SET verification_token = ? WHERE id = ?"
        );
        return $stmt->execute([$token, $userId]);
    }

    /**
     * Update a user's password hash (for password reset).
     */
    public function updatePassword(int $userId, string $newPassword): bool {
        $stmt = $this->db->prepare(
            "UPDATE users SET password_hash = ? WHERE id = ?"
        );
        return $stmt->execute([
            password_hash($newPassword, PASSWORD_DEFAULT),
            $userId,
        ]);
    }

    public function updateLastLogin(int $userId): void {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }
}

