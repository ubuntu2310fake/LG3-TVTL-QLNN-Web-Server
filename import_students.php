<?php
require_once 'includes/config.php';

// Chỉ ADMIN mới được phép import dữ liệu gốc
if (function_exists('checkRole')) {
    checkRole(['ADMIN']);
}

$isIframe = isset($_GET['iframe']) && $_GET['iframe'] == 1;

if (!$isIframe) {
    include 'includes/header.php';
}
?>

<style>
    .import-container { padding: 20px; max-width: 1000px; margin: 0 auto; }
    .win-upload-area { 
        border: 2px dashed var(--border-color); padding: 40px 20px; 
        text-align: center; border-radius: 12px; transition: 0.2s; 
        cursor: pointer; background: var(--bg-card); position: relative;
    }
    .win-upload-area:hover, .win-upload-area.dragover { 
        border-color: var(--primary-color); background: rgba(0, 95, 186, 0.05); 
    }
    .win-file-input { 
        position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
        opacity: 0; cursor: pointer; 
    }
    
    .preview-section { 
        display: none; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 20px; 
        animation: fadeIn 0.4s ease;
    }
    .win-table-wrapper { 
        max-height: 400px; overflow-y: auto; border: 1px solid var(--border-color); 
        border-radius: 8px; margin-bottom: 20px; background: var(--bg-card);
    }
    .win-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .win-table th { 
        background: var(--bg-hover); position: sticky; top: 0; z-index: 10; 
        padding: 12px; text-align: left; color: var(--text-main); border-bottom: 1px solid var(--border-color);
    }
    .win-table td { padding: 10px 12px; border-bottom: 1px solid var(--border-color); color: var(--text-main); }
    
    .loading-box { display: none; text-align: center; padding: 20px; color: var(--text-muted); }
</style>

<div class="import-container">
    <h2 style="margin-bottom: 20px; font-weight: 800; color: var(--primary-color);">
        <i class="fas fa-file-excel"></i> <?= __('import_student_data', 'Nhập dữ liệu Học sinh') ?>
    </h2>

    <div class="win-card" style="margin-bottom: 20px;">
        <div class="win-upload-area" id="dropZone">
            <input type="file" id="fileInput" class="win-file-input" accept=".xlsx, .xls">
            <i class="fas fa-cloud-upload-alt" style="font-size: 40px; color: var(--primary-color); margin-bottom: 15px;"></i>
            <h3 style="margin: 0 0 10px 0; color: var(--text-main);"><?= __('drag_drop_excel', 'Kéo thả file Excel vào đây') ?></h3>
            <p style="margin: 0; color: var(--text-muted); font-size: 14px;"><?= __('or_click_to_select', 'Hoặc click để chọn file từ máy tính') ?> (.xlsx, .xls)</p>
        </div>
    </div>

    <div id="loadingBox" class="loading-box">
        <i class="fas fa-circle-notch fa-spin fa-2x" style="color: var(--primary-color); margin-bottom: 10px;"></i>
        <div><?= __('processing_file_wait', 'Đang xử lý file, vui lòng đợi...') ?></div>
    </div>

    <div id="previewBox" class="preview-section">
        <h3 style="color: var(--text-main);"><?= __('preview', 'Bản xem trước') ?> (<span id="studentCount" style="color: var(--primary-color);">0</span> <?= __('students', 'học sinh') ?>)</h3>
        
        <div class="win-table-wrapper">
            <table class="win-table">
                <thead>
                    <tr>
                        <th><?= __('stt', 'STT') ?></th>
                        <th><?= __('student_code_sbd', 'Mã HS / SBD') ?></th>
                        <th><?= __('full_name', 'Họ và Tên') ?></th>
                        <th><?= __('class', 'Lớp') ?></th>
                        <th><?= __('dob', 'Ngày sinh') ?></th>
                    </tr>
                </thead>
                <tbody id="tableBody"></tbody>
            </table>
        </div>

        <div style="display: flex; gap: 15px;">
            <button class="win-btn win-btn-primary" id="btnSubmit" style="flex: 1; padding: 12px; font-size: 15px;">
                <i class="fas fa-save"></i> <?= __('start_import_db', 'Bắt đầu Nhập vào Database') ?>
            </button>
            <button class="win-btn win-btn-danger" id="btnReset" style="padding: 12px; font-size: 15px;">
                <i class="fas fa-radiation"></i> <?= __('clean_db', 'Làm sạch Database') ?>
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    // SPA LIFECYCLE: Khởi tạo các biến và Event Listener
    window.pageInit = function() {
        const fileInput = document.getElementById('fileInput');
        const dropZone = document.getElementById('dropZone');
        const btnSubmit = document.getElementById('btnSubmit');
        const btnReset = document.getElementById('btnReset');
        
        // Kéo thả hiệu ứng
        fileInput.addEventListener('dragenter', () => dropZone.classList.add('dragover'));
        fileInput.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        fileInput.addEventListener('drop', () => dropZone.classList.remove('dragover'));

        // Xử lý khi chọn file (Preview)
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;

            document.getElementById('previewBox').style.display = 'none';
            document.getElementById('loadingBox').style.display = 'block';

            let fd = new FormData(); 
            fd.append('action', 'preview');
            fd.append('file', file);
            
            fetch('api/import_students_api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                document.getElementById('loadingBox').style.display = 'none';
                if(data.status === 'success') {
                    document.getElementById('previewBox').style.display = 'block';
                    document.getElementById('studentCount').innerText = data.total_rows;
                    let html = '';
                    data.data.forEach((row, index) => {
                        html += `<tr>
                                    <td><b>${row.thuylinh !== null && row.thuylinh !== undefined ? row.thuylinh : (index + 1)}</b></td>
                                    <td><b>${row.code}</b></td>
                                    <td>${row.name}</td>
                                    <td><span style="background: var(--bg-input); padding: 3px 8px; border-radius: 4px;">${row.class_name}</span></td>
                                    <td>${row.dob}</td>
                                 </tr>`;
                    });
                    document.getElementById('tableBody').innerHTML = html;
                } else {
                    alert((window.LANG && window.LANG.error_prefix || "LỖI: ") + data.msg);
                    fileInput.value = ''; 
                }
            })
            .catch(err => { 
                alert((window.LANG && window.LANG.conn_err_prefix || "Lỗi kết nối: ") + err); 
                document.getElementById('loadingBox').style.display = 'none'; 
            });
        });

        // Nút Import
        btnSubmit.addEventListener('click', function() {
            const file = fileInput.files[0];
            if (!file) return alert(window.LANG && window.LANG.no_file || "Chưa có file!");

            const oldHtml = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';
            this.disabled = true;

            let fd = new FormData();
            fd.append('action', 'import');
            fd.append('file', file);

            fetch('api/import_students_api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                this.innerHTML = oldHtml;
                this.disabled = false;
                if(d.status === 'success') {
                    if(typeof Toastify !== 'undefined') Toastify({text: "✅ Đã nạp thành công!", style:{background:"#10b981"}}).showToast();
                    document.getElementById('previewBox').style.display = 'none';
                    fileInput.value = '';
                } else {
                    alert((window.LANG && window.LANG.load_err_prefix || "Lỗi nạp dữ liệu: ") + d.msg);
                }
            }).catch(e => { alert(window.LANG && window.LANG.network_err || "Lỗi mạng!"); this.innerHTML = oldHtml; this.disabled = false; });
        });

        // Nút Xóa sạch Database (Dùng WinUI.confirm từ hệ thống mới)
        btnReset.addEventListener('click', function() {
            if(window.WinUI && window.WinUI.confirm) {
                window.WinUI.confirm(window.LANG && window.LANG.danger_warning || "Cảnh báo nguy hiểm", window.LANG && window.LANG.delete_all_warning || "Hành động này sẽ XÓA SẠCH toàn bộ học sinh và lớp học trong CSDL. Bạn có chắc chắn?", function() {
                    executeReset();
                });
            } else {
                if(confirm(window.LANG && window.LANG.delete_all_confirm || "XÓA SẠCH TOÀN BỘ DỮ LIỆU?")) executeReset();
            }
        });

        function executeReset() {
            let fd = new FormData(); fd.append('action', 'reset');
            fetch('api/import_students_api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.status === 'success') {
                    alert(window.LANG && window.LANG.db_cleaned || "Đã làm sạch cơ sở dữ liệu!");
                    document.getElementById('previewBox').style.display = 'none';
                    fileInput.value = '';
                } else alert((window.LANG && window.LANG.error_prefix_colon || "Lỗi: ") + d.msg);
            });
        }
    };

    // SPA LIFECYCLE: Dọn dẹp sự kiện rác khi chuyển trang
    window.pageDestroy = function() {
        const fileInput = document.getElementById('fileInput');
        if(fileInput) fileInput.replaceWith(fileInput.cloneNode(true)); // Gỡ listeners
        const btnSubmit = document.getElementById('btnSubmit');
        if(btnSubmit) btnSubmit.replaceWith(btnSubmit.cloneNode(true));
        const btnReset = document.getElementById('btnReset');
        if(btnReset) btnReset.replaceWith(btnReset.cloneNode(true));
    };
})();
</script>

<?php
if (!$isIframe) {
    include 'includes/footer.php';
}
?>