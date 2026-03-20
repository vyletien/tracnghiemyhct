# Hệ thống Thi Trắc Nghiệm (TracNghiem)

Hệ thống thi trắc nghiệm trực tuyến được phát triển bằng PHP và MySQL.

## Tính năng chính
- Quản lý đề thi, câu hỏi và danh mục.
- Thi trực tuyến với thời gian đếm ngược.
- Tự động chấm điểm và xem lịch sử thi.
- Hỗ trợ nhiều lần thi (cấu hình bởi admin).
- Xuất báo cáo kết quả thi ra Excel.

## Cài đặt cục bộ (Local Setup)

1.  **Yêu cầu**: PHP >= 7.4, MySQL.
2.  **Cơ sở dữ liệu**:
    - Tạo database tên `tracnghiem`.
    - Import file `database.sql` vào database vừa tạo.
3.  **Cấu hình**:
    - Mở `config/database.php` và cập nhật thông số kết nối nếu cần.
4.  **Chạy ứng dụng**:
    - Sử dụng XAMPP/WAMP hoặc chạy lệnh: `php -S localhost:8000`

## Triển khai lên Railway.com

1.  **GitHub**: Đẩy toàn bộ code lên một kho lưu trữ (repository) trên GitHub.
2.  **Railway**: Biểu tượng `+ New` -> `GitHub Repo` -> Chọn repository của bạn.
3.  **Cơ sở dữ liệu (MySQL)**:
    - Thêm một dịch vụ MySQL trên Railway.
    - Copy nội dung file `database.sql` và chạy trong tab `Query` của dịch vụ MySQL trên Railway (hoặc dùng công cụ như TablePlus/DBeaver để kết nối và import).
4.  **Biến môi trường (Variables)**:
    - Railway sẽ tự động cung cấp các biến `MYSQLHOST`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`, `MYSQLPORT`. 
    - Ứng dụng đã được cấu hình để tự động nhận các biến này.

## Tài khoản mặc định
- **Admin**: `admin` / `123456`
- **Student**: `student1` / `123456`

## Liên hệ
Nếu có vấn đề gì, vui lòng liên hệ nhà phát triển.
