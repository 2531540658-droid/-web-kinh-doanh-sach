# Tích hợp SePay

## 1. Cấu hình môi trường

Đặt các biến sau trong môi trường Apache/PHP, không ghi thông tin bí mật vào mã nguồn:

- `SEPAY_BANK_CODE`: mã ngân hàng được QR SePay hỗ trợ, ví dụ mã ngân hàng trong cấu hình tài khoản của bạn.
- `SEPAY_ACCOUNT_NUMBER`: số tài khoản nhận tiền của bạn.
- `SEPAY_WEBHOOK_API_KEY`: API key bí mật dùng để xác thực webhook.
- `SEPAY_WEBHOOK_BANK` (tùy chọn): tên `gateway` SePay gửi về nếu khác mã ngân hàng dùng để tạo QR. Các alias thông dụng như `MB`, `MBBank`, `970422` được đối chiếu cùng một ngân hàng.

Biến môi trường được ưu tiên hơn cấu hình trong `config/local.php`, kể cả khi giá trị biến là chuỗi rỗng (tắt thanh toán). Có thể sao chép `config/local.example.php` thành `config/local.php` rồi điền trên máy chủ. `local.php` được Git bỏ qua; không thêm lại file này vào repository. Cấu hình database cũng có thể dùng file riêng này hoặc các biến `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`.

Với XAMPP, có thể khai báo `SetEnv` trong cấu hình VirtualHost của Apache, sau đó khởi động lại Apache:

```apache
SetEnv SEPAY_BANK_CODE YOUR_BANK_CODE
SetEnv SEPAY_ACCOUNT_NUMBER YOUR_ACCOUNT_NUMBER
SetEnv SEPAY_WEBHOOK_API_KEY YOUR_SECRET_API_KEY
```

Chỉ cấu hình tài khoản ngân hàng do bạn sở hữu. Không dùng thông tin ví dụ từ mã demo.

### Thay khóa từng xuất hiện trên GitHub

Khóa webhook cũ đã bị chặn bằng mã băm trong `config/sepay.php`. Xóa file khỏi bản code mới không xóa khóa khỏi lịch sử Git; không dùng lại khóa đó.

1. Trên máy chủ, tạo khóa mới bằng `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`. Giữ giá trị này riêng tư.
2. Đặt cùng khóa mới vào `SEPAY_WEBHOOK_API_KEY` (hoặc `sepay_webhook_api_key` trong `config/local.php`) và mục xác thực API Key của webhook trên SePay.
3. Khởi động lại Apache/PHP nếu dùng biến môi trường. Dùng **Gửi thử** trong SePay với đúng ngân hàng, số tài khoản, nội dung và số tiền của đơn thử để kiểm tra.

Sau khi cập nhật code, QR tạm không khả dụng cho đến khi có cấu hình đầy đủ với khóa hợp lệ. Trước khi cập nhật checkout từ Git, lưu lại cấu hình database/ngân hàng riêng ở máy chủ; phiên bản mới không phân phối `config/local.php` nữa. Không dán khóa vào PR, ảnh chụp hay HTML.

## 2. Cập nhật database

Database mới tạo từ `database/shop_db.sql` đã có các cột thanh toán. Với database hiện có, chạy một lần nội dung `database/add_sepay_payment_columns.sql` trong phpMyAdmin hoặc MySQL.

## 3. Cấu hình webhook SePay

Tạo webhook trong SePay trỏ tới URL HTTPS công khai:

```text
https://your-domain.example/sepay_webhook.php
```

Cấu hình header Authorization với định dạng `Apikey YOUR_SECRET_API_KEY`, dùng cùng giá trị với `SEPAY_WEBHOOK_API_KEY`, và chọn định dạng dữ liệu JSON. Website chỉ chấp nhận POST hợp lệ, giao dịch tiền vào, đúng `accountNumber` và `gateway` đã cấu hình, mã nội dung `DH<order_id>`, đúng số tiền và đơn hàng SePay đang ở trạng thái `pending`. Giao dịch của tài khoản/ngân hàng khác được bỏ qua và không cập nhật đơn hàng.

Để thử nghiệm từ máy local, dùng tunnel HTTPS có kiểm soát hoặc triển khai staging; SePay không thể gọi `localhost` trực tiếp. Không để API key trong URL, HTML hay JavaScript.

## 4. Luồng thanh toán

Khách chọn QR SePay ở checkout. Website tính tổng từ giỏ hàng phía máy chủ, tạo đơn `pending`, rồi hiển thị QR với nội dung `DH<order_id>`. Webhook đã xác thực sẽ đối chiếu số tiền và cập nhật trạng thái thành `completed`; trang checkout hỏi trạng thái đơn hàng định kỳ để cập nhật giao diện.

QR dùng dịch vụ `qr.sepay.vn` với `template=qronly`: logo BookStory được hiển thị bên ngoài ảnh, không chèn đè lên mã QR. Dòng mô tả vẫn ghi rõ chuyển khoản qua SePay. Tài liệu mẫu QR: https://docs.sepay.vn/tao-qr-code-vietqr-dong.html.

Trang thanh toán kiểm tra trạng thái tuần tự, mỗi yêu cầu chờ tối đa 8 giây. Lỗi kết nối/server hiện thông báo và nút **Kiểm tra lại**, tự thử tối đa 3 lần liên tiếp rồi chờ thao tác của khách. Hết phiên đăng nhập hoặc không tìm thấy đơn sẽ dừng kiểm tra và hiện liên kết phù hợp. Khi đang chờ, việc kiểm tra tiếp tục; khi hoàn tất, QR được ẩn và hiện xác nhận thành công. Endpoint trạng thái chỉ trả dữ liệu đơn thuộc người đăng nhập và không lưu cache.

Kiểm tra cấu hình tự động: `php tests/sepay_config.php`. Không có thay đổi quy tắc voucher trong bản cập nhật này.
