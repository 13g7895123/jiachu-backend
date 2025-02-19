<?php

namespace App\Models\Jiachu;

use CodeIgniter\Model;

class M_User extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['username', 'email', 'password', 'status'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'username' => 'required|min_length[3]|max_length[255]|is_unique[users.username,id,{id}]',
        'password' => 'required|min_length[8]',
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;

    /**
     * 驗證用戶登入
     */
    public function validateLogin(string $account, string $password): array
    {
        log_message('debug', 'Attempting login with account: ' . $account);
        
        $user = $this->where('username', $account)
                    ->where('status', 'active')
                    ->first();

        log_message('debug', 'Query result: ' . print_r($user, true));

        if (!$user) {
            log_message('debug', 'User not found or inactive');
            return [
                'success' => false,
                'message' => '用戶不存在或已被停用'
            ];
        }

        if (!password_verify($password, $user['password'])) {
            log_message('debug', 'Password verification failed');
            return [
                'success' => false,
                'message' => '密碼錯誤'
            ];
        }

        unset($user['password']);
        return [
            'success' => true,
            'user' => $user
        ];
    }

    /**
     * 創建新用戶
     */
    public function createUser(array $userData): array
    {
        try {
            log_message('debug', 'Starting user creation with data: ' . print_r($userData, true));

            // 確保必要欄位存在
            if (empty($userData['username']) || empty($userData['password'])) {
                log_message('error', 'Missing required fields');
                return [
                    'success' => false,
                    'message' => '缺少必要欄位'
                ];
            }

            $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            log_message('debug', 'Attempting to insert user');
            $this->db->transStart();
            // print_r($userData); die();
            $this->insert($userData);
            $insertId = $this->db->insertId();
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                log_message('error', 'Database transaction failed: ' . print_r($this->db->error(), true));
                return [
                    'success' => false,
                    'message' => '資料庫操作失敗'
                ];
            }

            log_message('debug', 'User created successfully with ID: ' . $insertId);
            return [
                'success' => true,
                'message' => '用戶創建成功',
                'user_id' => $insertId
            ];
        } catch (\Exception $e) {
            log_message('error', 'Error creating user: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => '用戶創建失敗: ' . $e->getMessage()
            ];
        }
    }
}
