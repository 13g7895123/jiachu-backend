<?php

namespace App\Models\Jiachu;

use CodeIgniter\Model;
class M_Product extends Model
{
    protected $table = 'product';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['sort', 'category_id','name', 'description', 'weight', 'shelf_life', 'main_ingredients', 'storage'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'name' => 'required|min_length[2]|max_length[100]|is_unique[product.name,id,{id}]',
        'description' => 'permit_empty|max_length[1000]',
    ];

    protected $validationMessages = [
        'name' => [
            'required' => '名稱為必填項',
            'min_length' => '名稱最少需要2個字元',
            'max_length' => '名稱最多100個字元',
        ],
    ];

    public function getProductByCategoryId($categoryId)
    {
        $data = $this->where('category_id', $categoryId)->orderBy('sort', 'ASC')->findAll();

        if (empty($data)) {
            return [];
        }

        $fileUrls = array();
        foreach ($data as $_key => $_val) {
            $fileData = $this->db->table('product_files')
                ->where('product_id', $_val['id'])
                ->get()
                ->getResultArray();

            if (empty($fileData)) {
                $data[$_key]['image'] = [null];
                continue;
            }

            $fileUrls = array();
            foreach ($fileData as $_file) {
                $fileUrls[] = base_url() . "api/image/{$_file['file_id']}";
            }
            $data[$_key]['image'] = $fileUrls;
        }

        return $data;
    }

    /**
     * 取得產品資料
     * @param int $id 產品ID
     * @return array 產品資料
     */
    public function getProductById($id)
    {
        $data = $this->where('id', $id)->first();
        return $data;
    }

    /**
     * 創建產品
     * @param array $data 產品資料
     * @return array 包含成功或錯誤訊息的陣列
     */

    public function create(array $data): array
    {
        try {
            $this->db->transStart();

            log_message('debug', 'Starting product creation with data: ' . print_r($data, true));

            $customSort = isset($data['sort']) ? $data['sort'] : 0;     // 自定義排序
            $data['sort'] = $this->getLatestSort($data['category_id']); // 取得最新排序

            // 插入產品資料
            if ($this->insert($data) === false) {
                log_message('error', 'Validation errors: ' . print_r($this->errors(), true));
                return ['error' => 'Failed to create product'];
            }
            $productId = $this->db->insertID();

            // 如果自定義排序不是0，則更新排序
            if ($customSort != 0) {
                $this->updateRank('product', $productId, $customSort);
            }

            $this->db->transComplete();
            return ['success' => true, 'message' => 'Product created successfully', 'product_id' => $productId];

        } catch (\Exception $e) {
            log_message('error', 'Error creating product: ' . $e->getMessage());
            return ['error' => 'Failed to create product: ' . $e->getMessage()];
        }
    }

    /**
     * 更新產品資料
     * @param array $data 產品資料
     * @return void
     */
    public function updateData(array $data)
    {
        // 排序有更新的話，需要重新排序
        $product = $this->getProductById($data['id']);
        if ($product['sort'] != $data['sort']) {
            $this->updateRank('product', $data['id'], $data['sort']);
        }

        // 更新產品資料
        $id = $data['id'];
        unset($data['id']);
        $this->db->table('product')
            ->where('id', $id)
            ->update($data);

        // 分類更新，兩邊的分類都要重新排序
        if ($product['category_id'] != $data['category_id']) {
            $this->updateCategoryRank($product['category_id']);     // 更新舊資料排序
            $this->updateCategoryRank($data['category_id']);        // 更新新資料排序
        }
    }

    /**
     * 刪除產品資料
     * @param int $id 產品ID
     * @return void
     */
    public function deleteData($id)
    {
        // 先解除關聯
        $this->db->table('product_files')
            ->where('product_id', $id)
            ->delete();

        // 再刪除產品
        $this->delete($id);
    }

    /**
     * 設定產品與檔案的連結
     * @param int $productId 產品ID
     * @param int $fileId 檔案ID
     * @return void
     */
    public function setLinkFile($productId, $fileId)
    {
        $this->db->table('product_files')->insert([
            'product_id' => $productId,
            'file_id' => $fileId
        ]);
    }

    public function unlinkFile($productId, $fileId)
    {
        $this->db->table('product_files')
            ->where('product_id', $productId)
            ->where('file_id', $fileId)
            ->delete();
    }

    /**
     * 取得最新排序
     * @param int $categoryId 類別ID
     * @return int 最新排序
     */
    public function getLatestSort($categoryId)
    {
        $data = $this->db->table('product')
            ->where('category_id', $categoryId)
            ->orderBy('sort', 'DESC')
            ->get()
            ->getRowArray();

        $latestSort = $data['sort'] ?? 0;

        return $latestSort + 1;
    }

    /* 更新排名(更新資料前) */
    public function updateRank($table, $id, $rank, $field='sort')
    {
        $originalData = $this->db->table($table)
            ->where('id', $id)
            ->get()
            ->getRowArray();

        $originalRank = $originalData[$field];

        if ($rank != $originalRank){
            $this->updateRankToN($originalRank, 999, $table, $field);    // 原始名次改為999

            if ((int)$rank < (int)$originalRank){
                for($i = ($originalRank - 1); $i >= $rank; $i--){
                    $this->updateRankToN($i, ($i + 1), $table, $field);
                }               
            }else{
                for($i = ($originalRank + 1); $i <= $rank; $i++){
                    $this->updateRankToN($i, ($i - 1), $table, $field);
                }
            }

            $this->updateRankToN(999, $rank, $table, $field);
        }

        return;
    }
    
    /**
     * 更新排名
     * @param int $rank 原始排名
     * @param int $newRank 新排名
     * @param string $table 表名
     * @param string $field 排序欄位
     */
    public function updateRankToN($rank, $newRank, $table, $field)
    {
        $updateData = array($field => $newRank);

        $this->db->table($table)
            ->where($field, $rank)
            ->update($updateData);
    }

    /**
     * 更新分類排名
     * @param int $categoryId 分類ID
     */
    public function updateCategoryRank($categoryId)
    {
        $productData = $this->db->table('product')
            ->where('category_id', $categoryId)
            ->orderBy('sort', 'ASC')
            ->get()
            ->getResultArray();

        // 重新排序
        $newSort = 1;
        $updateBatchData = array();
        foreach ($productData as $_val) {
            // 單筆要更新的資料
            $updateData = array(
                'id' => $_val['id'],
                'sort' => $newSort
            );

            // 批次要更新的資料
            $updateBatchData[] = $updateData;

            // 增加排序值
            $newSort++;
        }

        // 批次更新參數
        $batchSize = 100;
        $total = count($updateBatchData);

        // 批次更新
        for ($i = 0; $i < $total; $i += $batchSize) {
            $batchData = array_slice($updateBatchData, $i, $batchSize); // 切出 100 筆
            $this->db->table('product')->updateBatch($batchData, 'id');
        }
    }
}