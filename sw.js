// sw.js - Service Worker xử lý thông báo & Chế độ Offline

// =================================================================
// PHẦN 1: XỬ LÝ OFFLINE (PWA FALLBACK)
// =================================================================
const CACHE_NAME = 'lg3-offline-cache-v2';

// Giao diện HTML thuần sẽ hiển thị khi người dùng F5 lúc rớt mạng
const OFFLINE_HTML = `
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đang ngoại tuyến</title>
    <style>
        :root {
            --bg-body: #f5f7fa; --bg-card: #ffffff;
            --text-main: #1d1d1f; --text-muted: #64748b;
            --primary: #005fba; --danger: #d93025; --border: #e2e8f0;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg-body: #0f172a; --bg-card: #1e293b;
                --text-main: #ffffff; --text-muted: #e2e8f0;
                --primary: #38bdf8; --border: #334155;
            }
        }
        body { 
            margin: 0; padding: 0; background-color: var(--bg-body); 
            /* Sử dụng Font hệ thống siêu nhẹ, không cần mạng, giống Inter 99% */
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            display: flex; align-items: center; justify-content: center; height: 100vh; color: var(--text-main);
        }
        .offline-box { 
            text-align: center; background: var(--bg-card); padding: 40px 30px; 
            border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.1); 
            border: 1px solid var(--border); width: 85%; max-width: 320px; 
        }
        .offline-icon { font-size: 50px; color: var(--danger); margin-bottom: 20px; display: inline-block; font-weight: bold; }
        .offline-title { font-size: 20px; font-weight: 700; margin-bottom: 12px; }
        .offline-desc { font-size: 14px; color: var(--text-muted); margin-bottom: 25px; line-height: 1.5; }
        .btn-retry { 
            display: block; width: 100%; padding: 12px 0; font-size: 15px; font-weight: 600; 
            color: #fff; background: var(--primary); border: none; border-radius: 10px; 
            cursor: pointer; text-decoration: none; 
        }
        .btn-retry:active { transform: scale(0.97); }
    </style>
</head>
<body>
    <div class="offline-box">
        <div class="offline-icon">⚠</div>
        <div class="offline-title">Mất kết nối mạng</div>
        <div class="offline-desc">Không thể kết nối đến máy chủ. Vui lòng kiểm tra lại Internet của bạn.</div>
        <button class="btn-retry" onclick="window.location.reload()">Thử lại ngay</button>
    </div>
</body>
</html>
`;

// Cài đặt Service Worker
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            // Lưu trang offline vào cache
            return cache.put(new Request('/offline-fallback.html'), new Response(OFFLINE_HTML, {
                headers: { 'Content-Type': 'text/html; charset=utf-8' }
            }));
        })
    );
    self.skipWaiting();
});

// Kích hoạt SW và dọn rác cũ
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.filter(name => name !== CACHE_NAME).map(name => caches.delete(name))
            );
        })
    );
    self.clients.claim();
});

// Đánh chặn các yêu cầu mạng (Ngăn trang báo lỗi mặc định)
self.addEventListener('fetch', (event) => {
    // Chỉ quan tâm đến các yêu cầu điều hướng (chuyển trang/f5)
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => {
                // Nếu rớt mạng (fetch thất bại), lấy giao diện Offline từ cache ra trả về!
                return caches.match('/offline-fallback.html');
            })
        );
    }
});

// =================================================================
// PHẦN 2: XỬ LÝ THÔNG BÁO PUSH (GIỮ NGUYÊN)
// =================================================================
// 1. Lắng nghe sự kiện PUSH (Khi Server gửi tin đến)
self.addEventListener('push', function(event) {
    console.log('[Service Worker] Push Received.');

    // Mặc định nếu không có data
    let data = { title: (self.LANG && self.LANG.new_notification || "Thông báo mới"), body: (self.LANG && self.LANG.new_message || "Bạn có tin nhắn mới."), url: "/", icon: "/lg3192192.png" };

    if (event.data) {
        try {
            // Parse dữ liệu JSON từ PHP gửi sang
            data = event.data.json();
        } catch (e) {
            console.error((self.LANG && self.LANG.json_parse_error || 'Lỗi parse JSON push:'), e);
            data.body = event.data.text(); // Fallback nếu gửi text thường
        }
    }

    const title = data.title || (self.LANG && self.LANG.system_name || "Hệ thống Nền nếp");
    const options = {
        body: data.body,
        icon: data.icon || "/lg3192192.png",
        badge: "/lg3192192.png", // Icon nhỏ trên thanh status (Android)
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