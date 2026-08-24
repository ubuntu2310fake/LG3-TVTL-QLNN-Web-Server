<style>
    /* CSS WINUI 3.0 - ĐỒNG BỘ GATE_CHECK_VIEW */
    .custom-select-container { position: relative; width: 100%; margin-bottom: 20px; }
    .select-selected {
        background-color: var(--bg-input); border: 1px solid var(--border-color); border-radius: 10px;
        padding: 0 15px; display: flex; align-items: center; justify-content: space-between;
        font-size: 15px; height: 50px; box-sizing: border-box; color: var(--text-main);
        transition: 0.2s; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .select-selected:active, .select-selected.active { border-color: #0078d4; box-shadow: 0 0 0 3px rgba(0,120,212,0.15); }
    .select-arrow { width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid var(--text-muted); transition: 0.2s; }
    .select-selected.active .select-arrow { transform: rotate(180deg); }
    .select-items {
        position: absolute; top: 105%; left: 0; right: 0; z-index: 99;
        background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px;
        max-height: 250px; overflow-y: auto; display: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        animation: fadeIn 0.2s ease;
    }
    .select-items div { padding: 12px 15px; cursor: pointer; border-bottom: 1px solid var(--border-color); font-size: 14px; color: var(--text-main); }
    .select-items div:hover { background: var(--bg-hover); color: #0078d4; font-weight: 600; }
    
    /* STYLE UPLOAD AREA */
    .upload-area { border: 2px dashed #107c41; border-radius: 12px; background: var(--exam-dropzone-bg); transition: 0.3s; }
    .upload-area:hover { border-color: #059669; background: rgba(16, 185, 129, 0.05); }
    /* =========================================
   WINUI 3.0 MODAL COMPONENT
========================================= */

/* Lớp phủ nền mờ đen, luôn nằm trên cùng */
.win-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.45); /* Nền tối mờ */
    backdrop-filter: blur(4px); /* Hiệu ứng Blur chuẩn Win 11 */
    -webkit-backdrop-filter: blur(4px);
    z-index: 99999 !important; /* Ép buộc nổi lên trên mọi thứ */
    display: flex;
    justify-content: center;
    align-items: center; /* Căn giữa Modal ra giữa màn hình */
}

/* Khối nội dung Modal chính */
.win-modal-content {
    background-color: var(--bg-card, #ffffff);
    width: 90%;
    max-width: 450px;
    border-radius: 8px; /* WinUI 3 chuộng bo góc 8px cho Dialog */
    border: 1px solid var(--border-color, #e5e5e5);
    box-shadow: 0 32px 64px rgba(0,0,0,0.2), 0 2px 21px rgba(0,0,0,0.08); /* Bóng đổ siêu sâu của Win 11 */
    display: flex;
    flex-direction: column;
    color: var(--text-main);
    animation: win11-modal-pop 0.3s cubic-bezier(0.1, 0.9, 0.2, 1) forwards;
}

/* Phần đầu Modal */
.win-modal-header {
    padding: 24px 24px 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Nút X đóng Modal */
.win-modal-close {
    font-size: 20px;
    font-weight: bold;
    color: var(--text-muted, #666);
    cursor: pointer;
    width: 32px;
    height: 32px;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 6px;
    transition: all 0.15s;
}

/* Hiệu ứng hover cho nút X giống hệt Windows */
.win-modal-close:hover {
    background-color: #e81123;
    color: white;
}

/* Phần thân chứa Form */
.win-modal-body {
    padding: 0 24px 24px 24px;
}

/* Phần đuôi chứa các nút bấm */
.win-modal-footer {
    padding: 20px 24px;
    background-color: var(--bg-hover, #f3f3f3); /* Nền hơi xám nhẹ */
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
    display: flex;
    justify-content: flex-end;
    gap: 12px; /* Khoảng cách giữa các nút */
    border-top: 1px solid var(--border-color, #e5e5e5);
}

/* Hiệu ứng nảy nhẹ khi Modal xuất hiện */
@keyframes win11-modal-pop {
    0% {
        opacity: 0;
        transform: scale(0.95) translateY(10px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}
</style>

<div class="win-card page-transition">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="color: #107c41; margin: 0;"><i class="fas fa-database" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Quản lý & Nhập điểm' : 'Manage Exams & Import Scores') ?></h2>
        <button class="win-btn" id="btn_open_create_exam"><i class="fas fa-plus" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tạo kỳ thi mới' : 'Create New Exam') ?></button>
    </div>

    <div style="margin-bottom: 20px;">
        <label style="display:block; margin-bottom:8px; font-weight:600; font-size:14px; color:var(--text-muted);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bước 1: Chọn kỳ thi mục tiêu' : 'Step 1: Select Target Exam') ?></label>
        <div class="custom-select-container">
            <div class="select-selected" id="exam_selector_display" onclick="window.toggleDropdown(event, this)" role="button" tabindex="0">
                <span id="txt_target_exam"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '-- Nhấn để chọn kỳ thi --' : '-- Click to select exam --') ?></span>
                <div class="select-arrow"></div>
            </div>
            <div class="select-items" id="exam_list_dropdown"></div>
        </div>
    </div>

    <div class="upload-area" id="score_dropzone" style="text-align: center; padding: 45px 20px; cursor: pointer;" role="button" tabindex="0">
        <i class="fas fa-file-excel" style="font-size: 50px; color: #107c41; margin-bottom: 15px;" aria-hidden="true"></i>
        <h3 style="margin: 0; color: #107c41;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Kéo thả file Excel điểm vào đây' : 'Drag & drop Excel score file here') ?></h3>
        <p style="color: var(--text-muted); font-size: 13px; margin-top: 8px;">
            <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hệ thống tự nhận diện: <b>SBD, Họ tên, Lớp, Ngày sinh (DOB)</b> và điểm thành phần.' : 'System auto-detects: <b>ID, Name, Class, DOB</b> and scores.') ?>
        </p>
        <input type="file" id="inp_score_file" accept=".xlsx, .xls" style="display: none;" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tải lên file Excel' : 'Upload Excel File') ?>">
        
        <div id="fileInfoArea" style="margin-top: 25px; display: none;">
            <div style="background: #ecfdf5; color: #065f46; padding: 12px 18px; border-radius: 10px; border: 1px solid #10b981; display: inline-flex; align-items: center; gap: 12px;">
                <i class="fas fa-file-signature" aria-hidden="true"></i>
                <span id="txt_file_name" style="font-weight: 700;"></span>
                <i class="fas fa-times-circle" id="btn_remove_file" style="cursor: pointer; color: #ef4444; font-size: 18px;" role="button" tabindex="0" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xóa file' : 'Remove File') ?>"></i>
            </div>
        </div>
    </div>

    <button class="win-btn" id="btn_start_import" disabled style="width: 100%; margin-top: 25px; height: 55px; font-size: 16px; font-weight: bold;">
        <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bắt đầu xử lý & Lưu vào hệ thống' : 'Start Processing & Save') ?>
    </button>

    <hr style="margin: 40px 0; opacity: 0.1;">
    <h3 style="margin-bottom: 20px; font-size: 18px;"><i class="fas fa-history" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lịch sử kỳ thi' : 'Exam History') ?></h3>
    <div id="exam_history_list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 15px;"></div>
</div>

<div id="modal_create_exam" class="win-modal-overlay" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="title_create_exam">
    <div class="win-modal-content" style="max-width: 450px; border-radius: 16px;">
        <div class="win-modal-header" style="padding: 20px;">
            <h3 id="title_create_exam" style="margin:0; color:#107c41;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Khởi tạo kỳ thi mới' : 'Initialize New Exam') ?></h3>
            <span class="win-modal-close" id="btn_close_modal" role="button" tabindex="0" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đóng' : 'Close') ?>">&times;</span>
        </div>
        <div class="win-modal-body" style="padding: 0 20px 20px 20px;">
            <div style="margin-bottom: 18px;">
                <label style="display:block; margin-bottom:8px; font-weight:600;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tên kỳ thi (Tiếng Việt):' : 'Exam Name (Vietnamese):') ?></label>
                <input type="text" id="inp_new_exam_name" class="win-input" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ví dụ: Thi Giữa học kỳ II' : 'Example: Midterm Exam II') ?>">
            </div>
            <div style="margin-bottom: 18px;">
                <label style="display:block; margin-bottom:8px; font-weight:600;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tên kỳ thi (Tiếng Anh):' : 'Exam Name (English):') ?></label>
                <input type="text" id="inp_new_exam_name_en" class="win-input" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Example: Midterm Exam II' : 'Example: Midterm Exam II') ?>">
            </div>
            <div>
                <label style="display:block; margin-bottom:8px; font-weight:600;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đơn vị tổ chức:' : 'Organizer:') ?></label>
                <input type="text" id="inp_new_exam_school" class="win-input" value="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trường THPT Lạng Giang số 3' : 'Lang Giang No 3 High School') ?>">
            </div>
        </div>
        <div class="win-modal-footer">
            <button class="win-btn win-btn-secondary" id="btn_cancel_create"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hủy' : 'Cancel') ?></button>
            <button class="win-btn" id="btn_confirm_create"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xác nhận tạo' : 'Confirm Create') ?></button>
        </div>
    </div>
</div>

<script>
(function() {
    const currentLang = '<?= $_SESSION['lang'] ?? 'vi' ?>';
    let currentExamId = null;
    let selectedFile = null;

    // ==========================================
    // KHỞI TẠO SPA
    // ==========================================
    window.pageInit = function() {
        // 1. ĐƯA TOÀN BỘ HÀM TOÀN CỤC VÀO TRONG PAGEINIT
        // Việc này đảm bảo khi AJAX nạp lại trang, các hàm này luôn được tái tạo, không bị đơ
        window.toggleDropdown = function(e, el) { 
            e.stopPropagation(); 
            const items = el.nextElementSibling;
            const isVisible = items.style.display === 'block';
            if (window.closeAllSelects) window.closeAllSelects(el); 
            items.style.display = isVisible ? 'none' : 'block'; 
            el.classList.toggle('active'); 
        };

        window.closeAllSelects = function(except) { 
            document.querySelectorAll('.select-items').forEach(i => { if(i !== except?.nextElementSibling) i.style.display = 'none'; }); 
            document.querySelectorAll('.select-selected').forEach(e => { if(e !== except) e.classList.remove('active'); }); 
        };

        window.selectTargetExam = function(id, name) {
            currentExamId = id;
            document.getElementById('txt_target_exam').innerText = name;
            if (window.closeAllSelects) window.closeAllSelects(null);
            checkReady();
        };        window.deleteExam = function(id, name) {
            let examName = name ? name : '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "này" : "this") ?>';
            let msg = '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Bạn có chắc chắn muốn xóa kỳ thi <b>\"$examName\"</b> không?<br><br><span style=\"color:red;\">Toàn bộ điểm thi liên quan sẽ bị xóa sạch và không thể khôi phục!</span>" : "Are you sure you want to delete exam <b>\"$examName\"</b>?<br><br><span style=\"color:red;\">All related scores will be deleted and cannot be recovered!</span>") ?>'.replace('$examName', examName);
            
            if (window.WinUI && window.WinUI.confirm) {
                window.WinUI.confirm('<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "CẢNH BÁO XÓA KỲ THI" : "DELETE EXAM WARNING") ?>', msg, async function() {
                window.WinUI.confirm(('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'CẢNH BÁO XÓA KỲ THI' : 'DELETE EXAM WARNING') ?>'), msg, async function() {
                    try {
                        await fetch(`api/manage_exams_api.php?action=delete&id=${id}`);
                        if (typeof Toastify !== 'undefined') Toastify({ text: ('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đã xóa thành công!' : 'Deleted successfully!') ?>'), duration: 3000, style: { background: "#10b981" } }).showToast();
                        loadData();
                    } catch(e) {
                        alert(('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi kết nối khi xóa kỳ thi!' : 'Connection error when deleting exam!') ?>'));
                    }
                });
            } else {
                if(!confirm((window.LANG && window.LANG.confirm_delete_exam_fallback || `Xóa kỳ thi "${examName}" sẽ mất toàn bộ điểm liên quan. Chắc chắn chứ?`))) return;
                (async function() {
                    try {
                        await fetch(`api/manage_exams_api.php?action=delete&id=${id}`);
                        loadData();
                    } catch(e) {
                        alert(('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi khi xóa kỳ thi!' : 'Error deleting exam!') ?>'));
                    }
                })();
            }
        };

        function checkReady() {
            const btn = document.getElementById('btn_start_import');
            if(btn) btn.disabled = !(currentExamId && selectedFile);
        }

        async function loadData() {
            try {
                const res = await fetch('api/manage_exams_api.php?action=list');
                const json = await res.json();
                if(json.status === 'success') {
                    const dp = document.getElementById('exam_list_dropdown');
                    if(dp) dp.innerHTML = json.data.map(ex => {
                        const dispName = (currentLang === 'en' && ex.exam_name_en) ? ex.exam_name_en : ex.exam_name;
                        const escapedName = dispName.replace(/'/g, "\'");
                        return `<div onclick="window.selectTargetExam('${ex.id}', '${escapedName}')" role="button" tabindex="0">${dispName}</div>`;
                    }).join('');

                    const hl = document.getElementById('exam_history_list');
                    if(hl) hl.innerHTML = json.data.map(ex => {
                        const dispName = (currentLang === 'en' && ex.exam_name_en) ? ex.exam_name_en : ex.exam_name;
                        const escapedName = dispName.replace(/'/g, "\'");
                        return `
                        <div class="win-card" style="padding:18px; border-left: 5px solid #107c41; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <h4 style="margin:0 0 5px 0;">${dispName}</h4>
                                <p style="font-size:12px; color:gray; margin:0;"><i class="fas fa-school" aria-hidden="true"></i> ${ex.school_name}</p>
                            </div>
                            <button class="win-btn win-btn-danger" style="padding:6px 12px; font-size:11px;" onclick="window.deleteExam('${ex.id}', '${escapedName}')" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xóa' : 'Delete') ?>"><i class="fas fa-trash" aria-hidden="true"></i></button>
                        </div>
                    `;}).join('');
                }
            } catch(e) {
                console.error(('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi tải dữ liệu:' : 'Error loading data:') ?>'), e);
            }
        }

        const dropzone = document.getElementById('score_dropzone');
        const fileInp = document.getElementById('inp_score_file');
        const btnImport = document.getElementById('btn_start_import');
        
        dropzone.onclick = () => fileInp.click();
        fileInp.onchange = (e) => {
            if(e.target.files.length > 0) {
                selectedFile = e.target.files[0];
                document.getElementById('txt_file_name').innerText = selectedFile.name;
                document.getElementById('fileInfoArea').style.display = 'block';
                checkReady();
            }
        };

        document.getElementById('btn_remove_file').onclick = (e) => {
            e.stopPropagation(); selectedFile = null; fileInp.value = '';
            document.getElementById('fileInfoArea').style.display = 'none';
            checkReady();
        };

        document.getElementById('btn_open_create_exam').onclick = () => document.getElementById('modal_create_exam').style.display = 'flex';
        document.getElementById('btn_close_modal').onclick = () => document.getElementById('modal_create_exam').style.display = 'none';
        document.getElementById('btn_cancel_create').onclick = () => document.getElementById('modal_create_exam').style.display = 'none';

        document.getElementById('btn_confirm_create').onclick = async () => {
            const name = document.getElementById('inp_new_exam_name').value.trim();
            const nameEn = document.getElementById('inp_new_exam_name_en').value.trim();
            const school = document.getElementById('inp_new_exam_school').value.trim();

            if (!name) {
                if (typeof Toastify !== 'undefined') Toastify({ text: ('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vui lòng nhập tên kỳ thi (Tiếng Việt)!' : 'Please enter exam name (Vietnamese)!') ?>'), duration: 3000, style: { background: "#ef4444" } }).showToast();
                else alert(('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vui lòng nhập tên kỳ thi (Tiếng Việt)!' : 'Please enter exam name (Vietnamese)!') ?>'));
                return;
            }
            if (!nameEn) {
                if (typeof Toastify !== 'undefined') Toastify({ text: ('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vui lòng nhập tên kỳ thi (Tiếng Anh)!' : 'Please enter exam name (English)!') ?>'), duration: 3000, style: { background: "#ef4444" } }).showToast();
                else alert(('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vui lòng nhập tên kỳ thi (Tiếng Anh)!' : 'Please enter exam name (English)!') ?>'));
                return;
            }

            const btn = document.getElementById('btn_confirm_create');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + ('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang tạo...' : 'Creating...') ?>');

            try {
                const res = await fetch('api/manage_exams_api.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: name, name_en: nameEn, school: school })
                });
                
                if (!res.ok) throw new Error(('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Máy chủ trả về mã lỗi HTTP' : 'Server returned HTTP error code') ?>'));
                
                const json = await res.json();
                if (json.status === 'success') {
                    if (typeof Toastify !== 'undefined') Toastify({ text: ('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tạo kỳ thi thành công!' : 'Exam created successfully!') ?>'), duration: 3000, style: { background: "#10b981" } }).showToast();
                    else alert(('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tạo kỳ thi thành công!' : 'Exam created successfully!') ?>'));
                    
                    document.getElementById('modal_create_exam').style.display = 'none';
                    document.getElementById('inp_new_exam_name').value = '';
                    document.getElementById('inp_new_exam_name_en').value = '';
                    loadData();
                } else { 
                    alert(('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi: ' : 'Error: ') ?>') + (json.msg || ('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Không rõ nguyên nhân' : 'Unknown cause') ?>'))); 
                }
            } catch (e) { 
                console.error(e);
                alert(('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi kết nối máy chủ API hoặc dữ liệu hỏng!' : 'API connection error or corrupted data!') ?>')); 
            }
            
            btn.disabled = false;
            btn.innerHTML = ('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xác nhận tạo' : 'Confirm Create') ?>');
        };

        btnImport.onclick = async () => {
            btnImport.disabled = true;
            btnImport.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + ('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang xử lý...' : 'Processing...') ?>');
            const fd = new FormData();
            fd.append('fileScore', selectedFile);
            fd.append('exam_id', currentExamId);

            try {
                const res = await fetch('api/import_scores_api.php', { method: 'POST', body: fd });
                const json = await res.json();
                if(json.status === 'success') {
                    if (typeof Toastify !== 'undefined') Toastify({ text: ('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Dữ liệu đã được nạp thành công!' : 'Data loaded successfully!') ?>'), duration: 4000, style: { background: "#10b981" } }).showToast();
                    else alert(('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Dữ liệu đã được nạp thành công!' : 'Data loaded successfully!') ?>'));
                    
                    selectedFile = null; fileInp.value = '';
                    document.getElementById('fileInfoArea').style.display = 'none';
                    checkReady();
                } else { alert(('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi: ' : 'Error: ') ?>') + json.msg); }
            } catch(e) { alert(('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mất kết nối API' : 'API connection lost') ?>')); }
            btnImport.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> ' + ('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bắt đầu xử lý & Lưu vào hệ thống' : 'Start Processing & Save') ?>');
        };

        // 2. BẮT SỰ KIỆN CLICK TOÀN CỤC BẰNG ADDEVENTLISTENER
        // Cách này không ghi đè mất sự kiện của hệ thống App Shell
        window.globalDocClick = function(e) {
            if (typeof window.closeAllSelects === 'function') {
                window.closeAllSelects(null);
            }
        };
        document.addEventListener('click', window.globalDocClick);

        loadData();
    };

    // ==========================================
    // HỦY SPA (CLEANUP THEO ĐÚNG ĐẶC TẢ KỸ THUẬT)
    // ==========================================
    window.pageDestroy = function() {
        // Gỡ bỏ sự kiện click toàn cục
        if (window.globalDocClick) {
            document.removeEventListener('click', window.globalDocClick);
            window.globalDocClick = null;
        }
        
        currentExamId = null; 
        selectedFile = null;
        
        // Hủy bỏ biến toàn cục chuẩn xác
        window.toggleDropdown = null; 
        window.closeAllSelects = null;
        window.selectTargetExam = null; 
        window.deleteExam = null;
    };

})();
</script>