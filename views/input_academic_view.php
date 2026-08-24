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
    .win-input-sm.pseudo-focus { background-color: var(--bg-hover); font-weight: 700; color: var(--accent-color); border-radius: 6px; box-shadow: 0 0 0 2px var(--accent-color); }

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
            background: #005fba; color: #ffffff !important;
            padding: 5px 10px; border-radius: 8px; font-weight: 700; font-size: 14px;
            min-width: 60px; text-align: center;
        }
        .input-group { display: flex; flex-direction: column; gap: 5px; align-items: flex-end; }
        .input-row { display: flex; align-items: center; gap: 10px; }
        .input-label { font-size: 12px; color: var(--text-muted); }
        .win-input-sm { width: 70px; font-size: 14px; }
        
        .win-card { padding: 15px; padding-bottom: 200px; } /* Thêm padding-bottom để tránh bàn phím che nút Lưu */
        .header-section { flex-direction: column; align-items: flex-start; gap: 10px; }
        .week-control { width: 100%; justify-content: space-between; }
        .win-btn { width: 100%; justify-content: center; }
    }

    /* BÀN PHÍM ẢO TRÊN IOS/ANDROID */
    .ios-keypad-overlay {
        position: fixed; bottom: 0; left: 0; right: 0;
        background: var(--bg-input); z-index: 2147483647; padding: 6px; padding-bottom: env(safe-area-inset-bottom);
        transform: translateY(100%); transition: transform 0.2s cubic-bezier(0.2, 0.8, 0.2, 1);
        box-shadow: 0 -4px 10px rgba(0,0,0,0.2); display: none; border-top: 1px solid var(--border-color);
    }
    .ios-keypad-overlay.active { transform: translateY(0); }
    .keypad-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
    
    .key-btn {
        background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 5px; padding: 12px 0;
        font-size: 24px; font-weight: 500; color: var(--text-main);
        cursor: pointer; user-select: none; transition: 0.1s;
    }
    .key-btn:active { background-color: var(--bg-hover); transform: scale(0.92); }
    .key-btn.action-del { background: var(--bg-hover); }
    .key-btn.action-enter { background: var(--accent-color); color: var(--bg-card); font-weight: 700; font-size: 18px; }
</style>

<div class="win-card">
    <form id="academicForm">
        <div class="header-section">
            <h3 style="margin:0; color:var(--accent-color);"><i class="fas fa-edit" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập Điểm Học Tập' : 'Enter Academic Scores') ?></h3>
            
            <div class="week-control">
                <span style="font-weight:600; font-size:14px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần:' : 'Week:') ?></span>
                <input type="number" id="viewWeek" name="week" value="<?= $view_week ?>" 
                       class="win-input" style="width:60px; text-align:center; margin:0; border:none; background:transparent; font-weight:bold; font-size:16px;" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần:' : 'Week:') ?>">
            </div>
        </div>

        <div class="table-responsive">
            <table class="rank-table">
                <thead>
                    <tr>
                        <th style="text-align:left;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp' : 'Class') ?></th>
                        <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tổng Điểm' : 'Total Score') ?></th>
                        <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Số Tiết' : 'Lesson Count') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $c): ?>
                    <tr>
                        <td style="text-align:left; font-weight:700;">
                            <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp' : 'Class') ?> <?= htmlspecialchars($c['name']) ?>
                            <input type="hidden" name="class_ids[]" value="<?= $c['id'] ?>">
                        </td>
                        <td>
                            <input type="text" inputmode="decimal" name="score_<?= $c['id'] ?>" class="win-input-sm" placeholder="0" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tổng Điểm' : 'Total Score') ?> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp' : 'Class') ?> <?= htmlspecialchars($c['name']) ?>">
                        </td>
                        <td>
                            <input type="text" inputmode="numeric" name="count_<?= $c['id'] ?>" class="win-input-sm" placeholder="0" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Số Tiết' : 'Lesson Count') ?> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp' : 'Class') ?> <?= htmlspecialchars($c['name']) ?>">
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
                        <span class="input-label"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm:' : 'Score:') ?></span>
                        <input type="text" inputmode="decimal" id="m_score_<?= $c['id'] ?>" 
                               oninput="syncInput('<?= $c['id'] ?>', 'score', this.value)"
                               class="win-input-sm" placeholder="0" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm:' : 'Score:') ?> <?= htmlspecialchars($c['name']) ?>">
                    </div>
                    <div class="input-row">
                        <span class="input-label"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tiết:' : 'Lessons:') ?></span>
                        <input type="text" inputmode="numeric" id="m_count_<?= $c['id'] ?>" 
                               oninput="syncInput('<?= $c['id'] ?>', 'count', this.value)"
                               class="win-input-sm" placeholder="0" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tiết:' : 'Lessons:') ?> <?= htmlspecialchars($c['name']) ?>">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top:20px; text-align:right;">
            <button type="submit" class="win-btn" id="btnSave">
                <i class="fas fa-save" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'LƯU DỮ LIỆU' : 'SAVE DATA') ?>
            </button>
        </div>
    </form>
</div>

<div id="customKeypad" class="ios-keypad-overlay">
    <div class="keypad-grid">
        <button class="key-btn" data-key="1">1</button>
        <button class="key-btn" data-key="2">2</button>
        <button class="key-btn" data-key="3">3</button>
        <button class="key-btn" data-key="4">4</button>
        <button class="key-btn" data-key="5">5</button>
        <button class="key-btn" data-key="6">6</button>
        <button class="key-btn" data-key="7">7</button>
        <button class="key-btn" data-key="8">8</button>
        <button class="key-btn" data-key="9">9</button>
        <button class="key-btn" data-key=".">.</button>
        <button class="key-btn" data-key="0">0</button>
        <button class="key-btn action-del" data-key="DEL" aria-label="Xóa"><i class="fas fa-backspace" aria-hidden="true"></i></button>
    </div>
    <div style="margin-top: 6px;">
        <button class="key-btn action-enter" style="width: 100%;" data-key="ENTER"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'XONG' : 'DONE') ?></button>
    </div>
</div>

<script>
    // Dùng window.oldData để chống lỗi (window.LANG && window.LANG.txt_6288073592199558215 || "khai báo lại") 100% khi AJAX nạp trang nhiều lần
    window.oldData = <?= json_encode($academic_data) ?>;

    window.syncInput = function(classId, type, val) {
        var pcInput = document.querySelector(`input[name="${type}_${classId}"]`);
        if (pcInput) pcInput.value = val;
    };

    window.loadOldData = function() {
        try {
            var form = document.getElementById('academicForm');
            if(form) form.reset();
            
            for (var classId in window.oldData) {
                var val = window.oldData[classId];
                var pcScore = document.querySelector(`input[name="score_${classId}"]`);
                var pcCount = document.querySelector(`input[name="count_${classId}"]`);
                if (pcScore) pcScore.value = val.score;
                if (pcCount) pcCount.value = val.count;

                var mbScore = document.getElementById(`m_score_${classId}`);
                var mbCount = document.getElementById(`m_count_${classId}`);
                if (mbScore) mbScore.value = val.score;
                if (mbCount) mbCount.value = val.count;
            }
        } catch (e) { console.error(e); }
    };
    
    setTimeout(window.loadOldData, 100);

    var initAcademicForm = function() {
        var viewWeek = document.getElementById('viewWeek');
        if(viewWeek) {
            viewWeek.onchange = function() {
                if(typeof loadPage === 'function') loadPage(`input_academic.php?week=${this.value}`);
                else window.location.href = `input_academic.php?week=${this.value}`;
            };
        }

        var form = document.getElementById('academicForm');
        if(form) {
            form.onsubmit = function(e) {
                e.preventDefault();
                
                var sendFormData = new FormData();
                sendFormData.append('week', document.getElementById('viewWeek').value);
                
                var hasChanges = false;
                var classIds = document.querySelectorAll('input[name="class_ids[]"]');
                classIds.forEach(function(input) {
                    var cid = input.value;
                    var scoreInput = document.querySelector(`input[name="score_${cid}"]`);
                    var countInput = document.querySelector(`input[name="count_${cid}"]`);
                    
                    var newScore = scoreInput ? scoreInput.value.trim() : '';
                    var newCount = countInput ? countInput.value.trim() : '';
                    
                    var nv_s = newScore === '' ? 0 : parseFloat(newScore) || 0;
                    var ov_s = window.oldData[cid] ? parseFloat(window.oldData[cid].score) || 0 : 0;
                    
                    var nv_c = newCount === '' ? 0 : parseInt(newCount, 10) || 0;
                    var ov_c = window.oldData[cid] ? parseInt(window.oldData[cid].count, 10) || 0 : 0;
                    
                    if (Math.abs(nv_s - ov_s) > 0.0001 || nv_c !== ov_c) {
                        sendFormData.append('class_ids[]', cid);
                        sendFormData.append('score_' + cid, newScore);
                        sendFormData.append('count_' + cid, newCount);
                        hasChanges = true;
                    }
                });
                
                if (!hasChanges) {
                    if (typeof Toastify !== 'undefined') Toastify({text: <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'⚠️ Không có dữ liệu lớp nào bị thay đổi để lưu!'" : "'⚠️ No class data has been changed to save!'") ?>, style:{background:"#f59e0b"}}).showToast();
                    return;
                }

                var btn = document.getElementById('btnSave');
                var originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Đang lưu...'" : "'Saving...'") ?>;
                btn.disabled = true;

                fetch('input_academic.php', { method: 'POST', body: sendFormData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        if(typeof Toastify !== 'undefined') Toastify({text:"✅ " + data.msg, style:{background:"#10b981"}}).showToast();
                        else alert("✅ " + data.msg); 
                        
                        // Cập nhật lại oldData để ngầm hiểu là đã lưu
                        classIds.forEach(function(input) {
                            var cid = input.value;
                            var scoreInput = document.querySelector(`input[name="score_${cid}"]`);
                            var countInput = document.querySelector(`input[name="count_${cid}"]`);
                            var updatedScore = scoreInput ? scoreInput.value.trim() : '';
                            var updatedCount = countInput ? countInput.value.trim() : '';
                            if (!window.oldData[cid]) window.oldData[cid] = {score: 0, count: 0};
                            if (sendFormData.has(`score_${cid}`)) {
                                window.oldData[cid].score = updatedScore === '' ? 0 : parseFloat(updatedScore) || 0;
                                window.oldData[cid].count = updatedCount === '' ? 0 : parseInt(updatedCount, 10) || 0;
                            }
                        });
                        
                    } else { alert(<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Lỗi:'" : "'Error:'") ?> + data.msg); }
                })
                .catch(error => { console.error('Error:', error); alert(<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'❌ Có lỗi xảy ra khi kết nối tới server.'" : "'❌ An error occurred connecting to the server.'") ?>); })
                .finally(() => { btn.innerHTML = originalText; btn.disabled = false; });
            };
        }

        window.isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        
        window.initCustomKeypad = function() {
            if (!window.isMobile) return;
            const inputs = document.querySelectorAll('.win-input-sm'); 
            window.keypadEl = document.getElementById('customKeypad'); 
            window.chatBtn = document.getElementById('chatBubbleBtn');
            if(window.keypadEl) window.keypadEl.style.display = 'block';
            inputs.forEach(inp => {
                inp.readOnly = true; 
                inp.onclick = function(e) {
                    e.preventDefault(); e.stopPropagation(); 
                    document.querySelectorAll('.win-input-sm.pseudo-focus').forEach(el => el.classList.remove('pseudo-focus'));
                    window.activeInput = this; 
                    this.classList.add('pseudo-focus'); 
                    window.keypadEl.classList.add('active'); 
                    if(window.chatBtn) window.chatBtn.style.display = 'none'; 
                    window.resetOnNextInput = true;
                    setTimeout(() => { this.scrollIntoView({behavior: "smooth", block: "center"}); }, 100);
                };
            });
        };

        window.handleOutsideClick = function(e) {
            if (window.keypadEl && !window.keypadEl.contains(e.target) && !e.target.classList.contains('win-input-sm')) {
                window.keypadEl.classList.remove('active'); 
                if(window.chatBtn) window.chatBtn.style.display = ''; 
                if(window.activeInput) { window.activeInput.classList.remove('pseudo-focus'); window.activeInput = null; }
            }
        };

        window.kpPress = function(key) {
            if (!window.activeInput) return;
            if (key === 'ENTER') { 
                window.keypadEl.classList.remove('active'); 
                window.activeInput.classList.remove('pseudo-focus'); 
                if(window.chatBtn) window.chatBtn.style.display = ''; 
                window.activeInput = null; 
                return; 
            }
            let currentVal = window.activeInput.value.toString();
            if (window.resetOnNextInput) { 
                if (key === 'DEL') window.activeInput.value = ''; 
                else if (key === '.') window.activeInput.value = '0.'; 
                else window.activeInput.value = key; 
                window.resetOnNextInput = false; 
            } else { 
                if (key === 'DEL') window.activeInput.value = currentVal.slice(0, -1); 
                else if (key === '.') { 
                    if (!currentVal.includes('.')) window.activeInput.value = currentVal + '.'; 
                } else { 
                    if (currentVal === '0' && key !== '.') window.activeInput.value = key; 
                    else window.activeInput.value = currentVal + key; 
                } 
            }
            window.activeInput.dispatchEvent(new Event('input', { bubbles: true }));
            window.activeInput.focus();
        };

        if(window.isMobile) window.initCustomKeypad();
        
        const keypad = document.getElementById('customKeypad');
        if (keypad) {
            keypad.querySelectorAll('.key-btn').forEach(btn => {
                btn.onclick = function(e) { e.preventDefault(); e.stopPropagation(); window.kpPress(this.dataset.key); };
            });
        }
        document.removeEventListener('click', window.handleOutsideClick);
        document.addEventListener('click', window.handleOutsideClick);
    };
    setTimeout(initAcademicForm, 100);
</script>

<?php include 'includes/footer.php'; ?>