<?php
// models/PasswordReset.php
require_once __DIR__ . '/../core/Model.php';

class PasswordReset extends Model {

    /**
     * Delete any existing (unused) reset tokens for the email,
     * then create a fresh one.
     */
    public function create(string $email, string $token, string $expiresAt): bool {
        // Invalidate old tokens first
        $this->deleteOldTokens($email);

        $stmt = $this->db->prepare(
            "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)"
        );
        return $stmt->execute([$email, $token, $expiresAt]);
    }

    /**
     * Find a valid (non-expired, not-used) token.
     * Returns the row or false.
     */
    public function findValidToken(string $token): array|false {
        $stmt = $this->db->prepare(
            "SELECT * FROM password_resets
             WHERE token = ? AND used = 0 AND expires_at > NOW()
             LIMIT 1"
        );
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Mark a token as used so it can't be replayed.
     */
    public function markUsed(string $token): bool {
        $stmt = $this->db->prepare(
            "UPDATE password_resets SET used = 1 WHERE token = ?"
        );
        return $stmt->execute([$token]);
    }

    /**
     * Remove all reset tokens for an email address (cleanup).
     */
    public function deleteOldTokens(string $email): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM password_resets WHERE email = ?"
        );
        return $stmt->execute([$email]);
    }
}
