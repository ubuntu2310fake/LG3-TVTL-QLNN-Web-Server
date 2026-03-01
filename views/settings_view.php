<?php 
// settings_view.php
require_once 'includes/header.php'; 

// ==========================================================
// LOGIC XỬ LÝ TÊN TRƯỜNG THÔNG MINH
// ==========================================================
$raw_name = trim($SYS_LICENSE['school_name'] ?? 'Chưa xác định');
$lower_name = mb_strtolower($raw_name, 'UTF-8');

$has_prefix = (
    strpos($lower_name, 'trường') !== false || 
    strpos($lower_name, 'truong') !== false || 
    strpos($lower_name, 'thpt') !== false || 
    strpos($lower_name, 'thcs') !== false ||
    strpos($lower_name, 'tiểu học') !== false ||
    strpos($lower_name, 'mầm non') !== false ||
    strpos($lower_name, 'cao đẳng') !== false ||
    strpos($lower_name, 'đại học') !== false
);

$school_display_name = $has_prefix ? $raw_name : "Trường " . $raw_name;
?>

<style>
    /* CSS CƠ BẢN TỪ TRẠM CHỈ HUY */
    .mac-card { background: var(--bg-card, #ffffff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 16px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); margin-bottom: 24px; transition: background 0.3s, border-color 0.3s; }
    .mac-card h3 { margin: 0 0 20px 0; font-weight: 700; border-bottom: 1px solid var(--border-color, #f1f5f9); padding-bottom: 12px; color: var(--text-main, #0f172a); font-size: 20px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    
    .mac-input { width: 100%; padding: 12px 16px; background: var(--bg-hover, #f8fafc); border: 1px solid var(--border-color, #cbd5e1); border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; transition: 0.2s; box-sizing: border-box; color: var(--text-main, #1e293b); font-weight: 500;}
    .mac-input:focus { border-color: var(--primary-color, #3b82f6); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); background: var(--bg-card, #ffffff); }
    
    .mac-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 20px; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease; border: none; font-family: 'Inter', sans-serif; text-decoration: none;}
    .mac-btn:active { transform: scale(0.97); }
    .mac-btn:disabled { opacity: 0.7; cursor: not-allowed; }
    .mac-btn-primary { background: var(--primary-color, #2563eb); color: #fff; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2); }
    .mac-btn-dark { background: var(--text-main, #0f172a); color: var(--bg-card, #fff); }
    .mac-btn-gray { background: var(--bg-hover, #f1f5f9); color: var(--text-main, #334155); border: 1px solid var(--border-color, #cbd5e1); }
    
    .status-box { background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); border-left: 5px solid var(--primary-color, #3b82f6); padding: 20px; border-radius: 12px; margin-bottom: 25px; transition: background 0.3s; }
    
    /* GIAO DIỆN TOGGLE SWITCH KIỂU IOS/MAC */
    .mac-switch-wrap { display: inline-flex; align-items: center; cursor: pointer; gap: 15px; margin-bottom: 20px; user-select: none; }
    .mac-switch { position: relative; width: 50px; height: 28px; background: #cbd5e1; border-radius: 30px; transition: 0.3s; flex-shrink: 0; }
    .mac-switch::after { content: ''; position: absolute; top: 3px; left: 3px; width: 22px; height: 22px; background: #fff; border-radius: 50%; transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
    input:checked + .mac-switch { background: #10b981; }
    input:checked + .mac-switch::after { transform: translateX(22px); }
    .mac-switch-label { font-weight: 600; color: var(--text-main, #334155); font-size: 15px; }

    /* CẤU TRÚC THÔNG BÁO LỖI QUYỀN TRUY CẬP */
    .access-denied-card { background: var(--bg-card, #fff); border-radius: 16px; padding: 40px; text-align: center; border: 1px solid rgba(239, 68, 68, 0.3); box-shadow: 0 10px 30px rgba(239, 68, 68, 0.1); max-width: 500px; margin: 100px auto; }
    .ad-icon { font-size: 48px; color: #ef4444; margin-bottom: 20px; }
    .ad-title { font-size: 22px; font-weight: 800; color: var(--text-main, #1e293b); margin-bottom: 10px; }
    .ad-desc { color: var(--text-muted, #64748b); font-size: 14px; margin-bottom: 25px; line-height: 1.6; }

    /* UI OTA LOG & PROGRESS */
    .ota-log-console { background: #1e1e1e; color: #10b981; font-family: 'Consolas', monospace; font-size: 12px; padding: 15px; border-radius: 8px; height: 200px; overflow-y: auto; line-height: 1.5; margin-top: 15px; border: 1px solid #000; box-shadow: inset 0 2px 5px rgba(0,0,0,0.5); }
    .progress-bar-container { width: 100%; background: var(--border-color, #e2e8f0); border-radius: 20px; height: 14px; overflow: hidden; margin: 15px 0; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); }
    .progress-bar-fill { width: 0%; height: 100%; background: linear-gradient(90deg, #3b82f6, #10b981); transition: width 0.4s ease; }

    /* MODAL XUNG ĐỘT */
    .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.7); z-index:9999; align-items:center; justify-content:center; backdrop-filter: blur(5px); }
    .conflict-modal { background:var(--bg-card, #fff); width: 90%; max-width: 550px; padding: 35px; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); border: 1px solid var(--border-color, #e2e8f0); }
    #conflictFileList { background: var(--bg-hover, #f8fafc); padding: 15px; border-radius: 10px; max-height: 180px; overflow-y: auto; font-size: 13px; font-family: 'Consolas', monospace; margin-bottom: 25px; border: 1px solid var(--border-color, #e2e8f0); color: var(--text-main, #475569); line-height: 1.6; }

    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main, #1e293b); font-size: 13px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

    /* GHI ĐÈ CSS CHO CHẾ ĐỘ TỐI (DARK MODE) */
    [data-theme="dark"] .mac-switch { background: #475569; }
    [data-theme="dark"] .mac-btn-gray:hover { background: #334155; border-color: #475569; }
    [data-theme="dark"] #otaProgressWrapper { background: rgba(0,0,0,0.2) !important; border-color: var(--border-color) !important; }
    [data-theme="dark"] .license-box { background: rgba(37,99,235,0.1) !important; border-color: rgba(37,99,235,0.3) !important;}

    /* CỔNG KIỂM TRA DROPDOWN */
    .custom-select-container { position: relative; width: 100%; margin-bottom: 15px; user-select: none; max-width: 100%; }
    .select-selected { background-color: var(--bg-hover, #f8fafc); border: 1px solid var(--border-color, #cbd5e1); border-radius: 10px; padding: 0 16px; display: flex; align-items: center; justify-content: space-between; font-size: 14px; height: 45px; box-sizing: border-box; color: var(--text-main, #1e293b); font-weight: 500; transition: 0.2s; cursor: pointer; font-family: 'Inter', sans-serif; }
    .select-selected:active, .select-selected.active { border-color: var(--primary-color, #3b82f6); box-shadow: 0 0 0 3px rgba(59,130,246,0.15); background-color: var(--bg-card, #ffffff); }
    .select-arrow { width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid var(--text-muted, #64748b); transition: 0.2s; flex-shrink: 0; }
    .select-selected.active .select-arrow { transform: rotate(180deg); }
    .select-items { position: absolute; top: calc(100% + 5px); left: 0; right: 0; z-index: 1000; background: var(--bg-card, #ffffff); border: 1px solid var(--border-color, #cbd5e1); border-radius: 10px; max-height: 250px; overflow-y: auto; display: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1); animation: fadeIn 0.2s ease; }
    .select-items div { padding: 12px 16px; cursor: pointer; border-bottom: 1px solid var(--border-color, #f1f5f9); font-size: 14px; color: var(--text-main, #1e293b); transition: 0.2s; }
    .select-items div:last-child { border-bottom: none; }
    .select-items div:hover { background: var(--bg-hover, #f1f5f9); color: var(--primary-color, #2563eb); font-weight: 600; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

    /* CSS FIX RESPONSIVE ĐIỆN THOẠI TRÀN MÀN HÌNH */
    @media (max-width: 768px) {
        .grid-2 { grid-template-columns: 1fr; gap: 15px; }
        .mac-card { padding: 20px; margin-bottom: 15px; }
        .flex-wrap-mobile { flex-direction: column !important; align-items: flex-start !important; gap: 15px !important; }
        .border-mobile-fix { border-left: none !important; border-top: 1px dashed var(--border-color, #cbd5e1); padding-left: 0 !important; padding-top: 15px; width: 100%; }
        .mac-btn-mobile-full { width: 100%; justify-content: center; margin-top: 10px; }
        .status-box { padding: 15px; }
        .conflict-modal { width: 95%; padding: 20px; }
    }
</style>

<div class="main-content-inner">
    <?php if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN'): ?>
        <div class="access-denied-card">
            <div class="ad-icon"><i class="fas fa-shield-lock"></i></div>
            <div class="ad-title">KHÔNG CÓ QUYỀN TRUY CẬP</div>
            <div class="ad-desc">Khu vực này chứa các thiết lập quan trọng về hệ thống bảo mật và mã nguồn. Vui lòng liên hệ quản trị viên cấp cao để biết thêm chi tiết.</div>
            <a href="/" class="mac-btn mac-btn-gray" style="width: 100%; justify-content: center;"><i class="fas fa-arrow-left"></i> Quay lại trang chủ</a>
        </div>
    <?php else: ?>
        <div style="max-width: 1100px; margin: 0 auto;">
            
            <div class="mac-card license-box" style="background: linear-gradient(135deg, rgba(37,99,235,0.05), rgba(16,185,129,0.05)); border: 1px solid rgba(37,99,235,0.2);">
                <h3 style="color: var(--primary-color, #2563eb); border-bottom: none; margin-bottom: 10px;">
                    <i class="fas fa-medal"></i> Giấy Phép Bản Quyền
                </h3>
                
                <div class="flex-wrap-mobile" style="display: flex; flex-wrap: wrap; gap: 30px; align-items: center; margin-top: 20px;">
                    <div style="flex: 1; min-width: 250px; width: 100%;">
                        <div style="color: var(--text-muted, #64748b); font-size: 13px; font-weight: 700; text-transform: uppercase; margin-bottom: 5px;">Cấp phép cho đơn vị:</div>
                        <strong style="font-size: 26px; color: var(--text-main, #0f172a); font-weight: 900; word-break: break-word;"><?= htmlspecialchars($school_display_name) ?></strong>
                    </div>
                    
                    <div class="border-mobile-fix" style="flex: 1; min-width: 200px; border-left: 2px dashed var(--border-color, #cbd5e1); padding-left: 30px;">
                        <div style="color: var(--text-muted, #64748b); font-size: 13px; font-weight: 700; margin-bottom: 5px;"><i class="fas fa-box-open"></i> Gói đăng ký:</div>
                        <div style="font-size: 18px; color: var(--primary-color, #2563eb); font-weight: 700;">
                            <?= htmlspecialchars($SYS_LICENSE['pkg_name']) ?>
                        </div>
                    </div>

                    <div class="border-mobile-fix" style="flex: 1; min-width: 200px; border-left: 2px dashed var(--border-color, #cbd5e1); padding-left: 30px;">
                        <div style="color: var(--text-muted, #64748b); font-size: 13px; font-weight: 700; margin-bottom: 5px;"><i class="fas fa-calendar-times"></i> Hạn sử dụng (Còn <?= $SYS_LICENSE['days_left'] ?> ngày):</div>
                        <div style="font-size: 18px; color: #10b981; font-weight: 700;">
                            <?= htmlspecialchars($SYS_LICENSE['expiry_date']) ?>
                        </div>
                    </div>
                </div>
            </div>

            <form id="formFileConfigs">
                <div class="grid-2">
                    <div class="mac-card">
                        <h3><i class="fas fa-code"></i> Hệ thống (setup_variables)</h3>
                        
                        <div style="background: rgba(239, 68, 68, 0.05); border: 1px dashed rgba(239, 68, 68, 0.4); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <div class="form-group">
                                <label style="color: #ef4444;"><i class="fas fa-lock"></i> Tài khoản Master (Ẩn)</label>
                                <input type="text" value="<?= defined('LICENSE_EMAIL') ? LICENSE_EMAIL : '' ?>" class="mac-input" disabled style="background: var(--bg-hover); cursor: not-allowed; opacity: 0.8;">
                            </div>
                            <div class="form-group" style="margin-bottom: 5px;">
                                <label style="color: #ef4444;"><i class="fas fa-key"></i> Key Kích Hoạt (License Key)</label>
                                <input type="text" value="<?= defined('LICENSE_KEY') ? LICENSE_KEY : '' ?>" class="mac-input" disabled style="background: var(--bg-hover); cursor: not-allowed; opacity: 0.8; font-family: monospace; font-weight: bold; letter-spacing: 1px;">
                            </div>
                            <small style="color: #dc2626; font-weight: bold; font-size: 11px; display: block; margin-top: 10px; line-height: 1.4;">
                                <i class="fas fa-radiation"></i> CẢNH BÁO BẢO MẬT: Bắt buộc truy cập vào máy chủ để sửa khóa này. Mọi can thiệp F12 qua trình duyệt sẽ bị Engine từ chối ghi đè và gây hậu quả nghiêm trọng đến việc sử dụng phần mềm, Trân trọng!
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Domain App Python (TVTL_BASE_URL)</label>
                            <input type="text" name="TVTL_BASE_URL" value="<?= defined('TVTL_BASE_URL') ? TVTL_BASE_URL : '' ?>" class="mac-input" required>
                        </div>
                        <div class="form-group">
                            <label>Khóa bảo mật đồng bộ SSO (SSO_SECRET_KEY)</label>
                            <input type="text" name="SSO_SECRET_KEY" value="<?= defined('SSO_SECRET_KEY') ? SSO_SECRET_KEY : '' ?>" class="mac-input" required>
                        </div>
                        <div class="form-group">
                            <label>API nhận Log nội bộ (TVTL_API_URL)</label>
                            <input type="text" name="TVTL_API_URL" value="<?= defined('TVTL_API_URL') ? TVTL_API_URL : '' ?>" class="mac-input" required>
                        </div>

                        <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 20px 0;">
                        <label style="font-weight: 700; color: var(--text-main); margin-bottom: 10px; display: block;"><i class="fas fa-bell"></i> Push Notification (VAPID)</label>
                        <div class="form-group"><label>VAPID Public Key</label><input type="text" name="VAPID_PUBLIC_KEY" value="<?= defined('VAPID_PUBLIC_KEY') ? VAPID_PUBLIC_KEY : '' ?>" class="mac-input" required></div>
                        <div class="form-group"><label>VAPID Private Key</label><input type="text" name="VAPID_PRIVATE_KEY" value="<?= defined('VAPID_PRIVATE_KEY') ? VAPID_PRIVATE_KEY : '' ?>" class="mac-input" required></div>
                        <div class="form-group"><label>VAPID Subject (Mailto)</label><input type="text" name="VAPID_SUBJECT" value="<?= defined('VAPID_SUBJECT') ? VAPID_SUBJECT : '' ?>" class="mac-input" required></div>
                    </div>

                    <div>
                        <div class="mac-card">
                            <h3><i class="fas fa-database"></i> Database (db_config)</h3>
                            <?php global $db_host, $db_name, $db_user, $db_pass, $db_charset; ?>
                            <div class="form-group">
                                <label>Host máy chủ (DB_HOST)</label>
                                <input type="text" name="db_host" value="<?= $db_host ?? '127.0.0.1' ?>" class="mac-input" required>
                            </div>
                            <div class="form-group">
                                <label>Tên cơ sở dữ liệu (DB_NAME)</label>
                                <input type="text" name="db_name" value="<?= $db_name ?? '' ?>" class="mac-input" required>
                            </div>
                            <div class="grid-2" style="gap: 10px;">
                                <div class="form-group">
                                    <label>Tài khoản (DB_USER)</label>
                                    <input type="text" name="db_user" value="<?= $db_user ?? 'root' ?>" class="mac-input" required>
                                </div>
                                <div class="form-group">
                                    <label>Mật khẩu (DB_PASS)</label>
                                    <input type="password" name="db_pass" value="<?= $db_pass ?? '' ?>" class="mac-input">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Bảng mã (DB_CHARSET)</label>
                                <input type="text" name="db_charset" value="<?= $db_charset ?? 'utf8mb4' ?>" class="mac-input" required>
                            </div>
                            
                            <div style="margin-top: 25px;">
                                <button type="submit" id="btnSaveFileConfigs" class="mac-btn mac-btn-primary mac-btn-mobile-full" style="width: 100%; padding: 14px; font-size: 15px;">
                                    <i class="fas fa-save"></i> Áp Dụng Thay Đổi & Ghi Vào File
                                </button>
                                <small style="display: block; text-align: center; color: var(--text-muted); margin-top: 10px;">Hành động này sẽ sửa trực tiếp mã nguồn PHP trên ổ cứng.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="mac-card">
                <h3><i class="fas fa-cloud-download-alt" style="color: var(--primary-color, #2563eb);"></i> Cập Nhật Lõi (LG3 OTA Engine)</h3>
                
                <div class="status-box flex-wrap-mobile" style="display: flex; gap: 40px; align-items: center;">
                    <div style="width: 100%;">
                        <div style="color: var(--text-muted, #64748b); font-size: 12px; font-weight: 700; text-transform: uppercase;">Phiên bản Firmware</div>
                        <strong style="font-size: 24px; color: var(--text-main, #0f172a);">v<?= defined('APP_VERSION') ? APP_VERSION : '1.0.0' ?></strong>
                    </div>
                    
                    <div class="border-mobile-fix" style="border-left: 2px dashed var(--border-color, #cbd5e1); padding-left: 40px;">
                        <div style="color: var(--text-muted, #64748b); font-size: 12px; font-weight: 700; text-transform: uppercase;">Phiên bản Engine</div>
                        <strong style="font-size: 24px; color: #10b981;">v<?= defined('ENGINE_VERSION') ? ENGINE_VERSION : '1.0.0' ?></strong>
                    </div>

                    <div style="width: 100%; display: flex; justify-content: flex-start;">
                        <button type="button" class="mac-btn mac-btn-primary mac-btn-mobile-full" onclick="checkUpdateMaster()">
                            <i class="fas fa-sync-alt"></i> Kiểm tra bản cập nhật
                        </button>
                    </div>
                </div>

                <div id="otaProgressWrapper" style="display: none; margin-bottom: 30px; padding: 25px; background: var(--bg-hover, #f8fafc); border-radius: 12px; border: 1px dashed var(--border-color, #cbd5e1);">
                    <div id="otaProgressText" style="font-weight: 700; color: var(--text-main, #0f172a); margin-bottom: 10px;">Đang khởi tạo... 0%</div>
                    <div class="progress-bar-container">
                        <div id="otaProgressBar" class="progress-bar-fill"></div>
                    </div>
                    <div class="ota-log-console" id="otaLogConsole">Đang kết nối đến Updater Engine...</div>
                </div>

                <form id="formOtaSettings" style="border-top: 1px solid var(--border-color, #f1f5f9); padding-top: 25px;">
                    <div style="display: flex; align-items: flex-start; gap: 15px; margin-bottom: 25px;">
                        <div style="flex: 1;">
                            <label style="font-weight: 700; display: block; margin-bottom: 5px; color: var(--text-main, #1e293b);">Tự động cập nhật ngầm</label>
                            <small style="color: var(--text-muted, #64748b); display: block; line-height: 1.4;">Guard Service sẽ tự động tải các bản vá lỗi bảo mật định kỳ.</small>
                        </div>
                        <label class="mac-switch-wrap" style="margin-bottom: 0;">
                            <input type="checkbox" id="ota_auto_update" name="ota_auto_update" style="display:none;" <?= ($current_ota_auto ?? 1) ? 'checked' : '' ?>>
                            <div class="mac-switch"></div>
                        </label>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <label style="font-weight: 700; display: block; margin-bottom: 10px; color: var(--text-main, #1e293b);">Độ trễ cập nhật (Rollout Delay):</label>
                        
                        <input type="hidden" name="ota_delay_days" id="input_ota_delay" value="<?= $current_ota_delay ?? 0 ?>">
                        <div class="custom-select-container" style="max-width: 300px;">
                            <div class="select-selected" onclick="toggleDropdown(event, this)">
                                <span id="txtSelectedDelay">
                                    <?php 
                                        $d = $current_ota_delay ?? 0;
                                        if ($d == 0) echo 'Ngay lập tức (Real-time)';
                                        elseif ($d == 1) echo 'Trễ 1 ngày (Ổn định)';
                                        else echo 'Trễ 3 ngày';
                                    ?>
                                </span>
                                <div class="select-arrow"></div>
                            </div>
                            <div class="select-items">
                                <div onclick="selectDelayItem(0, 'Ngay lập tức (Real-time)', this)">Ngay lập tức (Real-time)</div>
                                <div onclick="selectDelayItem(1, 'Trễ 1 ngày (Ổn định)', this)">Trễ 1 ngày (Ổn định)</div>
                                <div onclick="selectDelayItem(3, 'Trễ 3 ngày', this)">Trễ 3 ngày</div>
                            </div>
                        </div>

                    </div>

                    <button type="submit" id="btnSaveOtaSettings" class="mac-btn mac-btn-dark mac-btn-mobile-full">
                        <i class="fas fa-save"></i> Lưu chính sách OTA
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<div id="conflictModal" class="modal-overlay">
    <div class="conflict-modal">
        <h3 style="color:#dc2626; margin-top:0; display:flex; align-items:center; gap: 10px;"><i class="fas fa-exclamation-triangle"></i> CẢNH BÁO MÃ NGUỒN</h3>
        <p style="font-size:14px; color:var(--text-muted, #475569); line-height: 1.6;">Phát hiện các file giao diện quan trọng đã bị thay đổi thủ công. Cập nhật có thể làm mất các chỉnh sửa của bạn:</p>
        
        <div id="conflictFileList"></div>

        <div style="display:flex; flex-direction:column; gap:12px;">
            <button class="mac-btn" style="background: #f59e0b; color:#fff; justify-content: flex-start; text-align: left; padding: 15px; width: 100%; height: auto;" onclick="proceedUpdate(true)">
                <div style="display:flex; gap:10px; align-items:flex-start;">
                    <i class="fas fa-shield-alt" style="font-size: 20px; margin-top: 3px;"></i>
                    <div style="line-height: 1.4;">
                        <strong>1. Cập nhật an toàn (BỎ QUA FILE TRÊN)</strong><br>
                        <small style="font-weight:normal; font-size: 12px; opacity: 0.9;">Lõi hệ thống sẽ update, giao diện custom được giữ nguyên.</small>
                    </div>
                </div>
            </button>
            <button class="mac-btn" style="background: #ef4444; color:#fff; justify-content: flex-start; text-align: left; padding: 15px; width: 100%; height: auto;" onclick="proceedUpdate(false)">
                <div style="display:flex; gap:10px; align-items:flex-start;">
                    <i class="fas fa-skull-crossbones" style="font-size: 20px; margin-top: 3px;"></i>
                    <div style="line-height: 1.4;">
                        <strong>2. GHI ĐÈ TẤT CẢ (Nguy hiểm)</strong><br>
                        <small style="font-weight:normal; font-size: 12px; opacity: 0.9;">Sẽ làm mất toàn bộ giao diện custom của bạn.</small>
                    </div>
                </div>
            </button>
            <button class="mac-btn mac-btn-gray" style="padding: 12px; width: 100%;" onclick="document.getElementById('conflictModal').style.display='none'">Hủy quá trình cập nhật</button>
        </div>
    </div>
</div>

<script>
    // =======================================================
    // JS CHO CUSTOM DROPDOWN
    // =======================================================
    function toggleDropdown(e, el) { 
        e.stopPropagation(); 
        closeAllSelects(el); 
        el.nextElementSibling.style.display = el.nextElementSibling.style.display==='block'?'none':'block'; 
        el.classList.toggle('active'); 
    }
    
    function closeAllSelects(except) { 
        document.querySelectorAll('.select-items').forEach(i => { if(i!==except?.nextElementSibling) i.style.display='none'; }); 
        document.querySelectorAll('.select-selected').forEach(e => { if(e!==except) e.classList.remove('active'); }); 
    }
    
    document.addEventListener('click', () => closeAllSelects());

    function selectDelayItem(value, text, itemEl) {
        document.getElementById('txtSelectedDelay').innerText = text;
        document.getElementById('input_ota_delay').value = value;
        itemEl.parentElement.style.display = 'none';
        itemEl.parentElement.previousElementSibling.classList.remove('active');
    }

    // =======================================================
    // 1. AJAX LƯU CONFIG FILE TRỰC TIẾP
    // =======================================================
    const formFileConfigs = document.getElementById('formFileConfigs');
    if(formFileConfigs) {
        formFileConfigs.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveFileConfigs');
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang ghi đè file...';
            btn.disabled = true;

            let fd = new FormData(this);
            fd.append('ajax_action', 'save_file_configs');

            fetch('settings.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                if(data.status === 'success') {
                    Toastify({ text: "✔️ " + data.msg, duration: 3000, gravity: "bottom", position: "center", style: { background: "var(--success-color, #10b981)", borderRadius: "8px" } }).showToast();
                } else {
                    Toastify({ text: "❌ " + data.msg, duration: 4000, gravity: "bottom", position: "center", style: { background: "var(--danger-color, #ef4444)", borderRadius: "8px" } }).showToast();
                }
            }).catch(err => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                Toastify({ text: "❌ Lỗi mạng hoặc server không phản hồi!", duration: 3000, gravity: "bottom", position: "center", style: { background: "var(--danger-color, #ef4444)", borderRadius: "8px" } }).showToast();
            });
        });
    }

    // =======================================================
    // 2. AJAX LƯU CHÍNH SÁCH OTA
    // =======================================================
    const formOtaSettings = document.getElementById('formOtaSettings');
    if(formOtaSettings) {
        formOtaSettings.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveOtaSettings');
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu chính sách...';
            btn.disabled = true;

            let fd = new FormData(this);
            fd.append('ajax_action', 'save_ota_settings');
            
            let cb = document.getElementById('ota_auto_update');
            if(cb && cb.checked) fd.set('ota_auto_update', 'on');

            fetch('settings.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                if(data.status === 'success') {
                    Toastify({ text: "✔️ " + data.msg, duration: 3000, gravity: "bottom", position: "center", style: { background: "var(--success-color, #10b981)", borderRadius: "8px" } }).showToast();
                } else {
                    Toastify({ text: "❌ " + data.msg, duration: 4000, gravity: "bottom", position: "center", style: { background: "var(--danger-color, #ef4444)", borderRadius: "8px" } }).showToast();
                }
            }).catch(err => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                Toastify({ text: "❌ Lỗi kết nối CSDL!", duration: 3000, gravity: "bottom", position: "center", style: { background: "var(--danger-color, #ef4444)", borderRadius: "8px" } }).showToast();
            });
        });
    }

    // =======================================================
    // 3. LOGIC OTA (CẬP NHẬT LÕI)
    // =======================================================
    let g_latestVersion = '';
    let g_conflictedFiles = [];
    let logInterval;

    function checkUpdateMaster() {
        Toastify({ text: "Đang kiểm tra cập nhật...", duration: 2000, gravity: "bottom", position: "center", style: { background: "var(--primary-color, #3b82f6)", borderRadius: "8px" } }).showToast();
        
        fetch('api/api_ota_manager.php?action=check')
            .then(res => res.json())
            .then(data => {
                if(data.status === 'ok') {
                    if(data.update) {
                        g_latestVersion = data.version;
                        if(data.has_conflict) {
                            g_conflictedFiles = data.conflicts.map(c => c.file);
                            let html = data.conflicts.map(c => `
                                <div style="padding: 6px 0; border-bottom: 1px dashed var(--border-color, #cbd5e1);">
                                    <i class="fas fa-file-code" style="color:#d16969;"></i> <strong style="color: var(--text-main, #0f172a);">${c.file}</strong> 
                                    <a href="${c.download_url}" target="_blank" style="float:right; color:var(--primary-color, #2563eb); text-decoration:none; font-weight:bold;">[Tải bản gốc]</a>
                                </div>
                            `).join('');
                            document.getElementById('conflictFileList').innerHTML = html;
                            document.getElementById('conflictModal').style.display = 'flex';
                        } else {
                            if(confirm(`Phát hiện bản cập nhật Firmware v${data.version}.\n[!] Yêu cầu Engine tối thiểu: v${data.required_engine}\n\nBạn muốn nâng cấp ngay?`)) {
                                proceedUpdate(false);
                            }
                        }
                    } else {
                        Toastify({ text: "Hệ thống đã là bản mới nhất!", duration: 3000, gravity: "bottom", position: "center", style: { background: "var(--success-color, #10b981)", borderRadius: "8px" } }).showToast();
                    }
                } else {
                    Toastify({ text: data.msg, duration: 4000, gravity: "bottom", position: "center", style: { background: "var(--danger-color, #ef4444)", borderRadius: "8px" } }).showToast();
                }
            })
            .catch(err => {
                Toastify({ text: "Lỗi kết nối kiểm tra cập nhật!", duration: 3000, gravity: "bottom", position: "center", style: { background: "var(--danger-color, #ef4444)", borderRadius: "8px" } }).showToast();
            });
    }

    function proceedUpdate(skipConflicts) {
        document.getElementById('conflictModal').style.display = 'none';
        document.getElementById('otaProgressWrapper').style.display = 'block';
        
        let fd = new FormData();
        fd.append('version', g_latestVersion);
        fd.append('skip_conflicts', skipConflicts);
        fd.append('conflicted_files', JSON.stringify(g_conflictedFiles));

        fetch('api/api_ota_manager.php?action=trigger', { method: 'POST', body: fd })
            .then(() => { startProgressTracker(); });
    }

    function startProgressTracker() {
        const bar = document.getElementById('otaProgressBar');
        const txt = document.getElementById('otaProgressText');
        const consoleBox = document.getElementById('otaLogConsole');

        logInterval = setInterval(() => {
            fetch('ota_update.log', { cache: "no-store" })
                .then(r => r.text())
                .then(logs => {
                    if(logs && logs.trim() !== "") {
                        consoleBox.innerText = logs;
                        consoleBox.scrollTop = consoleBox.scrollHeight;
                    }
                }).catch(e => {});
        }, 1000);

        let progressInterval = setInterval(() => {
            fetch('ota_progress.txt', { cache: "no-store" })
                .then(r => r.text())
                .then(percent => {
                    percent = percent.trim();
                    if(percent === 'ERROR') {
                        clearInterval(progressInterval);
                        clearInterval(logInterval);
                        Toastify({ text: "Lỗi thực thi Updater! Kiểm tra log engine.", duration: 5000, gravity: "bottom", position: "center", style: { background: "var(--danger-color, #ef4444)", borderRadius: "8px" } }).showToast();
                        return;
                    }
                    if(percent && !isNaN(percent)) {
                        bar.style.width = percent + '%';
                        if(percent != '100') {
                            txt.innerText = `Đang nạp dữ liệu cập nhật... ${percent}%`;
                        } else {
                            clearInterval(progressInterval);
                            setTimeout(() => clearInterval(logInterval), 2000);
                            txt.innerHTML = `
                                <span style="color: #10b981;"><i class="fas fa-check-circle"></i> Nạp bản vá thành công!</span>
                                <button onclick="window.location.reload()" class="mac-btn mac-btn-primary" style="padding: 6px 12px; margin-left: 15px; font-size: 12px;">
                                    <i class="fas fa-sync-alt"></i> Tải lại trang ngay
                                </button>
                            `;
                        }
                    }
                }).catch(e => {});
        }, 800);
    }
</script>

<?php require_once 'includes/footer.php'; ?>