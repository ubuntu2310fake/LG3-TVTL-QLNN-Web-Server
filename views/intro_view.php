<?php
include 'includes/header.php';

// FIX: Không khai báo cứng key ở đây nữa để tránh lệch với config.php
?>

<div style="display: flex; justify-content: center; align-items: center; min-height: 80vh;">
    <div class="win-card" style="width: 100%; max-width: 500px; text-align: center; padding: 40px 20px;">
        <div style="width: 80px; height: 80px; background: var(--bg-hover); color: var(--accent-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 36px; margin: 0 auto 20px;">
            <i class="fas fa-shield-alt"></i>
        </div>
        
        <h2 style="color: var(--accent-color); margin: 0 0 10px;">Cài Đặt Thông Báo</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">Sửa lỗi kết nối & Cài đặt thông báo.</p>

        <div id="status-box" style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 12px; padding: 15px; text-align: left; margin-bottom: 20px; color: var(--text-main);">
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px;">
                <span><i class="fas fa-server"></i> Máy chủ:</span>
                <span style="color: var(--success-color); font-weight: bold;">Đã kết nối</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 14px;">
                <span><i class="fas fa-bell"></i> Trạng thái Push:</span>
                <span id="push-status" style="color: var(--text-muted);">Đang kiểm tra...</span>
            </div>
        </div>

        <button id="btn-re-register" class="win-btn" style="width: 100%; justify-content: center; padding: 15px;">
            <i class="fas fa-sync-alt"></i> Kích hoạt lại Kết nối
        </button>
        
        <p style="font-size: 12px; color: var(--text-muted); margin-top: 15px;">Nhấn nút trên nếu bạn không nhận được thông báo.</p>
    </div>
</div>

<script>
    // FIX: Sử dụng Constant từ PHP Config để đảm bảo đồng bộ
    const PUBLIC_KEY = "<?= VAPID_PUBLIC_KEY ?>"; 

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    function getSmartDeviceName() {
        const ua = navigator.userAgent;
        if (/android/i.test(ua)) return "Android Device";
        if (/iPad|iPhone|iPod/.test(ua)) return "iOS Device";
        if (/windows/i.test(ua)) return "Windows PC";
        if (/macintosh/i.test(ua)) return "Macbook";
        return "Unknown Device";
    }

    const btnRe = document.getElementById('btn-re-register');
    const statusTxt = document.getElementById('push-status');

    // Kiểm tra trạng thái hiện tại
    if ('serviceWorker' in navigator && 'PushManager' in window) {
        navigator.serviceWorker.ready.then(reg => {
            reg.pushManager.getSubscription().then(sub => {
                if (sub) {
                    statusTxt.innerText = "Đã đăng ký";
                    statusTxt.style.color = "#10b981";
                } else {
                    statusTxt.innerText = "Chưa đăng ký";
                    statusTxt.style.color = "#ef4444";
                }
            });
        });
    } else {
        statusTxt.innerText = "Không hỗ trợ";
    }

    // Xử lý nút bấm
    btnRe.addEventListener('click', async () => {
        btnRe.disabled = true;
        btnRe.innerText = "Đang xử lý...";
        
        try {
            if (!('serviceWorker' in navigator)) throw new Error("Trình duyệt không hỗ trợ Service Worker");

            // Xóa SW cũ để clean
            const reg = await navigator.serviceWorker.getRegistration();
            if (reg) {
                const oldSub = await reg.pushManager.getSubscription();
                if (oldSub) await oldSub.unsubscribe(); 
                await reg.unregister(); 
            }

            // Đăng ký mới SW (Thêm timestamp để tránh cache)
            const newReg = await navigator.serviceWorker.register('/sw.js?v=' + Date.now());
            
            // Chờ SW active
            let active = newReg.active;
            if(!active) {
                await new Promise(r => { const i = setInterval(() => { if(newReg.active){clearInterval(i);r();} }, 100); });
            }

            // Đăng ký Push Manager
            const newSub = await newReg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(PUBLIC_KEY)
            });

            const jsonSub = newSub.toJSON();
            jsonSub.device_model = getSmartDeviceName();
            
            // Gửi về Server PHP
            const res = await fetch('api/subscribe.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(jsonSub)
            });

            const data = await res.json();
            if (data.status === 'success') {
                alert("✅ Đã kích hoạt lại thành công cho: " + jsonSub.device_model);
                location.reload();
            } else {
                throw new Error(data.msg || "Server từ chối.");
            }

        } catch (e) {
            alert("Lỗi: " + e.message);
            btnRe.disabled = false;
            btnRe.innerText = "Thử lại";
        }
    });
</script>

<?php include 'includes/footer.php'; ?>