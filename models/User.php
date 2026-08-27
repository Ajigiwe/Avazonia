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
        $driver=$this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $now = $driver==='sqlite' ? date('Y-m-d H:i:s') : null;
        // Support marketplace fields
        $stmt = $this->db->prepare(
            "INSERT INTO users (email, password_hash, full_name, phone, role, seller_type, buyer_type, verification_level, country_code, company_name, is_business, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)"
        );
        return $stmt->execute([
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['full_name'],
            $data['phone'] ?? null,
            $data['role'] ?? 'customer',
            $data['seller_type'] ?? null,
            $data['buyer_type'] ?? 'individual',
            $data['verification_level'] ?? 'unverified',
            $data['country_code'] ?? 'GH',
            $data['company_name'] ?? null,
            !empty($data['is_business']) ? 1 : 0,
        ]);
    }

    /**
     * Create user with a verification token set (email verification flow)
     */
    public function createWithVerification(array $data, string $token): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO users (email, password_hash, full_name, phone, role, seller_type, buyer_type, verification_level, country_code, company_name, is_business, email_verified, verification_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)"
        );
        return $stmt->execute([
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['full_name'],
            $data['phone'] ?? null,
            $data['role'] ?? 'customer',
            $data['seller_type'] ?? null,
            $data['buyer_type'] ?? 'individual',
            $data['verification_level'] ?? 'unverified',
            $data['country_code'] ?? 'GH',
            $data['company_name'] ?? null,
            !empty($data['is_business']) ? 1 : 0,
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
        $driver=$this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver==='sqlite') { $stmt=$this->db->prepare("UPDATE users SET last_login = datetime('now') WHERE id = ?"); $stmt->execute([$userId]); return; }
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }
    public function updateProfile(int $userId, array $data): bool {
        $stmt=$this->db->prepare("UPDATE users SET full_name=?, phone=?, buyer_type=?, company_name=?, is_business=? WHERE id=?");
        return $stmt->execute([$data['full_name'],$data['phone'],$data['buyer_type']??'individual',$data['company_name']??null, !empty($data['is_business'])?1:0, $userId]);
    }
}

