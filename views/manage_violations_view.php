<?php
// views/manage_violations_view.php
include 'includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/vn.js"></script>

<style>
    /* 1. BIẾN MÀU (LIGHT/DARK MODE) */
    :root {
        /* Mặc định (Sáng) */
        --tk-box-bg: #fdf4ff;
        --tk-box-border: #e879f9;
        --tk-title: #a21caf;
        --tk-text-muted: #64748b;
        --tk-input-bg: #ffffff;
        --tk-input-text: #334155;
        --tk-input-border: #cbd5e1;
        --tk-btn-bg: #f1f5f9;
        --tk-btn-border: #cbd5e1;
        --tk-btn-text: #334155;
        --tk-btn-hover: #e2e8f0;
        
        --card-bg: #ffffff;
        --card-border: #e2e8f0;
    }

    /* Dark Mode (Nếu web có class .dark hoặc attribute data-theme="dark") */
    [data-theme="dark"], body.dark, .dark-mode {
        --tk-box-bg: #4c1d95;
        --tk-box-border: #a21caf;
        --tk-title: #e879f9;
        --tk-text-muted: #94a3b8;
        --tk-input-bg: #1e293b;
        --tk-input-text: #f1f5f9;
        --tk-input-border: #475569;
        --tk-btn-bg: #334155;
        --tk-btn-border: #475569;
        --tk-btn-text: #f8fafc;
        --tk-btn-hover: #475569;

        --card-bg: #1e293b;
        --card-border: #334155;
    }

    /* CSS Cũ giữ nguyên & Tối ưu */
    .win-card h3 { font-size: 16px; font-weight: 700; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
    
    /* Grid cấu hình: Tự xuống dòng mềm mại hơn trên mobile */
    .config-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; }
    
    .code-editor { font-family: 'Consolas', 'Monaco', monospace; font-size: 13px; line-height: 1.5; color: #d63384; background: #f8f9fa; border: 1px solid #ced4da; border-radius: 4px; padding: 10px; width: 100%; height: 120px; resize: vertical; }
    .badge-scope { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
    .badge-gate { background: #e0f2fe; color: #0284c7; }
    .badge-class { background: #f3e8ff; color: #9333ea; }
    .table-actions .btn-icon { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; margin-left: 5px; border-radius: 4px; border: none; cursor: pointer; }
    .btn-edit { background: #e0e7ff; color: #4338ca; }
    .btn-delete { background: #fee2e2; color: #dc2626; }
    
    /* Ticker Box dùng biến */
    .ticker-box {
        background: var(--tk-box-bg);
        border: 1px dashed var(--tk-box-border);
        padding: 12px; border-radius: 6px; margin-bottom: 15px;
        transition: all 0.3s ease;
    }
    .ticker-title { margin: 0 0 8px; font-size: 13px; color: var(--tk-title); display: flex; align-items: center; gap: 5px; font-weight: bold; border-bottom: 1px dashed var(--tk-box-border); padding-bottom: 5px; }
    
    .ticker-input-wrap { display: flex; gap: 5px; }
    .ticker-input {
        flex: 1; 
        background: var(--tk-input-bg);
        color: var(--tk-input-text);
        border: 1px solid var(--tk-input-border);
        border-radius: 4px; padding: 8px; font-size: 13px;
        transition: all 0.2s;
    }
    .ticker-input:focus { border-color: var(--tk-title); outline: none; }
    
    .btn-dot {
        background: var(--tk-btn-bg);
        border: 1px solid var(--tk-btn-border);
        color: var(--tk-btn-text);
        padding: 0 12px; border-radius: 4px; cursor: pointer;
        font-weight: bold; font-size: 16px; line-height: 1;
        transition: all 0.2s;
    }

    /* Bố cục chính: Mặc định PC */
    .main-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }

    .timeline-group { margin-bottom: 12px; }
    .timeline-label { font-size: 13px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px; }

    /* --- RESPONSIVE MOBILE --- */
    @media (max-width: 768px) {
        /* 1. Chuyển Layout thành 1 cột */
        .main-grid { grid-template-columns: 1fr; }
        
        /* 2. Modal full màn hình */
        .modal-content { width: 95% !important; margin: 10px auto; }
        
        /* 3. Bảng chuyển thành Card */
        .rank-table thead { display: none; }
        .rank-table tr {
            display: block;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .rank-table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
            text-align: right;
            font-size: 13px;
        }
        .rank-table td:last-child { border-bottom: none; padding-bottom: 0; }
        .rank-table td::before {
            content: attr(data-label);
            font-weight: bold;
            color: var(--text-muted);
            text-align: left;
            margin-right: 15px;
        }
        
        /* Nút thao tác to hơn để dễ bấm */
        .table-actions .btn-icon { width: 36px; height: 36px; font-size: 14px; }
    }
</style>

<div class="dashboard-container" style="max-width: 1200px; margin: 0 auto; padding-bottom: 50px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0; color: var(--text-dark); display: flex; align-items: center; gap: 10px; font-size: 18px;">
            <i class="fas fa-gavel" style="color: var(--danger-color);"></i> QUẢN LÝ VI PHẠM
        </h2>
    </div>

    <div class="main-grid">
        
        <div style="display: flex; flex-direction: column; gap: 20px;">
            
            <div class="win-card">
                <h3><i class="fas fa-calendar-alt"></i> Thời Gian Hệ Thống</h3>
                <form class="ajax-form">
                    <input type="hidden" name="action" value="update_timeline">
                    
                    <div class="timeline-group">
                        <label class="timeline-label">Khai giảng Học kỳ 1:</label>
                        <input type="text" id="start_date_picker" name="start_date" value="<?= htmlspecialchars($current_start_date) ?>" class="win-input" required placeholder="Chọn ngày bắt đầu...">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 12px;">
                        <div class="timeline-group" style="margin-bottom: 0;">
                            <label class="timeline-label">Kết thúc HK1:</label>
                            <input type="text" id="hk1_picker" name="end_hk1_date" value="<?= htmlspecialchars($end_hk1_date) ?>" class="win-input" placeholder="Mặc định: 31/01">
                        </div>
                        <div class="timeline-group" style="margin-bottom: 0;">
                            <label class="timeline-label">Kết thúc Năm học:</label>
                            <input type="text" id="year_picker" name="end_year_date" value="<?= htmlspecialchars($end_year_date) ?>" class="win-input" placeholder="Mặc định: 31/05">
                        </div>
                    </div>

                    <div class="timeline-group">
                        <label class="timeline-label">
                            Ngày nghỉ (Không tính tuần): 
                            <i class="fas fa-info-circle" title="Các ngày này sẽ bị trừ ra khi tính tuần học. VD: Nghỉ Tết, Nghỉ lễ..." style="cursor: help;"></i>
                        </label>
                        <input type="text" id="excluded_picker" name="excluded_dates" value="<?= htmlspecialchars($excluded_dates_string) ?>" class="win-input" placeholder="Chọn nhiều ngày...">
                    </div>

                    <div style="background: var(--bg-hover); padding: 10px; border-radius: 6px; font-size: 13px;">
                        Hiện tại: <strong style="color: var(--primary); font-size: 15px;">Tuần <?= $current_week ?></strong>
                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                            (Đã trừ <?= count($excluded_list) ?> ngày nghỉ)
                        </div>
                    </div>

                    <button type="submit" class="win-btn" style="width: 100%; margin-top: 10px; justify-content: center;">
                        <i class="fas fa-sync-alt"></i> Cập nhật Thời gian
                    </button>
                </form>
            </div>

            <div class="win-card" style="border-top: 4px solid #8b5cf6;">
                <h3 style="color: #8b5cf6;"><i class="fas fa-cogs"></i> Cấu Hình & Ticker</h3>
                <form class="ajax-form">
                    <input type="hidden" name="action" value="save_rules">
                    
                    <div class="ticker-box">
                        <h4 class="ticker-title">
                            <i class="fas fa-bullhorn"></i> Thông báo chạy Header
                        </h4>
                        <div class="ticker-group" style="margin-bottom: 0;">
                            <label style="font-size: 12px; font-weight: bold; color: var(--text-muted); display:block; margin-bottom:4px;">Ticker Trường (Riêng):</label>
                            <div class="ticker-input-wrap">
                                <input type="text" id="tk_sch" name="ticker_school" class="ticker-input" value="<?= htmlspecialchars($ticker_school) ?>" placeholder="Tin riêng của trường...">
                                <button type="button" class="btn-dot" onclick="addDot('tk_sch')" title="Chèn dấu chấm giữa">·</button>
                            </div>
                        </div>
                    </div>

                    <div class="config-grid" style="margin-bottom: 15px;">
                        <div>
                            <label style="font-size: 12px; font-weight: bold;">Điểm Gốc</label>
                            <input type="number" step="0.5" name="max_base" value="<?= $rules['max_base'] ?>" class="win-input" required>
                        </div>
                        <div>
                            <label style="font-size: 12px; font-weight: bold;">Hệ số chia</label>
                            <input type="number" step="0.1" name="divisor" value="<?= $rules['divisor'] ?>" class="win-input" required>
                        </div>
                        <div>
                            <label style="font-size: 12px; font-weight: bold;">% Học Tập</label>
                            <input type="number" step="0.1" max="1" min="0" name="weight_aca" value="<?= $rules['weight_aca'] ?>" class="win-input" required>
                        </div>
                        <div>
                            <label style="font-size: 12px; font-weight: bold;">% Nền Nếp</label>
                            <input type="number" step="0.1" max="1" min="0" name="weight_con" value="<?= $rules['weight_con'] ?>" class="win-input" required>
                        </div>
                    </div>

                    <div style="margin-bottom: 10px;">
                        <label style="font-size: 12px; font-weight: bold; color: var(--danger-color);">
                            <i class="fas fa-code"></i> Cấu trúc Cột chấm (JSON)
                        </label>
                        <textarea name="class_cols" class="code-editor" required><?= $class_cols_json ?></textarea>
                    </div>

                    <button type="submit" class="win-btn" style="width: 100%; background: #8b5cf6; justify-content: center;">
                        <i class="fas fa-save"></i> Lưu Cấu Hình
                    </button>
                </form>
            </div>
        </div>

        <div class="win-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3><i class="fas fa-list-ul"></i> Danh Mục Lỗi</h3>
                <button onclick="openModal('add')" class="win-btn win-btn-primary" style="padding: 6px 12px; font-size: 13px;">
                    <i class="fas fa-plus"></i> <span class="hidden-xs">Thêm Lỗi</span>
                </button>
            </div>

            <div class="table-responsive">
                <table class="rank-table">
                    <thead>
                        <tr>
                            <th>Nội dung lỗi</th>
                            <th width="80" class="text-center">Mã</th>
                            <th width="80" class="text-center">Điểm</th>
                            <th width="100" class="text-center">Phạm vi</th>
                            <th width="80" class="text-right">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($violations as $v): ?>
                        <tr>
                            <td data-label="Nội dung"><?= htmlspecialchars($v['content']) ?></td>
                            <td data-label="Mã viết tắt" class="text-center"><code style="background: var(--bg-hover); padding:2px 4px; border-radius:3px; color:#c026d3;"><?= htmlspecialchars($v['short_code']) ?></code></td>
                            <td data-label="Điểm trừ" class="text-center" style="font-weight:bold; color:var(--danger-color);">-<?= $v['points'] ?></td>
                            <td data-label="Phạm vi" class="text-center"><span class="badge-scope <?= $v['scope'] === 'GATE' ? 'badge-gate' : 'badge-class' ?>"><?= $v['scope'] === 'GATE' ? 'Cổng' : 'Lớp' ?></span></td>
                            <td data-label="Thao tác" class="text-right table-actions">
                                <button class="btn-icon btn-edit" onclick='openModal("edit", <?= json_encode($v) ?>)'><i class="fas fa-edit"></i></button>
                                <button class="btn-icon btn-delete" onclick="deleteViolation(<?= $v['id'] ?>)"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="violationModal" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 450px;">
        <h3 id="modalTitle" style="margin-top: 0; margin-bottom: 15px;">Thêm Lỗi Vi Phạm</h3>
        <form class="ajax-form" id="violationForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="violationId">
            <div class="form-group"><label>Nội dung lỗi:</label><input type="text" name="content" id="vContent" class="win-input" required></div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group"><label>Mã (Code):</label><input type="text" name="short_code" id="vCode" class="win-input" required></div>
                <div class="form-group"><label>Điểm trừ:</label><input type="number" step="0.1" name="points" id="vPoints" class="win-input" required></div>
            </div>
            <div class="form-group"><label>Phạm vi áp dụng:</label><select name="scope" id="vScope" class="win-input"><option value="GATE">Cổng trường</option><option value="CLASS">Trong lớp</option></select></div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="win-btn win-btn-secondary" onclick="closeModal()">Hủy</button>
                <button type="submit" class="win-btn win-btn-primary">Lưu Lại</button>
            </div>
        </form>
    </div>
</div>

<script>
    // 1. DATE PICKER - NGÀY BẮT ĐẦU
    flatpickr("#start_date_picker", {
        dateFormat: "Y-m-d",
        locale: "vn"
    });

    // 2. DATE PICKER - NGÀY NGHỈ (MULTIPLE)
    flatpickr("#excluded_picker", {
        mode: "multiple",      // Cho phép chọn nhiều ngày
        dateFormat: "Y-m-d",
        locale: "vn",
        conjunction: ", "      // Nối các ngày bằng dấu phẩy
    });
    flatpickr("#hk1_picker", { dateFormat: "Y-m-d", locale: "vn" });
    flatpickr("#year_picker", { dateFormat: "Y-m-d", locale: "vn" });

    // 3. CHÈN DẤU CHẤM GIỮA (TICKER)
    function addDot(inputId) {
        const input = document.getElementById(inputId);
        const char = " · ";
        if (input.selectionStart || input.selectionStart == '0') {
            const startPos = input.selectionStart;
            const endPos = input.selectionEnd;
            input.value = input.value.substring(0, startPos) + char + input.value.substring(endPos, input.value.length);
            input.selectionStart = startPos + char.length;
            input.selectionEnd = startPos + char.length;
        } else {
            input.value += char;
        }
        input.focus();
    }

    // 4. XỬ LÝ AJAX SUBMIT
    document.querySelectorAll('.ajax-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const oldText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';
            btn.disabled = true;

            const fd = new FormData(this);
            fetch('manage_violations.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    btn.innerHTML = oldText; btn.disabled = false;
                    if(d.status === 'success') {
                        Toastify({ text: "✅ " + d.msg, style: { background: "#10b981" } }).showToast();
                        // Nếu lưu Timeline hoặc Lỗi thì reload để cập nhật số tuần hiển thị
                        if (this.querySelector('input[name="start_date"]') || this.id === 'violationForm') {
                             setTimeout(() => location.reload(), 1000);
                        }
                    } else {
                        alert("❌ " + d.msg);
                    }
                })
                .catch(err => {
                    alert("❌ Lỗi kết nối!");
                    btn.innerHTML = oldText; btn.disabled = false;
                });
        });
    });

    // CÁC SCRIPT KHÁC (Modal, Delete...) GIỮ NGUYÊN
    const modal = document.getElementById('violationModal');
    const form = document.getElementById('violationForm');

    function openModal(mode, data = null) {
        modal.style.display = 'flex';
        if (mode === 'add') {
            document.getElementById('modalTitle').innerText = "Thêm Lỗi Mới";
            document.getElementById('formAction').value = "add";
            form.reset();
        } else {
            document.getElementById('modalTitle').innerText = "Sửa Lỗi";
            document.getElementById('formAction').value = "edit";
            document.getElementById('violationId').value = data.id;
            document.getElementById('vContent').value = data.content;
            document.getElementById('vCode').value = data.short_code;
            document.getElementById('vPoints').value = data.points;
            document.getElementById('vScope').value = data.scope;
        }
    }

    function closeModal() { modal.style.display = 'none'; }

    function deleteViolation(id) {
        if(!confirm("Bạn có chắc chắn muốn xóa lỗi này không?")) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        fetch('manage_violations.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.status === 'success') {
                    Toastify({ text: "✅ Đã xóa!", style: { background: "#10b981" } }).showToast();
                    setTimeout(() => location.reload(), 500);
                } else {
                    alert("❌ " + d.msg);
                }
            });
    }

    modal.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });
</script>

<?php include 'includes/footer.php'; ?>