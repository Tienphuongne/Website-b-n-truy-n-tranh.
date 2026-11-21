<?php

class PasswordResetModel extends BaseModel
{
    public function create($userId, $tokenHash, $expiresAt)
    {
        $this->execute(
            'DELETE FROM password_resets WHERE user_id = :user_id OR expires_at < NOW()',
            ['user_id' => (int) $userId]
        );
        $this->execute(
            'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at)
             VALUES (:user_id, :token_hash, :expires_at, NOW())',
            [
                'user_id' => (int) $userId,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
            ]
        );
    }

    public function findValid($tokenHash)
    {
        return $this->execute(
            'SELECT pr.id, pr.user_id, pr.token_hash, pr.expires_at, u.full_name, u.email
             FROM password_resets pr
             INNER JOIN users u ON u.id = pr.user_id
             WHERE pr.token_hash = :token_hash AND pr.expires_at > NOW()
             LIMIT 1',
            ['token_hash' => $tokenHash]
        )->fetch();
    }

    public function delete($tokenHash)
    {
        $this->execute('DELETE FROM password_resets WHERE token_hash = :token_hash', ['token_hash' => $tokenHash]);
    }

    public function deleteForUser($userId)
    {
        $this->execute('DELETE FROM password_resets WHERE user_id = :user_id', ['user_id' => (int) $userId]);
    }
}
