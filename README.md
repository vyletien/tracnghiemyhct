# Hệ thống Thi Trắc Nghiệm YHCT

Đây là dự án độc lập, sử dụng cơ sở dữ liệu mới hoàn toàn và không phụ thuộc dữ liệu của dự án trắc nghiệm cũ.

## Tính năng chính

- Quản lý đề thi, câu hỏi và danh mục.
- Thí sinh vào thi bằng họ tên, số điện thoại và đơn vị.
- Quản trị viên có thể thêm, sửa, xóa danh sách đơn vị.
- Thi trực tuyến với thời gian đếm ngược.
- Tự động chấm điểm và xem lịch sử thi.
- Xuất báo cáo kết quả thi ra Excel.

## Hướng dẫn cài đặt trên cPanel / Hosting

1. **Tạo database mới**:
   - Vào cPanel -> **MySQL® Databases**.
   - Tạo một database riêng cho dự án (ví dụ: `u123_tracnghiemyhct`).
   - Tạo database user và gán toàn bộ quyền cần thiết cho database vừa tạo.
2. **Import cấu trúc dữ liệu mới**:
   - Vào cPanel -> **phpMyAdmin**.
   - Chọn database mới, trống vừa tạo và nhấn tab **Import**.
   - Chọn file `database.sql` rồi nhấn **Go**.
   - Không import file dump hoặc dữ liệu từ dự án cũ.
3. **Cấu hình mã nguồn**:
   - Mở file `config/database.php`.
   - Thay đổi `$dbname`, `$user`, `$pass` theo database mới vừa tạo.
4. **Upload mã nguồn**:
   - Upload mã nguồn lên thư mục web và giải nén.
   - Đăng nhập quản trị và tạo danh sách đơn vị trước khi mở hệ thống cho thí sinh.
   - Khi triển khai thực tế, thay mật khẩu quản trị mặc định bằng một chuỗi băm BCrypt riêng trong database.

## Cài đặt nhanh trên máy phát triển

Khi MySQL đang chạy và tài khoản `root` không có mật khẩu:

```bash
php setup_db.php
```

Lệnh này tạo database mới `tracnghiemyhct` và sẽ dừng nếu database đã có bảng dữ liệu.

## Tài khoản mặc định

- **Admin**: `admin` (mật khẩu mặc định được cung cấp riêng, không công khai trong repository)
- **Thí sinh**: Không cần tài khoản; nhập họ tên, số điện thoại và chọn đơn vị để vào thi.

Sau lần đăng nhập đầu tiên, quản trị viên cần tạo danh sách đơn vị trong mục **Quản lý đơn vị**.
