<?php

namespace App\Models\Jiachu;

use CodeIgniter\Model;

class M_File extends Model
{
    protected $table      = 'files';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'path', 'type', 'size', 'uploaded_at'];

    public function getPath($fileId)
    {
        return $this->where('id', $fileId)->first();
    }

    /**
     * 儲存檔案
     * @param object $file 檔案物件
     * @param string $path 檔案路徑
     * @param string $type 檔案類型
     * @param int $size 檔案大小
     * @return int 新增的檔案 ID
     */
    public function saveFile(object $file, string $path): int
    {
        if (!$file->isValid()) {
            log_message('error', 'Invalid file upload');
            return false;
        }

        $newName = $file->getRandomName();

        $file->move(WRITEPATH . 'uploads/' . $path, $newName);
        if (!$file->hasMoved()) {
            log_message('error', 'Failed to move uploaded file');
            return false;
        }

        $this->insert([
            'name'        => $file->getClientName(),
            'path'        => 'uploads/' . $path . '/' . $newName,
            'type'        => $file->getClientMimeType(),
            'size'        => $file->getSize(),
            'uploaded_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->getInsertID();
    }

    /**
     * 取得指定類別的所有檔案
     *
     * @param int $categoryId
     * @return array
     */
    public function getFilesByCategory(int $categoryId): array
    {
        return $this->select('files.*')
                    ->join('category_files', 'category_files.file_id = files.id')
                    ->where('category_files.category_id', $categoryId)
                    ->findAll();
    }

    /**
     * 取得指定產品的所有檔案
     * @param int $productId
     * @return array
     */
    public function getFilesByProduct(int $productId): array
    {
        return $this->select('files.*')
                    ->join('product_files', 'product_files.file_id = files.id')
                    ->where('product_files.product_id', $productId)
                    ->findAll();
    }

    public function getCategories($category_id)
    {
        $builder = $this->db->table('category_files');
        $builder->select('files.*');
        $builder->join('files', 'files.id = category_files.file_id');
        $builder->where('category_files.category_id', $category_id);
        return $builder->get()->getRowArray();
    }
}
