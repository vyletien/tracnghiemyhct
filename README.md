# Hệ thống Thi Trắc Nghiệm (TracNghiem)

Hệ thống thi trắc nghiệm trực tuyến được phát triển bằng PHP và MySQL.

## Tính năng chính
- Quản lý đề thi, câu hỏi và danh mục.
- Thí sinh vào thi bằng họ tên, số điện thoại và đơn vị.
- Quản trị viên có thể thêm, sửa, xóa danh sách đơn vị.
- Thi trực tuyến với thời gian đếm ngược.
- Tự động chấm điểm và xem lịch sử thi.
- Xuất báo cáo kết quả thi ra Excel.

## Hướng dẫn cài đặt trên cPanel / Hosting

1.  **Tạo Database**:
    - Vào cPanel -> **MySQL® Databases**.
    - Tạo một database mới (ví dụ: `u123_tracnghiem`).
    - Tạo một database user và gán quyền vào database vừa tạo.
2.  **Import dữ liệu**:
    - Vào cPanel -> **phpMyAdmin**.
    - Chọn database vừa tạo và nhấn tab **Import**.
    - Chọn file `database.sql` từ máy tính và nhấn **Go**.
    - Với hệ thống đã có dữ liệu, sao lưu cơ sở dữ liệu rồi chạy `migrate_db.php` một lần để bổ sung bảng đơn vị và chuyển dữ liệu cũ.
3.  **Cấu hình Code**:
    - Mở file `config/database.php`.
    - Thay đổi `$dbname`, `$user`, `$pass` theo thông tin bạn vừa tạo ở Bước 1.
    - Lưu file lại.
4.  **Upload File**:
    - Nén toàn bộ thư mục code thành file `.zip`.
    - Vào cPanel -> **File Manager** -> Thư mục `public_html`.
    - Upload file `.zip` lên và giải nén.

## Tài khoản mặc định
- **Admin**: `admin` / `Hoiphunu@789`
- **Thí sinh**: Không cần tài khoản; nhập họ tên, số điện thoại và chọn đơn vị để vào thi.

## Liên hệ
Nếu có vấn đề gì, vui lòng liên hệ nhà phát triển.
