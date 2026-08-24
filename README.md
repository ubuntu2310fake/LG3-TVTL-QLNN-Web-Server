# 🏫 LG3 - Web Server (Tư Vấn Tâm Lý & Quản Lý Thi Đua Nền Nếp)

Hệ thống Web Server & API quản lý thi đua nền nếp, tư vấn tâm lý học đường và chấm điểm trực tuyến dành cho các trường THPT.

## 🚀 Tính năng chính

- **Quản lý Nền nếp & Thi đua**:
  - Ghi nhận vi phạm trực tiếp qua cổng hoặc tại lớp học qua mã QR.
  - Sổ đầu bài điện tử, bảng ma trận tuần thi đua (Thứ 7 tuần trước -> Thứ 6 tuần này).
  - Bảng xếp hạng thi đua tuần / tháng / học kỳ.
  - Xuất báo cáo thống kê chuyên sâu (Excel/PDF).
- **Tư vấn Tâm lý & Hướng nghiệp**:
  - Hộp thư tư vấn tâm lý 1:1 bảo mật giữa học sinh và ban tư vấn.
  - Tích hợp AI tư vấn tâm lý 24/7 và kiểm tra ngữ pháp tiếng Anh.
  - Trắc nghiệm định hướng nghề nghiệp Holland, MI.
- **Hạ tầng & Dịch vụ**:
  - Web PWA & Realtime Server-Sent Events (SSE).
  - Background Worker gửi Web Push Notification đa nền tảng (`worker.php`).
  - Hỗ trợ đa ngôn ngữ (Tiếng Việt 🇻🇳, English 🇺🇸).

## 🛠️ Yêu cầu hệ thống

- **Web Server**: Apache 2.4+ (bật module `rewrite`, `headers`).
- **PHP**: PHP 8.2 trở lên với các extensions:
  - `pdo_mysql`, `mbstring`, `xml`, `curl`, `gd`, `zip`, `intl`, `bcmath`.
- **Database**: MariaDB 10.6+ hoặc MySQL 8.0+.
- **Composer**: Composer 2.x để cài đặt dependencies.

## 📦 Hướng dẫn cài đặt

1. **Clone mã nguồn**:
   ```bash
   git clone https://github.com/ubuntu2310fake/LG3-TVTL-QLNN-Web-Server.git
   cd LG3-TVTL-QLNN-Web-Server
   ```

2. **Cài đặt thư viện PHP**:
   ```bash
   composer install
   ```

3. **Cấu hình Cơ sở dữ liệu**:
   - Sao chép file mẫu:
     ```bash
     cp includes/db_config.example.php includes/db_config.php
     ```
   - Chỉnh sửa thông tin kết nối MySQL/MariaDB trong `includes/db_config.php`.

4. **Cấu hình Biến môi trường**:
   - Sao chép file cấu hình mẫu:
     ```bash
     cp includes/setup_variables.example.php includes/setup_variables.php
     cp includes/firebase_credentials.example.json includes/firebase_credentials.json
     ```
   - Điền các thông tin VAPID Keys, Email SMTP, Secret Keys tương ứng.

5. **Phân quyền thư mục**:
   ```bash
   chmod -R 775 .
   chmod -R 777 logs dash
   chown -R www-data:www-data .
   ```

6. **Khởi chạy Background Worker**:
   ```bash
   php worker.php
   ```

## 📄 Giấy phép (License)

Dự án được phân phối theo giấy phép mã nguồn mở **European Union Public Licence v. 1.2 (EUPL-1.2)**. Xem file [LICENSE](LICENSE) để biết thêm chi tiết.

## 👨‍💻 Bản quyền

- **Tác giả**: Trương Hiếu & Tập thể A1-K48
- **Bản quyền**: © 2026 Trường THPT Lạng Giang số 3
