<?
namespace App\Controllers\Jiachu;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\Jiachu\M_File;
use App\Models\Jiachu\M_Product;
use App\Models\Jiachu\M_Category;

class FileController extends BaseController
{
    use ResponseTrait;

    private $M_File;
    private $M_Product;
    private $M_Category;
    public function __construct()
    {
        $this->M_File = new M_File();
        $this->M_Product = new M_Product();
        $this->M_Category = new M_Category();
    }


    // 上傳檔案
    public function upload()
    {
        $file = $this->request->getFile('file');
        $postData = $this->request->getPost();
        if (isset($postData['product_id'])) {
            $productId = $postData['product_id'];
        } else if (isset($postData['category_id'])) {
            $categoryId = $postData['category_id'];
        }

        $fileId = $this->M_File->saveFile($file, 'images');

        if ($fileId === false) {
            return $this->fail('上傳失敗', 500);
        }

        if (isset($productId)) {
            $this->M_Product->setLinkFile($productId, $fileId);
        } else if (isset($categoryId)) {
            $this->M_Category->setLinkFile($categoryId, $fileId);
        }


        return $this->respond(['id' => $fileId], 200);
    }
}
