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
        <i class="fas fa-key" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đổi mật khẩu' : 'Change Password') ?>
    </h2>
    
    <form id="changePassForm" onsubmit="submitChangePass(event)">
        <div class="form-group">
            <label for="old_password"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mật khẩu hiện tại' : 'Current Password') ?></label>
            <input type="password" id="old_password" name="old_password" class="win-input" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập mật khẩu hiện tại' : 'Enter current password') ?>" required>
            <i class="fas fa-eye password-toggle" onclick="togglePass('old_password')" role="button" tabindex="0" onkeypress="if(event.key==='Enter'||event.key===' ') togglePass('old_password');" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hiển thị/Ẩn mật khẩu' : 'Toggle password visibility') ?>" aria-hidden="false"></i>
        </div>
        
        <hr style="border: 0; border-top: 1px dashed var(--border-color); margin: 20px 0;">

        <div class="form-group">
            <label for="new_password"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mật khẩu mới' : 'New Password') ?></label>
            <input type="password" id="new_password" name="new_password" class="win-input" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chữ hoa, chữ thường, số, ký tự đặc biệt' : 'Uppercase, lowercase, numbers, special characters') ?>" required>
            <i class="fas fa-eye password-toggle" onclick="togglePass('new_password')" role="button" tabindex="0" onkeypress="if(event.key==='Enter'||event.key===' ') togglePass('new_password');" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hiển thị/Ẩn mật khẩu' : 'Toggle password visibility') ?>" aria-hidden="false"></i>
            <small style="display:block; margin-top:5px; color:var(--text-muted); font-size:12px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '🔒 Yêu cầu: Ít nhất 6 ký tự, gồm chữ hoa (A-Z), chữ thường (a-z), số (0-9) và ký tự đặc biệt (!@#...).' : '🔒 Requirements: At least 6 characters, including uppercase (A-Z), lowercase (a-z), numbers (0-9) and special characters (!@#...).') ?></small>
        </div>

        <div class="form-group">
            <label for="confirm_password"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xác nhận mật khẩu mới' : 'Confirm New Password') ?></label>
            <input type="password" id="confirm_password" name="confirm_password" class="win-input" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập lại mật khẩu mới' : 'Re-enter new password') ?>" required>
            <i class="fas fa-eye password-toggle" onclick="togglePass('confirm_password')" role="button" tabindex="0" onkeypress="if(event.key==='Enter'||event.key===' ') togglePass('confirm_password');" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hiển thị/Ẩn mật khẩu' : 'Toggle password visibility') ?>" aria-hidden="false"></i>
        </div>
        
        <button type="submit" class="win-btn" style="width:100%; padding: 12px; font-weight: bold; margin-top: 10px; justify-content: center;">
            <i class="fas fa-save" style="margin-right: 5px;" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lưu thay đổi' : 'Save Changes') ?>
        </button>
    </form>
</div>

<script>
    window.pageDestroy = function() {};
    window.pageInit = function() {};

    window.togglePass = function(id) {
        var input = document.getElementById(id);
        var type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
    };

    window.submitChangePass = async function(e) {
        e.preventDefault(); var form = e.target; var btn = form.querySelector('button[type="submit"]'); var oldText = btn.innerHTML;
        var newPass = document.getElementById('new_password').value; var confirmPass = document.getElementById('confirm_password').value;
        if (newPass !== confirmPass) { if(typeof Toastify !== 'undefined') Toastify({ text: "❌ " + '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mật khẩu xác nhận không khớp!' : 'Passwords do not match!') ?>', style: { background: "#ef4444" } }).showToast(); return; }
        if (newPass === '123456') { if(typeof Toastify !== 'undefined') Toastify({ text: "❌ Mật khẩu mới không được là 123456!", style: { background: "#ef4444" } }).showToast(); return; }
        if (newPass.length < 6) { if(typeof Toastify !== 'undefined') Toastify({ text: "❌ Mật khẩu phải có ít nhất 6 ký tự!", style: { background: "#ef4444" } }).showToast(); return; }
        if (!/[A-Z]/.test(newPass)) { if(typeof Toastify !== 'undefined') Toastify({ text: "❌ Mật khẩu phải chứa ít nhất 1 chữ cái viết hoa (A-Z)!", style: { background: "#ef4444" } }).showToast(); return; }
        if (!/[a-z]/.test(newPass)) { if(typeof Toastify !== 'undefined') Toastify({ text: "❌ Mật khẩu phải chứa ít nhất 1 chữ cái viết thường (a-z)!", style: { background: "#ef4444" } }).showToast(); return; }
        if (!/[0-9]/.test(newPass)) { if(typeof Toastify !== 'undefined') Toastify({ text: "❌ Mật khẩu phải chứa ít nhất 1 chữ số (0-9)!", style: { background: "#ef4444" } }).showToast(); return; }
        if (!/[^A-Za-z0-9]/.test(newPass)) { if(typeof Toastify !== 'undefined') Toastify({ text: "❌ Mật khẩu phải chứa ít nhất 1 ký tự đặc biệt (!@#...)!", style: { background: "#ef4444" } }).showToast(); return; }
        
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> ' + '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang xử lý...' : 'Processing...') ?>';
        try {
            var response = await fetch('change_password.php', { method: 'POST', body: new FormData(form) });
            var data = await response.json();
            if (data.status === 'success') { if(typeof Toastify !== 'undefined') Toastify({ text: "✅ " + data.msg, style: { background: "#10b981" } }).showToast(); form.reset(); } 
            else { if(typeof Toastify !== 'undefined') Toastify({ text: "❌ " + data.msg, style: { background: "#ef4444" } }).showToast(); }
        } catch (error) { alert('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi kết nối server!' : 'Server connection error!') ?>'); } finally { btn.disabled = false; btn.innerHTML = oldText; }
    };
</script>

<?php include 'includes/footer.php'; ?>