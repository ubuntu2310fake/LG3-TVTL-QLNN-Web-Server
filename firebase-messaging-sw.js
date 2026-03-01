importScripts('https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.22.0/firebase-messaging-compat.js');

// --- CẤU HÌNH CỦA DỰ ÁN ENDERMAN (ID: 3182...) ---
const firebaseConfig = {
  apiKey: "AIzaSyBkUrtYsOx8u4pHd7s7lQH8AFzYOFDiavM",
  authDomain: "enderman-a66eb.firebaseapp.com",
  projectId: "enderman-a66eb",
  storageBucket: "enderman-a66eb.firebasestorage.app",
  messagingSenderId: "318238111941", // <--- QUAN TRỌNG NHẤT: Phải khớp với package.json
  appId: "1:318238111941:web:e1a058de9e2804f125d8ac",
  measurementId: "G-18YFH4X3C6"
};

firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

// Xử lý tin nhắn khi App chạy ngầm
messaging.onBackgroundMessage((payload) => {
  console.log('[SW] Nhận tin nhắn ngầm (Enderman):', payload);
  
  // Kiểm tra payload để tránh lỗi null
  const notificationTitle = payload.notification ? payload.notification.title : 'Thông báo mới';
  const notificationOptions = {
    body: payload.notification ? payload.notification.body : 'Bạn có tin nhắn mới',
    icon: 'https://testifiyonline.xyz/lg3192192.png', // Icon online
    data: payload.data // Giữ lại data để click vào mở link
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});