<?
namespace App\Controllers\Jiachu;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\Response;
use CodeIgniter\API\ResponseTrait;
use App\Models\Jiachu\M_File;

class ImageController extends BaseController
{
    use ResponseTrait;

    private $M_File;

    public function __construct()
    {
        $this->M_File = new M_File();
    }

    /**
     * 顯示圖片
     * @param int $fileId
     * @return void
     */
    public function show($fileId)
    {
        $fileData = $this->M_File->getPath($fileId);

        $path = WRITEPATH . $fileData['path'];

        if (!is_file($path)) {
            return $this->response->setStatusCode(404)->setBody('File not found.');
        }

        $mimeType = mime_content_type($path);

        return $this->response
            ->setHeader('Content-Type', $mimeType)
            ->setBody(file_get_contents($path));
    }

    public function showByPath($path)
    {
        $path = WRITEPATH . $path;

        if (!is_file($path)) {
            return $this->response->setStatusCode(404)->setBody('File not found.');
        }

        $mimeType = mime_content_type($path);

        return $this->response
            ->setHeader('Content-Type', $mimeType)
            ->setBody(file_get_contents($path));
    }
}
