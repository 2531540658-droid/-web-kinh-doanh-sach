USE `shop_db`;

-- Sách trước đây chưa có giá được đối chiếu ngày 2026-10-28; các sách mẫu khác
-- được đối chiếu ngày 2026-10-27. Giá bán hiện tại có thể thay đổi.

-- Trang bán hiện báo hết hàng; giá là mức giá bán đang hiển thị.
-- https://nxbkimdong.com.vn/products/bach-tuyet-toc-do-tap-16
UPDATE `products`
SET
  `price` = 27000,
  `details` = 'Tại Lilias, dịch cúm khiến công việc nghiên cứu của Shirayuki và Ryu thêm phần căng thẳng. Cả hai tiếp tục trưởng thành qua trải nghiệm ấy, trong khi Zen nhận một mệnh lệnh mới từ Izana.'
WHERE `image` = 'bachtuyettocdo.png';

-- https://nxbkimdong.com.vn/products/ban-giao-huong-cua-nuoc
UPDATE `products`
SET
  `price` = 58500,
  `details` = 'Tác phẩm khám phá vai trò của nước đối với sự sống, văn hóa và lịch sử qua nhiều góc nhìn. Nội dung cũng gợi mở suy nghĩ về việc trân trọng và bảo vệ nguồn nước.'
WHERE `image` = 'bangiaohuongcuanuoc.png';

-- Phiên bản "Tập 06 (2026)" hiện còn hàng.
-- https://nxbkimdong.com.vn/products/tap-le-duoc-su-tu-su-manga
UPDATE `products`
SET
  `price` = 42300,
  `details` = 'Miêu Miêu từng hành nghề dược sư ở phố hoa trước khi bị đưa vào cung đình, nơi kiến thức về dược liệu giúp cô lần theo những bí ẩn trong cung. Đây là phần giới thiệu tiền đề chung của bộ manga, không phải tóm tắt riêng tập 6.'
WHERE `image` = 'duocsutusu.png';

-- Phiên bản "Tập 23 (2026)" hiện còn hàng.
-- https://nxbkimdong.com.vn/products/tap-le-chu-thuat-hoi-chien
UPDATE `products`
SET
  `price` = 27000,
  `details` = 'Itadori Yuji là một học sinh trung học có thể lực phi thường; cuộc đời cậu rẽ sang hướng khác sau khi vướng vào một vật bị nguyền rủa. Đây là phần giới thiệu tiền đề chung của bộ manga Chú thuật hồi chiến, không phải tóm tắt riêng tập 23.'
WHERE `image` = 'jujustukaisen23.png';

-- Ảnh bìa này thuộc bản tiểu thuyết, không phải bản truyện tranh.
-- Trang bán hiện báo hết hàng; giá là mức giá bán đang hiển thị.
-- https://nxbkimdong.com.vn/products/hon-nhan-hanh-phuc-cua-toi-light-novel-tap-1-tang-02-clipsheet-01-card-nhua-trong
UPDATE `products`
SET
  `price` = 20000,
  `details` = 'Miyo lớn lên trong sự ghẻ lạnh của gia đình và bị sắp đặt kết hôn với Kiyoka, một quân nhân mang tiếng lạnh lùng. Cuộc gặp gỡ ban đầu đầy dè dặt mở ra cơ hội để cả hai dần hiểu nhau và tìm kiếm một mái ấm.'
WHERE `image` = 'honnhanhahphuc.png';

-- Trang bán bản thường đang hết hàng; trang bán bản giới hạn
-- cùng ISBN còn hàng với cùng mức giá hiển thị.
-- https://nxbkimdong.com.vn/products/loi-chao-tu-moskva-james-bond-ban-gioi-han-tang-kem-bia-ao-postcard
UPDATE `products`
SET
  `price` = 10000,
  `details` = 'Điệp viên James Bond trở thành mục tiêu của SMERSH trong một kế hoạch gài bẫy được chuẩn bị kỹ lưỡng. Sự xuất hiện của nữ điệp viên Tatiana Romanova khiến nhiệm vụ và lòng tin của Bond thêm phức tạp.'
WHERE `image` = 'moskva.jpg';

-- Trang bán đúng bản thường của Nhà Sách Phương Nam trên Tiki.
-- https://tiki.vn/son-goal-tap-3-p275838908.html?spid=275838909
UPDATE `products`
SET
  `price` = 45000,
  `details` = 'Sơn và Phong được gọi lên trại huấn luyện để chuẩn bị cho giải U15 châu Á. Trong lúc Phong quyết tâm giành suất thi đấu, Sơn tìm hiểu thêm về mẹ mình qua những câu chuyện của người thân. Cả hai phải đối mặt với thử thách để có cơ hội góp mặt trong đội tuyển.'
WHERE `image` = 'SonGoal.png';

-- Trang bán bản thường đang hết hàng; trang bán bản giới hạn với
-- cùng ISBN còn hàng với cùng mức giá hiển thị.
-- https://nxbkimdong.com.vn/products/su-gia-bon-mua-vu-dieu-mua-xuan-quyen-2-ban-gioi-han-tang-postcard-clear-bookmark
UPDATE `products`
SET
  `price` = 126000,
  `details` = 'Trong thế giới nơi bốn mùa được hình thành từ mùa đông, các sứ giả có nhiệm vụ đưa mùa đi theo dòng thời gian. Quyển 2 giới thiệu Hinagiku, sứ giả mùa xuân, cùng Sakura, kiếm sĩ đã thề bảo vệ cô; câu chuyện đặt họ trong một hành trình gắn với sứ mệnh và lời hẹn.'
WHERE `image` = 'sugia4mua.png';

-- Phiên bản "Tập 08 (2025)" hiện còn hàng.
-- https://nxbkimdong.com.vn/products/tap-le-thien-su-nha-ben-light-novel
UPDATE `products`
SET
  `price` = 85500,
  `details` = 'Amane Fujimiya sống cạnh Shiina Mahiru, nữ sinh nổi tiếng nhất trường; một lần gặp nhau trong ngày mưa mở đầu cho mối gắn kết giữa hai người. Đây là tiền đề chung của bộ tiểu thuyết Thiên sứ nhà bên, không phải tóm tắt riêng tập 8.'
WHERE `image` = 'thien-su-nha-ben-tap-8.jpg';

-- Phiên bản "Tập 02 (2025)" hiện còn hàng.
-- https://nxbkimdong.com.vn/products/tap-le-thien-su-nha-ben-light-novel
UPDATE `products`
SET
  `price` = 85500,
  `details` = 'Amane Fujimiya sống cạnh Shiina Mahiru, nữ sinh nổi tiếng nhất trường; một lần gặp nhau trong ngày mưa mở đầu cho mối gắn kết giữa hai người. Đây là tiền đề chung của bộ tiểu thuyết Thiên sứ nhà bên, không phải tóm tắt riêng tập 2.'
WHERE `image` = 'thiesunhaben.png';
