<?php

namespace App\Controllers\Jiachu;

use App\Controllers\BaseController;
use App\Models\Jiachu\M_User;
use App\Models\Jiachu\M_Token;
use CodeIgniter\API\ResponseTrait;

class User extends BaseController
{
    use ResponseTrait;

    protected $userModel;
    protected $session;
    protected $M_Token;
    public function __construct()
    {
        $this->userModel = new M_User();
        $this->session = \Config\Services::session();
        $this->M_Token = new M_Token();
    }

    /**
     * 登入處理
     */
    public function login()
    {
        try {
            if (!$this->request->is('post')) {
                return $this->fail('只接受 POST 請求', 405);
            }

            $rules = [
                'account' => 'required|min_length[3]',
                'password' => 'required|min_length[8]'
            ];

            if (!$this->validate($rules)) {
                return $this->fail($this->validator->getErrors(), 400);
            }

            $postData = $this->request->getJson(true);
            $account = $postData['account'];
            $password = $postData['password'];

            log_message('debug', 'Login attempt - Account: ' . $account);

            $result = $this->userModel->validateLogin($account, $password);

            if (!$result['success']) {
                return $this->fail($result['message'], 401);
            }

            $accessTokenData = $this->M_Token->createToken('access');
            $refreshTokenData = $this->M_Token->createToken('refresh');

            return $this->respond([
                'success' => True,
                'message' => '登入成功',
                'data' => $result['user'],
                'token' => array(
                    'access' => $accessTokenData[0],
                    'access_expired_at' => $accessTokenData[1],
                    'refresh' => $refreshTokenData[0],
                    'refresh_expired_at' => $refreshTokenData[1],
                ),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Login error: ' . $e->getMessage());
            return $this->fail('登入處理發生錯誤: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 登出處理
     */
    public function logout()
    {
        $this->session->destroy();
        return $this->respond(['message' => '登出成功']);
    }

    /**
     * 註冊新用戶
     */
    public function register()
    {
        try {
            if (!$this->request->is('post')) {
                return $this->fail('只接受 POST 請求', 405);
            }

            // 獲取原始請求數據
            // print_r($this->request->getPost()); die();
            $postData = $this->request->getPost();
            // $postData = $this->request->getJSON(true);
            // print_r($postData); die();
            log_message('debug', 'Register request data: ' . print_r($postData, true));

            $rules = [
                'username' => 'required|min_length[3]|is_unique[users.username]',
                'password' => 'required|min_length[8]'
            ];

            if (!$this->validate($rules)) {
                log_message('error', 'Validation errors: ' . print_r($this->validator->getErrors(), true));
                return $this->fail($this->validator->getErrors(), 400);
            }

            $userData = [
                'username' => $postData['username'],
                'password' => $postData['password'],
                'status'   => 'active'
            ];

            log_message('debug', 'Processing user data: ' . print_r($userData, true));
            
            $result = $this->userModel->createUser($userData);
            log_message('debug', 'Create user result: ' . print_r($result, true));

            if (!$result['success']) {
                return $this->fail($result['message'], 400);
            }

            return $this->respondCreated([
                'message' => $result['message'],
                'user_id' => $result['user_id']
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Register error: ' . $e->getMessage());
            return $this->fail('註冊處理發生錯誤: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 獲取當前用戶資訊
     */
    public function getCurrentUser()
    {
        if (!$this->session->get('isLoggedIn')) {
            return $this->fail('未登入', 401);
        }

        return $this->respond([
            'user' => $this->session->get('userData')
        ]);
    }
}
