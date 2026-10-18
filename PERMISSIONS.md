# Vai trò và quyền trong ReBox

## Khách hàng (`CUSTOMER`)

- Đăng ký, đăng nhập, cập nhật hồ sơ, tìm và lọc sản phẩm.
- Tạo đơn thuê, chọn điểm nhận/trả, theo dõi thanh toán và lịch sử bàn giao/nhận trả.
- Xem QR từng hộp, gia hạn, hủy đơn trước khi nhận hộp, nhận thông báo và đánh giá đơn đã hoàn tất.

## Nhân viên (`STAFF`)

- Xem và xử lý đơn tại điểm nhận/trả được quản trị viên phân công.
- Ghi nhận thanh toán tại quầy, bàn giao, nhận trả, tình trạng hộp và vệ sinh.
- Nhập, điều chuyển hoặc xuất hộp khỏi kho trong phạm vi địa điểm được giao.
- Xem lịch sử đơn liên quan tới địa điểm được phân công.

## Đối tác (`PARTNER`)

- Xem tồn kho và lịch sử hoạt động tại địa điểm được phân công.
- Không có quyền sửa đơn hoặc tồn kho.

## Quản trị viên (`ADMIN`)

- Quản lý sản phẩm, hộp/QR, địa điểm, tài khoản, vai trò và phân công.
- Xem báo cáo, xử lý thanh toán và tiền cọc, kiểm duyệt đánh giá.
- Truy cập toàn bộ đơn và lịch sử vận hành.

## Tạo dữ liệu demo

Sau khi import `database/schema.sql`, đặt biến môi trường `REBOX_SEED_PASSWORD` thành mật khẩu riêng dài ít nhất 12 ký tự, rồi chạy `php database/seed.php`. Seed tạo các tài khoản demo cho bốn vai trò. Seed đặt lại mật khẩu các tài khoản demo mỗi lần chạy; chỉ sử dụng trên database thử nghiệm, không dùng với dữ liệu thật.

Thanh toán và hoàn cọc hiện chỉ ghi nhận trong ứng dụng, chưa kết nối ngân hàng hoặc cổng thanh toán.
