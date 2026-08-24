<?php
include 'includes/header.php';

// FIX: Không khai báo cứng key ở đây nữa để tránh lệch với config.php
?>

<div style="display: flex; justify-content: center; align-items: center; min-height: 80vh;">
    <div class="win-card" style="width: 100%; max-width: 500px; text-align: center; padding: 40px 20px;">
        <div style="width: 80px; height: 80px; background: var(--bg-hover); color: var(--accent-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 36px; margin: 0 auto 20px;">
            <i class="fas fa-shield-alt" aria-hidden="true"></i>
        </div>
        
        <h2 style="color: var(--accent-color); margin: 0 0 10px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cài đặt thông báo' : 'Notification Settings') ?></h2>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Quản lý thông báo đẩy từ hệ thống' : 'Manage system push notifications') ?></p>

        <div id="status-box" style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 12px; padding: 15px; text-align: left; margin-bottom: 20px; color: var(--text-main);">
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px;">
                <span><i class="fas fa-server" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Máy chủ' : 'Server') ?>:</span>
                <span style="color: var(--success-color); font-weight: bold;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đã kết nối' : 'Connected') ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 14px;">
                <span><i class="fas fa-bell" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trạng thái thông báo:' : 'Push Status:') ?></span>
                <span id="push-status" style="color: var(--text-muted);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang kiểm tra...' : 'Checking...') ?></span>
            </div>
        </div>

        <button id="btn-re-register" class="win-btn" style="width: 100%; justify-content: center; padding: 15px;">
            <i class="fas fa-sync-alt" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đăng ký lại thông báo' : 'Re-register Notifications') ?>
        </button>
        
        <p style="font-size: 12px; color: var(--text-muted); margin-top: 15px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Sử dụng khi bạn không nhận được thông báo' : 'Use when you are not receiving notifications') ?></p>
    </div>
</div>

<script>
    var PUBLIC_KEY = "<?= VAPID_PUBLIC_KEY ?>"; 

    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        var rawData = window.atob(base64);
        var outputArray = new Uint8Array(rawData.length);
        for (var i = 0; i < rawData.length; ++i) { outputArray[i] = rawData.charCodeAt(i); }
        return outputArray;
    }

    function getSmartDeviceName() {
        var ua = navigator.userAgent;
        if (/android/i.test(ua)) return "Android Device";
        if (/iPad|iPhone|iPod/.test(ua)) return "iOS Device";
        if (/windows/i.test(ua)) return "Windows PC";
        if (/macintosh/i.test(ua)) return "Macbook";
        return "Unknown Device";
    }

    var btnRe = document.getElementById('btn-re-register');
    var statusTxt = document.getElementById('push-status');

    var initIntroUI = function() {
        if ('serviceWorker' in navigator && 'PushManager' in window) {
            navigator.serviceWorker.ready.then(reg => {
                reg.pushManager.getSubscription().then(sub => {
                    if (sub && window.Notification && Notification.permission === 'granted') { 
                        statusTxt.innerText = (window.LANG && window.LANG.registered || "Đã đăng ký"); 
                        statusTxt.style.color = "#10b981"; 
                    } else if (window.Notification && Notification.permission === 'denied') {
                        statusTxt.innerText = "Bị từ chối (Bị khóa)"; 
                        statusTxt.style.color = "#ef4444";
                    } else { 
                        statusTxt.innerText = (window.LANG && window.LANG.unregistered || "Chưa đăng ký"); 
                        statusTxt.style.color = "#f59e0b"; 
                    }
                });
            });
        } else { statusTxt.innerText = (window.LANG && window.LANG.not_supported || "Không hỗ trợ"); }
    };
    setTimeout(initIntroUI, 100);

    if (btnRe) {
        btnRe.onclick = async () => {
            btnRe.disabled = true;
            btnRe.innerText = (window.LANG && window.LANG.processing || "Đang xử lý...");
            try {
                if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                    throw new Error(window.LANG && window.LANG.sw_not_supported || "Trình duyệt không hỗ trợ Web Push Notification");
                }

                // 1. KIỂM TRA & YÊU CẦU CẤP QUYỀN TRÌNH DUYỆT TRƯỚC KHI SUBSCRIBE
                var perm = window.Notification ? Notification.permission : 'default';
                if (perm !== 'granted') {
                    perm = await Notification.requestPermission();
                }

                if (perm === 'denied') {
                    throw new Error("Quyền nhận thông báo đã bị TỪ CHỐI trên trình duyệt. Vui lòng bấm vào biểu tượng 🔒 (ổ khóa/cài đặt) ở thanh địa chỉ web để CHO PHÉP thông báo!");
                }

                if (perm !== 'granted') {
                    throw new Error("Bạn chưa cho phép cấp quyền nhận thông báo.");
                }

                // 2. ĐĂNG KÝ SERVICE WORKER CHUẨN
                var reg = await navigator.serviceWorker.register('/sw.js');
                await navigator.serviceWorker.ready;

                // 3. HỦY SUBSCRIPTION CŨ NẾU CÓ ĐỂ TẠO MỚI SẠCH SE
                try {
                    var oldSub = await reg.pushManager.getSubscription();
                    if (oldSub) await oldSub.unsubscribe();
                } catch (subErr) {}

                // 4. SUBSCRIBE PUSH MỚI
                var newSub = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(PUBLIC_KEY)
                });

                var jsonSub = newSub.toJSON();
                jsonSub.device_model = getSmartDeviceName();
                jsonSub.platform = 'web';

                var res = await fetch('api/subscribe.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(jsonSub)
                });

                var data = await res.json();
                if (data.status === 'success') {
                    alert("✅ " + (window.LANG && window.LANG.reactivate_success || "Đã kích hoạt lại thành công cho:") + " " + jsonSub.device_model);
                    if (typeof loadPage === 'function') loadPage(window.location.href);
                    else location.reload();
                } else {
                    throw new Error(data.msg || (window.LANG && window.LANG.server_rejected || "Server từ chối."));
                }
            } catch (e) {
                alert((window.LANG && window.LANG.error || "Lỗi:") + " " + e.message);
                btnRe.disabled = false;
                btnRe.innerText = (window.LANG && window.LANG.retry || "Thử lại");
            }
        };
    }
</script>

<?php include 'includes/footer.php'; ?>