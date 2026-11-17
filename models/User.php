<?php

class User
{
    private PDO $pdo;

    

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    

    public function findByEmail(string $email): array|false
    {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    

    public function findById(int|string $id): array|false
    {
        $sql = "SELECT id, full_name, email, phone, address, avatar, role, status, created_at 
                FROM users 
                WHERE id = :id 
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => (int)$id]);
        return $stmt->fetch();
    }

    

    public function create(array $data): int
    {
        $sql = "INSERT INTO users (full_name, email, password_hash, phone, address, role, status)
                VALUES (:full_name, :email, :password_hash, :phone, :address, :role, :status)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'full_name'     => $data['full_name'],
            'email'         => $data['email'],
            'password_hash' => $data['password_hash'],
            'phone'         => $data['phone'] ?? null,
            'address'       => $data['address'] ?? null,
            'role'          => $data['role'] ?? 'user',
            'status'        => isset($data['status']) ? (int)$data['status'] : 1,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    

    

    public function updateUser(int $id, array $data): bool
    {
        $sql = "UPDATE users
                SET full_name = :full_name,
                    email = :email,
                    phone = :phone,
                    address = :address,
                    role = :role
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'id'        => $id,
            'full_name' => $data['full_name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'address'   => $data['address'] ?? null,
            'role'      => $data['role'],
        ]);
    }

        public function updateProfile(int $id, array $data): bool
        {
            $sql = "UPDATE users 
                    SET full_name = :full_name, phone = :phone, address = :address
                    WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                'id'        => $id,
                'full_name' => $data['full_name'],
                'phone'     => $data['phone'] ?? null,
                'address'   => $data['address'] ?? null,
            ]);
        }

    

    public function changePassword(int $id, string $newPasswordHash): bool
    {
        $sql = "UPDATE users SET password_hash = :hash WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id'   => $id,
            'hash' => $newPasswordHash,
        ]);
    }

    

    public function getList(string $keyword = '', int $limit = 10, int $offset = 0): array
    {
        $params = [];
        $where = "WHERE 1=1";

    if ($keyword !== '') {
        $where .= " AND (full_name LIKE :keyword_name OR email LIKE :keyword_email)";
        $params['keyword_name'] = '%' . $keyword . '%';
        $params['keyword_email'] = '%' . $keyword . '%';
    }

        $sql = "SELECT id, full_name, email, phone, address, role, status, created_at 
                FROM users 
                {$where}
                ORDER BY created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    

    public function count(string $keyword = '') : int
    {
        $params = [];
        $where = "WHERE 1=1";

        if ($keyword !== '') {
        $where .= " AND (full_name LIKE :keyword_name OR email LIKE :keyword_email)";
        $params['keyword_name'] = '%' . $keyword . '%';
        $params['keyword_email'] = '%' . $keyword . '%';
    }

        $sql = "SELECT COUNT(*) FROM users {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    

    public function setStatus(int $id, int $status): bool
    {
        $sql = "UPDATE users SET status = :status WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id'     => $id,
            'status' => $status,
        ]);
    }

    

    public function setRole(int $id, string $role): bool
    {
        $sql = "UPDATE users SET role = :role WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id'   => $id,
            'role' => $role,
        ]);
    }
    

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'id' => $id
        ]);
    }

}
