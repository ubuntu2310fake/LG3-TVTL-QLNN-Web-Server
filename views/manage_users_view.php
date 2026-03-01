<?php
include 'includes/header.php';
?>

<style>
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
            <h3 style="color:var(--accent-color); margin-top:0; border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom: 20px;"><i class="fas fa-user-plus"></i> Tạo Thủ Công</h3>
            
            <form id="createUserForm" onsubmit="handleCreateUser(event)">
                <input type="hidden" name="action" value="create">
                <label style="font-weight:600; font-size:13px; color:var(--text-muted); display:block; margin-bottom:5px;">Tên đăng nhập (*)</label>
                <input type="text" name="username" class="win-input" required placeholder="VD: gv_toan">
                
                <label style="font-weight:600; font-size:13px; color:var(--text-muted); display:block; margin-bottom:5px;">Họ và Tên (*)</label>
                <input type="text" name="full_name" class="win-input" required placeholder="VD: Nguyễn Văn A">
                
                <label style="font-weight:600; font-size:13px; color:var(--text-muted); display:block; margin-bottom:5px;">Mật khẩu (*)</label>
                <input type="password" name="password" class="win-input" required placeholder="********">
                
                <label style="font-weight:600; font-size:13px; color:var(--text-muted); display:block; margin-bottom:5px;">Quyền hạn</label>
                <input type="hidden" name="role" id="input_role" value="TEACHER">
                <div class="custom-select-container">
                    <div class="select-selected" onclick="toggleDropdown(this)">
                        <span id="txt_role">Giáo Viên (TEACHER)</span><div class="select-arrow"></div>
                    </div>
                    <div class="select-items">
                        <div onclick="selectItem('input_role', 'txt_role', 'TEACHER', 'Giáo Viên (TEACHER)', this)">Giáo Viên (TEACHER)</div>
                        <div onclick="selectItem('input_role', 'txt_role', 'ADMIN', 'Quản Trị Viên (ADMIN)', this)">Quản Trị Viên (ADMIN)</div>
                    </div>
                </div>
                
                <button type="submit" class="win-btn" style="width:100%; justify-content: center; margin-top:15px;">Thêm mới</button>
            </form>
        </div>

        <div class="win-card" style="height: fit-content; margin-bottom: 0;">
            <h3 style="color:#15803d; margin-top:0; border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom: 10px;"><i class="fas fa-file-excel"></i> Nhập Từ Excel</h3>
            <p style="font-size:13px; color:var(--text-muted); margin-bottom: 10px;"><a href="download_template_teacher.php" style="color: var(--accent-color); font-weight: bold; text-decoration: none;">⬇ Tải file mẫu .xlsx</a></p>
            <form action="import_teachers.php" method="POST" enctype="multipart/form-data">
                <div class="upload-area" id="area-teacher">
                    <input type="file" name="file" id="file-teacher" class="file-input-hidden" accept=".xlsx, .xls" required>
                    <i class="fas fa-cloud-upload-alt" style="font-size: 30px; color: #cbd5e1; margin-bottom: 10px;"></i>
                    <p id="preview-teacher" style="margin:0; font-size:13px; color:var(--text-muted); font-weight:500;">Kéo thả file vào đây hoặc <span style="color:var(--accent-color); text-decoration:underline;">Click để chọn</span></p>
                </div>
                <button type="submit" class="win-btn" style="width:100%; justify-content: center; background-color: #15803d; margin-top: 15px;"><i class="fas fa-upload"></i> Tải lên</button>
            </form>
        </div>
    </div>

    <div class="win-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0; color:var(--text-main);">Danh sách Tài khoản</h3>
            <span style="background:var(--bg-hover); padding:5px 12px; border-radius:20px; font-size:12px; font-weight:700; color:var(--text-muted);"><?= count($users) ?> User</span>
        </div>

        <div class="table-responsive"> 
            <table class="rank-table user-list-table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">STT</th>
                        <th>Thông tin</th>
                        <th style="text-align: center;">Quyền</th>
                        <th style="width: 200px;">Lớp Chủ Nhiệm</th>
                        <th style="width: 50px; text-align: center;">Lưu</th>
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
                                    <div class="select-selected" onclick="toggleDropdown(this)">
                                        <span id="txt_class_<?= $u->id ?>"><?= $u->class_name ?: '-- Trống --' ?></span>
                                        <div class="select-arrow"></div>
                                    </div>
                                    <div class="select-items">
                                        <div onclick="selectItem('input_class_<?= $u->id ?>', 'txt_class_<?= $u->id ?>', '', '-- Trống --', this)">-- Trống --</div>
                                        <?php foreach ($classes as $c): ?>
                                        <div onclick="selectItem('input_class_<?= $u->id ?>', 'txt_class_<?= $u->id ?>', '<?= $c['id'] ?>', '<?= $c['name'] ?>', this)"><?= $c['name'] ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </form>
                        </td>
                        <td style="text-align:center;">
                            <button type="button" onclick="document.getElementById('form_cn_<?= $u->id ?>').dispatchEvent(new Event('submit'))" class="win-btn win-btn-secondary" style="padding:0; width:36px; height:36px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center;">
                                <i class="fas fa-check"></i>
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
    // 1. AJAX: Tạo User mới
    async function handleCreateUser(event) {
        event.preventDefault();
        const form = event.target;
        const btn = form.querySelector('button[type="submit"]');
        const oldText = btn.innerHTML;
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        try {
            const formData = new FormData(form);
            const res = await fetch('manage_users.php', {
                method: 'POST', body: formData, headers: {'X-Requested-With': 'XMLHttpRequest'}
            });
            const data = await res.json();
            
            if(data.status === 'success') {
                Toastify({ text: "✅ " + data.msg, style: { background: "#10b981" } }).showToast();
                if(data.reload) setTimeout(() => location.reload(), 1000);
            } else {
                Toastify({ text: "❌ " + data.msg, style: { background: "#ef4444" } }).showToast();
            }
        } catch(e) { alert("Lỗi kết nối"); }
        finally { btn.disabled = false; btn.innerHTML = oldText; }
    }

    // 2. AJAX: Gán lớp chủ nhiệm (Không reload)
    async function handleAssignHomeroom(event, uid) {
        event.preventDefault();
        const form = document.getElementById('form_cn_' + uid);
        const btn = form.parentElement.nextElementSibling.querySelector('button');
        
        // Hiệu ứng loading nút check
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        try {
            const formData = new FormData(form);
            const res = await fetch('manage_users.php', {
                method: 'POST', body: formData, headers: {'X-Requested-With': 'XMLHttpRequest'}
            });
            const data = await res.json();
            
            if(data.status === 'success') {
                Toastify({ text: "✅ " + data.msg, duration: 2000, style: { background: "#10b981" } }).showToast();
                // Giữ nguyên giao diện, không cần reload
            } else {
                Toastify({ text: "❌ " + data.msg, style: { background: "#ef4444" } }).showToast();
            }
        } catch(e) { alert("Lỗi kết nối"); }
        finally { 
            btn.innerHTML = '<i class="fas fa-check"></i>';
            btn.disabled = false;
        }
    }

    // Drag & Drop & Dropdown UI (Giữ nguyên)
    function setupDragDrop(areaId, inputId, previewId) {
        const area = document.getElementById(areaId);
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        if(!area || !input) return;
        area.addEventListener('click', () => input.click());
        input.addEventListener('change', () => {
            if (input.files.length > 0) {
                preview.innerHTML = `<i class="fas fa-file-excel" style="color:#15803d"></i> <b>${input.files[0].name}</b>`;
                area.style.borderColor = '#15803d'; area.style.background = '#f0fdf4';
            }
        });
        ['dragenter', 'dragover'].forEach(e => area.addEventListener(e, ev => { ev.preventDefault(); ev.stopPropagation(); area.style.borderColor='#15803d'; area.style.background='#eff6ff'; }));
        ['dragleave', 'drop'].forEach(e => area.addEventListener(e, ev => {
            ev.preventDefault(); ev.stopPropagation();
            if(e==='drop' && ev.dataTransfer.files.length > 0) {
                input.files = ev.dataTransfer.files;
                preview.innerHTML = `<i class="fas fa-file-excel" style="color:#15803d"></i> <b>${input.files[0].name}</b>`;
                area.style.borderColor = '#15803d'; area.style.background = '#f0fdf4';
            } else { if (input.files.length === 0) { area.style.borderColor='#cbd5e1'; area.style.background='#f8fafc'; } }
        }));
    }
    document.addEventListener("DOMContentLoaded", function() { setupDragDrop('area-teacher', 'file-teacher', 'preview-teacher'); });

    function toggleDropdown(el) {
        closeAllSelects(el);
        const list = el.nextElementSibling;
        const arrow = el.querySelector('.select-arrow');
        if (list.style.display === 'block') {
            list.style.display = 'none'; el.classList.remove('active'); if(arrow) arrow.style.transform = 'rotate(0deg)';
        } else {
            list.style.display = 'block'; el.classList.add('active'); if(arrow) arrow.style.transform = 'rotate(180deg)';
        }
        event.stopPropagation();
    }
    function selectItem(inputId, textId, value, text, itemEl) {
        document.getElementById(inputId).value = value;
        document.getElementById(textId).innerText = text;
        closeAllSelects();
        event.stopPropagation();
    }
    function closeAllSelects(exceptEl) {
        const items = document.querySelectorAll('.select-items');
        const selecteds = document.querySelectorAll('.select-selected');
        for (let i = 0; i < items.length; i++) {
            if (!exceptEl || items[i] !== exceptEl.nextElementSibling) items[i].style.display = 'none';
        }
        for (let i = 0; i < selecteds.length; i++) {
            if (selecteds[i] !== exceptEl) {
                selecteds[i].classList.remove('active');
                const arrow = selecteds[i].querySelector('.select-arrow');
                if(arrow) arrow.style.transform = 'rotate(0deg)';
            }
        }
    }
    document.addEventListener('click', () => closeAllSelects());
</script>
<?php include 'includes/footer.php'; ?>