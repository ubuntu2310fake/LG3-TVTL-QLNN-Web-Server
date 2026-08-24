# QUY TẮC BẮT BUỘC DÀNH CHO CLAUDE CODE & LẬP TRÌNH VIÊN (CRITICAL BUSINESS RULE)

## 📌 QUY TẮC THỨ TỰ NGÀY TRONG TUẦN THI ĐỦA (THPT LẠNG GIANG SỐ 3)

**MẬT LỆNH BẮT BUỘC (STRICTLY ENFORCED RULE):**
Theo luật của Trường THPT Lạng Giang số 3, tuần thi đua nề nếp BẮT ĐẦU từ **Thứ 7 tuần trước** (mã `7`) và kết thúc vào **Thứ 6 tuần này** (mã `6`).

### ⚠️ QUY ĐỊNH CẤM THAY ĐỔI:
1. **Thứ tự ngày trong tất cả bảng Sổ đầu bài / Sổ đoàn trường / Giao diện thi đua / API** BẮT BUỘC phải theo đúng thứ tự:
   `[7, 2, 3, 4, 5, 6]` (Tương ứng: Thứ 7 -> Thứ 2 -> Thứ 3 -> Thứ 4 -> Thứ 5 -> Thứ 6).
2. **Hàng/Cột Thứ 7 (Sat - day 7) LUÔN LUÔN NẰM Ở ĐẦU TIÊN (HÀNG ĐẦU TIÊN BẢNG MA TRẬN)**.
3. **TUYỆT ĐỐI KHÔNG ĐƯỢC TỰ Ý SỬA THÀNH `range(2,7)` HOẶC ĐẶT THỨ 2 NẰM ĐẦU.**

### Các file chính áp dụng quy tắc này:
- `teacher_dashboard.php` & `api/teacher_dashboard_api.php`
- `student_violations.php` & `api/student_violations_api.php`
- `class_check.php`, `views/class_check_view.php` & `api/class_check_api.php`
- Mọi API hoặc View mới có hiển thị thứ tự ngày thi đua trong tuần.
