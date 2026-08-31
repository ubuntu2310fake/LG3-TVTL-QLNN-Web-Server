<?php
include 'includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<style>
    /* CSS UPLOAD AREA */
    .upload-area { 
        position: relative; border: 2px dashed var(--border-color); padding: 15px; 
        text-align: center; border-radius: 12px; transition: 0.2s; 
        cursor: pointer; background: var(--bg-hover); height: 100%; display:flex; 
        flex-direction:column; justify-content:center; align-items:center;
        box-sizing: border-box;
    }
    .upload-area:hover { border-color: var(--accent-color); background: var(--bg-input); }
    
    /* CSS DROPDOWN */
    .custom-select-container { position: relative; width: 100%; }
    .select-selected {
        background-color: var(--bg-input); border: 1px solid var(--border-color); border-radius: 8px;
        padding: 0 15px; display: flex; align-items: center; justify-content: space-between;
        font-size: 14px; height: 42px; transition: 0.2s; cursor: pointer; color: var(--text-main);
    }
    .select-selected:active { border-color: var(--accent-color); }
    .select-arrow { width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid var(--text-muted); transition: 0.2s; }
    .select-selected.active .select-arrow { transform: rotate(180deg); }
    .select-items {
        position: absolute; top: 110%; left: 0; right: 0; z-index: 1000;
        background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px;
        max-height: 200px; overflow-y: auto; display: none;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .select-items div { padding: 10px 15px; cursor: pointer; border-bottom: 1px solid var(--border-color); font-size: 14px; color: var(--text-main); }
    .select-items div:hover { background: var(--bg-hover); color: var(--accent-color); }

    /* CSS MODAL CROP */
    #cropModal {
        display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%;
        background-color: rgba(0,0,0,0.8); align-items: center; justify-content: center;
    }
    .crop-container {
        background: var(--bg-card); padding: 20px; border-radius: 10px; width: 90%; max-width: 500px;
        display: flex; flex-direction: column; gap: 15px; color: var(--text-main);
    }
    .img-container { max-height: 60vh; overflow: hidden; background: #333; }
    .img-container img { max-width: 100%; display: block; }
</style>

<div class="win-card" style="max-width:700px; margin:0 auto;">
    <h2 style="color:var(--accent-color); text-align:center; margin-bottom:20px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
        <i class="fas fa-user-edit" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chỉnh Sửa Hồ Sơ' : 'Chỉnh Sửa Hồ Sơ') ?>
    </h2>

    <?php if ($student->has_pending_changes): ?>
    <div id="pendingBox" style="background:var(--bg-hover); border:1px solid #fcd34d; padding:15px; border-radius:10px; margin-bottom:20px;">
        <h4 style="margin:0 0 10px 0; color:#b45309;"><i class="fas fa-exclamation-circle" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Yêu cầu thay đổi thông tin' : 'Yêu cầu thay đổi thông tin') ?></h4>
        <div style="display:grid; grid-template-columns: 100px 1fr 1fr; gap:10px; font-size:13px; margin-bottom:15px;">
            <b><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mục' : 'Mục') ?></b> <b><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hiện tại' : 'Hiện tại') ?></b> <b><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mới (Chờ duyệt)' : 'Mới (Chờ duyệt)') ?></b>
            <span><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Họ tên' : 'Họ tên') ?></span> <span style="color:var(--text-muted);"><?= htmlspecialchars($student->name) ?></span> <span style="color:#059669; font-weight:bold;"><?= htmlspecialchars($student->pending_name ?: '---') ?></span>
            <span><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ngày sinh:' : 'Date of Birth:') ?></span> <span style="color:var(--text-muted);"><?= htmlspecialchars($student->dob) ?></span> <span style="color:#059669; font-weight:bold;"><?= htmlspecialchars($student->pending_dob ?: '---') ?></span>
        </div>
        <div style="display:flex; gap:10px;">
            <button type="button" onclick="handlePending('approve_changes')" class="win-btn" style="flex:1; background:#10b981;">
                <i class="fas fa-check" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chấp nhận' : 'Chấp nhận') ?>
            </button>
            <button type="button" onclick="handlePending('reject_changes')" class="win-btn win-btn-danger" style="flex:1;">
                <i class="fas fa-times" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Từ chối' : 'Từ chối') ?>
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data" id="editForm" onsubmit="submitEditForm(event)">
        <input type="hidden" name="action" value="update_direct">
        <input type="hidden" name="delete_image" id="deleteImageInput" value="0">
        
        <div style="display:grid; grid-template-columns: 140px 1fr; gap:20px; margin-bottom:20px;">
            <div style="text-align:center; display:flex; flex-direction:column; gap:10px;">
                <label style="font-weight:bold; font-size:13px; color:var(--text-muted); display:block;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ảnh hiện tại:' : 'Ảnh hiện tại:') ?></label>
                
                <img src="<?= $student->image_url ? '/'.$student->image_url : '/static/default.png' ?>" 
                     id="currentImg"
                     class="refresh-cache"
                     onerror="this.onerror=null; this.src='/static/default.png';"
                     style="width:120px; height:150px; object-fit:cover; border:1px solid var(--border-color); border-radius:8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin:0 auto;">
                
                <div id="btnDeleteImgWrapper" style="display: <?= $student->image_url ? 'block' : 'none' ?>;">
                    <button type="button" onclick="confirmDeleteImage()" class="win-btn win-btn-danger" style="padding: 5px 10px; font-size: 12px; width: 100%;">
                        <i class="fas fa-trash" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xóa ảnh' : 'Xóa ảnh') ?>
                    </button>
                </div>
            </div>

            <div style="display:flex; flex-direction:column;">
                <label style="font-weight:bold; font-size:13px; color:var(--text-muted); display:block; margin-bottom:5px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cập nhật ảnh mới:' : 'Cập nhật ảnh mới:') ?></label>
                
                <input type="file" name="image" id="inpFile" accept="image/*" style="display:none;" onchange="handleFileSelect(this)" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bấm vào đây để chọn ảnh' : 'Bấm vào đây để chọn ảnh') ?>">
                
                <div class="upload-area" id="uploadArea" role="button" tabindex="0" onclick="document.getElementById('inpFile').click()" onkeydown="if(event.key==='Enter' || event.key===' ') document.getElementById('inpFile').click()">
                    <div id="uploadDefault">
                        <i class="fas fa-cloud-upload-alt" aria-hidden="true" style="font-size:30px; color:var(--text-muted); margin-bottom:5px;"></i>
                        <div style="font-size:13px; color:var(--text-muted); font-weight:500;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bấm vào đây để chọn ảnh' : 'Bấm vào đây để chọn ảnh') ?></div>
                    </div>
                    
                    <div id="uploadPreview" style="display:none; text-align:center;">
                        <i class="fas fa-check-circle" aria-hidden="true" style="font-size:24px; color:#10b981; margin-bottom:5px;"></i>
                        <div id="uploadFileName" style="font-size:13px; color:var(--text-main); font-weight:bold;">filename.jpg</div>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:15px; margin-bottom:15px;">
            <div>
                <label style="font-weight:bold; font-size:13px; color:var(--text-muted);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'STT trong lớp:' : 'Class Ordinal (STT):') ?></label>
                <input type="number" name="thuylinh" value="<?= htmlspecialchars($student->thuylinh ?? '') ?>" class="win-input" placeholder="VD: 1, 2, 3..." min="1" max="999">
            </div>

            <div>
                <label style="font-weight:bold; font-size:13px; color:var(--text-muted);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mã HS (SBD):' : 'Mã HS (SBD):') ?></label>
                <input type="text" value="<?= htmlspecialchars($student->code) ?>" class="win-input" readonly style="background:var(--bg-hover); color:var(--text-muted);">
            </div>
            
            <div>
                <label style="font-weight:bold; font-size:13px; color:var(--text-muted);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp' : 'Class') ?></label>
                <input type="hidden" name="class_id" id="hiddenClassId" value="<?= $student->class_id ?>">
                <div class="custom-select-container">
                    <div class="select-selected" onclick="toggleDropdown(event, this)" onkeydown="if(event.key==='Enter' || event.key===' ') toggleDropdown(event, this)" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp' : 'Class') ?>">
                        <span id="txtSelectedClass" style="font-weight:600; color:var(--text-main);">
                            <?php foreach ($classes as $c): ?>
                                <?php if ($c['id'] == $student->class_id) echo $c['name']; ?>
                            <?php endforeach; ?>
                        </span>
                        <div class="select-arrow" aria-hidden="true"></div>
                    </div>
                    <div class="select-items" role="listbox">
                        <?php foreach ($classes as $c): ?>
                        <div onclick="selectClassOption('<?= $c['id'] ?>', '<?= $c['name'] ?>', this, 'hiddenClassId', 'txtSelectedClass')" onkeydown="if(event.key==='Enter' || event.key===' ') selectClassOption('<?= $c['id'] ?>', '<?= $c['name'] ?>', this, 'hiddenClassId', 'txtSelectedClass')" role="option" tabindex="0"><?= $c['name'] ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="border-top: 1px dashed var(--border-color); padding-top: 15px; margin-bottom: 15px;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div>
                    <label style="font-weight:bold; font-size:13px; color:var(--accent-color);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Quyền hạn:' : 'Quyền hạn:') ?></label>
                    <input type="hidden" name="user_role" id="hiddenUserRole" value="<?= $linked_user ? $linked_user->role : 'STUDENT' ?>">
                    <div class="custom-select-container">
                        <div class="select-selected" onclick="toggleDropdown(event, this)" onkeydown="if(event.key==='Enter' || event.key===' ') toggleDropdown(event, this)" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Quyền hạn:' : 'Quyền hạn:') ?>">
                            <span id="txtSelectedRole" style="font-weight:600; color:var(--text-main);">
                                <?php if ($linked_user && $linked_user->role == 'RED_FLAG'): ?>
                                    <i class="fas fa-flag" aria-hidden="true" style="color:#d93025;"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cờ đỏ / Lớp trưởng / Bí thư' : 'Cờ đỏ / Lớp trưởng / Bí thư') ?>
                                <?php else: ?>
                                    <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Học sinh' : 'Học sinh') ?>
                                <?php endif; ?>
                            </span>
                            <div class="select-arrow" aria-hidden="true"></div>
                        </div>
                        <div class="select-items" role="listbox">
                            <div onclick="selectRoleOption('STUDENT', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Học sinh' : 'Học sinh') ?>', this)" onkeydown="if(event.key==='Enter' || event.key===' ') selectRoleOption('STUDENT', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Học sinh' : 'Học sinh') ?>', this)" role="option" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Học sinh' : 'Học sinh') ?></div>
                            <div onclick="selectRoleOption('RED_FLAG', '<i class=\'fas fa-flag\' aria-hidden=\'true\'></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cờ đỏ / Lớp trưởng' : 'Cờ đỏ / Lớp trưởng') ?>', this)" onkeydown="if(event.key==='Enter' || event.key===' ') selectRoleOption('RED_FLAG', '<i class=\'fas fa-flag\' aria-hidden=\'true\'></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cờ đỏ / Lớp trưởng' : 'Cờ đỏ / Lớp trưởng') ?>', this)" role="option" tabindex="0">
                                <i class="fas fa-flag" aria-hidden="true" style="color:#d93025;"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cờ đỏ / Lớp trưởng' : 'Cờ đỏ / Lớp trưởng') ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="standingClassDiv" style="display: <?= ($linked_user && $linked_user->role == 'RED_FLAG') ? 'block' : 'none' ?>;">
                    <label style="font-weight:bold; font-size:13px; color:var(--accent-color);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đứng lớp:' : 'Đứng lớp:') ?></label>
                    <input type="hidden" name="standing_class_id" id="hiddenStandingClassId" value="<?= ($linked_user && $linked_user->homeroom_class_id) ? $linked_user->homeroom_class_id : '' ?>">
                    <div class="custom-select-container">
                        <div class="select-selected" onclick="toggleDropdown(event, this)" onkeydown="if(event.key==='Enter' || event.key===' ') toggleDropdown(event, this)" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đứng lớp:' : 'Đứng lớp:') ?>">
                            <span id="txtStandingClass" style="font-weight:600; color:var(--text-main);">
                                <?php 
                                    $found = false;
                                    if ($linked_user && $linked_user->homeroom_class_id) {
                                        foreach($classes as $c) if($c['id'] == $linked_user->homeroom_class_id) { echo (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp ' : 'Lớp ').$c['name']; $found=true; break; }
                                    }
                                    if (!$found) echo (($_SESSION['lang'] ?? 'vi') === 'vi' ? '-- Không đứng lớp --' : '-- Không đứng lớp --');
                                ?>
                            </span>
                            <div class="select-arrow" aria-hidden="true"></div>
                        </div>
                        <div class="select-items" role="listbox">
                            <div onclick="selectStandingClass('', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '-- Không đứng lớp --' : '-- Không đứng lớp --') ?>', this)" onkeydown="if(event.key==='Enter' || event.key===' ') selectStandingClass('', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '-- Không đứng lớp --' : '-- Không đứng lớp --') ?>', this)" role="option" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '-- Không đứng lớp --' : '-- Không đứng lớp --') ?></div>
                            <?php foreach ($classes as $c): ?>
                            <div onclick="selectStandingClass('<?= $c['id'] ?>', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp ' : 'Lớp ') ?><?= $c['name'] ?>', this)" onkeydown="if(event.key==='Enter' || event.key===' ') selectStandingClass('<?= $c['id'] ?>', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp ' : 'Lớp ') ?><?= $c['name'] ?>', this)" role="option" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp ' : 'Lớp ') ?><?= $c['name'] ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div style="font-size:11px; color:var(--text-muted); margin-top:5px; font-style:italic;">
                * <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cờ đỏ có quyền: Chấm nền nếp tất cả các lớp, Xem báo cáo.' : 'Cờ đỏ có quyền: Chấm nền nếp tất cả các lớp, Xem báo cáo.') ?><br>
                * <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đứng lớp: Được quyền xem Dashboard quản lý của lớp đó.' : 'Đứng lớp: Được quyền xem Dashboard quản lý của lớp đó.') ?>
            </div>
        </div>

        <label style="font-weight:bold; font-size:13px; color:var(--text-muted); display:block;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Họ và Tên:' : 'Họ và Tên:') ?></label>
        <input type="text" name="name" value="<?= htmlspecialchars($student->name) ?>" class="win-input" required>

        <label style="font-weight:bold; font-size:13px; color:var(--text-muted); display:block; margin-top:10px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ngày sinh:' : 'Date of Birth:') ?></label>
        <input type="text" name="dob" value="<?= htmlspecialchars($student->dob ?: '') ?>" class="win-input" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'VD: 15/05/2008' : 'Ex: 15/05/2008') ?>">

        <div style="display:flex; gap:10px; margin-top:25px;">
            <a href="manage_students" class="win-btn win-btn-secondary" style="text-decoration:none; padding:10px 20px;">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Quay lại' : 'Go Back') ?>
            </a>
            <button type="submit" class="win-btn" style="flex:1;">
                <i class="fas fa-save" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'LƯU THAY ĐỔI' : 'SAVE CHANGES') ?>
            </button>
        </div>
    </form>
</div>

<div id="cropModal">
    <div class="crop-container">
        <h3 style="margin:0; text-align:center; color:var(--accent-color);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cắt Ảnh Avatar' : 'Crop Avatar') ?></h3>
        <div style="font-size:13px; color:var(--text-muted); text-align:center;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vui lòng căn chỉnh ảnh về hình vuông.' : 'Please align the image to a square.') ?></div>
        <div class="img-container">
            <img id="imageToCrop" src="">
        </div>
        <div style="display:flex; gap:10px;">
            <button type="button" class="win-btn win-btn-secondary" onclick="closeCrop(false)" style="flex:1;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hủy' : 'Cancel') ?></button>
            <button type="button" class="win-btn" onclick="saveCrop()" style="flex:1;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xong, Đóng lại' : 'Done, Close') ?></button>
        </div>
    </div>
</div>

<script>
    window.pageDestroy = function() {
        if(window.cropper) window.cropper.destroy();
        window.cropper = null;
        document.onclick = null;
    };

    window.pageInit = function() {
        window.cropper = null;
        document.onclick = function(e) { window.closeAllSelects(e.target); };
    };

    window.submitEditForm = async function(event) {
        event.preventDefault(); var form = document.getElementById('editForm'); var btn = form.querySelector('button[type="submit"]'); var originalText = btn.innerHTML;
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; var formData = new FormData(form);
        try { var response = await fetch(window.location.href, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } }); var data = await response.json(); if (data.status === 'success') { Toastify({ text: "✅ " + data.msg, duration: 3000, style: { background: "#10b981" } }).showToast(); if (data.new_avatar_url) { document.getElementById('currentImg').src = data.new_avatar_url + '?t=' + new Date().getTime(); var delBtn = document.getElementById('btnDeleteImgWrapper'); if(data.new_avatar_url.includes('default.png')) delBtn.style.display = 'none'; else delBtn.style.display = 'block'; window.resetUploadUI(); } } else { Toastify({ text: "❌ " + (window.LANG && window.LANG.error_prefix || (window.LANG && window.LANG.txt_2890926251746949903 || "Lỗi:")) + data.msg, style: { background: "#ef4444" } }).showToast(); } } catch (error) { Toastify({ text: "❌ " + (window.LANG && window.LANG.server_connection_error || (window.LANG && window.LANG.txt_6613096360626305300 || "Lỗi kết nối server!")), style: { background: "#ef4444" } }).showToast(); } finally { btn.disabled = false; btn.innerHTML = originalText; }
    };

    window.resetUploadUI = function() { document.getElementById('inpFile').value = ''; document.getElementById('deleteImageInput').value = '0'; document.getElementById('uploadPreview').style.display = 'none'; document.getElementById('uploadDefault').style.display = 'block'; };
    window.handlePending = async function(action) { var fd = new FormData(); fd.append('action', action); try { var res = await fetch(window.location.href, { method: 'POST', body: fd, headers: {'X-Requested-With': 'XMLHttpRequest'} }); var d = await res.json(); if(d.status === 'success') { Toastify({text: "✅ " + d.msg, style:{background:"#10b981"}}).showToast(); document.getElementById('pendingBox').style.display = 'none'; if(d.reload) setTimeout(() => { if(window.loadPage) window.loadPage(window.location.href, false, {force: true}); else location.reload(); }, 1000); } else alert(d.msg); } catch(e) { alert(window.LANG && window.LANG.connection_error || (window.LANG && window.LANG.txt_8957956000854301468 || "Lỗi kết nối")); } };
    window.handleFileSelect = function(input) { if (input.files && input.files[0]) { var reader = new FileReader(); reader.onload = function(e) { window.openCropModal(e.target.result); }; reader.readAsDataURL(input.files[0]); } };
    window.openCropModal = function(imgSrc) { const imgEl = document.getElementById('imageToCrop'); const modal = document.getElementById('cropModal'); imgEl.src = imgSrc; modal.style.display = 'flex'; if(window.cropper) window.cropper.destroy(); window.cropper = new Cropper(imgEl, { aspectRatio: 1, viewMode: 1, autoCropArea: 0.8 }); };
    window.closeCrop = function(save) { document.getElementById('cropModal').style.display = 'none'; if(window.cropper) window.cropper.destroy(); if(!save) document.getElementById('inpFile').value = ''; };
    window.saveCrop = function() { if(!window.cropper) return; window.cropper.getCroppedCanvas({ width: 256, height: 256 }).toBlob((blob) => { var newFile = new File([blob], "avatar_cropped.jpg", { type: "image/jpeg" }); var dt = new DataTransfer(); dt.items.add(newFile); document.getElementById('inpFile').files = dt.files; document.getElementById('uploadDefault').style.display = 'none'; document.getElementById('uploadPreview').style.display = 'block'; document.getElementById('uploadFileName').innerText = newFile.name; document.getElementById('currentImg').src = URL.createObjectURL(blob); window.closeCrop(true); }, 'image/jpeg', 0.9); };
    window.confirmDeleteImage = function() { if(confirm(window.LANG && window.LANG.confirm_delete_avatar || (window.LANG && window.LANG.txt_8660763051158226047 || "Xóa ảnh đại diện?"))) { document.getElementById('deleteImageInput').value = "1"; window.submitEditForm(new Event('submit')); } };
    window.toggleDropdown = function(e, el) { e.stopPropagation(); window.closeAllSelects(el); el.nextElementSibling.style.display = el.nextElementSibling.style.display==='block'?'none':'block'; el.classList.toggle('active'); };
    window.selectClassOption = function(id, name, el, hiddenId, txtId) { document.getElementById(txtId).innerText = name; document.getElementById(hiddenId).value = id; el.parentElement.style.display = 'none'; };
    window.selectRoleOption = function(role, htmlContent, el) { document.getElementById('txtSelectedRole').innerHTML = htmlContent; document.getElementById('hiddenUserRole').value = role; const standingDiv = document.getElementById('standingClassDiv'); if (role === 'RED_FLAG') { standingDiv.style.display = 'block'; } else { standingDiv.style.display = 'none'; document.getElementById('hiddenStandingClassId').value = ""; document.getElementById('txtStandingClass').innerText = (window.LANG && window.LANG.no_homeroom_class || (window.LANG && window.LANG.txt_1949623918004989075 || "-- Không đứng lớp --")); } el.parentElement.style.display = 'none'; };
    window.selectStandingClass = function(id, name, el) { document.getElementById('txtStandingClass').innerText = name; document.getElementById('hiddenStandingClassId').value = id; el.parentElement.style.display = 'none'; };
    window.closeAllSelects = function(target) { document.querySelectorAll('.select-items').forEach(i => { if(!target || !target.classList.contains('select-selected')) i.style.display='none'; }); document.querySelectorAll('.select-selected').forEach(e => { if(e!==target) e.classList.remove('active'); }); };
</script>
<?php include 'includes/footer.php'; ?>