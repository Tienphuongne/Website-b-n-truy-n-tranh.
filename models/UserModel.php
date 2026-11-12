<?php

class UserModel extends BaseModel
{
    public function findByLogin($login)
    {
        return $this->execute(
            'SELECT * FROM users WHERE email = :email OR username = :username LIMIT 1',
            ['email' => $login, 'username' => $login]
        )->fetch();
    }

    public function create(array $user)
    {
        $this->execute(
            "INSERT INTO users (full_name, username, email, phone, password_hash, role, created_at)
             VALUES (:full_name, :username, :email, :phone, :password_hash, 'customer', NOW())",
            [
                'full_name' => $user['full_name'],
                'username' => $user['username'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'password_hash' => password_hash($user['password'], PASSWORD_DEFAULT),
            ]
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function emailExists($email)
    {
        return (bool) $this->execute(
            'SELECT 1 FROM users WHERE email = :email LIMIT 1',
            ['email' => $email]
        )->fetchColumn();
    }

    public function findById($id)
    {
        return $this->execute(
            'SELECT id, full_name, username, email, phone, role FROM users WHERE id = :id LIMIT 1',
            ['id' => (int) $id]
        )->fetch();
    }

    public function findByRememberToken($tokenHash)
    {
        return $this->execute(
            'SELECT id, full_name, email, phone, role FROM users
             WHERE remember_token_hash = :token_hash
               AND remember_token_expires_at > NOW()
             LIMIT 1',
            ['token_hash' => $tokenHash]
        )->fetch();
    }

    public function saveRememberToken($id, $tokenHash, $expiresAt)
    {
        $this->execute(
            'UPDATE users SET remember_token_hash = :token_hash,
             remember_token_expires_at = :expires_at WHERE id = :id',
            [
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
                'id' => (int) $id,
            ]
        );
    }

    public function clearRememberToken($id)
    {
        $this->execute(
            'UPDATE users SET remember_token_hash = NULL,
             remember_token_expires_at = NULL WHERE id = :id',
            ['id' => (int) $id]
        );
    }

    public function updateProfile($id, array $profile)
    {
        $this->execute(
            'UPDATE users SET full_name = :full_name, phone = :phone WHERE id = :id',
            [
                'full_name' => $profile['full_name'],
                'phone' => $profile['phone'],
                'id' => (int) $id,
            ]
        );
    }

    public function updatePassword($id, $password)
    {
        $this->execute(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id',
            [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => (int) $id,
            ]
        );
    }
}
