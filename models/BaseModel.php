<?php

class BaseModel
{
    protected $table;
    protected $pdo;

    public function __construct()
    {
        if (DB_NAME === '') {
            throw new RuntimeException('Chưa cấu hình tên cơ sở dữ liệu. Vui lòng đặt biến môi trường DB_NAME.');
        }

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $this->pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
    }

    protected function execute($sql, array $params = [])
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement;
    }
}
