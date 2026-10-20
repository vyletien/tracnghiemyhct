# Triển khai lên cPanel

## Yêu cầu

- PHP 8.1 trở lên (khuyến nghị PHP 8.2).
- MySQL/MariaDB với PDO MySQL được bật.
- Apache hỗ trợ `.htaccess`.
- HTTPS đã bật cho tên miền.

## 1. Tạo database

Trong cPanel:

1. Vào **MySQL Databases**, tạo database và user riêng.
2. Gán user vào database với **All Privileges**.
3. Mở **phpMyAdmin**, chọn database mới và import file `database.sql`.

Không chạy `setup_db.php` trên hosting và không dùng lại database của dự án cũ.

## 2. Cấu hình kết nối

Mở `config/database.php` và thay các giá trị sau bằng thông tin cPanel:

```php
$host = 'localhost';
$dbname = 'ten_tai_khoan_tracnghiemyhct';
$user = 'ten_tai_khoan_dbuser';
$pass = 'mat_khau_database';
```

Tên database và user trên cPanel thường có tiền tố tên tài khoản, ví dụ `letie_tracnghiemyhct`.

## 3. Upload mã nguồn

Giải nén gói triển khai vào `public_html` hoặc thư mục domain. Giữ nguyên các thư mục `admin`, `assets`, `config`, `includes` và các file PHP ở thư mục gốc.

## 4. Kiểm tra sau khi upload

- Mở `/login.php`.
- Đăng nhập admin bằng tài khoản mặc định trong database rồi đổi mật khẩu ngay.
- Vào **Cấu hình cuộc thi** để kiểm tra tên cuộc thi và URL ảnh chia sẻ.
- Vào **Quản lý đơn vị** để kiểm tra danh sách Khoa/Phòng.
- Mở đề mẫu để thử vào thi, nộp bài và xem báo cáo.

## 5. Ảnh chia sẻ mạng xã hội

Ảnh mặc định nằm tại `assets/og-default.png`. Trong **Quản trị → Cấu hình cuộc thi**, có thể nhập URL ảnh riêng dạng `https://tenmien.vn/assets/ten-anh.png`.

Nên dùng ảnh PNG/JPG công khai, kích thước khoảng 1200×630 hoặc lớn hơn. Sau khi đổi ảnh, Zalo/Facebook có thể cần thời gian cập nhật cache.

## Bảo mật trước khi mở cho khách

- Bật HTTPS và ép chuyển hướng HTTP sang HTTPS trong cPanel.
- Không công khai file `database.sql` hoặc thông tin database.
- Đổi mật khẩu admin mặc định.
- Xóa các file kiểm thử nếu chúng xuất hiện trong thư mục web.
- Sao lưu database trước khi chạy cuộc thi chính thức.
