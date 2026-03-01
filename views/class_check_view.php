<?php 
include 'includes/header.php';

// =================================================================
// [GÀI BIẾN ĐỘNG] - ĐỌC CẤU HÌNH CỘT ĐIỂM TỪ DATABASE
// =================================================================
$stmtCfg = $pdo->query("SELECT value FROM config WHERE `key` = 'class_check_cols'");
$colsStr = $stmtCfg->fetchColumn();

// Mặc định nếu trường chưa lưu cấu hình nào
$defaultCols = ["SS"=>1, "VS"=>1, "CSVC"=>1, "TB"=>1, "XE"=>1, "DP"=>2, "SV"=>1, "THE"=>1, "DT"=>1];
$baseScores = $colsStr ? json_decode($colsStr, true) : $defaultCols;

// Tính số lượng cột để span footer cho chuẩn
$colspan = count($baseScores) + 1; 
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
    /* =========================================
       1. CẤU HÌNH GIAO DIỆN CHUNG
       ========================================= */
    :root {
        --primary: #2563eb; --primary-light: #eff6ff; --primary-hover: #1d4ed8;
        --text-dark: #1e293b; --text-gray: #64748b; --border-color: #cbd5e1;
        --danger: #ef4444; --danger-bg: #fef2f2; --white: #ffffff;
    }
    body { background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; padding-bottom: 20px; }
    .dashboard-container { max-width: 1200px; margin: 0 auto; padding-bottom: 120px; }

    /* CARD & HEADER */
    .modern-card { background: var(--white); border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 24px; margin-top: 20px; border: 1px solid #e2e8f0; }
    .header-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 20px; flex-wrap: nowrap; }
    .page-title { font-size: 20px; font-weight: 800; color: var(--text-dark); text-transform: uppercase; white-space: nowrap; display: flex; align-items: center; gap: 10px; }
    .page-title i { color: var(--primary); }
    
    /* === [SỬA LỖI LỆCH HÀNG - ALIGNMENT FIX] === */
    .control-group { 
        display: flex; 
        gap: 8px; 
        align-items: center; /* Căn giữa trục dọc */
        justify-content: flex-end; 
        flex-grow: 1; 
    }
    
    /* 1. Thiết lập chiều cao chuẩn cho cả 3 phần tử */
    .custom-select-container .select-selected,
    .week-control,
    .btn-save {
        height: 40px !important; /* Chiều cao cố định */
        box-sizing: border-box !important; /* Tính border vào chiều cao */
        border-radius: 8px !important; /* Bo góc đồng bộ */
        font-size: 14px !important;
        display: flex !important;
        align-items: center !important;
        margin: 0 !important; /* Xóa margin ẩn */
    }

    /* 2. Dropdown */
    .custom-select-container { position: relative; min-width: 180px; }
    .select-selected {
        background-color: var(--white);
        border: 1px solid var(--border-color);
        padding: 0 12px;
        justify-content: space-between;
        color: var(--text-dark);
        cursor: pointer; 
        width: 100%;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .select-items {
        position: absolute; top: 105%; left: 0; right: 0; z-index: 1000;
        background: var(--white);
        border: 1px solid var(--border-color); border-radius: 8px;
        max-height: 300px; overflow-y: auto; display: none;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .select-selected:active, .select-selected.active { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.15); }
    .select-arrow { width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid var(--text-gray); transition: 0.2s; flex-shrink: 0; }
    .select-selected.active .select-arrow { transform: rotate(180deg); }
    .select-items div { padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: var(--text-dark); }
    .select-items div:hover { background: var(--primary-light); color: var(--primary); font-weight: 600; }

    /* 3. Ô nhập Tuần */
    .week-control { 
        background: var(--white); 
        border: 1px solid var(--border-color); 
        padding: 0 10px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .week-input { 
        width: 40px; border: none; font-size: 15px; 
        font-weight: 700; color: var(--primary); 
        text-align: center; outline: none; background: transparent;
        padding: 0; margin: 0; height: 100%; /* Full height */
    }
    .week-label { font-size: 13px; color: var(--text-gray); font-weight: 600; margin-right: 5px; white-space: nowrap; }
    
    /* 4. Nút Lưu */
    .btn-save { 
        background: var(--primary); color: white; border: none; 
        padding: 0 20px; font-weight: 700; cursor: pointer; 
        gap: 8px; white-space: nowrap; 
        box-shadow: 0 2px 4px rgba(37,99,235,0.3);
        justify-content: center;
    }

    /* MOBILE: Căn chỉnh lại cho gọn */
    @media (max-width: 768px) {
        .control-group {
            justify-content: space-between;
            width: 100%;
            gap: 6px;
        }
        .custom-select-container {
            flex: 1; /* Dropdown co giãn */
            min-width: 0; 
        }
        .select-selected {
            padding: 0 10px;
            font-size: 13px !important;
        }
        .week-control {
            width: auto;
            padding: 0 8px;
        }
        .btn-save {
            padding: 0 15px;
        }
        .btn-save span { display: none; } /* Ẩn chữ Lưu, chỉ hiện icon */
    }
    /* ========================================= */

    .week-control { display: flex; align-items: center; background: var(--white); border: 1px solid var(--border-color); border-radius: 8px; padding: 5px 15px; height: 42px; }
    .week-input { width: 50px; border: none; font-size: 16px; font-weight: 800; color: var(--primary); text-align: center; outline: none; background: transparent; }
    .week-label { font-size: 13px; color: var(--text-gray); font-weight: 600; margin-right: 8px; }
    .btn-save { background: var(--primary); color: white; border: none; padding: 0 25px; height: 42px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; display: flex; align-items: center; gap: 8px; white-space: nowrap; box-shadow: 0 2px 4px rgba(37,99,235,0.3); }

    /* TABLE */
    .table-container { width: 100%; border-radius: 8px; overflow: hidden; border: 1px solid var(--border-color); }
    .paper-table { width: 100%; border-collapse: collapse; background: var(--white); }
    .paper-table th, .paper-table td { border: 1px solid var(--border-color); padding: 12px 10px; text-align: center; vertical-align: middle; }
    .paper-table th { background-color: #f8fafc; color: #334155; font-weight: 700; font-size: 13px; text-transform: uppercase; }
    .cell-total { color: var(--primary); font-weight: 800; font-size: 16px; background-color: #f0f9ff; width: 60px; }

    /* INPUT SỐ */
    .score-input { width: 100%; border: none; text-align: center; font-size: 16px; font-weight: 500; color: var(--text-dark); outline: none; background: transparent; padding: 5px 0; -webkit-user-select: none; user-select: none; }
    .score-input:focus { background-color: #dbeafe; font-weight: 700; color: var(--primary); }
    
    /* Focus giả lập */
    .score-input.pseudo-focus { background-color: #dbeafe !important; font-weight: 700; color: var(--primary); border-radius: 4px; box-shadow: 0 0 0 2px #3b82f6 !important; }
    .score-input.changed { background-color: #fee2e2; color: var(--danger); font-weight: 800; }

    /* FOOTER & NOTE */
    .tfoot-label { text-align: right; padding-right: 20px; font-weight: 700; color: var(--text-gray); font-size: 14px; border: none !important; }
    .tfoot-val { font-size: 20px; color: var(--primary); font-weight: 800; background: #fff; border: 1px solid var(--border-color); }
    .section-header { font-size: 15px; font-weight: 700; color: var(--text-dark); margin: 25px 0 10px 0; border-left: 4px solid var(--primary); padding-left: 10px; text-transform: uppercase; }
    .note-textarea { width: 100%; min-height: 100px; padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 15px; resize: vertical; }
    .gate-alert { margin-top: 25px; background: var(--danger-bg); border: 1px solid #fca5a5; border-radius: 8px; padding: 20px; display: none; }
    .gate-title { color: #991b1b; font-weight: 700; font-size: 15px; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }

    /* MOBILE */
    @media (max-width: 768px) {
        .dashboard-container { padding: 0 0 200px 0; }
        .modern-card { margin-top: 0; border-radius: 0; border: none; padding: 10px 5px; box-shadow: none; }
        .page-title, .week-label, .btn-save span { display: none; }
        .header-controls { margin-bottom: 10px; gap: 8px; padding: 0 5px; }
        .control-group { width: 100%; justify-content: space-between; gap: 5px; }
        .custom-select-container { min-width: auto; flex-grow: 1; }
        .select-selected { padding: 0 10px; font-size: 13px; height: 38px; min-width: auto; }
        .week-control { height: 38px; padding: 0 5px; }
        .week-input { width: 30px; font-size: 14px; }
        .btn-save { height: 38px; padding: 0 12px; font-size: 13px; }
        .table-container { border: none; }
        .paper-table { table-layout: fixed; width: 100%; }
        .paper-table th, .paper-table td { padding: 4px 1px !important; height: 40px; border: 1px solid #e2e8f0; }
        .paper-table th { font-size: 9px !important; line-height: 1.1; word-wrap: break-word; overflow: hidden; white-space: normal; }
        .score-input { font-size: 14px !important; margin: 0; }
        .paper-table th:first-child, .paper-table td:first-child { width: 8%; font-size: 12px; }
        .paper-table th:last-child, .paper-table td:last-child { width: 10%; font-size: 12px; }
        .tfoot-label { font-size: 11px; padding-right: 5px; }
        .tfoot-val { font-size: 14px; padding: 0 5px; }
        .gate-alert { padding: 10px; font-size: 13px; }
    }

    /* DARK MODE */
    [data-theme="dark"] {
        --primary: #60a5fa; --primary-light: #1e3a8a; --primary-hover: #3b82f6; --text-dark: #f1f5f9; --text-gray: #94a3b8; --border-color: #334155; --danger: #f87171; --danger-bg: #450a0a; --white: #1e293b;
    }
    [data-theme="dark"] body { background-color: #0f172a; color: var(--text-dark); }
    [data-theme="dark"] .week-control, [data-theme="dark"] .btn-save, [data-theme="dark"] .tfoot-val { background-color: var(--white); border-color: var(--border-color); color: var(--text-dark); }
    [data-theme="dark"] .note-textarea { background-color: #0f172a; color: var(--text-dark); border-color: var(--border-color); }
    [data-theme="dark"] .paper-table th { background-color: #334155; color: #e2e8f0; border-color: var(--border-color); }
    [data-theme="dark"] .paper-table td { background-color: #1e293b !important; border-color: #334155 !important; color: #f1f5f9; }
    [data-theme="dark"] .paper-table td:first-child { background-color: #334155 !important; color: #e2e8f0; }
    [data-theme="dark"] .cell-total { background-color: #172554 !important; color: #93c5fd; }
    [data-theme="dark"] .score-input { background-color: transparent !important; border: none !important; color: #f1f5f9 !important; }
    [data-theme="dark"] .score-input.pseudo-focus { background-color: #1e40af !important; color: #ffffff !important; box-shadow: 0 0 0 2px #3b82f6 !important; }
    [data-theme="dark"] .score-input.changed { background-color: #450a0a !important; color: #fca5a5 !important; }
    [data-theme="dark"] .ios-keypad-overlay { background: #1e293b; border-top: 1px solid #334155; }
    [data-theme="dark"] .key-btn { background: #334155; color: #fff; box-shadow: 0 1px 0 #0f172a; }
    
    /* CUSTOM KEYPAD STYLE (UNIVERSAL) */
    .ios-keypad-overlay {
        position: fixed; bottom: 0; left: 0; right: 0;
        background: #d1d5db; 
        z-index: 2147483647 !important; 
        padding: 6px;
        padding-bottom: env(safe-area-inset-bottom);
        transform: translateY(100%); transition: transform 0.2s cubic-bezier(0.2, 0.8, 0.2, 1);
        box-shadow: 0 -4px 10px rgba(0,0,0,0.1); display: none;
    }
    .ios-keypad-overlay.active { transform: translateY(0); }
    .keypad-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
    
    .key-btn {
        background: #ffffff; border: none; border-radius: 5px; padding: 12px 0;
        font-size: 24px; font-weight: 500; color: #000;
        box-shadow: 0 1px 0 #888; cursor: pointer; user-select: none;
        -webkit-tap-highlight-color: transparent;
        transition: background-color 0.05s, transform 0.05s;
    }
    .key-btn.pressed { background-color: #e5e7eb !important; transform: scale(0.92); box-shadow: none !important; }
    .key-btn.action-del { background: #acb4c1; }
    .key-btn.action-del.pressed { background: #9ca3af !important; }
    .key-btn.action-enter { background: var(--primary); color: #fff; font-weight: 700; font-size: 18px; }
    .key-btn.action-enter.pressed { background: #1e40af !important; }

    [data-theme="dark"] .key-btn.pressed { background-color: #475569 !important; }
    [data-theme="dark"] .key-btn.action-del.pressed { background-color: #64748b !important; }
</style>

<div class="dashboard-container">
    <div class="header-controls">
        <div class="page-title"><i class="fas fa-clipboard-check"></i> <span>SỔ CHẤM ĐIỂM</span></div>
        <div class="control-group">
            
            <div class="custom-select-container">
                <div class="select-selected" onclick="toggleDropdown(event, this)">
                    <span id="txtSelectedClass" style="font-weight:600;">-- Chọn Lớp --</span>
                    <div class="select-arrow"></div>
                </div>
                <div class="select-items">
                    <?php foreach ($classes as $c): ?>
                    <div onclick="selectClass('<?= $c['id'] ?>', '<?= htmlspecialchars($c['name']) ?>', this)"> <?= htmlspecialchars($c['name']) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="week-control">
                <span class="week-label">Tuần</span>
                <input type="number" id="globalWeekInput" value="<?= $default_week ?>" class="week-input" onchange="loadClassData()">
            </div>
            <button onclick="submitAllData()" class="btn-save"><i class="fas fa-save"></i> <span>LƯU</span></button>
        </div>
    </div>

    <div class="modern-card" id="paperForm" style="display:none;">
        <div class="section-header">1. Bảng Điểm Chi Tiết</div>
        <div class="table-container">
            <table class="paper-table" id="scoreTable">
                <thead>
                    <tr>
                        <th>Thứ</th>
                        <?php foreach($baseScores as $code => $max): ?>
                            <th><?= htmlspecialchars($code) ?></th>
                        <?php endforeach; ?>
                        <th>Tổng</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr>
                        <td colspan="<?= $colspan ?>" style="text-align:right; padding-right:10px; font-weight:bold; color:var(--success);">ĐIỂM CỘNG (+):</td>
                        <td><input type="text" id="bonusScore" class="score-input" value="0" data-is-bonus="1" style="color:var(--success); font-weight:bold;" inputmode="numeric" oninput="validateAndCalc(this)"></td>
                    </tr>
                    <tr>
                        <td colspan="<?= $colspan ?>" style="text-align:right; padding-right:10px; font-weight:bold;">TỔNG:</td>
                        <td class="tfoot-val"><div id="grandTotal">0</div><div id="bonusDisplay" class="bonus-display" style="display:none;">+0</div></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div>
            <div class="section-header">Ghi chú vi phạm:</div>
            <textarea id="txtGeneralNote" class="note-textarea" placeholder="Nhập chi tiết lỗi vi phạm..."></textarea>
        </div>
        <div id="gateInfoBox" class="gate-alert">
            <div class="gate-title"><i class="fas fa-exclamation-triangle"></i> Trừ điểm Đoàn trường:</div>
            <ul id="gateContent" style="padding-left:20px; margin:0; color:#b91c1c;"></ul>
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
        <button class="key-btn action-del" data-key="DEL"><i class="fas fa-backspace"></i></button>
    </div>
    <div style="margin-top: 6px;">
        <button class="key-btn action-enter" style="width: 100%;" data-key="ENTER">XONG</button>
    </div>
</div>

<script>
    let currentClassId = null;
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth < 992;
    
    // =================================================================
    // [GÀI BIẾN ĐỘNG] - ĐỔ BIẾN TỪ PHP SANG JS CHO LOGIC CHẤM ĐIỂM
    // =================================================================
    const COLS = [
        <?php 
        $colArr = [];
        foreach($baseScores as $code => $max) {
            $colArr[] = "{code: '$code', max: $max}";
        }
        echo implode(", ", $colArr);
        ?>
    ];

    const DAYS = [{val: 7, label: "7"}, {val: 2, label: "2"}, {val: 3, label: "3"}, {val: 4, label: "4"}, {val: 5, label: "5"}, {val: 6, label: "6"}];

    function toggleDropdown(e, el) { 
        e.stopPropagation(); 
        closeAllSelects(el); 
        el.nextElementSibling.style.display = el.nextElementSibling.style.display==='block'?'none':'block'; 
        el.classList.toggle('active'); 
    }
    
    function closeAllSelects(except) { 
        document.querySelectorAll('.select-items').forEach(i => { 
            if(i!==except?.nextElementSibling) i.style.display='none'; 
        }); 
        document.querySelectorAll('.select-selected').forEach(e => { 
            if(e!==except) e.classList.remove('active'); 
        }); 
    }
    
    document.addEventListener('click', () => closeAllSelects());

    function selectClass(id, name, el) {
        document.getElementById('txtSelectedClass').innerText = name;
        currentClassId = id;
        el.parentElement.style.display = 'none';
        document.getElementById('paperForm').style.display = 'block';
        loadClassData();
    }

    function renderForm() {
        const tbody = document.querySelector('#scoreTable tbody'); tbody.innerHTML = '';
        
        // [ĐÃ SỬA]: Tự động tính tổng điểm tối đa của 1 ngày dựa vào mảng COLS từ DB
        let maxRowTotal = 0;
        COLS.forEach(c => maxRowTotal += parseFloat(c.max));

        DAYS.forEach(d => {
            let tr = document.createElement('tr'); tr.dataset.day = d.val;
            let h = `<td style="font-weight:bold; background:#f8fafc;">${d.label}</td>`;
            COLS.forEach(c => {
                let evt = isMobile ? '' : 'onfocus="this.select()"';
                h += `<td><input type="${isMobile?'text':'number'}" class="score-input" value="${c.max}" data-max="${c.max}" data-code="${c.code}" ${evt} oninput="validateAndCalc(this)"></td>`;
            });
            
            // Ép thẳng cái tổng vừa tính vào cột Tổng
            h += `<td class="cell-total">${maxRowTotal.toFixed(2)}</td>`; 
            tr.innerHTML = h; tbody.appendChild(tr);
        });
        if(isMobile) initCustomKeypad();
    }

    // HÀM CHẶN SỐ LỚN HƠN MAX RẤT AN TOÀN VÌ LẤY TỪ DATA-MAX
    function validateAndCalc(inp) {
        if(inp.dataset.isBonus) { calcGrandTotal(); return; }
        let max = parseFloat(inp.dataset.max), val = parseFloat(inp.value);
        if(isNaN(val)) return;
        
        // CHÍNH NÓ NÀY: Giới hạn theo MAX cấu hình
        if(val > max) inp.value = max; 
        if(val < 0) inp.value = 0;
        
        if(parseFloat(inp.value) < max) inp.classList.add('changed'); else inp.classList.remove('changed');
        
        let tr = inp.closest('tr'), rowTotal = 0;
        tr.querySelectorAll('.score-input').forEach(i => rowTotal += parseFloat(i.value||0));
        tr.querySelector('.cell-total').innerText = rowTotal.toFixed(2);
        calcGrandTotal();
    }

    function calcGrandTotal() {
        let total = 0;
        document.querySelectorAll('tbody .cell-total').forEach(td => total += parseFloat(td.innerText));
        let bonus = parseFloat(document.getElementById('bonusScore').value) || 0;
        document.getElementById('grandTotal').innerText = total.toFixed(2);
        
        let d = document.getElementById('bonusDisplay');
        if(bonus > 0) { d.style.display='block'; d.innerText = `+${bonus}`; } else d.style.display='none';
    }

    function loadClassData() {
        if(!currentClassId) return;
        
        renderForm();
        calcGrandTotal(); // [ĐÃ SỬA]: Ép tính luôn tổng toàn bảng ngay sau khi render xong
        
        const week = document.getElementById('globalWeekInput').value;
        const fd = new FormData(); fd.append('get_class_students', '1'); fd.append('class_id', currentClassId); fd.append('week_override', week);
        
        fetch('class_check.php?action=load_matrix&class_id='+currentClassId+'&week='+week).then(r=>r.json()).then(d=>{
            if(d.bonus_score) document.getElementById('bonusScore').value = d.bonus_score;
            if(d.saved_scores) d.saved_scores.forEach(s => {
                let inp = document.querySelector(`tr[data-day="${s.day}"] input[data-code="${s.code}"]`);
                if(inp) { 
                    inp.value = parseFloat(inp.dataset.max) - s.deduction; 
                    validateAndCalc(inp); // Cứ có sửa là tự động tính lại
                }
            });
            document.getElementById('txtGeneralNote').value = d.general_note || '';
            let box = document.getElementById('gateInfoBox'), list = document.getElementById('gateContent');
            if(d.gate_data.length > 0) {
                box.style.display = 'block';
                list.innerHTML = d.gate_data.map(g => `<li><b>[${g.recorded_violation_name}]</b> -${g.pts}</li>`).join('');
            } else box.style.display = 'none';
            calcGrandTotal(); // Chốt sổ lần cuối
        });
    }

    // =================================================================
    // [SỬA LỖI] - CẤU TRÚC JSON GỬI VỀ CHO CONTROLLER LƯU DB
    // =================================================================
    function submitAllData() {
        if(!currentClassId) return alert('Chưa chọn lớp!');
        let btn = document.querySelector('.btn-save'), old = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; btn.disabled = true;
        
        let scores = [];
        document.querySelectorAll('tbody tr').forEach(tr => {
            tr.querySelectorAll('.score-input').forEach(i => {
                let maxScore = parseFloat(i.dataset.max);
                let inputtedScore = parseFloat(i.value || 0);
                
                // Vì giao diện hiển thị "Điểm còn lại", nên số điểm bị trừ sẽ bằng Max - Hiện tại
                let deduc = maxScore - inputtedScore;
                
                scores.push({
                    day: tr.dataset.day, 
                    code: i.dataset.code, 
                    deduction: deduc // Gửi điểm bị trừ về cho Backend
                });
            });
        });

        fetch('class_check.php?action=save_matrix', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                class_id: currentClassId, 
                week: document.getElementById('globalWeekInput').value,
                scores: scores, 
                general_note: document.getElementById('txtGeneralNote').value,
                bonus_score: document.getElementById('bonusScore').value
            })
        }).then(r=>r.json()).then(d=>{
            btn.innerHTML = old; btn.disabled = false;
            if(d.status==='success') {
                if(typeof Toastify !== 'undefined') Toastify({text: "✅ Đã lưu!", duration: 2000, style: {background: "#10b981"}}).showToast();
                else alert('✅ Đã lưu thành công!');
            } else alert('❌ Lỗi: ' + d.msg);
        }).catch(e=>{ btn.innerHTML = old; btn.disabled = false; alert('❌ Lỗi mạng hoặc server!'); });
    }

    // KEYPAD LOGIC GIỮ NGUYÊN
    let activeInput = null;
    let resetOnNextInput = false;
    let isKeypadEventsAttached = false;
    const keypadEl = document.getElementById('customKeypad');
    const chatBtn = document.getElementById('chatBubbleBtn');

    function initCustomKeypad() {
        if (!isMobile) return;
        const inputs = document.querySelectorAll('.score-input');
        keypadEl.style.display = 'block';

        inputs.forEach(inp => {
            inp.readOnly = true; 
            inp.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                document.querySelectorAll('.score-input.pseudo-focus').forEach(el => el.classList.remove('pseudo-focus'));
                
                activeInput = this;
                this.classList.add('pseudo-focus');
                keypadEl.classList.add('active');
                if(chatBtn) chatBtn.style.display = 'none';
                resetOnNextInput = true;
                setTimeout(() => { this.scrollIntoView({behavior: "smooth", block: "center"}); }, 100);
            });
        });

        if (!isKeypadEventsAttached) {
            const keys = keypadEl.querySelectorAll('.key-btn');
            keys.forEach(btn => {
                const handleInput = (e) => {
                    e.preventDefault(); e.stopPropagation();
                    kpPress(btn.getAttribute('data-key'));
                };
                const pressEffect = () => btn.classList.add('pressed');
                const releaseEffect = () => btn.classList.remove('pressed');

                btn.addEventListener('touchstart', (e) => { pressEffect(); handleInput(e); });
                btn.addEventListener('touchend', releaseEffect);
                btn.addEventListener('touchcancel', releaseEffect);
                btn.addEventListener('mousedown', (e) => { pressEffect(); handleInput(e); });
                btn.addEventListener('mouseup', releaseEffect);
                btn.addEventListener('mouseleave', releaseEffect);
            });
            isKeypadEventsAttached = true;
        }

        document.removeEventListener('click', handleOutsideClick);
        document.addEventListener('click', handleOutsideClick);
    }

    function handleOutsideClick(e) {
        if (!keypadEl.contains(e.target) && !e.target.classList.contains('score-input')) {
            keypadEl.classList.remove('active');
            if(chatBtn) chatBtn.style.display = '';
            if(activeInput) { activeInput.classList.remove('pseudo-focus'); activeInput = null; }
        }
    }

    function kpPress(key) {
        if (!activeInput) return;
        if (key === 'ENTER') {
            keypadEl.classList.remove('active');
            activeInput.classList.remove('pseudo-focus');
            if(chatBtn) chatBtn.style.display = '';
            activeInput = null;
            return;
        }

        let currentVal = activeInput.value.toString();
        if (resetOnNextInput) {
            if (key === 'DEL') activeInput.value = '';
            else if (key === '.') activeInput.value = '0.'; 
            else activeInput.value = key;
            resetOnNextInput = false;
        } else {
            if (key === 'DEL') activeInput.value = currentVal.slice(0, -1);
            else if (key === '.') { if (!currentVal.includes('.')) activeInput.value = currentVal + '.'; } 
            else {
                if (currentVal === '0' && key !== '.') activeInput.value = key;
                else activeInput.value = currentVal + key;
            }
        }
        validateAndCalc(activeInput);
    }
</script>
<?php include 'includes/footer.php'; ?>