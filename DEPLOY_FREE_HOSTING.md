# Hướng dẫn đưa ReBox lên hosting miễn phí

Phương án tham khảo: [InfinityFree](https://www.infinityfree.com/). Nhà cung cấp hiện công bố PHP 8.4, MySQL 8/MariaDB 11.4, subdomain và SSL miễn phí. Gói miễn phí phù hợp chạy thử/đồ án; không xem đây là hạ tầng đảm bảo cho hoạt động kinh doanh quan trọng.

## 1. Tạo hosting và database

1. Tạo tài khoản ở InfinityFree, xác nhận email, rồi tạo một hosting account với free subdomain.
2. Trong Control Panel, mở **MySQL Databases** và tạo database.
3. Ghi lại đúng **database name**, **username**, **hostname** và mật khẩu được hiển thị. Hostname do nhà cung cấp cấp thường có dạng `sqlxxx.infinityfree.com`; không tự thay bằng `localhost`.
4. Mở phpMyAdmin của hosting, chọn database vừa tạo, vào **Import** và tải lên `database/schema_hosting.sql`.

## 2. Tải mã nguồn

1. Tải ZIP từ [repo ReBox](https://github.com/phucdn25it-dotcom/REBOX/archive/refs/heads/main.zip) và giải nén trên máy.
2. Dùng File Manager hoặc FTP của hosting, tải các thư mục `app`, `database`, `public`, `storage` cùng `index.php` vào thư mục `htdocs`. Tải nội dung bên trong thư mục ReBox, không tạo thêm một thư mục ReBox lồng bên trong `htdocs`.
3. Trong `htdocs/app`, sao chép `config.local.example.php` thành `config.local.php`, rồi điền thông tin DB của hosting. Không đưa `config.local.php` lên GitHub và không gửi mật khẩu DB trong tin nhắn.
4. Mở subdomain. Trang sẽ chuyển đến `/public/`; các trang khác sẽ dùng đường dẫn dưới `/public/`.
5. Bật SSL trong Control Panel và truy cập bằng HTTPS.

## 3. Tạo tài khoản quản trị

Schema không tạo sẵn tài khoản quản trị với mật khẩu công khai. Tạo hash mật khẩu riêng trên máy có PHP:

```powershell
C:\xampp\php\php.exe -r "echo password_hash('DoiThanhMatKhauRieng123!', PASSWORD_DEFAULT), PHP_EOL;"
```

Trong phpMyAdmin của hosting, chọn database rồi chạy câu lệnh sau; thay tên, email và `HASH_TU_LENH_PHP` bằng giá trị của bạn:

```sql
INSERT INTO users(role_id,name,email,password_hash)
SELECT id,'Quan tri ReBox','email-cua-ban@example.com','HASH_TU_LENH_PHP'
FROM roles WHERE code='ADMIN';
```

Đăng nhập tài khoản đó, rồi tạo/phân công nhân viên. Không chạy `database/seed.php` trên website public.

## Lưu ý

- Free hosting có thể áp giới hạn công bằng sử dụng, tài nguyên và thời gian xử lý. Sao lưu cơ sở dữ liệu thường xuyên.
- Không nhập dữ liệu khách hàng thật hoặc dùng thanh toán thật cho bản demo. Thanh toán ReBox hiện chỉ ghi nhận trạng thái trong ứng dụng, chưa tích hợp cổng ngân hàng.
- Nếu hosting không cho import file SQL dung lượng nhỏ hoặc phát sinh lỗi tương thích, dừng tại đó và gửi nguyên văn lỗi để xử lý; không import file sửa encoding lên dữ liệu thật.
