<?php

namespace App\Models\Jiachu;

use App\Models\Jiachu\M_Product;
use CodeIgniter\Model;
class M_Category extends Model
{
    protected $table = 'category';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['code', 'name', 'sub_name', 'description'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $db;

    protected $validationRules = [
        'code' => 'required|min_length[2]|max_length[50]|is_unique[category.code,id,{id}]',
        'name' => 'required|min_length[2]|max_length[100]|is_unique[category.name,id,{id}]',
        'sub_name' => 'permit_empty|min_length[2]|max_length[100]',
        'description' => 'permit_empty|max_length[1000]',
    ];

    protected $validationMessages = [
        'code' => [
            'required' => '代碼為必填項',
            'min_length' => '代碼最少需要2個字元',
            'max_length' => '代碼最多50個字元',
            'is_unique' => '此代碼已被使用',
        ],
        'name' => [
            'required' => '名稱為必填項',
            'min_length' => '名稱最少需要2個字元',
            'max_length' => '名稱最多100個字元',
        ],
        'sub_name' => [
            'min_length' => '子名稱最少需要2個字元',
            'max_length' => '子名稱最多100個字元',
        ],
    ];

    /**
     * 取得食品類別資料
     * @param array $where 條件
     * @param bool $queryMultiple 是否查詢多筆
     * @return array|object 查詢結果
     */
    public function getData($where = [], $queryMultiple = False)
    {
        $this->db = \Config\Database::connect();
        $builder = $this->db->table('category');

        if (!empty($where)) {
            $builder->where($where);
        }
        
        return ($queryMultiple) ? $builder->get()->getResultArray() : $builder->get()->getRowArray();
    }

    /**
     * 創建食品類別
     */
    public function create(array $data): array
    {
        try {
            $this->db->transStart();

            log_message('debug', 'Starting category creation with data: ' . print_r($data, true));

            // 插入類別資料
            if ($this->db->table('category')->insert($data) === false) {
                log_message('error', 'Validation errors: ' . print_r($this->errors(), true));
                return [
                    'success' => false,
                    'message' => '驗證失敗',
                    'errors' => $this->errors()
                ];
            }

            $categoryId = $this->db->insertID();

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                log_message('error', 'Transaction failed');
                return [
                    'success' => false,
                    'message' => '資料庫交易失敗'
                ];
            }

            log_message('debug', 'Category created successfully with ID: ' . $categoryId);
            return [
                'success' => true,
                'message' => '食品類別創建成功',
                'category_id' => $categoryId
            ];
        } catch (\Exception $e) {
            log_message('error', 'Error creating category: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => '食品類別創建失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 更新食品類別
     */
    public function updateData(int $categoryId, array $data): bool
    {
        return $this->db->table('category')->where('id', $categoryId)->update($data);
    }


    /**
     * 連結檔案
     * @param int $categoryId 類別ID
     * @param string $fileId 檔案ID
     * @return bool 是否成功
     */
    public function setLinkFile(int $categoryId, string $fileId)
    {
        return $this->db->table('category_files')->insert([
            'category_id' => $categoryId,
            'file_id' => $fileId
        ]);
    }


    /**
     * 獲取類別的檔案
     */
    public function getCategoryFiles(int $categoryId): array
    {
        $builder = $this->db->table('food_category_files');
        $builder->select('files.*');
        $builder->join('files', 'files.id = food_category_files.file_id');
        $builder->where('food_category_files.category_id', $categoryId);
        return $builder->get()->getResultArray();
    }

    /**
     * 翻譯
     */ 
    public function translate(string $text): string
    {
        $url = 'http:/170.187.229.132:9501/translate';
        $payload = array('text' => $text);

        $client = \Config\Services::curlrequest();
        $response = $client->post($url, ['json' => $payload]);
        $result = json_decode($response->getBody(), true);

        return $result['translated_text'];
    }

    /**
     * 取得分類資料(前端)
     */
    public function getCategoryByCode(string $code): array
    {
        $categoryData = $this->db->table('category')
            ->where('code', $code)
            ->get()
            ->getRowArray();

        if (empty($categoryData)) {
            return False;
        }

        $M_Product = new M_Product();
        $data = array(
            'category' => $categoryData,
            'products' => $M_Product->getProductByCategoryId($categoryData['id'])
        );

        return $data;
    }
} 