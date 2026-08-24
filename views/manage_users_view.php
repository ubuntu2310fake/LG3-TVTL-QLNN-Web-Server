<?php
include 'includes/header.php';
?>

<style>
    [data-theme="dark"] [style*="#fff7ed"], [data-theme="dark"] [style*="#fef9c3"], [data-theme="dark"] [style*="#e2e8f0"], [data-theme="dark"] [style*="#f1f5f9"], [data-theme="dark"] [style*="#f8fafc"], [data-theme="dark"] [style*="#fef3c7"], [data-theme="dark"] [style*="#eff6ff"], [data-theme="dark"] [style*="#f0fdf4"] { background: #111111 !important; color: var(--text-main) !important; border-color: var(--border-color) !important; }

    .custom-select-container { position: relative; width: 100%; margin-bottom: 0; }
    
    /* Cập nhật dùng biến màu */
    .select-selected { 
        background-color: var(--bg-input); 
        border: 1px solid var(--border-color); 
        border-radius: 8px; padding: 10px 12px; cursor: pointer; 
        display: flex; justify-content: space-between; align-items: center; 
        font-size: 14px; color: var(--text-main); height: 42px; 
        box-sizing: border-box; text-align: left; 
    }
    
    .select-selected span { text-align: left; width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .select-arrow { width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid var(--text-muted); margin-left: 10px; }
    
    .select-items { 
        position: absolute; top: 105%; left: 0; right: 0; z-index: 999; 
        background: var(--bg-card); border: 1px solid var(--border-color); 
        border-radius: 8px; max-height: 250px; overflow-y: auto; display: none; 
        box-shadow: 0 10px 25px rgba(0,0,0,0.15); text-align: left; 
    }
    
    .select-items div { padding: 10px 15px; cursor: pointer; border-bottom: 1px solid var(--border-color); font-size: 13px; color: var(--text-main); }
    .select-items div:hover { background: var(--bg-hover); color: var(--accent-color); }
    
    .table-dropdown { width: 100%; }
    .table-dropdown .select-selected { height: 36px; padding: 5px 10px; font-size: 13px; background-color: var(--bg-body); border-color: var(--border-color); }
    
    .role-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; min-width: 60px; text-align: center; display: inline-block; }
    .role-admin { background: #fee2e2; color: #991b1b; }
    .role-teacher { background: #dbeafe; color: #1e40af; }
</style>

<div class="grid-sidebar-layout">
    
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="win-card" style="height: fit-content; margin-bottom: 0;">
            <h3 style="color:var(--accent-color); margin-top:0; border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom: 20px;"><i class="fas fa-user-plus" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tạo Thủ Công' : 'Create Manually') ?></h3>
            
            <form id="createUserForm" onsubmit="handleCreateUser(event)">
                <input type="hidden" name="action" value="create">
                <label style="font-weight:600; font-size:13px; color:var(--text-muted); display:block; margin-bottom:5px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tên đăng nhập' : 'Username') ?> (*)</label>
                <input type="text" name="username" class="win-input" required placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'VD: gv_toan' : 'Ex: math_teacher') ?>">
                
                <label style="font-weight:600; font-size:13px; color:var(--text-muted); display:block; margin-bottom:5px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Họ và tên' : 'Full Name') ?> (*)</label>
                <input type="text" name="full_name" class="win-input" required placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'VD: Nguyễn Văn A' : 'Ex: Nguyen Van A') ?>">
                
                <label style="font-weight:600; font-size:13px; color:var(--text-muted); display:block; margin-bottom:5px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mật khẩu' : 'Password') ?> (*)</label>
                <input type="password" name="password" class="win-input" required placeholder="********">
                
                <label style="font-weight:600; font-size:13px; color:var(--text-muted); display:block; margin-bottom:5px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vai trò' : 'Role') ?></label>
                <input type="hidden" name="role" id="input_role" value="TEACHER">
                <div class="custom-select-container">
                    <div class="select-selected" onclick="toggleDropdown(this)" role="button" tabindex="0">
                        <span id="txt_role"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Giáo Viên (TEACHER)' : 'Teacher (TEACHER)') ?></span><div class="select-arrow"></div>
                    </div>
                    <div class="select-items">
                        <div onclick="selectItem('input_role', 'txt_role', 'TEACHER', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Giáo Viên (TEACHER)' : 'Teacher (TEACHER)') ?>', this)" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Giáo Viên (TEACHER)' : 'Teacher (TEACHER)') ?></div>
                        <div onclick="selectItem('input_role', 'txt_role', 'ADMIN', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Quản Trị Viên (ADMIN)' : 'Admin (ADMIN)') ?>', this)" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Quản Trị Viên (ADMIN)' : 'Admin (ADMIN)') ?></div>
                    </div>
                </div>
                
                <button type="submit" class="win-btn" style="width:100%; justify-content: center; margin-top:15px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thêm mới' : 'Add New') ?></button>
            </form>
        </div>

        <div class="win-card" style="height: fit-content; margin-bottom: 0;">
            <h3 style="color:#15803d; margin-top:0; border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom: 10px;"><i class="fas fa-file-excel" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập Từ Excel' : 'Import from Excel') ?></h3>
            <p style="font-size:13px; color:var(--text-muted); margin-bottom: 10px;"><a href="download_template_teacher.php" style="color: var(--accent-color); font-weight: bold; text-decoration: none;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '⬇ Tải file mẫu .xlsx' : '⬇ Download .xlsx template') ?></a></p>
            <form action="import_teachers.php" method="POST" enctype="multipart/form-data">
                <div class="upload-area" id="area-teacher" role="button" tabindex="0">
                    <input type="file" name="file" id="file-teacher" class="file-input-hidden" accept=".xlsx, .xls" required aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập Từ Excel' : 'Import from Excel') ?>">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 30px; color: #cbd5e1; margin-bottom: 10px;" aria-hidden="true"></i>
                    <p id="preview-teacher" style="margin:0; font-size:13px; color:var(--text-muted); font-weight:500;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Kéo thả file vào đây hoặc' : 'Drag and drop file here or') ?> <span style="color:var(--accent-color); text-decoration:underline;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Click để chọn' : 'Click to select') ?></span></p>
                </div>
                <button type="submit" class="win-btn" style="width:100%; justify-content: center; background-color: #15803d; margin-top: 15px;"><i class="fas fa-upload" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tải lên' : 'Upload') ?></button>
            </form>
        </div>
    </div>

    <div class="win-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0; color:var(--text-main);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Danh sách Tài khoản' : 'Account List') ?></h3>
            <span style="background:var(--bg-hover); padding:5px 12px; border-radius:20px; font-size:12px; font-weight:700; color:var(--text-muted);"><?= count($users) ?> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'User' : 'User') ?></span>
        </div>

        <div class="table-responsive"> 
            <table class="rank-table user-list-table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'STT' : 'No.') ?></th>
                        <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thông tin' : 'Information') ?></th>
                        <th style="text-align: center;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Quyền' : 'Role') ?></th>
                        <th style="width: 200px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp Chủ Nhiệm' : 'Homeroom Class') ?></th>
                        <th style="width: 50px; text-align: center;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lưu' : 'Save') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $idx=1; foreach ($users as $u): ?>
                    <tr>
                        <td style="text-align:center; color:var(--text-muted);"><?= $idx++ ?></td>
                        <td>
                            <div style="font-weight:700; color:var(--text-main); font-size:15px;"><?= htmlspecialchars($u->full_name ?: '---') ?></div>
                            <div style="font-size: 13px; color:var(--text-muted);">@<?= htmlspecialchars($u->username) ?></div>
                        </td>
                        <td style="text-align:center;">
                            <span class="role-badge <?= ($u->role=='ADMIN')?'role-admin':'role-teacher' ?>"><?= $u->role ?></span>
                        </td>
                        <td>
                            <form id="form_cn_<?= $u->id ?>" style="margin:0;" onsubmit="handleAssignHomeroom(event, '<?= $u->id ?>')">
                                <input type="hidden" name="action" value="assign_homeroom">
                                <input type="hidden" name="user_id" value="<?= $u->id ?>">
                                <input type="hidden" name="class_id" id="input_class_<?= $u->id ?>" value="<?= $u->homeroom_class_id ?: '' ?>">
                                <div class="custom-select-container table-dropdown">
                                    <div class="select-selected" onclick="toggleDropdown(this)" role="button" tabindex="0">
                                        <span id="txt_class_<?= $u->id ?>"><?= $u->class_name ?: (($_SESSION['lang'] ?? 'vi') === 'vi' ? '-- Trống --' : '-- Empty --') ?></span>
                                        <div class="select-arrow"></div>
                                    </div>
                                    <div class="select-items">
                                        <div onclick="selectItem('input_class_<?= $u->id ?>', 'txt_class_<?= $u->id ?>', '', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '-- Trống --' : '-- Empty --') ?>', this)" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '-- Trống --' : '-- Empty --') ?></div>
                                        <?php foreach ($classes as $c): ?>
                                        <div onclick="selectItem('input_class_<?= $u->id ?>', 'txt_class_<?= $u->id ?>', '<?= $c['id'] ?>', '<?= $c['name'] ?>', this)" role="button" tabindex="0"><?= $c['name'] ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </form>
                        </td>
                        <td style="text-align:center;">
                            <button type="button" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lưu' : 'Save') ?>" onclick="document.getElementById('form_cn_<?= $u->id ?>').dispatchEvent(new Event('submit'))" class="win-btn win-btn-secondary" style="padding:0; width:36px; height:36px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center;">
                                <i class="fas fa-check" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    window.pageDestroy = function() {
        document.onclick = null;
    };

    window.pageInit = function() {
        window.setupDragDrop('area-teacher', 'file-teacher', 'preview-teacher');
        document.onclick = function() { window.closeAllSelects(); };
    };

    window.handleCreateUser = async function(event) {
        event.preventDefault(); var form = event.target; var btn = form.querySelector('button[type="submit"]'); var oldText = btn.innerHTML;
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        try {
            var res = await fetch('manage_users.php', { method: 'POST', body: new FormData(form), headers: {'X-Requested-With': 'XMLHttpRequest'} });
            var data = await res.json();
            if(data.status === 'success') { Toastify({ text: "✅ " + data.msg, style: { background: "#10b981" } }).showToast(); if(data.reload) setTimeout(() => { if(window.loadPage) window.loadPage(window.location.href, false, {force: true}); else location.reload(); }, 1000); } 
            else Toastify({ text: "❌ " + data.msg, style: { background: "#ef4444" } }).showToast();
        } catch(e) { alert('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi kết nối' : 'Connection Error') ?>'); } finally { btn.disabled = false; btn.innerHTML = oldText; }
    };

    window.handleAssignHomeroom = async function(event, uid) {
        event.preventDefault(); var form = document.getElementById('form_cn_' + uid); var btn = form.parentElement.nextElementSibling.querySelector('button');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; btn.disabled = true;
        try {
            var res = await fetch('manage_users.php', { method: 'POST', body: new FormData(form), headers: {'X-Requested-With': 'XMLHttpRequest'} });
            var data = await res.json();
            if(data.status === 'success') Toastify({ text: "✅ " + data.msg, duration: 2000, style: { background: "#10b981" } }).showToast(); 
            else Toastify({ text: "❌ " + data.msg, style: { background: "#ef4444" } }).showToast();
        } catch(e) { alert('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi kết nối' : 'Connection Error') ?>'); } finally { btn.innerHTML = '<i class="fas fa-check"></i>'; btn.disabled = false; }
    };

    window.setupDragDrop = function(areaId, inputId, previewId) {
        var area = document.getElementById(areaId); var input = document.getElementById(inputId); var preview = document.getElementById(previewId);
        if(!area || !input) return;
        area.onclick = () => input.click();
        input.onchange = () => { if (input.files.length > 0) { preview.innerHTML = `<i class="fas fa-file-excel" style="color:#15803d"></i> <b>${input.files[0].name}</b>`; area.style.borderColor = '#15803d'; area.style.background = '#f0fdf4'; } };
        ['dragenter', 'dragover'].forEach(e => area.addEventListener(e, ev => { ev.preventDefault(); ev.stopPropagation(); area.style.borderColor='#15803d'; area.style.background='#eff6ff'; }));
        ['dragleave', 'drop'].forEach(e => area.addEventListener(e, ev => {
            ev.preventDefault(); ev.stopPropagation();
            if(e==='drop' && ev.dataTransfer.files.length > 0) { input.files = ev.dataTransfer.files; preview.innerHTML = `<i class="fas fa-file-excel" style="color:#15803d"></i> <b>${input.files[0].name}</b>`; area.style.borderColor = '#15803d'; area.style.background = '#f0fdf4'; } 
            else if (input.files.length === 0) { area.style.borderColor='#cbd5e1'; area.style.background='#f8fafc'; }
        }));
    };

    window.toggleDropdown = function(el) { window.closeAllSelects(el); var list = el.nextElementSibling; var arrow = el.querySelector('.select-arrow'); if (list.style.display === 'block') { list.style.display = 'none'; el.classList.remove('active'); if(arrow) arrow.style.transform = 'rotate(0deg)'; } else { list.style.display = 'block'; el.classList.add('active'); if(arrow) arrow.style.transform = 'rotate(180deg)'; } event.stopPropagation(); };
    window.selectItem = function(inputId, textId, value, text, itemEl) { document.getElementById(inputId).value = value; document.getElementById(textId).innerText = text; window.closeAllSelects(); event.stopPropagation(); };
    window.closeAllSelects = function(exceptEl) {
        document.querySelectorAll('.select-items').forEach(i => { if (!exceptEl || i !== exceptEl.nextElementSibling) i.style.display = 'none'; });
        document.querySelectorAll('.select-selected').forEach(s => { if (s !== exceptEl) { s.classList.remove('active'); var arrow = s.querySelector('.select-arrow'); if(arrow) arrow.style.transform = 'rotate(0deg)'; } });
    };
</script>
<?php include 'includes/footer.php'; ?>