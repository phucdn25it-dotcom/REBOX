<?php
namespace App\Controllers;

use App\Core\{Controller, Database, Flash, ProductImageUpload};

final class ProductAdminController extends Controller
{
    public function save(): void { $this->persist(false); }
    public function update(): void { $this->persist(true); }

    private function persist(bool $update): void
    {
        $user = $this->requireAuth(['ADMIN']);
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        $db = Database::connection();
        $savedFile = null;
        $oldImage = null;
        try {
            $name = trim($_POST['name'] ?? '');
            $sku = strtoupper(trim($_POST['sku'] ?? ''));
            $category = (int)($_POST['category_id'] ?? 0);
            $capacity = (int)($_POST['capacity_ml'] ?? 0);
            $price = (float)($_POST['rental_price'] ?? -1);
            $deposit = (float)($_POST['deposit_amount'] ?? -1);
            $urlImage = trim($_POST['image_url'] ?? '');
            $imageScheme = strtolower((string)parse_url($urlImage, PHP_URL_SCHEME));
            if ($name === '' || !preg_match('/^[A-Z0-9-]{3,40}$/', $sku) || $category < 1 || $capacity < 1 || $price < 0 || $deposit < 0 || ($urlImage !== '' && (!filter_var($urlImage, FILTER_VALIDATE_URL) || !in_array($imageScheme,['http','https'],true)))) {
                throw new \RuntimeException('Vui lòng kiểm tra tên, SKU, danh mục, dung tích, giá và URL ảnh.');
            }
            if ($update) {
                if ($id < 1) throw new \RuntimeException('Sản phẩm không hợp lệ.');
                $q = $db->prepare('SELECT image_url FROM products WHERE id=?'); $q->execute([$id]);
                $oldImage = $q->fetchColumn();
                if ($oldImage === false) throw new \RuntimeException('Không tìm thấy sản phẩm.');
            }
            $savedFile = ProductImageUpload::save($_FILES['image_file'] ?? []);
            $image = $savedFile ?? ($urlImage !== '' ? $urlImage : ($update ? $oldImage : ''));
            $slugBase = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
            $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($slugBase)), '-') . '-' . strtolower($sku);
            $values = [$category,$sku,$name,$slug,trim($_POST['description']??''),trim($_POST['material']??''),$capacity,trim($_POST['dimensions']??''),(int)($_POST['weight_g']??0),trim($_POST['food_types']??''),$image,$price,$deposit,max(0,(float)($_POST['late_fee_per_day']??0)),max(1,(int)($_POST['max_rental_days']??7))];
            $db->beginTransaction();
            if ($update) {
                $status = ($_POST['status'] ?? 'ACTIVE') === 'INACTIVE' ? 'INACTIVE' : 'ACTIVE';
                if ($status === 'INACTIVE') {
                    $check=$db->prepare("SELECT COUNT(*) FROM containers WHERE product_id=? AND lifecycle_status IN ('RESERVED','RENTED')");$check->execute([$id]);
                    if ((int)$check->fetchColumn()) throw new \RuntimeException('Sản phẩm đang có hộp được giữ hoặc cho thuê.');
                }
                $db->prepare('UPDATE products SET category_id=?,sku=?,name=?,slug=?,description=?,material=?,capacity_ml=?,dimensions=?,weight_g=?,food_types=?,image_url=?,rental_price=?,deposit_amount=?,late_fee_per_day=?,max_rental_days=?,status=? WHERE id=?')->execute([...$values,$status,$id]);
                $entityId = $id;
            } else {
                $db->prepare('INSERT INTO products(category_id,sku,name,slug,description,material,capacity_ml,dimensions,weight_g,food_types,image_url,rental_price,deposit_amount,late_fee_per_day,max_rental_days) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($values);
                $entityId = (int)$db->lastInsertId();
            }
            $db->prepare('INSERT INTO audit_logs(actor_user_id,action,entity_type,entity_id,ip_address) VALUES(?,?,?,?,?)')->execute([$user['id'],$update?'UPDATE_PRODUCT':'CREATE_PRODUCT','products',$entityId,$_SERVER['REMOTE_ADDR']??null]);
            $db->commit();
            if ($update && $oldImage !== $image) ProductImageUpload::deleteLocal($oldImage);
            Flash::set('success', $update ? 'Đã cập nhật thông tin và ảnh hộp.' : 'Đã thêm sản phẩm mới.');
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            if ($savedFile) ProductImageUpload::deleteLocal($savedFile);
            Flash::set('error', $e instanceof \RuntimeException ? $e->getMessage() : 'Không thể lưu sản phẩm. SKU có thể đã được sử dụng.');
        }
        $this->redirect('/admin/products');
    }
}
