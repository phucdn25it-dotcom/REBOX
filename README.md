# ReBox

Website PHP MVC cho dịch vụ thuê hộp đựng thức ăn tái sử dụng. Dự án dùng PHP 8.1+, MySQL/MariaDB, PDO MySQL và Apache `mod_rewrite`.

## Chạy trên XAMPP

1. Đặt thư mục `ReBox` trong `C:\xampp\htdocs`.
2. Mở phpMyAdmin và import `database/schema.sql`.
3. Nếu database đã tồn tại, import các tệp trong `database/migrations` theo thứ tự tên tệp.
4. Cấu hình kết nối qua các biến môi trường `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`. Mặc định local là `127.0.0.1`, database `rebox`, user `root`, mật khẩu trống.
5. Mở `http://localhost/ReBox/public/`.

### Dữ liệu demo

Chạy seed chỉ trên database demo. Đặt `REBOX_SEED_PASSWORD` thành mật khẩu riêng dài ít nhất 12 ký tự trước khi chạy `php database/seed.php`. Seed tạo các tài khoản `admin@rebox.local`, `staff@rebox.local`, `khach@rebox.local` và `partner@rebox.local`, dùng chung mật khẩu bạn vừa cấu hình. Không dùng dữ liệu demo hoặc mật khẩu demo trên website thật.

## Chạy bằng Docker

Cài Docker Desktop, sao chép `deploy.env.example` thành `.env`, đặt hai mật khẩu database riêng, rồi chạy:

```powershell
docker compose up --build -d
```

Mở `http://localhost:8080`. MySQL khởi tạo schema ở lần chạy đầu tiên và giữ dữ liệu trong volume `rebox_db`. Không xóa volume nếu muốn giữ dữ liệu. Không chạy seed demo trên database có dữ liệu thật.

## Đưa website lên Internet

GitHub lưu trữ mã nguồn nhưng GitHub Pages không chạy PHP. Để website hoạt động công khai, triển khai image Docker này lên hosting hỗ trợ Docker hoặc dùng hosting PHP/MySQL thông thường. Hosting cần HTTPS, MySQL/MariaDB, PHP 8.1+ với PDO MySQL và mbstring, Apache `mod_rewrite`; document root phải trỏ vào `public`. Cấu hình `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` trong phần secrets/environment của hosting. Import `database/schema.sql` cho database mới hoặc migration tương ứng cho database đã có. Không commit `.env`, thông tin kết nối database hoặc dữ liệu khách hàng.

Với shared hosting, có thể cấu hình kết nối DB qua `app/config.local.php` (tạo từ `app/config.local.example.php`). Hướng dẫn từng bước cho phương án miễn phí nằm ở [DEPLOY_FREE_HOSTING.md](DEPLOY_FREE_HOSTING.md).

## Tính năng

- Đăng ký, đăng nhập và phân quyền ADMIN, STAFF, CUSTOMER, PARTNER.
- Danh mục và sản phẩm thuê, giỏ hàng, đơn thuê, điểm nhận/trả, thanh toán tiền mặt/chuyển khoản demo.
- Nhân viên quản lý tồn hộp, bàn giao, nhận trả, vệ sinh; khách và nhân viên theo dõi lịch sử của đơn.
- Khu vực quản trị sản phẩm, hộp, địa điểm, tài khoản và báo cáo.

Mật khẩu được băm bằng `password_hash`; truy vấn dùng PDO prepared statements; biểu mẫu có CSRF token; dữ liệu đầu ra được escape. Phần thanh toán hiện chỉ ghi nhận trong ứng dụng, chưa kết nối cổng thanh toán/ngân hàng.
