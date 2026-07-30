=== TNStack Toolkit ===
Contributors: tnstack
Tags: performance, security, catalog, ux builder, wordpress toolkit
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 2.2.1
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
3. Tạo và push tag, ví dụ: `git tag v2.2.1` rồi `git push origin v2.2.1`.
4. GitHub Actions sẽ tạo Release và tải lên asset `tnstack-toolkit.zip`.
5. Trong WordPress, mở TNStack Toolkit → Plugin & Hệ thống để kiểm tra cập nhật.

== Frequently Asked Questions ==

= Plugin có bắt buộc dùng Flatsome không? =

Không. Các tính năng phụ thuộc Flatsome được kiểm tra trước khi khởi động và
không thay thế shortcode của theme khác khi Flatsome không có mặt.

= Nếu một module gặp lỗi thì sao? =

Lỗi được cô lập ở module tương ứng. Các module còn lại tiếp tục hoạt động và
quản trị viên nhận được thông báo để kiểm tra.

== Changelog ==

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
