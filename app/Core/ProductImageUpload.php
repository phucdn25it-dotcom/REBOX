<?php
namespace App\Core;

final class ProductImageUpload
{
    public static function save(array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new \RuntimeException('Ảnh tải lên bị lỗi hoặc lớn hơn 5 MB.');
        }
        if (!is_uploaded_file($file['tmp_name'] ?? '')) throw new \RuntimeException('Tệp ảnh tải lên không hợp lệ.');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensions[$mime]) || @getimagesize($file['tmp_name']) === false) {
            throw new \RuntimeException('Chỉ nhận ảnh JPG, PNG hoặc WEBP hợp lệ.');
        }
        $directory = dirname(__DIR__, 2) . '/public/uploads/products';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('Không thể tạo thư mục lưu ảnh.');
        }
        $filename = bin2hex(random_bytes(18)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
            throw new \RuntimeException('Không thể lưu ảnh sản phẩm.');
        }
        return 'uploads/products/' . $filename;
    }

    public static function deleteLocal(?string $path): void
    {
        if (!$path || !preg_match('~^uploads/products/[a-f0-9]{36}\.(?:jpg|png|webp)$~', $path)) return;
        $file = dirname(__DIR__, 2) . '/public/' . $path;
        if (is_file($file)) @unlink($file);
    }
}
