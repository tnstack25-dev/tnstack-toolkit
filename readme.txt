=== TNStack Toolkit ===
Contributors: tnstack
Tags: performance, security, catalog, ux builder, wordpress toolkit
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 2.4.0
License: GPLv2 or later

TNStack Toolkit cung cấp các module hiệu năng, bảo mật, nội dung, Slim Catalog
và thành phần mở rộng cho Flatsome UX Builder.

== Description ==

Plugin sử dụng một bootstrap duy nhất và chỉ khởi động sau khi WordPress đã nạp
các plugin khác. Mỗi module có thể bật hoặc tắt độc lập trong trang quản trị.

Các nhóm tính năng chính:

* Tối ưu hiệu năng và bảo mật.
* Slim Catalog và trang chi tiết sản phẩm.
* Table of Contents phân cấp, dark mode và thu gọn.
* Đổi URL đăng nhập quản trị.
* Thành phần mở rộng cho Flatsome UX Builder.
* SMTP, analytics, redirect, cookie và các tiện ích nội dung.

== Installation ==

1. Tải thư mục `tnstack-toolkit` lên `/wp-content/plugins/` hoặc cài bằng file ZIP.
2. Kích hoạt TNStack Toolkit.
3. Mở TNStack Toolkit trong quản trị để bật, tắt và cấu hình module.

== GitHub Updates ==

Repository mặc định: `tnstack25-dev/tnstack-toolkit`.

1. Commit và push mã nguồn, bao gồm workflow trong `.github/workflows/release.yml`.
2. Đảm bảo version trong `tnstack-toolkit.php` khớp với tag.
3. Tạo và push tag, ví dụ: `git tag v2.4.0` rồi `git push origin v2.4.0`.
4. GitHub Actions sẽ tạo Release và tải lên asset `tnstack-toolkit.zip`.
5. Trong WordPress, mở TNStack Toolkit → Plugin & Hệ thống để kiểm tra cập nhật.

== Phân quyền tài khoản ==

Trang TNStack Toolkit → Phân quyền tài khoản cho phép cấp độc lập các quyền:

* Truy cập TNStack Toolkit.
* Xem trang WAF và giám sát mã độc.
* Sửa cài đặt Toolkit.
* Quản lý hoặc cài đặt plugin.
* Quản lý giao diện.
* Sửa tệp plugin và giao diện.

Khi chưa lưu bảng phân quyền, các quản trị viên tiếp tục sử dụng Toolkit như bình thường.
Sau khi lưu, chỉ tài khoản được chỉ định mới có quyền tương ứng. Plugin luôn yêu cầu giữ
ít nhất một quản trị viên có quyền truy cập và sửa cài đặt để tránh tự khóa.

== Frequently Asked Questions ==

= Plugin có bắt buộc dùng Flatsome không? =

Không. Các tính năng phụ thuộc Flatsome được kiểm tra trước khi khởi động và
không thay thế shortcode của theme khác khi Flatsome không có mặt.

= Nếu một module gặp lỗi thì sao? =

Lỗi được cô lập ở module tương ứng. Các module còn lại tiếp tục hoạt động và
quản trị viên nhận được thông báo để kiểm tra.

== Changelog ==

= 2.4.0 =

* Thêm Preload Cache theo sitemap index, sitemap URL và sitemap nén `.gz`.
* Thêm hàng đợi preload chạy nền theo batch, khóa chống chạy trùng, retry lỗi tạm thời và giới hạn URL/sitemap an toàn.
* Thêm điều khiển bắt đầu, tạm dừng, tiếp tục, hủy và theo dõi tiến độ preload trong trang Cache.
* Thêm tùy chọn bỏ qua URL còn cache mới và tự preload sau khi xóa toàn bộ cache thủ công.
* Thêm drop-in `advanced-cache.php` để trả HTML cache trước khi WordPress kết nối database.
* Tự cài đặt, đồng bộ và kiểm tra `WP_CACHE`, drop-in và cấu hình TTL; không ghi đè drop-in của plugin khác.
* Hiển thị trạng thái Advanced Cache và tự dọn drop-in TNStack khi vô hiệu hóa plugin.

= 2.3.0 =

* Khởi động page cache sớm hơn, sửa thống kê cache khi ghi đè và giảm request CSS nền.
* Chỉ preconnect Google Fonts khi thực sự sử dụng và tôn trọng tối ưu ảnh gốc của WordPress.
* Thêm WAF với chế độ chặn/theo dõi, allowlist và nhật ký sự kiện có giới hạn.
* Thêm giám sát mã độc theo lịch, checksum WordPress core, baseline file và cảnh báo email.
* Tách WAF và giám sát mã độc sang trang quản trị riêng.
* Sửa Custom Login URL để chặn wp-admin cho khách chưa đăng nhập mà vẫn giữ admin-ajax/admin-post.
* Tăng TTL cache trang mặc định và giới hạn tối đa lên 7 ngày.
* Chỉ giữ lại Pricing Grid Table, FAQ Accordion và Countdown Timer trong nhóm UX Builder.
* Thêm phân quyền theo tài khoản cho Toolkit, WAF/mã độc, plugin, giao diện và trình sửa tệp.
* Sửa xung đột phân quyền plugin/giao diện với WP Site Monitor Agent và bổ sung quyền vô hiệu hóa, khôi phục plugin/giao diện.

= 2.2.2 =

* Thêm Facebook, TikTok và số điện thoại thứ hai cho Floating Contact.
* Thêm tùy chọn hiển thị số cạnh nút Zalo và từng nút điện thoại.
* Giữ nhãn số đứng yên khi icon liên hệ chạy hiệu ứng lắc.

= 2.2.1 =

* Xóa hoàn toàn cấu hình GitHub token và dữ liệu cấu hình cũ.
* Chỉ sử dụng GitHub Releases công khai để cập nhật plugin.

= 2.2.0 =

* Đổi trang GitHub Updates thành trang tổng quan thông tin plugin, website và máy chủ.
* Tối ưu tốc độ bằng cache kết quả và lỗi kết nối GitHub, không chặn tải trang quản trị.
* Không gửi URL website trong User-Agent khi kiểm tra cập nhật.

= 2.1.1 =

* Sửa workflow phát hành không đọc được Version header khi chạy trên GitHub Actions.

= 2.1.0 =

* Thêm cập nhật plugin trực tiếp từ GitHub Releases trong WordPress.
* Thêm workflow tự tạo gói `tnstack-toolkit.zip` khi push tag phiên bản.

= 2.0.4 =

* Đồng bộ style cho Host, Username, Port và Tên người gửi trên trang SMTP Email.

= 2.0.3 =

* Thêm giao diện quản trị responsive cho trang SMTP Email.
* Chia nhóm máy chủ, xác thực và thông tin người gửi.
* Gia cố kiểm tra port và phương thức mã hóa khi lưu.

= 2.0.2 =

* Thêm giao diện quản trị responsive cho trang Custom Login URL.
* Bổ sung khối cảnh báo URL hiện tại và hướng dẫn khôi phục khẩn cấp.

= 2.0.1 =

* Thêm giao diện quản trị thống nhất cho Floating Contact, Table of Contents và Export / Import.
* Bổ sung bố cục responsive, card cài đặt và trạng thái import rõ ràng.

= 2.0.0 =

* Chuẩn hóa bootstrap theo vòng đời WordPress.
* Tách lớp kích hoạt, vô hiệu hóa và nâng cấp.
* Cô lập lỗi khi nạp module.
* Làm mới cache cài đặt ngay sau khi lưu.
* Gia cố tương thích khi thiếu Flatsome, UX Builder hoặc plugin phụ thuộc.
* Xử lý an toàn xung đột với bản Slim Catalog độc lập.
