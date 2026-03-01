<?php 
// views/change_password_view.php
include 'includes/header.php'; 
?>

<style>
    /* Căn giữa chuẩn giống file gốc */
    .win-card { 
        max-width: 500px; 
        margin: 40px auto; /* Cách top 40px và căn giữa */
    }
    .win-input { box-sizing: border-box !important; width: 100%; }
    .form-group { margin-bottom: 20px; text-align: left; position: relative; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-muted); font-size: 13px; }
    .password-toggle {
        position: absolute; right: 15px; top: 38px; cursor: pointer; color: var(--text-muted); font-size: 14px;
    }
    .password-toggle:hover { color: var(--accent-color); }
</style>

<div class="win-card">
    <h2 style="text-align: center; margin-bottom: 10px; color: var(--accent-color); border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">
        <i class="fas fa-key"></i> Đổi Mật Khẩu
    </h2>
    
    <form id="changePassForm" onsubmit="submitChangePass(event)">
        <div class="form-group">
            <label for="old_password">Mật khẩu hiện tại</label>
            <input type="password" id="old_password" name="old_password" class="win-input" placeholder="Nhập mật khẩu cũ..." required>
            <i class="fas fa-eye password-toggle" onclick="togglePass('old_password')"></i>
        </div>
        
        <hr style="border: 0; border-top: 1px dashed var(--border-color); margin: 20px 0;">

        <div class="form-group">
            <label for="new_password">Mật khẩu mới</label>
            <input type="password" id="new_password" name="new_password" class="win-input" placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)" required>
            <i class="fas fa-eye password-toggle" onclick="togglePass('new_password')"></i>
        </div>

        <div class="form-group">
            <label for="confirm_password">Nhập lại mật khẩu mới</label>
            <input type="password" id="confirm_password" name="confirm_password" class="win-input" placeholder="Xác nhận lại mật khẩu..." required>
            <i class="fas fa-eye password-toggle" onclick="togglePass('confirm_password')"></i>
        </div>
        
        <button type="submit" class="win-btn" style="width:100%; padding: 12px; font-weight: bold; margin-top: 10px; justify-content: center;">
            <i class="fas fa-save" style="margin-right: 5px;"></i> LƯU THAY ĐỔI
        </button>
    </form>
</div>

<script>
    // Hàm hiện/ẩn mật khẩu
    function togglePass(id) {
        const input = document.getElementById(id);
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
    }

    // Xử lý Submit Form bằng AJAX
    async function submitChangePass(e) {
        e.preventDefault();
        
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        const oldText = btn.innerHTML;
        
        // Validate Client
        const newPass = document.getElementById('new_password').value;
        const confirmPass = document.getElementById('confirm_password').value;
        
        if (newPass !== confirmPass) {
            if(typeof Toastify !== 'undefined') Toastify({ text: "❌ Mật khẩu xác nhận không khớp!", style: { background: "#ef4444" } }).showToast();
            return;
        }
        if (newPass.length < 6) {
            if(typeof Toastify !== 'undefined') Toastify({ text: "❌ Mật khẩu phải có ít nhất 6 ký tự!", style: { background: "#ef4444" } }).showToast();
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';

        try {
            const formData = new FormData(form);
            const response = await fetch('change_password.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.status === 'success') {
                if(typeof Toastify !== 'undefined') Toastify({ text: "✅ " + data.msg, style: { background: "#10b981" } }).showToast();
                form.reset(); 
            } else {
                if(typeof Toastify !== 'undefined') Toastify({ text: "❌ " + data.msg, style: { background: "#ef4444" } }).showToast();
            }
        } catch (error) {
            alert("Lỗi kết nối server!");
        } finally {
            btn.disabled = false;
            btn.innerHTML = oldText;
        }
    }
</script>

<?php include 'includes/footer.php'; ?>