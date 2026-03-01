// sw.js - Service Worker xử lý thông báo

// 1. Lắng nghe sự kiện PUSH (Khi Server gửi tin đến)
self.addEventListener('push', function(event) {
    console.log('[Service Worker] Push Received.');

    // Mặc định nếu không có data
    let data = { title: "Thông báo mới", body: "Bạn có tin nhắn mới.", url: "/", icon: "https://qlnn.testifiyonline.xyz/lg3192192.png" };

    if (event.data) {
        try {
            // Parse dữ liệu JSON từ PHP gửi sang
            data = event.data.json();
        } catch (e) {
            console.error('Lỗi parse JSON push:', e);
            data.body = event.data.text(); // Fallback nếu gửi text thường
        }
    }

    const title = data.title || "Hệ thống Nền nếp";
    const options = {
        body: data.body,
        icon: data.icon || "https://qlnn.testifiyonline.xyz/lg3192192.png",
        badge: "https://qlnn.testifiyonline.xyz/lg3192192.png", // Icon nhỏ trên thanh status (Android)
        vibrate: [100, 50, 100],
        data: {
            url: data.url || "/" // Lưu link để dùng khi bấm vào
        }
    };

    // Hiển thị thông báo
    event.waitUntil(self.registration.showNotification(title, options));
});

// 2. Lắng nghe sự kiện CLICK (Khi người dùng bấm vào thông báo)
self.addEventListener('notificationclick', function(event) {
    console.log('[Service Worker] Notification click received.');

    event.notification.close(); // Đóng thông báo ngay

    // Lấy URL đã lưu trong options.data ở trên
    const urlToOpen = event.notification.data.url;

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
            // A. Nếu tab đó đã mở sẵn -> Focus vào nó
            for (let i = 0; i < clientList.length; i++) {
                let client = clientList[i];
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            // B. Nếu chưa mở -> Mở tab mới
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});