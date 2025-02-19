<?php

namespace App\Models\Jiachu;
use CodeIgniter\Model;
use App\Models\M_Common as M_Model_Common;

class M_Token extends Model
{
    protected $db;
    protected $table;
    // protected $M_Model_Common;

    public function __construct()
    {
        $this->db = \Config\Database::connect();  // 預設資料庫
        // $this->M_Model_Common = new M_Model_Common();
    }

    /**
     * 建立新Token
     * @param   string    $type   類型
     * @return  string    $token
     */
    public function createToken($type)
    {
        $length = 20;
        $characters = 'abcdefghjklmnpqrstuvwxyz23456789';  // 排除 I, O, 1, 0
        $maxIndex = strlen($characters) - 1;
        
        do{
            $token = '';

            for ($i = 0; $i < $length; $i++) {
                $randomIndex = mt_rand(0, $maxIndex);
                $token .= $characters[$randomIndex];
            }

            $checkToken = $this->checkTokenExist($token);
        }while($checkToken === False);

        $createTime = date('Y-m-d H:i:s');
        $expireTime = (strtolower($type) === 'access') ? date('Y-m-d H:i:s', strtotime('+1 hour')) : date('Y-m-d H:i:s', strtotime('+1 day'));
        
        $insertData = array(
            'type' => $type,
            'token' => $token,
            'created_at' => $createTime,
            'expired_at' => $expireTime,   
        );
        $this->db->table('token')->insert($insertData);

        return [$token, $expireTime];
    }

    /**
     * 確認Token是否存在
     * @param string $token Token
     * @return boolean
     */
    private function checkTokenExist($token)
    {
        $tokenData = $this->db->table('token')
            ->where('token', $token)
            ->get()
            ->getRowArray();

        return (empty($tokenData)) ? True : False;
    }

    /**
     * 取得Token資料
     * @param string $token Token
     * @return array
     */
    public function getTokenInfo($token)
    {
        $tokenData = $this->db->table('token')
            ->where('token', $token)
            ->get()
            ->getRowArray();

        return $tokenData;
    }
}