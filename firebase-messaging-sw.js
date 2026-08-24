importScripts('https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.22.0/firebase-messaging-compat.js');

// --- CẤU HÌNH FIREBASE MESSAGING ---
const firebaseConfig = {
  apiKey: "YOUR_FIREBASE_API_KEY",
  authDomain: "your-project-id.firebaseapp.com",
  projectId: "your-project-id",
  storageBucket: "your-project-id.firebasestorage.app",
  messagingSenderId: "000000000000",
  appId: "1:000000000000:web:0000000000000000000000",
  measurementId: "G-0000000000"
};

firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

// Xử lý tin nhắn khi App chạy ngầm
messaging.onBackgroundMessage((payload) => {
  console.log((self.LANG && self.LANG.sw_bg_message_received || '[SW] Nhận tin nhắn ngầm (Enderman):'), payload);
  
  // Kiểm tra payload để tránh lỗi null
  const notificationTitle = payload.notification ? payload.notification.title : (self.LANG && self.LANG.new_notification || 'Thông báo mới');
  const notificationOptions = {
    body: payload.notification ? payload.notification.body : (self.LANG && self.LANG.new_message || 'Bạn có tin nhắn mới'),
    icon: '/lg3192192.png', // Icon online
    data: payload.data // Giữ lại data để click vào mở link
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});