<?php
/**
 * Lớp xử lý dữ liệu người dùng (User Model)
 * Phục vụ TV2 (Xác thực, Quản lý tài khoản, Đổi mật khẩu, Admin quản lý user)
 */
class User
{
    private PDO $pdo;

    /**
     * Khởi tạo User model với đối tượng kết nối PDO
     *
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Tìm người dùng theo địa chỉ email
     *
     * @param string $email Địa chỉ email
     * @return array|false
     */
    public function findByEmail(string $email): array|false
    {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    /**
     * Tìm người dùng theo ID
     *
     * @param int|string $id ID người dùng
     * @return array|false
     */
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

    /**
     * Tạo tài khoản người dùng mới
     *
     * @param array{full_name: string, email: string, password_hash: string, phone?: string, address?: string, role?: string, status?: int} $data
     * @return int ID của người dùng vừa tạo
     */
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

    /**
     * Cập nhật thông tin hồ sơ người dùng
     *
     * @param int $id ID người dùng
     * @param array{full_name: string, phone?: string, address?: string} $data
     * @return bool
     */
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

    /**
     * Đổi mật khẩu người dùng
     *
     * @param int $id ID người dùng
     * @param string $newPasswordHash Mật khẩu băm mới
     * @return bool
     */
    public function changePassword(int $id, string $newPasswordHash): bool
    {
        $sql = "UPDATE users SET password_hash = :hash WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id'   => $id,
            'hash' => $newPasswordHash,
        ]);
    }

    /**
     * Lấy danh sách người dùng có phân trang và tìm kiếm (cho Admin - TV2)
     *
     * @param string $keyword Từ khóa tìm kiếm (tên hoặc email)
     * @param int $limit Số bản ghi mỗi trang
     * @param int $offset Vị trí bắt đầu
     * @return array
     */
    public function getList(string $keyword = '', int $limit = 10, int $offset = 0): array
    {
        $params = [];
        $where = "WHERE 1=1";

        if ($keyword !== '') {
            $where .= " AND (full_name LIKE :keyword OR email LIKE :keyword)";
            $params['keyword'] = '%' . $keyword . '%';
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

    /**
     * Đếm tổng số người dùng theo điều kiện tìm kiếm
     *
     * @param string $keyword
     * @return int
     */
    public function count(string $keyword = '') : int
    {
        $params = [];
        $where = "WHERE 1=1";

        if ($keyword !== '') {
            $where .= " AND (full_name LIKE :keyword OR email LIKE :keyword)";
            $params['keyword'] = '%' . $keyword . '%';
        }

        $sql = "SELECT COUNT(*) FROM users {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Cập nhật trạng thái khóa/mở khóa tài khoản (Admin - TV2)
     *
     * @param int $id
     * @param int $status (1 = hoạt động, 0 = khóa)
     * @return bool
     */
    public function setStatus(int $id, int $status): bool
    {
        $sql = "UPDATE users SET status = :status WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id'     => $id,
            'status' => $status,
        ]);
    }

    /**
     * Thay đổi vai trò người dùng (Admin - TV2)
     *
     * @param int $id
     * @param string $role ('admin' hoặc 'user')
     * @return bool
     */
    public function setRole(int $id, string $role): bool
    {
        $sql = "UPDATE users SET role = :role WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id'   => $id,
            'role' => $role,
        ]);
    }
}
