<?php

namespace App\Controllers\Jiachu;

use App\Controllers\BaseController;
use CodeIgniter\Files\File;
use CodeIgniter\API\ResponseTrait;

use App\Models\Jiachu\M_File;
use App\Models\Jiachu\M_Category;
use App\Models\Jiachu\M_Product;

class Category extends BaseController
{
    use ResponseTrait;

    protected $M_Category;
    protected $M_Product;
    protected $M_File;

    public function __construct()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $this->M_Category = new M_Category();
        $this->M_Product = new M_Product();
        $this->M_File = new M_File();
    }

    public function index()
    {
        $categories = $this->M_Category->findAll();

        if (empty($categories)) {
            return $this->respondNoContent();
        }

        foreach ($categories as $_key => $category) {
            $categories[$_key]['count'] = count($this->M_Product->getProductByCategoryId($category['id']));
            $fileData = $this->M_File->getCategories($category['id']);

            if (!empty($fileData)) {
                $file = new File($fileData['path']);
                $protocol = $this->request->getServer('HTTPS') === 'on' ? 'https' : 'http';
                $imageUrl = $protocol . '://' . $this->request->getServer('HTTP_HOST') . '/api/image/' . $fileData['id'];
                
                $categories[$_key]['image'] = $imageUrl;
            }
        }

        return $this->respond($categories);
    }

    public function create()
    {
        try {
            if (!$this->request->is('post')) {
                return $this->fail('只接受 POST 請求', 405);
            }

            $postData = $this->request->getJson(true);
            log_message('debug', 'Create category request data: ' . print_r($postData, true));

            $rules = [
                'name' => 'required|min_length[2]|max_length[100]',
                'description' => 'permit_empty|max_length[1000]',
            ];

            if (!$this->validate($rules)) {
                log_message('error', 'Validation errors: ' . print_r($this->validator->getErrors(), true));
                return $this->fail($this->validator->getErrors(), 400);
            }

            // 分類資料
            $title = ($postData['sub_name'] != '') ? $postData['name'] . $postData['sub_name'] : $postData['name'];
            // print_r($title); die();
            $categoryData = [
                // 'sort' => $this->M_Category->getLatestSort($postData['category_id']),
                'code' => strtolower($this->M_Category->translate($title)),
                'name' => $postData['name'],
                'sub_name' => $postData['sub_name'] ?? null,
                'description' => $postData['description'] ?? null,
            ];

            // 創建分類
            $createResult = $this->M_Category->create($categoryData);

            if ($createResult['success'] === False) {
                return $this->fail($createResult['message'], 400);
            }

            $categoryId = $createResult['category_id'];

            return $this->respondCreated([
                'message' => '創建食品類別成功',
                'category_id' => $categoryId
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Create category error: ' . $e->getMessage());
            return $this->fail('創建食品類別時發生錯誤: ' . $e->getMessage(), 500);
        }
    }

    public function update($categoryId)
    {
        $postData = $this->request->getJson(true);
        $updateResult = $this->M_Category->updateData($categoryId, $postData);

        if ($updateResult === False) {
            return $this->fail('更新食品類別失敗', 400);    
        }

        return $this->respondCreated([
            'message' => '更新產品成功',
            'category_id' => $categoryId
        ]);
    }

    public function delete($categoryId)
    {
        $deleteResult = $this->M_Category->delete($categoryId);

        if ($deleteResult === False) {
            return $this->fail('刪除食品類別失敗', 400);
        }

        return $this->respondDeleted();
    }

    /**
     * 取得分類資料(前端)
     * @return void
     */
    public function getCategory()
    {
        $postData = $this->request->getJson(true);
        $code = $postData['code'];
        $categoryData = $this->M_Category->getCategoryByCode($code);

        if ($categoryData === False) {
            return $this->fail('找不到分類資料', 404);
        }

        return $this->respond($categoryData);
    }

    public function test()
    {
        echo $this->M_Category->translate('排放源'); die();
    }
} 