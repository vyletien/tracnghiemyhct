# Hệ thống Thi Trắc Nghiệm (TracNghiem)

Hệ thống thi trắc nghiệm trực tuyến được phát triển bằng PHP và MySQL.

## Tính năng chính
- Quản lý đề thi, câu hỏi và danh mục.
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
3.  **Cấu hình Code**:
    - Mở file `config/database.php`.
    - Thay đổi `$dbname`, `$user`, `$pass` theo thông tin bạn vừa tạo ở Bước 1.
    - Lưu file lại.
4.  **Upload File**:
    - Nén toàn bộ thư mục code thành file `.zip`.
    - Vào cPanel -> **File Manager** -> Thư mục `public_html`.
    - Upload file `.zip` lên và giải nén.

## Tài khoản mặc định
- **Admin**: `admin` / `123456`
- **Student**: `student1` / `123456`

## Liên hệ
Nếu có vấn đề gì, vui lòng liên hệ nhà phát triển.
