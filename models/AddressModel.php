<?php

class AddressModel extends BaseModel
{
    public function listForUser($userId)
    {
        return $this->execute(
            'SELECT * FROM user_addresses
             WHERE user_id = :user_id
             ORDER BY is_default DESC, updated_at DESC, id DESC',
            ['user_id' => (int) $userId]
        )->fetchAll();
    }

    public function findOwned($id, $userId)
    {
        return $this->execute(
            'SELECT * FROM user_addresses WHERE id = :id AND user_id = :user_id LIMIT 1',
            ['id' => (int) $id, 'user_id' => (int) $userId]
        )->fetch();
    }

    public function create($userId, array $address)
    {
        $isDefault = !empty($address['is_default']);
        $this->pdo->beginTransaction();
        try {
            if ($isDefault) {
                $this->execute('UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id', ['user_id' => (int) $userId]);
            }
            $exists = (int) $this->execute(
                'SELECT COUNT(*) FROM user_addresses WHERE user_id = :user_id',
                ['user_id' => (int) $userId]
            )->fetchColumn();
            if ($exists === 0) {
                $isDefault = true;
            }

            $this->execute(
                'INSERT INTO user_addresses
                 (user_id, label, full_name, phone, city, district, ward, address, is_default, created_at)
                 VALUES (:user_id, :label, :full_name, :phone, :city, :district, :ward, :address, :is_default, NOW())',
                [
                    'user_id' => (int) $userId,
                    'label' => $address['label'] !== '' ? $address['label'] : 'Địa chỉ đã lưu',
                    'full_name' => $address['full_name'],
                    'phone' => $address['phone'],
                    'city' => $address['city'],
                    'district' => $address['district'],
                    'ward' => $address['ward'],
                    'address' => $address['address'],
                    'is_default' => $isDefault ? 1 : 0,
                ]
            );
            $id = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();

            return $id;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function update($id, $userId, array $address)
    {
        if (!$this->findOwned($id, $userId)) {
            throw new DomainException('Không tìm thấy địa chỉ cần chỉnh sửa.');
        }

        $this->pdo->beginTransaction();
        try {
            if (!empty($address['is_default'])) {
                $this->execute('UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id', ['user_id' => (int) $userId]);
            }
            $this->execute(
                'UPDATE user_addresses
                 SET label = :label, full_name = :full_name, phone = :phone, city = :city,
                     district = :district, ward = :ward, address = :address,
                     is_default = :is_default
                 WHERE id = :id AND user_id = :user_id',
                [
                    'label' => $address['label'] !== '' ? $address['label'] : 'Địa chỉ đã lưu',
                    'full_name' => $address['full_name'],
                    'phone' => $address['phone'],
                    'city' => $address['city'],
                    'district' => $address['district'],
                    'ward' => $address['ward'],
                    'address' => $address['address'],
                    'is_default' => !empty($address['is_default']) ? 1 : 0,
                    'id' => (int) $id,
                    'user_id' => (int) $userId,
                ]
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function delete($id, $userId)
    {
        $address = $this->findOwned($id, $userId);
        if (!$address) {
            throw new DomainException('Không tìm thấy địa chỉ cần xóa.');
        }

        $this->pdo->beginTransaction();
        try {
            $this->execute('DELETE FROM user_addresses WHERE id = :id AND user_id = :user_id', [
                'id' => (int) $id,
                'user_id' => (int) $userId,
            ]);
            if (!empty($address['is_default'])) {
                $next = $this->execute(
                    'SELECT id FROM user_addresses WHERE user_id = :user_id ORDER BY updated_at DESC, id DESC LIMIT 1',
                    ['user_id' => (int) $userId]
                )->fetchColumn();
                if ($next) {
                    $this->execute('UPDATE user_addresses SET is_default = 1 WHERE id = :id', ['id' => (int) $next]);
                }
            }
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function setDefault($id, $userId)
    {
        if (!$this->findOwned($id, $userId)) {
            throw new DomainException('Không tìm thấy địa chỉ.');
        }

        $this->pdo->beginTransaction();
        try {
            $this->execute('UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id', ['user_id' => (int) $userId]);
            $this->execute('UPDATE user_addresses SET is_default = 1 WHERE id = :id AND user_id = :user_id', [
                'id' => (int) $id,
                'user_id' => (int) $userId,
            ]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
