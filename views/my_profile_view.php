<?php
include 'includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<style>
    .profile-header { text-align: center; margin-bottom: 20px; }
    .qr-box { 
        background: var(--bg-card); 
        padding: 15px; border-radius: 12px; 
        border: 1px solid var(--border-color); 
        display: inline-block; margin-top: 15px; 
    }
    .dev-list { margin-top: 15px; border-top: 1px solid var(--border-color); }
    .dev-item { 
        display: flex; align-items: center; justify-content: space-between; 
        padding: 12px 0; border-bottom: 1px solid var(--border-color); 
    }
    .dev-info { font-size: 14px; color: var(--text-main); font-weight: 500; }
    .dev-sub { font-size: 11px; color: var(--text-muted); font-family: monospace; margin-top: 2px; }
    
    .btn-action { border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; }
    .btn-del-dev { background: #fee2e2; color: #ef4444; }
    .btn-del-dev:hover { background: #fecaca; }
    .btn-test-dev { background: #3b82f6; color: var(--bg-card); margin-right: 5px; } /* Đổi từ white */
    .btn-test-dev:hover { background: #2563eb; }
    .win-input { box-sizing: border-box !important; width: 100%; }

    #cropModal {
        display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%;
        background-color: rgba(0,0,0,0.8); align-items: center; justify-content: center;
    }
    .crop-container {
        background: var(--bg-card); color: var(--text-main);
        padding: 20px; border-radius: 10px; width: 90%; max-width: 500px;
        display: flex; flex-direction: column; gap: 15px;
    }
    .img-container { max-height: 60vh; overflow: hidden; background: #333; }
    .img-container img { max-width: 100%; display: block; }

    /* Dark mode fixes for profile page */
    [data-theme="dark"] .win-input {
        background-color: #121212 !important;
        color: #ffffff !important;
        border-color: #262626 !important;
    }
    [data-theme="dark"] .btn-del-dev {
        background: rgba(239, 68, 68, 0.2) !important;
        color: #f87171 !important;
    }
    [data-theme="dark"] .qr-box {
        background: #111111 !important;
    }
    [data-theme="dark"] .profile-header h2 {
        color: #ffffff !important;
    }
</style>

<div class="win-card" style="max-width:600px; margin:0 auto;">
    <h2 style="color:var(--text-heading); text-align:center; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
        <i class="fas fa-id-card" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hồ sơ của tôi' : 'My Profile') ?>
    </h2>

    <div class="profile-header">
        <div style="position: relative; display: inline-block;">
            <img src="<?= htmlspecialchars($avatar_url) ?>" 
                 id="displayAvatar" 
                 class="refresh-cache"
                 onerror="this.src='static/default.png'"
                 style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid var(--border-color);">
            
            <div style="position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%); display:flex; gap:5px;">
                <button type="button" onclick="document.getElementById('file-avatar').click()" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thay đổi ảnh đại diện' : 'Change Avatar') ?>" style="background: #005fba; color: #ffffff !important; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border:none; box-shadow: 0 2px 5px rgba(0,0,0,0.4);">
                    <i class="fas fa-camera" aria-hidden="true"></i>
                </button>
    
                <?php 
                // Kiểm tra: Nếu có avatar và không phải là avatar mặc định thì hiện nút xóa
                if (!empty($avatar_url) && strpos($avatar_url, 'default.png') === false): 
                ?>
                <button type="button" onclick="deleteAvatar()" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xóa ảnh đại diện' : 'Delete Avatar') ?>" style="background: #ef4444; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border:none; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                    <i class="fas fa-trash" aria-hidden="true"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div style="margin-top: 15px; font-weight: bold; font-size: 18px; color:var(--text-main);">
            <?= htmlspecialchars($user['full_name'] ?: $user['username']) ?>
        </div>
        <div style="color: var(--text-muted); font-size: 14px;">
            <?= $user['role'] == 'STUDENT' ? (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Học sinh' : 'Student') : ($user['role'] == 'TEACHER' ? (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Giáo viên' : 'Teacher') : $user['role']) ?>
        </div>
        <?php if ($student): 
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $domain_url = $protocol . $_SERVER['HTTP_HOST'];
            $qr_data = urlencode($domain_url . '/' . $student['code']);
        ?>
        <div class="qr-box">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= $qr_data ?>" style="width:120px; height:120px;">
            <div style="font-weight:bold; font-family:monospace; color:var(--text-main); margin-top:5px;"><?= $student['code'] ?></div>
            <div style="margin-top: 10px;">
                <a href="/<?= htmlspecialchars($student['code']) ?>" target="_blank" class="win-btn" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; padding: 6px 12px; background: #005fba; text-decoration: none; color: #ffffff !important; border-radius: 6px;">
                    <i class="fas fa-external-link-alt" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xem trang Bio của tôi' : 'View My Bio Page') ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div style="background: var(--bg-hover); padding: 15px; border-radius: 8px; border: 1px dashed var(--border-color); margin-bottom: 20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; cursor:pointer;" onclick="toggleDevicesCollapse()" role="button" tabindex="0" onkeypress="if(event.key==='Enter'||event.key===' ') toggleDevicesCollapse();">
            <h4 style="margin:0; color:var(--text-main); display:flex; align-items:center; gap:8px; user-select:none;">
                <i class="fas fa-mobile-alt" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thiết bị đăng nhập' : 'Login Devices') ?>
                <i id="devices-chevron" class="fas fa-chevron-down" style="font-size:12px; transition: transform 0.2s;" aria-hidden="true"></i>
            </h4>
            <button onclick="event.stopPropagation(); registerCurrentDevice()" class="win-btn" style="padding: 6px 10px; font-size: 12px; background: #10b981;">
                <i class="fas fa-plus" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thêm thiết bị này' : 'Add This Device') ?>
            </button>
        </div>
        <div id="devices-list-container" style="display: none; margin-top: 15px; border-top: 1px dashed var(--border-color); padding-top: 15px;">
            <div class="dev-list" style="margin-top: 0; border-top: none;">
                <?php if ($devices): foreach ($devices as $d): ?>
                <div class="dev-item" id="dev-<?= $d['session_id'] ?>">
                    <div style="flex: 1; padding-right: 10px;">
                        <div class="dev-info">
                            <i class="fas <?= $d['icon_class'] ?>" style="color:var(--text-muted); margin-right:5px;" aria-hidden="true"></i> 
                            <?= htmlspecialchars($d['device_name']) ?>
                            <?php if($d['session_id'] == $currentSessId) echo "<span style='color:#10b981; font-size:11px; margin-left:5px;'>(" . ( (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thiết bị hiện tại' : 'Current Device') ) . ")</span>"; ?>
                        </div>
                        <div class="dev-sub">
                            Active: <?= date('d/m H:i', strtotime($d['last_active'])) ?>
                            <?php if($d['push_id']) echo " • <span style='color:#3b82f6'>" . ( (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đã bật thông báo' : 'Notifications Enabled') ) . "</span>"; ?>
                        </div>
                    </div>
                    
                    <?php if ($d['session_id'] != $currentSessId): ?>
                    <div style="display: flex;">
                        <?php if($d['push_id']): ?>
                        <button onclick="testPush('<?= $d['session_id'] ?>')" class="btn-action btn-test-dev"><i class="fas fa-paper-plane" aria-hidden="true"></i> Test</button>
                        <?php endif; ?>
                        
                        <button onclick="removeDevice('<?= $d['session_id'] ?>')" class="btn-action btn-del-dev"><i class="fas fa-sign-out-alt" aria-hidden="true"></i> Kick</button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; else: ?>
                <div style="text-align:center; padding:15px; color:var(--text-muted); font-size:13px; font-style:italic;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Không có phiên đăng nhập nào khác' : 'No other sessions') ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- CARD BẢO MẬT 2 YẾU TỐ (2FA) -->
    <div style="background: var(--bg-hover); padding: 15px; border-radius: 8px; border: 1px dashed var(--border-color); margin-bottom: 20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div>
                <h4 style="margin:0; color:var(--text-main); display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-shield-alt" style="color: <?= $is_2fa_enabled ? '#10b981' : '#f59e0b' ?>;"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bảo mật 2 Yếu tố (2FA)' : 'Two-Factor Authentication (2FA)') ?>
                </h4>
                <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">
                    <?= $is_2fa_enabled ? '<span style="color:#10b981; font-weight:bold;"><i class="fas fa-check-circle"></i> ' . ( (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đã kích hoạt (Xác thực 2 bước bằng ứng dụng Authenticator)' : 'Activated (2-Step Verification using Authenticator app)') ) . '</span>' : ( (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chưa bật 2FA. Hãy bật để nâng cao độ an toàn cho tài khoản.' : '2FA is disabled. Enable it to improve account security.') ) ?>
                </div>
            </div>
            <?php if ($is_2fa_enabled): ?>
                <button type="button" onclick="openDisable2FAModal()" class="win-btn" style="padding: 6px 12px; font-size: 12px; background: #ef4444; color: white;">
                    <i class="fas fa-power-off"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tắt 2FA' : 'Disable 2FA') ?>
                </button>
            <?php else: ?>
                <button type="button" onclick="openSetup2FAModal()" class="win-btn" style="padding: 6px 12px; font-size: 12px; background: #10b981; color: white;">
                    <i class="fas fa-key"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bật 2FA ngay' : 'Enable 2FA Now') ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- CARD LIÊN KẾT EMAIL KHÔI PHỤC MẬT KHẨU -->
    <div style="background: var(--bg-hover); padding: 15px; border-radius: 8px; border: 1px dashed var(--border-color); margin-bottom: 20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div>
                <h4 style="margin:0; color:var(--text-main); display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-envelope" style="color: <?= $is_email_verified ? '#10b981' : '#005fba' ?>;"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Liên kết Email Khôi phục Mật khẩu' : 'Linked Recovery Email') ?>
                </h4>
                <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">
                    <?php if ($is_email_verified && !empty($user_email)): ?>
                        <span style="color:#10b981; font-weight:bold;"><i class="fas fa-check-circle"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đã xác thực: ' : 'Verified: ') . htmlspecialchars($user_email) ?></span>
                    <?php else: ?>
                        <span><?= !empty($user_email) ? ((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chưa xác thực OTP cho email: ' : 'Unverified OTP for: ') . htmlspecialchars($user_email)) : ((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Liên kết email để tự lấy lại mật khẩu khi quên.' : 'Link email to recover password when forgotten.')) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($is_email_verified): ?>
                <button type="button" onclick="unlinkEmailAccount()" class="win-btn" style="padding: 6px 12px; font-size: 12px; background: #ef4444; color: white;">
                    <i class="fas fa-unlink"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hủy liên kết' : 'Unlink') ?>
                </button>
            <?php else: ?>
                <button type="button" onclick="openLinkEmailModal()" class="win-btn" style="padding: 6px 12px; font-size: 12px; background: #005fba; color: white;">
                    <i class="fas fa-link"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Liên kết Email ngay' : 'Link Email Now') ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <form id="profileForm" onsubmit="submitProfileForm(event)" enctype="multipart/form-data">
        <input type="hidden" name="delete_image" id="deleteImageInput" value="0">
        <label for="file-avatar" style="display: none;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ảnh đại diện' : 'Avatar') ?></label>
        <input type="file" name="image" id="file-avatar" accept="image/*" style="display: none;">
        
        <?php if ($student): ?>
            <?php if ($student['has_pending_changes']): ?>
            <div style="background:#fff7ed; color:#c2410c; padding:10px; border-radius:8px; margin-bottom:15px; font-size:13px; border: 1px solid #ffedd5;">
                <i class="fas fa-clock" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang chờ giáo viên duyệt' : 'Pending teacher approval') ?>
            </div>
            <?php endif; ?>

            <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1; min-width: 240px;">
                    <label for="student_name" style="font-weight:bold; font-size:13px; color:var(--text-muted); display:block; margin-bottom:5px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Họ và tên' : 'Full Name') ?></label>
                    <input type="text" id="student_name" name="name" value="<?= htmlspecialchars($student['has_pending_changes'] ? $student['pending_name'] : $student['name']) ?>" class="win-input" required>
                </div>
                <div style="flex: 1; min-width: 240px;">
                    <label for="student_dob" style="font-weight:bold; font-size:13px; color:var(--text-muted); display:block; margin-bottom:5px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ngày sinh' : 'Date of Birth') ?></label>
                    <input type="text" id="student_dob" name="dob" value="<?= htmlspecialchars($student['has_pending_changes'] ? $student['pending_dob'] : $student['dob']) ?>" class="win-input" placeholder="DD/MM/YYYY">
                </div>
            </div>

            <h4 style="margin: 25px 0 12px 0; color: var(--accent-color); border-bottom: 1px solid var(--border-color); padding-bottom: 6px; font-size: 14px;">
                <i class="fas fa-share-nodes" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Liên kết mạng xã hội' : 'Social Links') ?>
            </h4>
            
            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 15px;">
                <div>
                    <label for="facebook_url" style="font-weight:bold; font-size:13px; color:var(--text-muted); display:flex; align-items:center; gap:8px; margin-bottom:5px;">
                        <i class="fab fa-facebook" style="color: #1877F2; font-size: 16px;" aria-hidden="true"></i> Facebook:
                    </label>
                    <input type="url" id="facebook_url" name="facebook_url" value="<?= htmlspecialchars($student['facebook_url'] ?? '') ?>" class="win-input" placeholder="https://facebook.com/yourprofile" pattern="https?://.*" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập URL hợp lệ' : 'Enter valid URL') ?>">
                </div>
                <div>
                    <label for="tiktok_url" style="font-weight:bold; font-size:13px; color:var(--text-muted); display:flex; align-items:center; gap:8px; margin-bottom:5px;">
                        <i class="fab fa-tiktok" style="color: var(--text-main); font-size: 16px;" aria-hidden="true"></i> TikTok:
                    </label>
                    <input type="url" id="tiktok_url" name="tiktok_url" value="<?= htmlspecialchars($student['tiktok_url'] ?? '') ?>" class="win-input" placeholder="https://tiktok.com/@yourprofile" pattern="https?://.*" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập URL hợp lệ' : 'Enter valid URL') ?>">
                </div>
                <div>
                    <label for="instagram_url" style="font-weight:bold; font-size:13px; color:var(--text-muted); display:flex; align-items:center; gap:8px; margin-bottom:5px;">
                        <i class="fab fa-instagram" style="color: #E1306C; font-size: 16px;" aria-hidden="true"></i> Instagram:
                    </label>
                    <input type="url" id="instagram_url" name="instagram_url" value="<?= htmlspecialchars($student['instagram_url'] ?? '') ?>" class="win-input" placeholder="https://instagram.com/yourprofile" pattern="https?://.*" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập URL hợp lệ' : 'Enter valid URL') ?>">
                </div>
                <div>
                    <label for="youtube_url" style="font-weight:bold; font-size:13px; color:var(--text-muted); display:flex; align-items:center; gap:8px; margin-bottom:5px;">
                        <i class="fab fa-youtube" style="color: #FF0000; font-size: 16px;" aria-hidden="true"></i> YouTube:
                    </label>
                    <input type="url" id="youtube_url" name="youtube_url" value="<?= htmlspecialchars($student['youtube_url'] ?? '') ?>" class="win-input" placeholder="https://youtube.com/c/yourchannel" pattern="https?://.*" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập URL hợp lệ' : 'Enter valid URL') ?>">
                </div>
                <div>
                    <label for="zalo_url" style="font-weight:bold; font-size:13px; color:var(--text-muted); display:flex; align-items:center; gap:8px; margin-bottom:5px;">
                        <i class="fas fa-comment-dots" style="color: #0068FF; font-size: 16px;" aria-hidden="true"></i> Zalo (Link Zalo.me):
                    </label>
                    <input type="url" id="zalo_url" name="zalo_url" value="<?= htmlspecialchars($student['zalo_url'] ?? '') ?>" class="win-input" placeholder="https://zalo.me/username_or_phone" pattern="https?://.*" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập URL hợp lệ' : 'Enter valid URL') ?>">
                </div>
                <div>
                    <label for="github_url" style="font-weight:bold; font-size:13px; color:var(--text-muted); display:flex; align-items:center; gap:8px; margin-bottom:5px;">
                        <i class="fab fa-github" style="color: #24292e; font-size: 16px;" aria-hidden="true"></i> GitHub:
                    </label>
                    <input type="url" id="github_url" name="github_url" value="<?= htmlspecialchars($student['github_url'] ?? '') ?>" class="win-input" placeholder="https://github.com/yourusername" pattern="https?://.*" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập URL hợp lệ' : 'Enter valid URL') ?>">
                </div>
                <div>
                    <label for="threads_url" style="font-weight:bold; font-size:13px; color:var(--text-muted); display:flex; align-items:center; gap:8px; margin-bottom:5px;">
                        <i class="fab fa-at" style="color: var(--text-main); font-size: 16px;" aria-hidden="true"></i> Threads:
                    </label>
                    <input type="url" id="threads_url" name="threads_url" value="<?= htmlspecialchars($student['threads_url'] ?? '') ?>" class="win-input" placeholder="https://threads.net/@yourusername" pattern="https?://.*" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập URL hợp lệ' : 'Enter valid URL') ?>">
                </div>
            </div>
        <?php else: ?>
            <div style="margin-bottom: 15px;">
                <label for="full_name" style="font-weight:bold; font-size:13px; color:var(--text-muted); display:block; margin-bottom:5px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tên hiển thị' : 'Display Name') ?></label>
                <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" class="win-input">
            </div>
        <?php endif; ?>

        <button type="submit" class="win-btn" style="width:100%; margin-top:10px;">
            <i class="fas fa-save" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cập nhật hồ sơ' : 'Update Profile') ?>
        </button>
    </form>
</div>

<div id="cropModal"><div class="crop-container"><h3 style="text-align:center; margin:0;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cắt ảnh' : 'Crop Image') ?></h3><div class="img-container"><img id="imageToCrop" src=""></div><div style="display:flex; gap:10px; margin-top:15px;"><button type="button" class="win-btn win-btn-secondary" onclick="closeCrop()" style="flex:1;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hủy' : 'Dismiss') ?></button><button type="button" class="win-btn" onclick="saveCrop()" style="flex:1;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lưu thay đổi' : 'Save Changes') ?></button></div></div></div>

<script>
    window.toggleDevicesCollapse = function() {
        var container = document.getElementById('devices-list-container');
        var chevron = document.getElementById('devices-chevron');
        if (container.style.display === 'none') {
            container.style.display = 'block';
            chevron.style.transform = 'rotate(180deg)';
        } else {
            container.style.display = 'none';
            chevron.style.transform = 'rotate(0deg)';
        }
    };

    window.pageDestroy = function() {
        if(window.cropper) { window.cropper.destroy(); window.cropper = null; }
    };

    window.pageInit = function() {
        window.vapidKey = "<?= $vapid_key ?? '' ?>";
        window.cropper = null;
        var fileInp = document.getElementById('file-avatar');
        if(fileInp) {
            fileInp.onchange = e => { if(e.target.files[0]){ var r=new FileReader(); r.onload=ev=>{ document.getElementById('imageToCrop').src=ev.target.result; document.getElementById('cropModal').style.display='flex'; if(window.cropper)window.cropper.destroy(); window.cropper=new Cropper(document.getElementById('imageToCrop'),{aspectRatio:1,viewMode:1}); }; r.readAsDataURL(e.target.files[0]); }};
        }
    };

    window.urlBase64ToUint8Array = function(base64String) { var padding = '='.repeat((4 - base64String.length % 4) % 4); var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/'); var rawData = window.atob(base64); var outputArray = new Uint8Array(rawData.length); for (var i = 0; i < rawData.length; ++i) { outputArray[i] = rawData.charCodeAt(i); } return outputArray; };
    window.registerCurrentDevice = async function() {
        if(!('serviceWorker' in navigator) || !('PushManager' in window)) return alert('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trình duyệt không hỗ trợ Web Push Notification' : 'Browser does not support Web Push Notifications') ?>');
        try {
            var perm = window.Notification ? Notification.permission : 'default';
            if (perm !== 'granted') perm = await Notification.requestPermission();
            if (perm === 'denied') return alert("Quyền nhận thông báo đã bị TỪ CHỐI. Vui lòng bấm vào biểu tượng 🔒 ở thanh địa chỉ web để Cho phép thông báo!");
            if (perm !== 'granted') return;

            var reg = await navigator.serviceWorker.ready; var sub = await reg.pushManager.getSubscription();
            if(!sub) sub = await reg.pushManager.subscribe({userVisibleOnly:true, applicationServerKey:window.urlBase64ToUint8Array(window.vapidKey)});
            var jsonSub = sub.toJSON(); jsonSub.platform = 'web';
            var res = await fetch('api/subscribe.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(jsonSub)});
            var d = await res.json();
            if(d.status==='success') { 
                Toastify({text:'<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '✅ Đã thêm thiết bị!' : '✅ Device added!') ?>', style:{background:"#10b981"}}).showToast(); 
                setTimeout(()=>{if(window.loadPage) window.loadPage(window.location.href, false, {force:true}); else location.reload();},1000); 
            } else { alert(d.msg || "Lỗi Server"); }
        } catch(e) { alert("Lỗi: " + e.message); }
    };
    window.removeDevice = function(sid) {
        const title = '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đăng xuất thiết bị này?' : 'Logout this device?') ?>';
        const msg = '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn có chắc chắn muốn đăng xuất khỏi thiết bị này?' : 'Are you sure you want to logout from this device?') ?>';
        if (window.WinUI && window.WinUI.confirm) {
            window.WinUI.confirm(title, msg, async function() {
                var fd = new FormData(); fd.append('action', 'delete_device'); fd.append('device_id', sid); 
                try { 
                    var res = await fetch('api/profile_api.php', {method:'POST', body:fd}); 
                    var d = await res.json(); 
                    if(d.status==='success') { 
                        document.getElementById('dev-'+sid).remove(); 
                        Toastify({text:"✅ "+d.msg, style:{background:"#ef4444"}}).showToast(); 
                    } else {
                        alert(d.msg); 
                    }
                } catch(e) { 
                    alert('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi kết nối server' : 'Server connection error') ?>'); 
                }
            });
        } else {
            if (confirm(msg)) {
                var fd = new FormData(); fd.append('action', 'delete_device'); fd.append('device_id', sid);
                fetch('api/profile_api.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{ if(d.status==='success') { document.getElementById('dev-'+sid).remove(); Toastify({text:"✅ "+d.msg, style:{background:"#ef4444"}}).showToast(); } });
            }
        }
    };
    window.testPush = async function(sid) { var fd = new FormData(); fd.append('action', 'test_push'); fd.append('device_id', sid); fetch('api/profile_api.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{ if(d.status==='success') Toastify({text:'<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '🚀 Đã gửi test!' : '🚀 Test sent!') ?>', style:{background:"#3b82f6"}}).showToast(); else alert(d.msg); }); };
    window.closeCrop = function() { document.getElementById('cropModal').style.display='none'; };
    window.saveCrop = function() { 
        if (!window.cropper) return;
        window.cropper.getCroppedCanvas({width:300,height:300}).toBlob(async blob => { 
            var submitBtn = document.querySelector('#profileForm button[type="submit"]');
            var oldBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) { submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i>'; submitBtn.disabled = true; }
            
            var fd = new FormData();
            fd.append('image', blob, 'avatar.jpg');
            
            try {
                var r = await fetch('api/profile_api.php', { method: 'POST', body: fd });
                var d = await r.json();
                if (d.status === 'success') {
                    Toastify({ text: "✅ " + d.msg, style: { background: "#10b981" } }).showToast();
                    if (d.new_avatar_url) {
                        var newSrc = d.new_avatar_url + '?t=' + Date.now();
                        var dispAvt = document.getElementById('displayAvatar');
                        if (dispAvt) dispAvt.src = newSrc;
                        var headerAvt = document.querySelector('.header-avatar');
                        if (headerAvt) headerAvt.src = newSrc;
                    }
                } else {
                    alert(d.msg || "Không thể lưu file ảnh!");
                }
            } catch (err) {
                alert('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi xảy ra khi tải ảnh lên.' : 'Error occurred while uploading image.') ?>');
            } finally {
                if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = oldBtnHtml; }
                window.closeCrop();
            }
        }, 'image/jpeg', 0.9);
    };
    window.submitProfileForm = async function(e) {
        e.preventDefault(); var btn=e.target.querySelector('button[type="submit"]'); var old=btn.innerHTML; btn.innerHTML='<i class="fas fa-spinner fa-spin" aria-hidden="true"></i>'; btn.disabled=true;
        try { var r = await fetch('api/profile_api.php', {method:'POST', body:new FormData(e.target)}); var d = await r.json(); if(d.status==='success') { Toastify({text:"✅ "+d.msg, style:{background:"#10b981"}}).showToast(); if(d.new_avatar_url) { var newSrc = d.new_avatar_url+'?t='+Date.now(); document.getElementById('displayAvatar').src = newSrc; var headerAvt = document.querySelector('.header-avatar'); if(headerAvt) headerAvt.src = newSrc; } } else alert(d.msg); } catch(err) { alert('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi xảy ra.' : 'An error occurred.') ?>'); } btn.disabled=false; btn.innerHTML=old;
    };
    window.deleteAvatar = function() {
        const title = '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xóa ảnh đại diện?' : 'Delete avatar?') ?>';
        const msg = '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn có chắc chắn muốn xóa ảnh đại diện của mình?' : 'Are you sure you want to delete your avatar?') ?>';
        if (window.WinUI && window.WinUI.confirm) {
            window.WinUI.confirm(title, msg, function() {
                document.getElementById('deleteImageInput').value = "1"; 
                document.getElementById('profileForm').dispatchEvent(new Event('submit'));
            });
        } else {
            if (confirm(msg)) {
                document.getElementById('deleteImageInput').value = "1"; 
                document.getElementById('profileForm').dispatchEvent(new Event('submit'));
            }
        }
    };

    // --- XỬ LÝ 2FA MODALS & JAVASCRIPT ---
    window.openSetup2FAModal = async function() {
        try {
            var fd = new FormData(); fd.append('action', 'get_2fa_setup');
            var r = await fetch('api/profile_api.php', { method: 'POST', body: fd });
            var d = await r.json();
            if (d.status === 'success') {
                document.getElementById('qr2FAImg').src = d.qr_code;
                document.getElementById('secret2FAText').innerText = d.secret;
                document.getElementById('otp2FACode').value = '';
                document.getElementById('modal2FASetup').style.display = 'flex';
            } else {
                alert(d.msg || 'Không thể khởi tạo 2FA!');
            }
        } catch(e) {
            alert('Lỗi kết nối Server!');
        }
    };

    window.closeSetup2FAModal = function() {
        document.getElementById('modal2FASetup').style.display = 'none';
    };

    window.submitEnable2FA = async function() {
        var code = document.getElementById('otp2FACode').value.trim();
        if (code.length !== 6) {
            alert('Vui lòng nhập đủ 6 chữ số mã xác thực Authenticator!');
            return;
        }
        var btn = document.getElementById('btnSubmitEnable2FA');
        btn.disabled = true;
        try {
            var fd = new FormData();
            fd.append('action', 'enable_2fa');
            fd.append('code', code);
            var r = await fetch('api/profile_api.php', { method: 'POST', body: fd });
            var d = await r.json();
            if (d.status === 'success') {
                Toastify({ text: "✅ " + d.msg, style: { background: "#10b981" } }).showToast();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                alert(d.msg);
            }
        } catch(e) {
            alert('Lỗi gửi dữ liệu!');
        } finally {
            btn.disabled = false;
        }
    };

    window.openDisable2FAModal = function() {
        document.getElementById('disable2FACode').value = '';
        document.getElementById('modal2FADisable').style.display = 'flex';
    };

    window.closeDisable2FAModal = function() {
        document.getElementById('modal2FADisable').style.display = 'none';
    };

    window.submitDisable2FA = async function() {
        var code = document.getElementById('disable2FACode').value.trim();
        if (!code) {
            alert('Vui lòng nhập mã OTP 6 số hoặc Mật khẩu hiện tại để xác nhận tắt 2FA!');
            return;
        }
        var btn = document.getElementById('btnSubmitDisable2FA');
        btn.disabled = true;
        try {
            var fd = new FormData();
            fd.append('action', 'disable_2fa');
            if (code.length === 6 && /^\d+$/.test(code)) {
                fd.append('code', code);
            } else {
                fd.append('password', code);
            }
            var r = await fetch('api/profile_api.php', { method: 'POST', body: fd });
            var d = await r.json();
            if (d.status === 'success') {
                Toastify({ text: "✅ " + d.msg, style: { background: "#ef4444" } }).showToast();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                alert(d.msg);
            }
        } catch(e) {
            alert('Lỗi gửi dữ liệu!');
        } finally {
            btn.disabled = false;
        }
    };
</script>

<!-- MODAL CẤU HÌNH BẬT 2FA -->
<div id="modal2FASetup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 99999; justify-content: center; align-items: center; padding: 15px; box-sizing: border-box; backdrop-filter: blur(4px);">
    <div style="background: var(--bg-card); max-width: 480px; width: 100%; border-radius: 12px; border: 1px solid var(--border-color); overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.4); animation: modalFadeIn 0.2s ease;">
        <div style="background: #10b981; color: white; padding: 15px; text-align: center; font-weight: 700; font-size: 16px;">
            <i class="fas fa-qrcode"></i> BẬT XÁC THỰC 2 YẾU TỐ (2FA)
        </div>
        <div style="padding: 20px; color: var(--text-main); font-size: 14px; text-align: center;">
            <p style="margin-top:0; color: var(--text-muted);">
                Quét mã QR dưới đây bằng ứng dụng <b>Google Authenticator</b>, <b>Authy</b> hoặc <b>Microsoft Authenticator</b>:
            </p>
            <div style="background: white; padding: 10px; border-radius: 10px; display: inline-block; border: 1px solid var(--border-color); margin-bottom: 15px;">
                <img id="qr2FAImg" src="" alt="2FA QR Code" style="width: 180px; height: 180px; display: block;">
            </div>
            <div style="background: var(--bg-hover); padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); font-size: 12px; font-family: monospace; margin-bottom: 15px;">
                <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Secret Key (Nhập tay):' : 'Secret Key (Manual entry):') ?> <strong id="secret2FAText" style="color: var(--primary-color); font-size: 14px; letter-spacing: 1px;">-</strong>
            </div>
            <div style="text-align: left; margin-bottom: 15px;">
                <label style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 5px;">
                    <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập mã 6 chữ số từ ứng dụng Authenticator:' : 'Enter 6-digit code from Authenticator app:') ?>
                </label>
                <input type="text" id="otp2FACode" class="win-input" placeholder="000000" maxlength="6" style="text-align: center; font-size: 20px; letter-spacing: 6px; font-weight: bold; height: 45px;">
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="closeSetup2FAModal()" class="win-btn" style="padding: 8px 16px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hủy' : 'Cancel') ?></button>
                <button type="button" id="btnSubmitEnable2FA" onclick="submitEnable2FA()" class="win-btn" style="background: #10b981; color: white; padding: 8px 20px; font-weight: 600;">Kích hoạt 2FA</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TẮT 2FA -->
<div id="modal2FADisable" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 99999; justify-content: center; align-items: center; padding: 15px; box-sizing: border-box; backdrop-filter: blur(4px);">
    <div style="background: var(--bg-card); max-width: 400px; width: 100%; border-radius: 12px; border: 1px solid var(--border-color); overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.4); animation: modalFadeIn 0.2s ease;">
        <div style="background: #ef4444; color: white; padding: 15px; text-align: center; font-weight: 700; font-size: 16px;">
            <i class="fas fa-exclamation-triangle"></i> XÁC NHẬN TẮT 2FA
        </div>
        <div style="padding: 20px; color: var(--text-main); font-size: 14px;">
            <p style="margin-top:0; color: var(--text-muted);">
                <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập mã OTP 6 số từ ứng dụng Authenticator hoặc Mật khẩu hiện tại để tắt 2FA:' : 'Enter 6-digit OTP code from Authenticator app or your current Password to disable 2FA:') ?>
            </p>
            <div style="margin-bottom: 20px;">
                <input type="password" id="disable2FACode" class="win-input" placeholder="Mã OTP (6 số) hoặc Mật khẩu" style="text-align: center; font-size: 15px; height: 42px;">
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="closeDisable2FAModal()" class="win-btn" style="padding: 8px 16px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hủy' : 'Cancel') ?></button>
                <button type="button" id="btnSubmitDisable2FA" onclick="submitDisable2FA()" class="win-btn" style="background: #ef4444; color: white; padding: 8px 20px; font-weight: 600;">Tắt 2FA</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL LIÊN KẾT EMAIL -->
<div id="modalLinkEmail" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 99999; justify-content: center; align-items: center; padding: 15px; box-sizing: border-box; backdrop-filter: blur(4px);">
    <div style="background: var(--bg-card); max-width: 440px; width: 100%; border-radius: 12px; border: 1px solid var(--border-color); overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.4); animation: modalFadeIn 0.2s ease;">
        <div style="background: #005fba; color: white; padding: 15px; text-align: center; font-weight: 700; font-size: 16px;">
            <i class="fas fa-envelope"></i> LIÊN KẾT EMAIL KHÔI PHỤC MẬT KHẨU
        </div>
        <div style="padding: 20px; color: var(--text-main); font-size: 14px;">
            <div id="emailStep1">
                <p style="margin-top:0; color: var(--text-muted); font-size: 13px;">
                    <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập địa chỉ Email cá nhân của bạn để nhận mã xác nhận OTP:' : 'Enter your personal Email address to receive OTP confirmation code:') ?>
                </p>
                <div style="margin-bottom: 15px;">
                    <label style="font-weight: 600; font-size: 12.5px; display: block; margin-bottom: 5px;">Địa chỉ Email:</label>
                    <input type="email" id="inputLinkEmail" class="win-input" placeholder="example@gmail.com" value="<?= htmlspecialchars($user_email) ?>" style="height: 42px;">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeLinkEmailModal()" class="win-btn" style="padding: 8px 16px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hủy' : 'Cancel') ?></button>
                    <button type="button" id="btnSendEmailOTP" onclick="sendEmailOTP()" class="win-btn" style="background: #005fba; color: white; padding: 8px 20px; font-weight: 600;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Gửi mã OTP' : 'Send OTP code') ?></button>
                </div>
            </div>
            
            <div id="emailStep2" style="display: none;">
                <p style="margin-top:0; color: #10b981; font-size: 13px; font-weight: 600;" id="otpSentNotice">
                    ✅ Đã gửi mã OTP xác nhận đến email!
                </p>
                <div style="margin-bottom: 15px;">
                    <label style="font-weight: 600; font-size: 12.5px; display: block; margin-bottom: 5px;">Mã xác nhận OTP (6 chữ số):</label>
                    <input type="text" id="inputEmailOTP" class="win-input" placeholder="000000" maxlength="6" style="text-align: center; font-size: 20px; letter-spacing: 6px; font-weight: bold; height: 45px;">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; align-items: center;">
                    <button type="button" onclick="sendEmailOTP()" class="win-btn" style="padding: 8px 12px; font-size: 12px; background: var(--bg-hover);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Gửi lại mã' : 'Resend code') ?></button>
                    <button type="button" id="btnVerifyEmailOTP" onclick="verifyEmailOTP()" class="win-btn" style="background: #10b981; color: white; padding: 8px 20px; font-weight: 600;">Xác nhận liên kết</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openLinkEmailModal() {
        document.getElementById('modalLinkEmail').style.display = 'flex';
        document.getElementById('emailStep1').style.display = 'block';
        document.getElementById('emailStep2').style.display = 'none';
    }
    function closeLinkEmailModal() {
        document.getElementById('modalLinkEmail').style.display = 'none';
    }

    async function sendEmailOTP() {
        var email = document.getElementById('inputLinkEmail').value.trim();
        var btn = document.getElementById('btnSendEmailOTP');
        if (!email) { alert('Vui lòng nhập địa chỉ Email!'); return; }
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';

        try {
            var fd = new FormData();
            fd.append('action', 'send_email_otp');
            fd.append('email', email);

            var res = await fetch('api/profile_api.php', { method: 'POST', body: fd });
            var data = await res.json();

            if (data.status === 'success') {
                Toastify({ text: "✅ " + data.msg, style: { background: "#005fba" } }).showToast();
                document.getElementById('emailStep1').style.display = 'none';
                document.getElementById('emailStep2').style.display = 'block';
                document.getElementById('otpSentNotice').innerText = "✅ " + data.msg;
            } else {
                alert(data.msg);
            }
        } catch(e) {
            alert('Lỗi kết nối máy chủ!');
        } finally {
            btn.disabled = false;
            btn.innerHTML = (window.currentLang === 'en' ? 'Send OTP code' : 'Gửi mã OTP');
        }
    }

    async function verifyEmailOTP() {
        var otp = document.getElementById('inputEmailOTP').value.trim();
        var btn = document.getElementById('btnVerifyEmailOTP');
        if (!otp || otp.length !== 6) { alert('Vui lòng nhập đủ 6 số OTP!'); return; }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';

        try {
            var fd = new FormData();
            fd.append('action', 'verify_email_otp');
            fd.append('otp', otp);

            var res = await fetch('api/profile_api.php', { method: 'POST', body: fd });
            var data = await res.json();

            if (data.status === 'success') {
                Toastify({ text: "🎉 " + data.msg, style: { background: "#10b981" } }).showToast();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                alert(data.msg);
            }
        } catch(e) {
            alert('Lỗi kết nối máy chủ!');
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Xác nhận liên kết';
        }
    }

    async function unlinkEmailAccount() {
        if (!confirm('Bạn có chắc chắn muốn hủy liên kết email? Sau khi hủy, bạn sẽ không thể tự khôi phục mật khẩu nếu lỡ quên.')) return;
        try {
            var fd = new FormData();
            fd.append('action', 'unlink_email');
            var res = await fetch('api/profile_api.php', { method: 'POST', body: fd });
            var data = await res.json();

            if (data.status === 'success') {
                Toastify({ text: "✅ " + data.msg, style: { background: "#ef4444" } }).showToast();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                alert(data.msg);
            }
        } catch(e) {
            alert('Lỗi kết nối!');
        }
    }
</script>
<?php include 'includes/footer.php'; ?>

<style>
/* Fix light-color boxes in dark mode only - NOT win-card (it already uses --bg-card=#000000) */
[data-theme="dark"] [style*="#fff7ed"],
[data-theme="dark"] [style*="#fef9c3"],
[data-theme="dark"] [style*="#e2e8f0"],
[data-theme="dark"] [style*="#f1f5f9"],
[data-theme="dark"] [style*="#f8fafc"],
[data-theme="dark"] [style*="#fef3c7"],
[data-theme="dark"] [style*="#eff6ff"],
[data-theme="dark"] [style*="#f0fdf4"],
[data-theme="dark"] .note-box,
[data-theme="dark"] .info-box {
    background: #111111 !important;
    color: var(--text-main) !important;
    border-color: var(--border-color) !important;
}
</style>
