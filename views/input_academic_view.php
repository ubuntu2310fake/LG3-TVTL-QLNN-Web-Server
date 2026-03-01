<?php
include 'includes/header.php';
?>

<style>
    /* --- STYLE CHUNG --- */
    .win-card {
        background: var(--bg-card); 
        padding: 20px; /* Padding cho desktop */
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05); color: var(--text-main);
    }
    
    .header-section {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px;
        flex-wrap: wrap; gap: 15px;
    }

    .week-control {
        display: flex; align-items: center; gap: 10px;
        background: var(--bg-hover); padding: 5px 15px; border-radius: 20px; border: 1px solid var(--border-color);
    }

    /* --- TABLE DESKTOP --- */
    .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .rank-table { width: 100%; border-collapse: collapse; min-width: 600px; }
    .rank-table th, .rank-table td {
        padding: 12px 15px; border-bottom: 1px solid var(--border-color);
        text-align: center; vertical-align: middle;
    }
    .rank-table th {
        background-color: var(--bg-hover); color: var(--text-muted);
        font-weight: 600; text-transform: uppercase; font-size: 13px;
    }
    .win-input-sm {
        width: 80px; padding: 8px; text-align: center;
        border: 1px solid var(--border-color); border-radius: 6px;
        background: var(--bg-input); color: var(--text-main); font-weight: 600;
    }
    .win-input-sm:focus { border-color: var(--accent-color); outline: none; background: var(--bg-card); }

    /* --- MOBILE CARD VIEW --- */
    .mobile-list { display: none; } /* Mặc định ẩn trên PC */

    @media (max-width: 768px) {
        .table-responsive { display: none; } /* Ẩn bảng trên mobile */
        .mobile-list { display: flex; flex-direction: column; gap: 15px; }
        
        .mobile-item {
            background: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: 12px; padding: 15px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        .class-badge {
            background: var(--accent-color); color: white;
            padding: 5px 10px; border-radius: 8px; font-weight: 700; font-size: 14px;
            min-width: 60px; text-align: center;
        }
        .input-group { display: flex; flex-direction: column; gap: 5px; align-items: flex-end; }
        .input-row { display: flex; align-items: center; gap: 10px; }
        .input-label { font-size: 12px; color: var(--text-muted); }
        .win-input-sm { width: 70px; font-size: 14px; }
        
        .win-card { padding: 15px; }
        .header-section { flex-direction: column; align-items: flex-start; gap: 10px; }
        .week-control { width: 100%; justify-content: space-between; }
        .win-btn { width: 100%; justify-content: center; }
    }
</style>

<div class="win-card">
    <form id="academicForm">
        <div class="header-section">
            <h3 style="margin:0; color:var(--accent-color);"><i class="fas fa-edit"></i> Nhập Điểm Học Tập</h3>
            
            <div class="week-control">
                <span style="font-weight:600; font-size:14px;">Tuần:</span>
                <input type="number" id="viewWeek" name="week" value="<?= $view_week ?>" 
                       class="win-input" style="width:60px; text-align:center; margin:0; border:none; background:transparent; font-weight:bold; font-size:16px;">
            </div>
        </div>

        <div class="table-responsive">
            <table class="rank-table">
                <thead>
                    <tr>
                        <th style="text-align:left;">Lớp</th>
                        <th>Tổng Điểm</th>
                        <th>Số Tiết</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $c): ?>
                    <tr>
                        <td style="text-align:left; font-weight:700;">
                            Lớp <?= htmlspecialchars($c['name']) ?>
                            <input type="hidden" name="class_ids[]" value="<?= $c['id'] ?>">
                        </td>
                        <td>
                            <input type="number" step="0.01" name="score_<?= $c['id'] ?>" class="win-input-sm" placeholder="0">
                        </td>
                        <td>
                            <input type="number" name="count_<?= $c['id'] ?>" class="win-input-sm" placeholder="0">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mobile-list">
            <?php foreach ($classes as $c): ?>
            <div class="mobile-item">
                <div class="class-badge"><?= htmlspecialchars($c['name']) ?></div>
                <div class="input-group">
                    <div class="input-row">
                        <span class="input-label">Điểm:</span>
                        <input type="number" step="0.01" id="m_score_<?= $c['id'] ?>" 
                               oninput="syncInput('<?= $c['id'] ?>', 'score', this.value)"
                               class="win-input-sm" placeholder="0">
                    </div>
                    <div class="input-row">
                        <span class="input-label">Tiết:</span>
                        <input type="number" id="m_count_<?= $c['id'] ?>" 
                               oninput="syncInput('<?= $c['id'] ?>', 'count', this.value)"
                               class="win-input-sm" placeholder="0">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top:20px; text-align:right;">
            <button type="submit" class="win-btn" id="btnSave">
                <i class="fas fa-save"></i> LƯU DỮ LIỆU
            </button>
        </div>
    </form>
</div>

<script>
    // Dữ liệu cũ từ PHP
    const oldData = <?= json_encode($academic_data) ?>;

    // 1. Hàm đồng bộ giữa Mobile và PC input
    window.syncInput = function(classId, type, val) {
        // Tìm input tương ứng ở bảng PC và gán giá trị
        const pcInput = document.querySelector(`input[name="${type}_${classId}"]`);
        if (pcInput) pcInput.value = val;
    };

    // 2. Load dữ liệu cũ vào Form
    const loadOldData = () => {
        try {
            // Reset form trước
            document.getElementById('academicForm').reset();
            
            for (const [classId, val] of Object.entries(oldData)) {
                // Fill PC
                const pcScore = document.querySelector(`input[name="score_${classId}"]`);
                const pcCount = document.querySelector(`input[name="count_${classId}"]`);
                if (pcScore) pcScore.value = val.score;
                if (pcCount) pcCount.value = val.count;

                // Fill Mobile (nếu đang ẩn cũng fill để sync)
                const mbScore = document.getElementById(`m_score_${classId}`);
                const mbCount = document.getElementById(`m_count_${classId}`);
                if (mbScore) mbScore.value = val.score;
                if (mbCount) mbCount.value = val.count;
            }
        } catch (e) { console.error(e); }
    };
    
    // Khi đổi tuần -> Reload trang để lấy dữ liệu mới
    document.getElementById('viewWeek').addEventListener('change', function() {
        window.location.href = `input_academic.php?week=${this.value}`;
    });

    window.addEventListener('DOMContentLoaded', loadOldData);

    // 3. Xử lý lưu dữ liệu bằng AJAX
    document.getElementById('academicForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const btn = document.getElementById('btnSave');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';
        btn.disabled = true;

        const formData = new FormData(this);

        fetch('input_academic.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                if(typeof Toastify !== 'undefined') Toastify({text:"✅ " + data.msg, style:{background:"#10b981"}}).showToast();
                else alert("✅ " + data.msg); 
            } else {
                alert("❌ Lỗi: " + data.msg);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("❌ Có lỗi xảy ra khi kết nối tới server.");
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
</script>

<?php include 'includes/footer.php'; ?>