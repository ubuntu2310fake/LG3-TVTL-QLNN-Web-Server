<?php 
include 'includes/header.php';

// =================================================================
// [GÀI BIẾN ĐỘNG] - ĐỌC CẤU HÌNH CỘT ĐIỂM TỪ DATABASE
// =================================================================
$stmtClsCols = $pdo->query("SELECT short_code, max_penalty_points FROM violation_type WHERE scope = 'CLASS' ORDER BY id ASC");
$dbScores = [];
while ($row = $stmtClsCols->fetch(PDO::FETCH_ASSOC)) {
    $dbScores[$row['short_code']] = (float)$row['max_penalty_points'];
}
$baseScores = [];
$defaultOrder = ["SS", "VS", "CSVC", "TB", "XE", "DP", "SV", "THE", "DT"];
foreach ($defaultOrder as $code) {
    if (isset($dbScores[$code])) {
        $baseScores[$code] = $dbScores[$code];
        unset($dbScores[$code]);
    }
}
foreach ($dbScores as $code => $max) {
    $baseScores[$code] = $max;
}
if (empty($baseScores)) {
    $baseScores = ["SS"=>1, "VS"=>1, "CSVC"=>1, "TB"=>1, "XE"=>1, "DP"=>2, "SV"=>1, "THE"=>1, "DT"=>1];
}

// Tính số lượng cột để span footer cho chuẩn (Thứ + Nghỉ + các cột vi phạm)
$colspan = count($baseScores) + 2; 
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
    /* =========================================
       1. CẤU HÌNH GIAO DIỆN CHUẨN AMOLED
       Đã gỡ bỏ sạch sẽ các đoạn :root tự chế
       ========================================= */
    
    .dashboard-container { max-width: 1200px; margin: 0 auto; padding-bottom: 120px; }

    /* CARD & HEADER */
    .modern-card { background: var(--bg-card); border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 24px; margin-top: 20px; border: 1px solid var(--border-color); }
    .header-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 20px; flex-wrap: nowrap; }
    .page-title { font-size: 20px; font-weight: 800; color: var(--text-main); text-transform: uppercase; white-space: nowrap; display: flex; align-items: center; gap: 10px; }
    .page-title i { color: var(--primary-color); }
    
    .control-group { display: flex; gap: 8px; align-items: center; justify-content: flex-end; flex-grow: 1; }
    
    .custom-select-container .select-selected,
    .week-control,
    .btn-save {
        height: 40px; box-sizing: border-box; border-radius: 8px; font-size: 14px;
        display: flex; align-items: center; margin: 0;
    }

    /* Dropdown */
    .custom-select-container { position: relative; min-width: 180px; }
    .select-selected {
        background-color: var(--bg-card); border: 1px solid var(--border-color); padding: 0 12px;
        justify-content: space-between; color: var(--text-main); cursor: pointer; width: 100%;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .select-items {
        position: absolute; top: 105%; left: 0; right: 0; z-index: 1000;
        background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px;
        max-height: 300px; overflow-y: auto; display: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .select-selected:active, .select-selected.active { border-color: var(--primary-color); }
    .select-arrow { width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid var(--text-muted); transition: 0.2s; flex-shrink: 0; }
    .select-selected.active .select-arrow { transform: rotate(180deg); }
    .select-items div { padding: 10px 15px; cursor: pointer; border-bottom: 1px solid var(--border-color); font-size: 14px; color: var(--text-main); }
    .select-items div:hover { background: var(--bg-hover); color: var(--primary-color); font-weight: 600; }

    /* Ô nhập Tuần */
    .week-control { 
        background: var(--bg-card); border: 1px solid var(--border-color); padding: 0 10px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .week-input { 
        width: 40px; border: none; font-size: 15px; font-weight: 700; color: var(--primary-color); 
        text-align: center; outline: none; background: transparent; padding: 0; margin: 0; height: 100%;
    }
    .week-label { font-size: 13px; color: var(--text-muted); font-weight: 600; margin-right: 5px; white-space: nowrap; }
    
    /* Nút Lưu - FIX Màu chữ ăn theo var(--bg-card) */
    .btn-save { 
        background: var(--primary-color); color: var(--bg-card); border: none; 
        padding: 0 20px; font-weight: 700; cursor: pointer; gap: 8px; white-space: nowrap; 
        box-shadow: 0 2px 4px rgba(0,0,0,0.1); justify-content: center;
    }

    /* BẢNG ĐIỂM */
    .table-container { width: 100%; border-radius: 8px; overflow: hidden; border: 1px solid var(--border-color); }
    .paper-table { width: 100%; border-collapse: collapse; background: var(--bg-card); }
    .paper-table th, .paper-table td { border: 1px solid var(--border-color); padding: 12px 10px; text-align: center; vertical-align: middle; }
    .paper-table th { background-color: var(--bg-hover); color: var(--text-muted); font-weight: 700; font-size: 13px; text-transform: uppercase; }
    .cell-total { color: var(--primary-color); font-weight: 800; font-size: 16px; background-color: var(--bg-hover); width: 60px; }

    /* INPUT SỐ TRONG BẢNG */
    .score-input { width: 100%; border: none; text-align: center; font-size: 16px; font-weight: 500; color: var(--text-main); outline: none; background: transparent; padding: 5px 0; user-select: none; }
    .score-input.pseudo-focus { background-color: var(--bg-hover); font-weight: 700; color: var(--primary-color); border-radius: 4px; box-shadow: 0 0 0 2px var(--primary-color); }
    .score-input.changed { background-color: rgba(239, 68, 68, 0.1); color: var(--danger-color); font-weight: 800; }

    /* FOOTER & NOTE */
    .tfoot-label { text-align: right; padding-right: 20px; font-weight: 700; color: var(--text-muted); font-size: 14px; border: none !important; }
    .tfoot-val { font-size: 20px; color: var(--primary-color); font-weight: 800; background: var(--bg-input); border: 1px solid var(--border-color); }
    .section-header { font-size: 15px; font-weight: 700; color: var(--text-main); margin: 25px 0 10px 0; border-left: 4px solid var(--primary-color); padding-left: 10px; text-transform: uppercase; }
    .note-textarea { width: 100%; min-height: 100px; padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 15px; resize: vertical; background: var(--bg-input); color: var(--text-main); box-sizing: border-box; }
    .gate-alert { margin-top: 25px; background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger-color); border-radius: 8px; padding: 20px; display: none; }
    .gate-title { color: var(--danger-color); font-weight: 700; font-size: 15px; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }

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
    .key-btn.pressed { background-color: var(--bg-hover); transform: scale(0.92); }
    .key-btn.action-del { background: var(--bg-hover); }
    
    /* FIX MÀU NÚT ENTER - Chữ lật đen theo bg-card */
    .key-btn.action-enter { background: var(--primary-color); color: var(--bg-card); font-weight: 700; font-size: 18px; }

    @media (max-width: 768px) {
        .dashboard-container { padding: 0 0 200px 0; }
        .modern-card { margin-top: 0; border-radius: 0; border: none; padding: 10px 5px; box-shadow: none; }
        .page-title, .week-label, .btn-save span { display: none; }
        .header-controls { margin-bottom: 10px; gap: 8px; padding: 0 5px; }
        .control-group { width: 100%; justify-content: space-between; gap: 5px; }
        .custom-select-container { min-width: auto; flex-grow: 1; }
        .select-selected { padding: 0 10px; font-size: 13px; height: 38px; min-width: auto; }
        .week-control { height: 38px; padding: 0 5px; width: auto; }
        .week-input { width: 30px; font-size: 14px; }
        .btn-save { height: 38px; padding: 0 15px; font-size: 13px; }
        .table-container { border: none; }
        .paper-table { table-layout: fixed; width: 100%; }
        .paper-table th, .paper-table td { padding: 4px 1px; height: 40px; }
        .paper-table th { font-size: 9px; line-height: 1.1; word-wrap: break-word; overflow: hidden; white-space: normal; }
        .score-input { font-size: 14px; margin: 0; }
        .paper-table th:first-child, .paper-table td:first-child { width: 8%; font-size: 12px; }
        .paper-table th:last-child, .paper-table td:last-child { width: 10%; font-size: 12px; }
        .tfoot-label { font-size: 11px; padding-right: 5px; }
        .tfoot-val { font-size: 14px; padding: 0 5px; }
        .gate-alert { padding: 10px; font-size: 13px; }
    }
</style>

<div class="dashboard-container">
    <div class="header-controls">
        <div class="page-title"><i aria-hidden="true" class="fas fa-clipboard-check"></i> <span><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'SỔ CHẤM ĐIỂM' : 'SCORE BOOK') ?></span></div>
        <div class="control-group">
            
            <div class="custom-select-container">
                <div role="button" tabindex="0" class="select-selected" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chọn Lớp' : 'Select Class') ?>" onclick="toggleDropdown(event, this)">
                    <span id="txtSelectedClass" style="font-weight:600;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '-- Chọn Lớp --' : '-- Select Class --') ?></span>
                    <div class="select-arrow"></div>
                </div>
                <div class="select-items">
                    <?php foreach ($classes as $c): ?>
                    <div role="button" tabindex="0" onclick="selectClass('<?= $c['id'] ?>', '<?= htmlspecialchars($c['name']) ?>', this)"> <?= htmlspecialchars($c['name']) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="week-control">
                <span class="week-label"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'Week') ?></span>
                <input type="number" id="globalWeekInput" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'Week') ?>" value="<?= $default_week ?>" class="week-input" onchange="loadClassData()">
            </div>
            <button onclick="submitAllData()" class="btn-save"><i aria-hidden="true" class="fas fa-save"></i> <span><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'LƯU' : 'SAVE') ?></span></button>
        </div>
    </div>

    <div class="modern-card" id="paperForm" style="display:none;">
        <div class="section-header">1. <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bảng Điểm Chi Tiết' : 'Detailed Score Table') ?></div>
        <div id="skippedWeekNotice" class="gate-alert" style="display:none; background:rgba(245,158,11,0.1); border-color:#f59e0b; color:#d97706; margin-bottom:15px; padding:15px; border-radius:8px;">
            <div class="gate-title" style="color:#d97706; font-weight:bold; margin-bottom:5px;"><i aria-hidden="true" class="fas fa-exclamation-triangle"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần nghỉ lễ/Tết (Đốc lịch)' : 'Holiday Week Notice') ?></div>
            <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần học này đã được đóng băng do trùng lịch nghỉ lễ/Tết của toàn trường. Bạn không thể ghi nhận vi phạm cho tuần này.' : 'This week is frozen due to school holidays. You cannot record violations for this week.') ?>
        </div>
        <div class="table-container">
            <table class="paper-table" id="scoreTable">
                <thead>
                    <tr>
                        <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thứ' : 'Day') ?></th>
                        <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nghỉ' : 'Off') ?></th>
                        <?php foreach($baseScores as $code => $max): 
                            $fullName = isset($vioContent[$code]) ? $vioContent[$code] : $code;
                        ?>
                            <th aria-label="<?= htmlspecialchars($fullName) ?>"><?= htmlspecialchars($code) ?></th>
                        <?php endforeach; ?>
                        <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tổng' : 'Total') ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr>
                        <td colspan="<?= $colspan ?>" style="text-align:right; padding-right:10px; font-weight:bold; color:#10b981;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'ĐIỂM CỘNG (+):' : 'BONUS SCORE (+):') ?></td>
                        <td><input type="text" id="bonusScore" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'ĐIỂM CỘNG (+)' : 'BONUS SCORE (+)') ?>" class="score-input" value="0" data-is-bonus="1" style="color:#10b981; font-weight:bold;" inputmode="numeric" oninput="validateAndCalc(this)"></td>
                    </tr>
                    <tr>
                        <td colspan="<?= $colspan ?>" style="text-align:right; padding-right:10px; font-weight:bold; color:var(--text-main);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'TỔNG:' : 'TOTAL:') ?></td>
                        <td class="tfoot-val"><div id="grandTotal">0</div><div id="bonusDisplay" class="bonus-display" style="display:none; color:#10b981; font-size:11px;">+0</div></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div>
            <div class="section-header"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ghi chú vi phạm:' : 'Violation notes:') ?></div>
            <textarea id="txtGeneralNote" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập chi tiết lỗi vi phạm...' : 'Enter violation details...') ?>" class="note-textarea" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập chi tiết lỗi vi phạm...' : 'Enter violation details...') ?>"></textarea>
        </div>
        <div id="gateInfoBox" class="gate-alert">
            <div class="gate-title"><i aria-hidden="true" class="fas fa-exclamation-triangle"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trừ điểm Đoàn trường:' : 'School Union Deduction:') ?></div>
            <ul id="gateContent" style="padding-left:20px; margin:0; color:var(--danger-color);"></ul>
        </div>
    </div>
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
        <button aria-label="Action button" class="key-btn action-del" data-key="DEL"><i aria-hidden="true" class="fas fa-backspace"></i></button>
    </div>
    <div style="margin-top: 6px;">
        <button class="key-btn action-enter" style="width: 100%;" data-key="ENTER"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'XONG' : 'DONE') ?></button>
    </div>
</div>

<script>
    window.pageDestroy = function() {
        document.onclick = null; window.cc_currentClassId = null;
    };

    window.pageInit = function() {
        window.cc_currentClassId = null;
        window.isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth < 992;
        window.COLS = [ <?php $colArr = []; foreach($baseScores as $code => $max) { 
            $fullName = isset($vioContent[$code]) ? $vioContent[$code] : $code;
            $colArr[] = "{code: '$code', name: '" . addslashes($fullName) . "', max: $max}"; 
        } echo implode(", ", $colArr); ?> ];
        // Quy định THPT Lạng Giang số 3: Tuần thi đua bắt đầu từ Thứ 7 tuần trước (7), sau đó đến Thứ 2 -> Thứ 6 (2, 3, 4, 5, 6)
        window.DAYS = [{val: 7, label: "7"}, {val: 2, label: "2"}, {val: 3, label: "3"}, {val: 4, label: "4"}, {val: 5, label: "5"}, {val: 6, label: "6"}];
        document.onclick = function(e) { window.handleOutsideClick(e); window.closeAllSelects(null); };

        if (!window.isKeypadEventsAttached) {
            const keypadEl = document.getElementById('customKeypad');
            if(keypadEl) {
                const keys = keypadEl.querySelectorAll('.key-btn');
                keys.forEach(btn => {
                    const handleInput = (e) => { e.preventDefault(); e.stopPropagation(); window.kpPress(btn.getAttribute('data-key')); };
                    const pressEffect = () => btn.classList.add('pressed');
                    const releaseEffect = () => btn.classList.remove('pressed');
                    btn.addEventListener('touchstart', (e) => { pressEffect(); handleInput(e); });
                    btn.addEventListener('touchend', releaseEffect); btn.addEventListener('touchcancel', releaseEffect);
                    btn.addEventListener('mousedown', (e) => { pressEffect(); handleInput(e); });
                    btn.addEventListener('mouseup', releaseEffect); btn.addEventListener('mouseleave', releaseEffect);
                });
            }
            window.isKeypadEventsAttached = true;
        }
    };

    window.toggleDropdown = function(e, el) { e.stopPropagation(); window.closeAllSelects(el); el.nextElementSibling.style.display = el.nextElementSibling.style.display==='block'?'none':'block'; el.classList.toggle('active'); };
    window.closeAllSelects = function(except) { document.querySelectorAll('.select-items').forEach(i => { if(i!==except?.nextElementSibling) i.style.display='none'; }); document.querySelectorAll('.select-selected').forEach(e => { if(e!==except) e.classList.remove('active'); }); };
    
    window.selectClass = function(id, name, el) {
        document.getElementById('txtSelectedClass').innerText = name; window.cc_currentClassId = id;
        el.parentElement.style.display = 'none'; document.getElementById('paperForm').style.display = 'block'; window.loadClassData();
    };

    window.renderForm = function() {
        const tbody = document.querySelector('#scoreTable tbody'); tbody.innerHTML = '';
        let maxRowTotal = 0; window.COLS.forEach(c => maxRowTotal += parseFloat(c.max));
        window.DAYS.forEach(d => {
            const tr = document.createElement('tr'); tr.dataset.day = d.val;
            let h = `<td style="font-weight:bold; background:var(--bg-hover);">${d.label}</td>`;
            h += `<td><input type="checkbox" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nghỉ' : 'Off') ?> Thứ ${d.label}" class="holiday-checkbox" onchange="window.toggleHolidayRow(this, ${d.val})"></td>`;
            window.COLS.forEach(c => {
                const evt = window.isMobile ? '' : 'onfocus="this.select()"';
                h += `<td><input type="${window.isMobile?'text':'number'}" aria-label="Thứ ${d.label} điểm ${c.name}" class="score-input" value="${c.max}" data-max="${c.max}" data-code="${c.code}" ${evt} oninput="window.validateAndCalc(this)"></td>`;
            });
            h += `<td class="cell-total">${maxRowTotal.toFixed(2)}</td>`; tr.innerHTML = h; tbody.appendChild(tr);
        });
        if(window.isMobile) window.initCustomKeypad();
    };

    window.toggleHolidayRow = function(cb, dayVal) {
        const isChecked = cb.checked;
        const tr = cb.closest('tr');
        const inputs = tr.querySelectorAll('.score-input');
        
        inputs.forEach(inp => {
            inp.disabled = isChecked;
            inp.value = inp.dataset.max; // Reset to no deductions
            inp.classList.remove('changed');
        });
        
        let rowTotal = 0;
        inputs.forEach(i => rowTotal += parseFloat(i.value||0));
        tr.querySelector('.cell-total').innerText = rowTotal.toFixed(2);
        window.calcGrandTotal();
        
        const dateStr = window.dayDates ? window.dayDates[dayVal] : null;
        if (!dateStr) return;
        
        fetch('class_check.php?action=toggle_holiday', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ date: dateStr, is_holiday: isChecked })
        }).then(r => r.json()).then(d => {
            if (d.status !== 'success') {
                alert('❌ ' + <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Lỗi lưu ngày nghỉ:'" : "'Error saving holiday:'") ?> + d.msg);
                cb.checked = !isChecked; // Revert
                window.toggleHolidayRow(cb, dayVal); // Re-run to restore state
            }
        });
    };

    window.validateAndCalc = function(inp) {
        if(inp.dataset.isBonus) { window.calcGrandTotal(); return; }
        const max = parseFloat(inp.dataset.max); let val = parseFloat(inp.value);
        if(isNaN(val)) return;
        if(val > max) inp.value = max; if(val < 0) inp.value = 0;
        if(parseFloat(inp.value) < max) inp.classList.add('changed'); else inp.classList.remove('changed');
        const tr = inp.closest('tr'); let rowTotal = 0;
        tr.querySelectorAll('.score-input').forEach(i => rowTotal += parseFloat(i.value||0));
        tr.querySelector('.cell-total').innerText = rowTotal.toFixed(2);
        window.calcGrandTotal();
    };

    window.calcGrandTotal = function() {
        let total = 0; document.querySelectorAll('tbody .cell-total').forEach(td => total += parseFloat(td.innerText));
        const bonus = parseFloat(document.getElementById('bonusScore').value) || 0;
        document.getElementById('grandTotal').innerText = total.toFixed(2);
        const d = document.getElementById('bonusDisplay');
        if(bonus > 0) { d.style.display='block'; d.innerText = `+${bonus}`; } else d.style.display='none';
    };

    window.loadClassData = function() {
        if(!window.cc_currentClassId) return;
        window.renderForm(); window.calcGrandTotal(); 
        const week = document.getElementById('globalWeekInput').value;
        
        document.getElementById('skippedWeekNotice').style.display = 'none';
        document.querySelector('.btn-save').disabled = false;
        document.querySelector('.btn-save').style.opacity = '1';
        
        fetch('class_check.php?action=load_matrix&class_id='+window.cc_currentClassId+'&week='+week).then(r=>r.json()).then(d=>{
            window.dayDates = d.day_dates;
            
            if (d.is_skipped) {
                document.getElementById('skippedWeekNotice').style.display = 'block';
                document.querySelector('.btn-save').disabled = true;
                document.querySelector('.btn-save').style.opacity = '0.5';
                document.querySelectorAll('.holiday-checkbox').forEach(cb => {
                    cb.checked = true;
                    cb.disabled = true;
                    cb.closest('tr').querySelectorAll('.score-input').forEach(i => {
                        i.value = i.dataset.max;
                        i.disabled = true;
                    });
                });
            } else if (d.holidays) {
                d.holidays.forEach(dayVal => {
                    const cb = document.querySelector(`tr[data-day="${dayVal}"] .holiday-checkbox`);
                    if (cb) {
                        cb.checked = true;
                        cb.closest('tr').querySelectorAll('.score-input').forEach(i => {
                            i.value = i.dataset.max;
                            i.disabled = true;
                        });
                    }
                });
            }
            
            if(d.bonus_score) document.getElementById('bonusScore').value = d.bonus_score;
            if(d.saved_scores) d.saved_scores.forEach(s => {
                const inp = document.querySelector(`tr[data-day="${s.day}"] input[data-code="${s.code}"]`);
                if(inp) { inp.value = parseFloat(inp.dataset.max) - s.deduction; window.validateAndCalc(inp); }
            });
            document.getElementById('txtGeneralNote').value = d.general_note || '';
            const box = document.getElementById('gateInfoBox'), list = document.getElementById('gateContent');
            if(d.gate_data.length > 0) { box.style.display = 'block'; list.innerHTML = d.gate_data.map(g => `<li><b>[${g.recorded_violation_name}]</b> -${g.recorded_points}</li>`).join(''); } else box.style.display = 'none';
            window.calcGrandTotal(); 
        });
    };

    window.submitAllData = function() {
        if(!window.cc_currentClassId) return alert(<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Chưa chọn lớp!'" : "'No class selected!'") ?>);
        const btn = document.querySelector('.btn-save'); const old = btn.innerHTML; btn.innerHTML = '<i aria-hidden="true" class="fas fa-spinner fa-spin"></i>'; btn.disabled = true;
        const scores = [];
        document.querySelectorAll('tbody tr').forEach(tr => { tr.querySelectorAll('.score-input').forEach(i => { const deduc = parseFloat(i.dataset.max) - parseFloat(i.value || 0); scores.push({ day: tr.dataset.day, code: i.dataset.code, deduction: deduc }); }); });
        fetch('class_check.php?action=save_matrix', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ class_id: window.cc_currentClassId, week: document.getElementById('globalWeekInput').value, scores: scores, general_note: document.getElementById('txtGeneralNote').value, bonus_score: document.getElementById('bonusScore').value }) })
        .then(r=>r.json()).then(d=>{ btn.innerHTML = old; btn.disabled = false; if(d.status==='success') { if(typeof Toastify !== 'undefined') Toastify({text: <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'✅ Đã lưu!'" : "'✅ Saved successfully!'") ?>, duration: 2000, style: {background: "#10b981"}}).showToast(); else alert(<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'✅ Đã lưu!'" : "'✅ Saved successfully!'") ?>); } else alert('❌ ' + <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Lỗi:'" : "'Error:'") ?> + d.msg); }).catch(e=>{ btn.innerHTML = old; btn.disabled = false; alert('❌ ' + <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Lỗi mạng!'" : "'Network error!'") ?>); });
    };

    window.initCustomKeypad = function() {
        if (!window.isMobile) return;
        const inputs = document.querySelectorAll('.score-input'); window.keypadEl = document.getElementById('customKeypad'); window.chatBtn = document.getElementById('chatBubbleBtn');
        if(window.keypadEl) window.keypadEl.style.display = 'block';
        inputs.forEach(inp => {
            inp.readOnly = true; 
            inp.onclick = function(e) {
                e.preventDefault(); e.stopPropagation(); document.querySelectorAll('.score-input.pseudo-focus').forEach(el => el.classList.remove('pseudo-focus'));
                window.activeInput = this; this.classList.add('pseudo-focus'); window.keypadEl.classList.add('active'); if(window.chatBtn) window.chatBtn.style.display = 'none'; window.resetOnNextInput = true;
                setTimeout(() => { this.scrollIntoView({behavior: "smooth", block: "center"}); }, 100);
            };
        });
    };

    window.handleOutsideClick = function(e) {
        if (window.keypadEl && !window.keypadEl.contains(e.target) && !e.target.classList.contains('score-input')) {
            window.keypadEl.classList.remove('active'); if(window.chatBtn) window.chatBtn.style.display = ''; if(window.activeInput) { window.activeInput.classList.remove('pseudo-focus'); window.activeInput = null; }
        }
    };

    window.kpPress = function(key) {
        if (!window.activeInput) return;
        if (key === 'ENTER') { window.keypadEl.classList.remove('active'); window.activeInput.classList.remove('pseudo-focus'); if(window.chatBtn) window.chatBtn.style.display = ''; window.activeInput = null; return; }
        let currentVal = window.activeInput.value.toString();
        if (window.resetOnNextInput) { if (key === 'DEL') window.activeInput.value = ''; else if (key === '.') window.activeInput.value = '0.'; else window.activeInput.value = key; window.resetOnNextInput = false; } 
        else { if (key === 'DEL') window.activeInput.value = currentVal.slice(0, -1); else if (key === '.') { if (!currentVal.includes('.')) window.activeInput.value = currentVal + '.'; } else { if (currentVal === '0' && key !== '.') window.activeInput.value = key; else window.activeInput.value = currentVal + key; } }
        window.validateAndCalc(window.activeInput);
        window.activeInput.focus();
    };
</script>
<?php include 'includes/footer.php'; ?>