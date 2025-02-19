<?php

namespace App\Controllers\Jiachu;

use App\Controllers\BaseController;
use CodeIgniter\Files\File;
use CodeIgniter\API\ResponseTrait;

use App\Models\Jiachu\M_File;
use App\Models\Jiachu\M_Product;
use App\Models\Jiachu\M_Category;
use Kint\Zval\TraceValue;

class Product extends BaseController
{
    use ResponseTrait;

    protected $M_Product;
    protected $M_File;
    protected $M_Category;

    public function __construct()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $this->M_Product = new M_Product();
        $this->M_File = new M_File();
        $this->M_Category = new M_Category();
    }

    /**
     * 取得指定類別的產品
     * @param int $categoryId 類別ID
     * @return \CodeIgniter\HTTP\Response
     */
    public function getProductByCategoryId($categoryId)
    {
        $products = $this->M_Product->getProductByCategoryId($categoryId);

        foreach ($products as $_key => $_val) {
            $categoryData = $this->M_Category->getData(['id' => $_val['category_id']]);
            $products[$_key]['category_text'] = $this->M_Category->getData(['id' => $_val['category_id']])['name'];
        }

        return $this->respond($products);
    }

    /**
     * 取得指定產品
     * @param int $id 產品ID
     * @return \CodeIgniter\HTTP\Response
     */
    public function getProduct($id)
    {
        $product = $this->M_Product->getProductById(['id' => $id]);
        
        // 加入分類資料
        $categoryData = $this->M_Category->getData(['id' => $product['category_id']]);
        $product['category_text'] = $categoryData['name'];

        return $this->respond($product);
    }

    public function create()
    {
        try {
            if (!$this->request->is('post')) {
                return $this->fail('只接受 POST 請求', 405);
            }

            $postData = $this->request->getJson(true);
            $postData['category_id'] = $postData['category'];
            unset($postData['category']);
            log_message('debug', 'Create category request data: ' . print_r($postData, true));

            $rules = [
                'name' => 'required|min_length[2]|max_length[100]',
                'description' => 'permit_empty|max_length[1000]',
            ];

            if (!$this->validate($rules)) {
                log_message('error', 'Validation errors: ' . print_r($this->validator->getErrors(), true));
                return $this->fail($this->validator->getErrors(), 400);
            }

            // 創建產品
            $createResult = $this->M_Product->create($postData);

            if ($createResult['success'] === False) {
                return $this->fail($createResult['message'], 400);
            }

            $productId = $createResult['product_id'];

            return $this->respondCreated([
                'message' => '創建食品類別成功',
                'product_id' => $productId
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Create category error: ' . $e->getMessage());
            return $this->fail('創建產品時發生錯誤: ' . $e->getMessage(), 500);
        }
    }

    public function update()
    {
        $postData = $this->request->getJson(true);
        $postData['category_id'] = $postData['category'];
        unset($postData['category']);

        $this->M_Product->updateData($postData);

        return $this->respondCreated([
            'message' => '更新產品成功',
            'product_id' => $postData['id']
        ]);
    }

    public function delete($productId)
    {
        $this->M_Product->deleteData($productId);

        return $this->respondCreated([
            'message' => '刪除產品成功',
            'product_id' => $productId
        ]);
    }

    /**
     * 刪除產品圖片
     * @return \CodeIgniter\HTTP\Response
     */
    public function deleteImage()
    {
        $postData = $this->request->getJson(true);
        $productId = $postData['product_id'];
        $fileId = $postData['file_id'];

        $this->M_Product->unlinkFile($productId, $fileId);

        return $this->respondCreated([
            'message' => '刪除產品圖片成功',    
        ]);
    }
    
}