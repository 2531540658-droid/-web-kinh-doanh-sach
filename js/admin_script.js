// Lấy các phần tử từ DOM
const navbar = document.querySelector(".header .flex .navbar");
const userBox = document.querySelector(".header .flex .account-box");
const menuBtn = document.querySelector("#menu-btn");
const userBtn = document.querySelector("#user-btn");

// Định nghĩa sự kiện nhấn cho nút trình đơn
menuBtn.onclick = () => {
  navbar.classList.toggle("active");
  userBox.classList.remove("active");
};

// Định nghĩa sự kiện cuộn trang cho cửa sổ trình duyệt
window.onscroll = () => {
  navbar.classList.remove("active");
  userBox.classList.remove("active");
};

// Định nghĩa sự kiện nhấn cho nút người dùng
userBtn.onclick = () => {
  userBox.classList.toggle("active");
  navbar.classList.remove("active");
};
