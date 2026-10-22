# SmartLife Hub

SmartLife Hub là website quản lý cá nhân, giúp người dùng theo dõi học tập, công việc, tài chính, thói quen và mục tiêu trong cùng một tài khoản. Dashboard tổng hợp các số liệu; AI Assistant hiện sử dụng quy tắc PHP để tạo gợi ý từ dữ liệu của tài khoản đang đăng nhập.

## Công nghệ

- PHP 8+
- MySQL hoặc MariaDB
- HTML, CSS và JavaScript
- XAMPP để chạy ứng dụng cục bộ
- Chart.js qua CDN để hiển thị biểu đồ

## Các chức năng

- Đăng ký, đăng nhập và đăng xuất
- Dashboard và SmartLife Score
- Quản lý môn học và bài tập
- Quản lý công việc, deadline và mức ưu tiên
- Theo dõi khoản thu, khoản chi và số dư
- Quản lý thói quen, check-in theo ngày và streak
- Theo dõi tiến độ mục tiêu
- Xem và đánh dấu thông báo đã đọc
- AI Assistant mô phỏng bằng các quy tắc PHP

## Cài đặt trên XAMPP

1. Cài XAMPP có Apache, PHP và MySQL/MariaDB.
2. Sao chép thư mục dự án vào `C:\xampp\htdocs\smartlife`.
3. Mở XAMPP Control Panel và khởi động Apache cùng MySQL.
4. Mở phpMyAdmin tại `http://localhost/phpmyadmin/` và tạo cơ sở dữ liệu tên `smartlife`, dùng bộ mã hóa `utf8mb4`.
5. Chuẩn bị cấu trúc bảng cho cơ sở dữ liệu. Repository chưa kèm bản sao dữ liệu cá nhân; nếu cần tạo file để cài mới, hãy xuất **chỉ cấu trúc** bảng từ phpMyAdmin (không chọn dữ liệu), kiểm tra kỹ trước khi đưa file SQL lên repository, rồi import file đó vào database `smartlife`.
6. Sao chép `config/database.example.php` thành `config/database.php`. Mở `config/database.php` và điền host, tên người dùng, mật khẩu MySQL trên máy của bạn. File `config/database.php` bị loại khỏi Git bằng `.gitignore`.
7. Truy cập `http://localhost/smartlife/` và đăng ký tài khoản để bắt đầu sử dụng.

## Cấu trúc dữ liệu

Cơ sở dữ liệu của ứng dụng hiện sử dụng các bảng chính sau:

| Bảng | Mục đích |
| --- | --- |
| `users` | Tài khoản người dùng |
| `subjects`, `assignments` | Môn học và bài tập |
| `tasks` | Công việc cá nhân |
| `incomes`, `expenses` | Thu nhập và chi tiêu |
| `habits`, `habit_logs` | Thói quen và lịch sử check-in |
| `goals` | Mục tiêu và tiến độ |
| `notifications` | Thông báo |
| `ai_conversations`, `ai_messages` | Cuộc trò chuyện và tin nhắn AI |

Các bảng nghiệp vụ được gắn với tài khoản bằng `user_id` hoặc liên kết qua bảng cha. Cấu trúc chính xác cần khớp với mã nguồn hiện tại.

## AI Assistant

AI Assistant hiện là bản mô phỏng bằng quy tắc PHP, không gọi OpenAI và không cần API key. Hệ thống nhận diện nhóm câu hỏi như deadline, học tập, tài chính, thói quen và mục tiêu, sau đó tạo phản hồi từ dữ liệu SmartLife của tài khoản đang đăng nhập. Vì dùng quy tắc và từ khóa cố định, khả năng trò chuyện linh hoạt còn hạn chế.

## Lưu ý cấu hình và dữ liệu

- Không đưa `config/database.php`, mật khẩu, dữ liệu người dùng thật hoặc bản sao lưu cơ sở dữ liệu lên repository công khai.
- Không tải `test_db.php` lên GitHub; trang này có thể hiển thị thông tin người dùng trong cơ sở dữ liệu.
- Cấu hình mẫu trong `config/database.example.php` chỉ dành cho môi trường XAMPP cục bộ. Không dùng tài khoản MySQL mặc định khi triển khai công khai.
- Khi dùng Chart.js qua CDN, trình duyệt cần có kết nối Internet để tải thư viện biểu đồ.

## Giấy phép

Đồ án học tập SmartLife Hub. Chưa khai báo giấy phép tái sử dụng.
