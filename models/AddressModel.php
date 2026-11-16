<?php

class AddressModel extends BaseModel
{
    const MAX_ADDRESSES = 6;

    public function forUser($userId)
    {
        return $this->execute(
            'SELECT * FROM user_addresses
             WHERE user_id = :user_id
             ORDER BY is_default DESC, updated_at DESC, id DESC',
            ['user_id' => (int) $userId]
        )->fetchAll();
    }

    public function findForUser($id, $userId)
    {
        return $this->execute(
            'SELECT * FROM user_addresses WHERE id = :id AND user_id = :user_id LIMIT 1',
            ['id' => (int) $id, 'user_id' => (int) $userId]
        )->fetch();
    }

    public function defaultForUser($userId)
    {
        return $this->execute(
            'SELECT * FROM user_addresses WHERE user_id = :user_id
             ORDER BY is_default DESC, updated_at DESC, id DESC LIMIT 1',
            ['user_id' => (int) $userId]
        )->fetch();
    }

    /**
     * Lưu địa chỉ vào sổ. Địa chỉ trùng sẽ được cập nhật và đặt làm mặc định.
     */
    public function saveForUser($userId, array $address)
    {
        $userId = (int) $userId;
        $payload = [
            'user_id' => $userId,
            'label' => limit_text(trim((string) ($address['label'] ?? '')), 60) ?: 'Địa chỉ đã lưu',
            'full_name' => trim((string) ($address['full_name'] ?? '')),
            'phone' => trim((string) ($address['phone'] ?? '')),
            'city' => trim((string) ($address['city'] ?? '')),
            'district' => trim((string) ($address['district'] ?? '')),
            'ward' => trim((string) ($address['ward'] ?? '')),
            'address' => trim((string) ($address['address'] ?? '')),
        ];

        $this->pdo->beginTransaction();

        try {
            $existing = $this->execute(
                'SELECT id FROM user_addresses
                 WHERE user_id = :user_id AND full_name = :full_name AND phone = :phone
                   AND city = :city AND district = :district AND ward = :ward AND address = :address
                 LIMIT 1',
                [
                    'user_id' => $payload['user_id'],
                    'full_name' => $payload['full_name'],
                    'phone' => $payload['phone'],
                    'city' => $payload['city'],
                    'district' => $payload['district'],
                    'ward' => $payload['ward'],
                    'address' => $payload['address'],
                ]
            )->fetch();

            if ($existing) {
                $addressId = (int) $existing['id'];
                $this->execute(
                    'UPDATE user_addresses SET label = :label, updated_at = NOW() WHERE id = :id',
                    ['label' => $payload['label'], 'id' => $addressId]
                );
            } else {
                $this->trimAddresses($userId);
                $this->execute(
                    'INSERT INTO user_addresses
                     (user_id, label, full_name, phone, city, district, ward, address, is_default, created_at, updated_at)
                     VALUES (:user_id, :label, :full_name, :phone, :city, :district, :ward, :address, 0, NOW(), NOW())',
                    $payload
                );
                $addressId = (int) $this->pdo->lastInsertId();
            }

            $this->execute(
                'UPDATE user_addresses SET is_default = (id = :id) WHERE user_id = :user_id',
                ['id' => $addressId, 'user_id' => $userId]
            );
            $this->pdo->commit();

            return $addressId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function deleteForUser($id, $userId)
    {
        $address = $this->findForUser($id, $userId);
        if (!$address) {
            return false;
        }

        $this->pdo->beginTransaction();

        try {
            $this->execute(
                'DELETE FROM user_addresses WHERE id = :id AND user_id = :user_id',
                ['id' => (int) $id, 'user_id' => (int) $userId]
            );

            if (!empty($address['is_default'])) {
                $next = $this->execute(
                    'SELECT id FROM user_addresses WHERE user_id = :user_id
                     ORDER BY updated_at DESC, id DESC LIMIT 1',
                    ['user_id' => (int) $userId]
                )->fetch();
                if ($next) {
                    $this->execute(
                        'UPDATE user_addresses SET is_default = 1 WHERE id = :id',
                        ['id' => (int) $next['id']]
                    );
                }
            }

            $this->pdo->commit();

            return true;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * Giữ sổ địa chỉ gọn nhẹ: xóa địa chỉ cũ nhất không phải mặc định khi vượt giới hạn.
     */
    private function trimAddresses($userId)
    {
        $total = (int) $this->execute(
            'SELECT COUNT(*) FROM user_addresses WHERE user_id = :user_id',
            ['user_id' => (int) $userId]
        )->fetchColumn();

        if ($total < self::MAX_ADDRESSES) {
            return;
        }

        $overflow = $total - self::MAX_ADDRESSES + 1;
        for ($index = 0; $index < $overflow; $index++) {
            $oldest = $this->execute(
                'SELECT id FROM user_addresses
                 WHERE user_id = :user_id AND is_default = 0
                 ORDER BY updated_at ASC, id ASC LIMIT 1',
                ['user_id' => (int) $userId]
            )->fetch();
            if (!$oldest) {
                break;
            }
            $this->execute(
                'DELETE FROM user_addresses WHERE id = :id',
                ['id' => (int) $oldest['id']]
            );
        }
    }
}
