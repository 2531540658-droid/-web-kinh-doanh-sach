# BookBot – Chatbot tư vấn và gợi ý sách cho website PHP/MySQL

Bộ mã nguồn này cung cấp 3 chức năng:

1. **Gợi ý sản phẩm:** lấy sách từ bảng `products`, hiển thị thẻ sản phẩm và liên kết đến trang chi tiết.
2. **Chatbot tư vấn:** tìm sách trong MySQL, trả lời các câu hỏi thông dụng. Có thể bật LLM qua OpenAI Responses API; nếu chưa có API key, chatbot vẫn có câu trả lời dự phòng dựa trên dữ liệu sản phẩm.
3. **Cá nhân hóa:** ghi nhận lượt xem, click, thêm giỏ, yêu thích và mua hàng; sau đó ưu tiên thể loại người dùng quan tâm và sản phẩm phổ biến.

> Đây là bản tích hợp nền tảng để chạy trong PHP/XAMPP. Phần tìm kiếm hiện dùng truy vấn SQL `LIKE` (RAG-lite), chưa triển khai BGE-M3 + Qdrant. Ranking cá nhân hóa hiện là công thức baseline, chưa phải mô hình XGBoost đã huấn luyện.

## 1. Cấu trúc thư mục

Trong cấu hình hiện tại, thư mục chatbot và website nằm cạnh nhau trong `C:\xampp\htdocs`:

```text
C:\xampp\htdocs\
├── web-kinh-doanh-s-ch\
│   ├── config\
│   ├── database\
│   ├── pages\
│   └── components\footer.php
└── bookstore-ai-chatbot\
    ├── api\
    ├── assets\
    ├── database\
    ├── config.php
    └── widget.php
```

## 2. Cài đặt trên XAMPP

1. Mở XAMPP Control Panel và khởi động **Apache** cùng **MySQL**.
2. Website hiện có cấu hình kết nối MySQL trong `web-kinh-doanh-s-ch\config\local.php`; `bookstore-ai-chatbot\config.php` đọc lại cấu hình đó (hoặc dùng các biến môi trường `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`). Giá trị mặc định là `localhost`, `root`, mật khẩu trống và database `shop_db`.
3. Mở phpMyAdmin tại `http://localhost/phpmyadmin`, chọn database `shop_db`, rồi chạy `bookstore-ai-chatbot\database\install.sql`. Script chỉ tạo hai bảng mới `ai_book_interactions` và `ai_book_chat_messages`; không sửa bảng sản phẩm, người dùng hay đơn hàng.
4. Widget đã được gọi từ `web-kinh-doanh-s-ch\components\footer.php`, nên xuất hiện ở các trang dùng footer này. Hai thư mục cần nằm tại đúng các đường dẫn trong cấu trúc ở trên.
5. Mở `http://localhost/web-kinh-doanh-s-ch/` để kiểm tra.

`config.php` ánh xạ sẵn cấu trúc cửa hàng hiện tại: `products.id`, `name`, `details`, `category`, `price`, `image`; không có cột tồn kho. Trang chi tiết sách dùng `pages/view_page.php?pid=...`. Nếu đổi tên thư mục website hoặc chatbot, cập nhật `site_base_path`, `chatbot_url_path` và `image_base_url` trong `config.php`.

**Lưu ý:** Website đang sử dụng `$_SESSION['user_id']`; widget tiếp tục dùng cùng phiên đăng nhập. Nếu tự tích hợp sang trang khác, hãy gọi `session_start()` trước khi xuất HTML và include `widget.php` sau khi session được khởi tạo.

## 3. Cấu hình bảng sản phẩm

Bộ mã nguồn mặc định giả định bảng `products` có các cột:

```text
id, name, description, category, price, image, stock
```

Nếu bảng của bạn dùng tên khác, sửa mapping trong `config.php`. Ví dụ nếu cột tên sách là `product_name`, cột ảnh là `image_01`, cột số lượng là `quantity`:

```php
'product_columns' => [
  'id' => 'id',
  'name' => 'product_name',
  'description' => 'details',
  'category' => 'category',
  'price' => 'price',
  'image' => 'image_01',
  'stock' => 'quantity',
],
```

Nếu không có cột mô tả hoặc tồn kho, đặt giá trị tương ứng thành `null`. Cột `id`, `name` và `price` nên có.

Nếu route trang chi tiết khác, sửa `product_url_template`, ví dụ:

```php
'product_url_template' => '/pages/product_detail.php?id={id}',
```

Nếu ảnh lưu dạng `uploaded_img/ten-anh.jpg`, cấu hình hiện tại dùng `image_base_url` là `/web-kinh-doanh-s-ch/`.

## 4. Bật chatbot AI bằng OpenAI API (không bắt buộc)

Để bật câu trả lời bằng AI, đặt API key trong biến môi trường máy chủ `OPENAI_API_KEY`. Có thể cấu hình trực tiếp giá trị `api_key` trong `bookstore-ai-chatbot\config.php` trên máy chủ cục bộ. Không gửi API key lên chat, không đưa vào `chatbot.js`, HTML hoặc GitHub.

- Tài khoản API cần có quyền sử dụng model đã cấu hình (`gpt-4.1-mini`); chi phí phụ thuộc model và lượng token.
- Nếu để `api_key` trống, chức năng tìm sản phẩm và câu trả lời dự phòng vẫn hoạt động.
- Máy chủ PHP cần bật extension `curl` và `mbstring`.

## 5. Ghi nhận hành vi để cá nhân hóa

Bộ widget tự ghi nhận click vào thẻ sách do chatbot gợi ý. Để cá nhân hóa tốt hơn, gọi `BookBot.track(event, productId)` ở các thao tác trong website.

**Khi mở trang chi tiết sản phẩm** (sau khi widget đã được include):

```php
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (window.BookBot) window.BookBot.track('view', <?= (int)$product['id'] ?>);
  });
</script>
```

Thay `$product['id']` bằng biến sản phẩm thực tế của trang.

**Khi thêm vào giỏ hàng thành công:**

```javascript
if (window.BookBot) BookBot.track('cart', productId);
```

**Khi thêm yêu thích thành công:**

```javascript
if (window.BookBot) BookBot.track('favorite', productId);
```

**Khi đơn hàng được xác nhận thanh toán thành công:**

```javascript
if (window.BookBot) BookBot.track('purchase', productId);
```

Chỉ ghi nhận `purchase` sau khi đơn hàng thực sự được xác nhận; không ghi nhận khi chỉ mới bấm nút đặt hàng.

## 6. API nội bộ

| Endpoint | Phương thức | Mục đích |
|---|---|---|
| `/bookstore-ai-chatbot/api/chat.php` | POST | Gửi câu hỏi, nhận câu trả lời và sách liên quan |
| `/bookstore-ai-chatbot/api/recommend.php?limit=8` | GET | Lấy danh sách gợi ý cá nhân hóa |
| `/bookstore-ai-chatbot/api/track.php` | POST | Ghi nhận hành vi người dùng |

Các POST request sử dụng CSRF token do `widget.php` cấp. Chat API có giới hạn 40 tin nhắn/giờ cho mỗi phiên trình duyệt.

## 7. Kiểm tra sau khi cài

- Mở `http://localhost/web-kinh-doanh-s-ch/`.
- Góc phải dưới phải xuất hiện nút **Hỏi BookBot**.
- Mở tab **Dành cho bạn** để xem sách gợi ý.
- Hỏi thử: `Tìm sách lập trình Python` hoặc `Gợi ý sách dưới 200000 đồng`.
- Nếu lỗi, mở DevTools → Network và xem phản hồi của `chat.php` / `recommend.php`; đồng thời kiểm tra `C:\xampp\apache\logs\error.log` và log PHP.

## 8. Những giới hạn cần biết

- Mô-đun giả định cấu trúc bảng sản phẩm được ánh xạ chính xác trong `config.php`; chưa tự đoán được schema riêng của từng website.
- Cá nhân hóa dùng lịch sử trong cùng trình duyệt/phiên và ID user nếu người dùng đăng nhập. Muốn gộp lịch sử khách trước và sau đăng nhập cần viết thêm quy trình merge visitor_key.
- `category` nên là tên thể loại dạng text để baseline cá nhân hóa nhóm theo thể loại. Nếu cột này là `category_id`, nên JOIN bảng danh mục và ánh xạ tên thể loại.
- Dữ liệu giá/tồn kho lấy từ bảng sản phẩm hiện tại. Nên kiểm tra lại tên cột và trạng thái hàng trước khi đưa lên môi trường thật.
- Không lưu dữ liệu thẻ thanh toán hoặc thông tin bí mật vào hội thoại.
- Để triển khai RAG nâng cao theo sơ đồ bài giảng, có thể bổ sung BGE-M3 để tạo embedding, Qdrant để truy xuất vector, rồi thêm reranker trước khi gửi context cho LLM.
