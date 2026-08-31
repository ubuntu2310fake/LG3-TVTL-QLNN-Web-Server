<?php
include 'includes/header.php';
?>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" type="text/javascript"></script>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js" type="text/javascript"></script>

<style>
    /* CSS CƠ BẢN */
    .custom-select-container, .search-wrapper { position: relative; width: 100%; margin-bottom: 15px; }
    .select-selected, .search-input-box {
        background-color: var(--bg-input); border: 1px solid var(--border-color); border-radius: 10px;
        padding: 0 15px; display: flex; align-items: center; justify-content: space-between;
        font-size: 15px; height: 50px; box-sizing: border-box; color: var(--text-main);
        transition: 0.2s; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .search-input-box input {
        border: none; outline: none; width: 100%; height: 100%;
        font-size: 15px; color: var(--text-main); background: transparent; font-family: inherit;
    }
    .select-selected:active, .search-input-box:focus-within { border-color: var(--accent-color); box-shadow: 0 0 0 3px rgba(0,95,186,0.15); }
    .select-arrow { width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid var(--text-muted); transition: 0.2s; flex-shrink: 0; }
    .select-selected.active .select-arrow { transform: rotate(180deg); }
    .select-items, .search-results {
        position: absolute; top: 110%; left: 0; right: 0; z-index: 1000;
        background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px;
        max-height: 300px; overflow-y: auto; display: none;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1); animation: fadeIn 0.2s ease;
    }
    .select-items div, .result-item { padding: 12px 15px; cursor: pointer; border-bottom: 1px solid var(--border-color); font-size: 14px; color: var(--text-main); }
    .select-items div:hover, .result-item:hover { background: var(--bg-hover); color: var(--accent-color); font-weight: 500; }
    
    .student-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 5px; max-height: 50vh; overflow-y: auto; }
    .student-card {
        background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px;
        padding: 10px; cursor: pointer; transition: all 0.2s ease;
        display: flex; flex-direction: column; justify-content: center; text-align: left;
    }
    .student-card:hover { background: var(--bg-hover); border-color: var(--accent-color); transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0, 95, 186, 0.1); }
    .st-name { font-weight: 700; font-size: 13px; color: var(--text-main); margin-bottom: 3px; }
    .st-code { font-size: 11px; color: var(--text-muted); background: var(--bg-hover); padding: 2px 6px; border-radius: 4px; width: fit-content; margin-top: 3px;}
    
    #qr-reader { width: 100%; border-radius: 12px; overflow: hidden; border: none !important; background: transparent !important; }
    #qr-reader__scan_region { background: transparent !important; } 
    #qr-reader img[alt="Info icon"] { display: none; }
    
    .chk-label { display: flex; align-items: center; padding: 12px; background: var(--bg-input); border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 8px; cursor: pointer; transition: 0.2s; color: var(--text-main); }
    .chk-label input { margin-right: 12px; width: 26px; height: 26px; accent-color: var(--accent-color) !important; cursor: pointer; -webkit-appearance: checkbox; }
    
    .history-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; background: var(--bg-card); border-bottom: 1px solid var(--border-color); font-size: 13px; color: var(--text-main); }
    .btn-delete-record { background: var(--bg-hover) !important; color: var(--danger-color) !important; border: 1px solid var(--border-color) !important; }
    .btn-delete-record:hover { background: var(--danger-color) !important; color: white !important; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
    .win-input { box-sizing: border-box !important; width: 100%; }
</style>

<div class="grid-sidebar-layout">
    <div class="win-card">
        <h3 style="margin-top:0; color:var(--accent-color); border-bottom:1px solid var(--border-color); padding-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
            <span><i aria-hidden="true" class="fas fa-torii-gate"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Kiểm Tra Cổng' : 'Gate Check') ?></span>
            <div style="display:flex; align-items:center; background:var(--bg-hover); padding:5px 15px; border-radius:20px;">
                <span style="font-size:13px; color:var(--text-muted); margin-right:5px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần:' : 'Week') ?></span>
                <input type="number" id="globalWeekInput" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'Week') ?>" value="<?= $default_week ?>" min="1" 
                       style="width:40px; border:none; background:transparent; font-weight:bold; color:var(--accent-color); text-align:center; font-size:15px; outline:none;">
            </div>
        </h3>

        <button onclick="toggleQRScanner()" class="win-btn" style="width: 100%; margin-bottom: 15px; justify-content: center; background-color: var(--text-main); color: var(--bg-body);">
            <i aria-hidden="true" class="fas fa-qrcode"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'QUÉT MÃ QR HỌC SINH' : 'SCAN STUDENT QR CODE') ?>
        </button>

        <div id="qrBox" style="display: none; margin-bottom: 20px; animation: fadeIn 0.3s; position: relative;">
            <div id="cameraDropdownWrapper" class="custom-select-container" style="display:none; margin-bottom: 10px;">
                <div role="button" tabindex="0" class="select-selected" onclick="toggleDropdown(event, this)">
                    <span id="txtSelectedCamera"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang chọn camera...' : 'Selecting camera...') ?></span>
                    <div class="select-arrow"></div>
                </div>
                <div id="cameraListItems" class="select-items"></div>
            </div>
            <div style="text-align: center; margin-bottom: 5px; font-size: 13px; color: var(--text-muted);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đưa mã vào khung hình' : 'Point the code into the frame') ?></div>
            
            <div style="position: relative; width: 100%; border-radius: 12px; overflow: hidden; background: #000; display: flex; justify-content: center; align-items: center; min-height: 250px;">
                <video id="qr-video" playsinline autoplay muted style="width: 100%; height: auto; max-height: 60vh; object-fit: cover; display: block;"></video>
                <!-- Reticle Overlay -->
                <div id="qrOverlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; box-sizing: border-box; display: flex; align-items: center; justify-content: center;">
                    <div id="qrTargetBox" style="width: 220px; height: 220px; border: 3px solid #10b981; border-radius: 16px; box-shadow: 0 0 0 4000px rgba(0, 0, 0, 0.4); position: relative; transition: border-color 0.2s;">
                        <div style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%); background: #10b981; color: white; font-size: 11px; padding: 2px 8px; border-radius: 10px; font-weight: bold; white-space: nowrap;">
                            <i class="fas fa-qrcode"></i> QUÉT MÃ QR
                        </div>
                    </div>
                </div>
            </div>
            
            <input type="file" id="inpFileQR" accept="image/*" style="display: none;" onchange="window.scanQRFromFile(this)">
            
            <div style="display: flex; gap: 8px; margin-top: 10px;">
                <button type="button" onclick="document.getElementById('inpFileQR').click()" class="win-btn" style="flex: 1; justify-content: center; background: #005fba; color: #ffffff !important;">
                    <i aria-hidden="true" class="fas fa-file-image"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tải ảnh thẻ lên' : 'Upload ID image') ?>
                </button>
                <button type="button" onclick="stopQRScanner()" class="win-btn win-btn-secondary" style="flex: 1; justify-content: center;">
                    <i aria-hidden="true" class="fas fa-stop-circle"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tắt Camera' : 'Turn off Camera') ?>
                </button>
            </div>
        </div>

        <div class="search-wrapper">
            <div class="search-input-box">
                <input type="text" id="inpSearch" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tìm kiếm học sinh' : '🔍 Enter name or student ID...') ?>" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '🔍 Nhập tên hoặc mã HS...' : '🔍 Enter name or student ID...') ?>" autocomplete="off">
            </div>
            <div id="resultBox" class="search-results"></div>
        </div>

        <div style="text-align:center; margin-bottom:15px; font-size:12px; color:var(--text-muted); font-weight:600;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '— HOẶC CHỌN LỚP —' : '-- OR SELECT CLASS --') ?></div>

        <div class="custom-select-container">
            <div role="button" tabindex="0" class="select-selected" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chọn lớp' : '-- Select class --') ?>" onclick="toggleDropdown(event, this)">
                <span id="txtSelectedClass" style="font-weight:600;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '-- Chọn lớp --' : '-- Select class --') ?></span>
                <div class="select-arrow"></div>
            </div>
            <div class="select-items">
                <?php foreach ($classes as $c): ?>
                <div role="button" tabindex="0" onclick="selectClassItem('<?= $c['id'] ?>', '<?= $c['name'] ?>', this)"><?= $c['name'] ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="studentListContainer" style="display:none; margin-top:15px; border-top:1px solid var(--border-color); padding-top:10px;"></div>
    </div>

    <div style="display:flex; flex-direction:column; gap:20px;">
        <div class="win-card" id="violationForm" style="display:none; border:2px solid var(--accent-color); animation: fadeIn 0.3s;">
            <div id="selectedStudentInfo" style="display:flex; gap:10px; align-items:center; margin-bottom:15px; background:var(--bg-hover); padding:10px; border-radius:8px;">
                <img src="static/default.png" id="stuImg" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border: 2px solid #fff;">
                <div>
                    <div style="font-weight:bold; color:var(--accent-color); font-size:15px;" id="stuName">...</div>
                    <div style="font-size:12px; color:var(--text-muted);" id="stuClass">...</div>
                </div>
                <button aria-label="Action button" onclick="clearSelection()" style="margin-left:auto; border:none; background:var(--bg-input); width:30px; height:30px; border-radius:50%; cursor:pointer; color:var(--text-main);"><i aria-hidden="true" class="fas fa-times"></i></button>
            </div>

            <div style="max-height:350px; overflow-y:auto; padding-right:5px;">
                <?php foreach ($violations as $v): ?>
                <label class="chk-label">
                    <input type="checkbox" id="v_<?= $v['id'] ?>" aria-label="Lỗi vi phạm" name="v_ids" value="<?= $v['id'] ?>">
                    <span style="flex:1; font-weight:500; font-size:14px; color:var(--text-main);"><?= ($_SESSION['lang'] ?? 'vi') === 'en' && !empty($v['content_en']) ? htmlspecialchars($v['content_en']) : htmlspecialchars($v['content']) ?></span>
                    <b style="color:var(--danger-color); font-size:13px; background:var(--bg-hover); padding:2px 6px; border-radius:4px;">-<?= $v['points'] ?></b>
                </label>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top:15px; background:var(--bg-hover); padding:10px; border-radius:8px; border:1px dashed var(--border-color);">
                <label class="chk-label" style="border:none; padding:0; background:none; margin-bottom:5px;">
                    <input type="checkbox" id="chkTime" aria-label="Sửa ngày giờ" onchange="toggleTimeInput()">
                    <span style="font-weight:500; color:var(--text-main);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chấm bù / Sửa ngày giờ cũ' : 'Make-up check / Edit old time') ?></span>
                </label>
                <div id="timeInputArea" style="display:none; margin-top:5px;">
                    <input type="datetime-local" id="customTime" aria-label="Chọn ngày giờ" class="win-input" style="margin:0;">
                </div>
            </div>

            <input type="text" id="noteInput" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ghi chú thêm...' : 'Additional notes...') ?>" class="win-input" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ghi chú thêm...' : 'Additional notes...') ?>" style="margin-top:10px;">
            
            <button onclick="submitViolation()" class="win-btn win-btn-danger" style="width:100%; margin-top:10px; font-weight:bold; padding:12px;">
                <i aria-hidden="true" class="fas fa-save"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'LƯU VI PHẠM' : 'SAVE VIOLATION') ?>
            </button>
        </div>

        <div class="win-card">
            <h4 style="margin-top:0; color:var(--text-muted); border-bottom:1px solid var(--border-color); padding-bottom:5px;">
                <i aria-hidden="true" class="fas fa-history"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vừa chấm xong' : 'Just checked') ?>
            </h4>
            <div id="historyList">
                <?php if (!empty($recent_violations)): ?>
                    <?php foreach ($recent_violations as $r): ?>
                    <div class="history-item" id="rec_<?= $r['id'] ?>">
                        <div>
                            <b><?= $r['student_name'] ?? (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tập thể' : 'Collective') ?></b> <small>(<?= $r['class_name'] ?? '' ?>)</small><br>
                            <span style="color:var(--danger-color); font-weight:500;">- <?= ($_SESSION['lang'] ?? 'vi') === 'en' && !empty($r['recorded_violation_name_en']) ? htmlspecialchars($r['recorded_violation_name_en']) : htmlspecialchars($r['recorded_violation_name']) ?></span><br>
                            <small style="color:var(--text-muted);"><?= date('H:i d/m', strtotime($r['date_created'])) ?></small>
                        </div>
                        <button onclick="deleteRecord('<?= $r['id'] ?>')" class="btn-delete-record" style="border-radius:4px; padding:5px 8px; cursor:pointer;">
                            <i aria-hidden="true" class="fas fa-trash"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align:center; color:var(--text-muted); padding:20px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chưa có dữ liệu mới.' : 'No new data.') ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    window.pageDestroy = async function() {
        if (window.gc_scanner && window.gc_isScanning) {
            try { await window.gc_scanner.stop(); window.gc_scanner.clear(); } catch(e) {}
        }
        window.gc_scanner = null; window.gc_isScanning = false; document.onclick = null;
    };

    window.pageInit = function() {
        // Gọi ngay 1 lần để đè lên cache PJAX cũ nếu người dùng vừa chuyển tab về
        fetch('gate_check.php?action=recent_json', { credentials: 'same-origin' }).then(r => r.json()).then(data => {
            if (data.status !== 'success') return;
            const list = document.getElementById('historyList');
            if (!list) return;
            const violations = data.violations || [];
            if (!violations.length) return;
            const lang = window.currentLangCode || 'vi';
            list.innerHTML = violations.map(r => {
                const name = (lang === 'en' && r.display_name_en) ? r.display_name_en : (r.display_name || r.recorded_violation_name);
                return `<div class="history-item" id="rec_${r.id}">
                    <div>
                        <b>${r.student_name || 'Tập thể'}</b> <small>(${r.class_name || ''})</small><br>
                        <span style="color:var(--danger-color); font-weight:500;">- ${name}</span><br>
                        <small style="color:var(--text-muted);">${r.time_label}</small>
                    </div>
                    <button onclick="deleteRecord('${r.id}')" class="btn-delete-record" style="border-radius:4px; padding:5px 8px; cursor:pointer;">
                        <i aria-hidden="true" class="fas fa-trash"></i>
                    </button>
                </div>`;
            }).join('');
        });

        window.gc_scanner = null; window.gc_isScanning = false; window.gc_currentStudentId = null;
        
        const inpSearch = document.getElementById('inpSearch');
        if(inpSearch) {
            const handleSearch = function(e) {
                let q = inpSearch.value.trim();
                const box = document.getElementById('resultBox');
                if (q.length < 2) { box.style.display = 'none'; return; }
                
                // Tự động bóc tách mã học sinh bắt đầu bằng chữ K (Ví dụ K48A1016 từ chuỗi URL/Thẻ)
                const matchK = q.match(/K[0-9]{2}A[0-9]{1,2}[0-9]{2,3}/i);
                if (matchK) {
                    q = matchK[0].toUpperCase();
                }

                const fd = new FormData(); fd.append('suggest_query', q);
                fetch('gate_check.php', {method:'POST', body: fd}).then(r=>r.json()).then(d=>{
                    let html = '';
                    if (d.results && d.results.length > 0) {
                        const exactMatch = d.results.find(s => s.code && s.code.toUpperCase() === q);
                        if (e && e.key === 'Enter') {
                            const target = exactMatch || d.results[0];
                            window.selectStudent(target.id, target.name, target.class_name, target.code, target.image_url);
                            box.style.display = 'none';
                            inpSearch.value = '';
                            return;
                        }

                        d.results.forEach(s => { 
                            const avatarUrl = s.image_url ? s.image_url : 'static/default.png';
                            html += `<div role="button" tabindex="0" class="result-item" style="display:flex; align-items:center; gap:10px;" onclick="window.selectStudent('${s.id}', '${s.name}', '${s.class_name}', '${s.code}', '${s.image_url}')">
                                        <img src="${avatarUrl}" style="width:35px; height:35px; border-radius:50%; object-fit:cover; border:1px solid var(--border-color);">
                                        <div><div style="font-weight:bold;">${s.name}</div><div style="font-size:12px; color:var(--text-muted);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp' : 'Class') ?>: ${s.class_name}; <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mã HS' : 'Mã HS') ?>: ${s.code}</div></div>
                                     </div>`; 
                        });
                        box.innerHTML = html; box.style.display = 'block';
                    } else { box.style.display = 'none'; }
                });
            };

            inpSearch.oninput = handleSearch;
            inpSearch.onkeydown = function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    handleSearch(e);
                }
            };
        }
        document.onclick = () => window.closeAllSelects();


        // === SSEManager: Nhận push vi phạm mới từ server ngay lập tức ===
        if (window.SSEManager) {
            // Nhận vi phạm MỚI → prepend vào đầu danh sách
            window.SSEManager.on('violation_new', (data) => {
                const list = document.getElementById('historyList');
                if (!list) return;
                // Xóa thông báo "chưa có dữ liệu" nếu còn
                const empty = list.querySelector('div[style*="text-align:center"]');
                if (empty) empty.remove();
                // Không thêm lại nếu đã có
                if (document.getElementById('rec_' + data.id)) return;
                const el = document.createElement('div');
                const lang = window.currentLangCode || 'vi';
                const name = (lang === 'en' && data.display_name_en) ? data.display_name_en : data.display_name;
                el.innerHTML = `<div class="history-item" id="rec_${data.id}">
                    <div>
                        <b>${data.student_name || "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tập thể' : 'Collective') ?>"}</b>
                        <small>(${data.class_name || ''})</small><br>
                        <span style="color:var(--danger-color); font-weight:500;">- ${name}</span><br>
                        <small style="color:var(--text-muted);">${data.time_label || ''}</small>
                    </div>
                    <button onclick="deleteRecord('${data.id}')" class="btn-delete-record"
                        aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xóa' : 'Delete') ?>"
                        style="border-radius:4px; padding:5px 8px; cursor:pointer;">
                        <i aria-hidden="true" class="fas fa-trash"></i>
                    </button>
                </div>`;
                list.insertBefore(el.firstElementChild, list.firstChild);
                // Giới hạn 15 items
                while (list.children.length > 15) list.removeChild(list.lastChild);
            });

            // Nhận sự kiện XÓA → remove item khỏi DOM
            window.SSEManager.on('violation_deleted', (data) => {
                const el = document.getElementById('rec_' + data.id);
                if (el) { el.style.opacity = '0'; setTimeout(() => el.remove(), 200); }
            });
        }

        // === DataManager: Fallback polling nếu SSE không hoạt động (mỗi 30 giây) ===
        if (window.DataManager) {
            window.DataManager.register(
                'gate_recent_fallback',
                () => fetch('gate_check.php?action=recent_json', { credentials: 'same-origin' }).then(r => r.json()),
                30000, // 30s — chỉ là backup
                (data) => {
                    if (data.status !== 'success') return;
                    if (window.SSEManager && window.SSEManager.isConnected()) return; // SSE đang chạy → bỏ qua
                    const list = document.getElementById('historyList');
                    if (!list) return;
                    const violations = data.violations || [];
                    if (!violations.length) return;
                    const firstId = violations[0]?.id;
                    if (list.dataset.firstId == firstId) return;
                    list.dataset.firstId = firstId;
                    const lang = window.currentLangCode || 'vi';
                    list.innerHTML = violations.map(r => {
                        const name = (lang === 'en' && r.display_name_en) ? r.display_name_en : (r.display_name || r.recorded_violation_name);
                        return `<div class="history-item" id="rec_${r.id}">
                            <div>
                                <b>${r.student_name || 'Tập thể'}</b> <small>(${r.class_name || ''})</small><br>
                                <span style="color:var(--danger-color); font-weight:500;">- ${name}</span><br>
                                <small style="color:var(--text-muted);">${r.time_label}</small>
                            </div>
                            <button onclick="deleteRecord('${r.id}')" class="btn-delete-record" style="border-radius:4px; padding:5px 8px; cursor:pointer;">
                                <i aria-hidden="true" class="fas fa-trash"></i>
                            </button>
                        </div>`;
                    }).join('');
                }
            );
        }

    };

    window.gc_videoStream = null;
    window.gc_animFrameId = null;
    window.gc_isScanning = false;
    window.gc_barcodeDetector = null;

    if ('BarcodeDetector' in window) {
        try {
            window.gc_barcodeDetector = new BarcodeDetector({ formats: ['qr_code'] });
        } catch(e) {
            window.gc_barcodeDetector = null;
        }
    }

    window.playBeep = function() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            gain.gain.setValueAtTime(0.1, ctx.currentTime);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.15);
        } catch(e) {}
    };

    window.toggleQRScanner = function() {
        const box = document.getElementById('qrBox');
        if (box.style.display === 'none') {
            box.style.display = 'block';
            window.startNativeQRScanner();
        } else {
            window.stopQRScanner();
        }
    };

    window.startNativeQRScanner = async function(selectedDeviceId = null) {
        window.stopQRScanner(true);
        document.getElementById('qrBox').style.display = 'block';

        const video = document.getElementById('qr-video');
        if (!video) return;

        let constraints = {
            video: {
                facingMode: { ideal: "environment" },
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        };

        if (selectedDeviceId) {
            constraints = { video: { deviceId: { exact: selectedDeviceId } } };
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            window.gc_videoStream = stream;
            video.srcObject = stream;
            video.setAttribute('playsinline', true);
            await video.play();

            window.gc_isScanning = true;
            window.populateNativeCameraList();
            window.requestAnimationFrame(window.scanVideoFrameLoop);
        } catch (err) {
            console.error("Camera access error:", err);
            try {
                const fallbackStream = await navigator.mediaDevices.getUserMedia({ video: true });
                window.gc_videoStream = fallbackStream;
                video.srcObject = fallbackStream;
                video.setAttribute('playsinline', true);
                await video.play();

                window.gc_isScanning = true;
                window.populateNativeCameraList();
                window.requestAnimationFrame(window.scanVideoFrameLoop);
            } catch(fallbackErr) {
                alert("❌ Không thể truy cập Camera!\nLỗi: " + (fallbackErr.message || fallbackErr) + "\n\n⚠️ Lưu ý: Trình duyệt yêu cầu kết nối bảo mật HTTPS để cấp quyền mở Camera!");
                window.stopQRScanner();
            }
        }
    };

    window.populateNativeCameraList = async function() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return;
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(d => d.kind === 'videoinput');
            const wrapper = document.getElementById('cameraDropdownWrapper');
            const listItems = document.getElementById('cameraListItems');
            
            if (!wrapper || !listItems) return;

            if (videoDevices.length > 1) {
                listItems.innerHTML = "";
                wrapper.style.display = "block";
                
                let activeDeviceId = "";
                if (window.gc_videoStream) {
                    const tracks = window.gc_videoStream.getVideoTracks();
                    if (tracks.length > 0) {
                        const settings = tracks[0].getSettings();
                        activeDeviceId = settings.deviceId || "";
                    }
                }

                videoDevices.forEach((dev, idx) => {
                    const label = dev.label || `Camera ${idx + 1}`;
                    const div = document.createElement('div');
                    div.innerText = label;
                    div.onclick = () => {
                        document.getElementById('txtSelectedCamera').innerText = label;
                        window.startNativeQRScanner(dev.deviceId);
                        wrapper.querySelector('.select-selected').click();
                    };
                    listItems.appendChild(div);
                    if (dev.deviceId === activeDeviceId) {
                        document.getElementById('txtSelectedCamera').innerText = label;
                    }
                });
            } else {
                wrapper.style.display = "none";
            }
        } catch(e) {}
    };

    window.scanVideoFrameLoop = async function() {
        if (!window.gc_isScanning) return;
        const video = document.getElementById('qr-video');
        
        if (video && video.readyState === video.HAVE_ENOUGH_DATA) {
            let qrCodeFound = null;

            if (window.gc_barcodeDetector) {
                try {
                    const barcodes = await window.gc_barcodeDetector.detect(video);
                    if (barcodes && barcodes.length > 0) {
                        qrCodeFound = barcodes[0].rawValue;
                    }
                } catch(e) {}
            }

            if (!qrCodeFound && window.jsQR) {
                if (!window.gc_canvas) {
                    window.gc_canvas = document.createElement('canvas');
                    window.gc_ctx = window.gc_canvas.getContext('2d', { willReadFrequently: true });
                }
                const canvas = window.gc_canvas;
                const ctx = window.gc_ctx;
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: "dontInvert"
                });
                if (code && code.data) {
                    qrCodeFound = code.data;
                }
            }

            if (qrCodeFound) {
                window.playBeep();
                const targetBox = document.getElementById('qrTargetBox');
                if (targetBox) targetBox.style.borderColor = "#10b981";
                window.onScanSuccess(qrCodeFound);
                return;
            }
        }

        if (window.gc_isScanning) {
            window.gc_animFrameId = window.requestAnimationFrame(window.scanVideoFrameLoop);
        }
    };

    window.stopQRScanner = function(keepBoxVisible = false) {
        window.gc_isScanning = false;
        if (window.gc_animFrameId) {
            window.cancelAnimationFrame(window.gc_animFrameId);
            window.gc_animFrameId = null;
        }
        if (window.gc_videoStream) {
            window.gc_videoStream.getTracks().forEach(track => track.stop());
            window.gc_videoStream = null;
        }
        const video = document.getElementById('qr-video');
        if (video) video.srcObject = null;

        if (!keepBoxVisible) {
            const box = document.getElementById('qrBox');
            if (box) box.style.display = 'none';
        }
    };

    window.scanQRFromFile = function(fileInput) {
        if (!fileInput.files || !fileInput.files[0]) return;
        const file = fileInput.files[0];
        
        if (typeof Toastify !== 'undefined') {
            Toastify({ text: "🖼️ Đang phân tích mã QR từ ảnh...", style: { background: "#3b82f6" } }).showToast();
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = async function() {
                let qrCodeFound = null;

                if (window.gc_barcodeDetector) {
                    try {
                        const barcodes = await window.gc_barcodeDetector.detect(img);
                        if (barcodes && barcodes.length > 0) {
                            qrCodeFound = barcodes[0].rawValue;
                        }
                    } catch(err) {}
                }

                if (!qrCodeFound && window.jsQR) {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    ctx.drawImage(img, 0, 0);
                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const code = jsQR(imageData.data, imageData.width, imageData.height, {
                        inversionAttempts: "dontInvert"
                    });
                    if (code && code.data) {
                        qrCodeFound = code.data;
                    }
                }

                if (qrCodeFound) {
                    window.playBeep();
                    window.onScanSuccess(qrCodeFound);
                } else {
                    alert("❌ Không nhận diện được mã QR trong bức ảnh này. Vui lòng chụp ảnh vuông góc và rõ nét hơn!");
                }
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    };

    window.onScanSuccess = function(decodedText) {
        if (!decodedText) return;
        let raw = decodedText.trim();
        
        // 1. Tìm chuỗi KxxA... (ví dụ: Ma_HS_16_Truong Thanh Hieu_K48A1016 -> K48A1016)
        let code = raw;
        const matchK = code.match(/K[0-9]{1,2}A[0-9]+/i);
        if (matchK) {
            code = matchK[0].toUpperCase();
        } else {
            // 2. Loại bỏ Ma_HS_ và tách theo gạch dưới _
            code = code.replace(/^Ma_HS_/i, '').replace(/Ma_HS_/gi, '');
            if (code.includes('/')) {
                const parts = code.split('/');
                code = parts[parts.length - 1];
            }
            if (code.includes('_')) {
                const parts = code.split('_');
                code = parts[parts.length - 1];
            }
            code = code.trim().toUpperCase();
        }

        window.stopQRScanner();
        if (typeof Toastify !== 'undefined') Toastify({text: "🔍 Đang tìm mã: " + code, style:{background:"#3b82f6"}}).showToast();

        const fd = new FormData(); fd.append('suggest_query', raw); // Truyền cả chuỗi raw lên server để SQL fallback
        fetch('gate_check.php', {method: 'POST', body: fd}).then(r => r.json()).then(d => {
            if (d.status === 'success' && d.results && d.results.length > 0) {
                const target = d.results.find(s => s.code && s.code.toUpperCase() === code) || d.results[0];
                window.selectStudent(target.id, target.name, target.class_name, target.code, target.image_url);
                if (typeof Toastify !== 'undefined') Toastify({text: "✅ Đã chọn: " + target.name + " (" + target.class_name + ")", style:{background:"#10b981"}}).showToast();
            } else {
                alert("❌ Không tìm thấy học sinh với mã QR: " + code + "\n(Dữ liệu quét được: " + raw + ")");
            }
        }).catch(err => {
            alert("Lỗi kết nối khi tìm học sinh!");
        });
    };

    window.selectClassItem = function(id, name, itemEl) {
        document.getElementById('txtSelectedClass').innerText = name; itemEl.parentElement.style.display = 'none';
        const container = document.getElementById('studentListContainer'); container.style.display = 'block'; container.innerHTML = '<div style="padding:20px; text-align:center; color:var(--text-muted);"><i aria-hidden="true" class="fas fa-spinner fa-spin"></i> ' + (window.LANG && window.LANG.loading || "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang tải...' : 'Đang tải...') ?>") + '</div>';
        const fd = new FormData(); fd.append('get_class_students', 1); fd.append('class_id', id);
        fetch('gate_check.php', {method:'POST', body: fd}).then(r=>r.json()).then(d=>{
            let html = '<div class="student-grid">';
            d.students.forEach(s => { 
                // Nâng cấp: Thêm avatar vào danh sách học sinh theo lớp
                const avatarUrl = s.image_url ? s.image_url : 'static/default.png';
                html += `<div role="button" tabindex="0" class="student-card" onclick="window.selectStudent('${s.id}', '${s.name}', '${s.class_name}', '${s.code}', '${s.image_url}')">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <img src="${avatarUrl}" style="width:30px; height:30px; border-radius:50%; object-fit:cover; border:1px solid var(--border-color);">
                                <div><div class="st-name" style="margin-bottom:0;">${s.name}</div><div class="st-code" style="margin-top:2px;">${s.thuylinh ? 'STT ' + s.thuylinh + ' • ' : ''}${s.code}</div></div>
                            </div>
                         </div>`; 
            });
            html += '</div>'; container.innerHTML = html;
        });
    };

    // NHẬN THÊM PARAMETER AVATARURL
    window.selectStudent = function(id, name, cls, code, avatarUrl) {
        window.gc_currentStudentId = id; 
        document.getElementById('resultBox').style.display = 'none'; 
        document.getElementById('violationForm').style.display = 'block';
        document.getElementById('stuName').innerText = name; 
        document.getElementById('stuClass').innerText = cls + (code ? ` (${code})` : '');
        
        // Render Avatar thật vào ô xác nhận lỗi
        const imgEl = document.getElementById('stuImg');
        if (avatarUrl && avatarUrl !== 'null' && avatarUrl !== 'undefined') {
            imgEl.src = avatarUrl;
        } else {
            imgEl.src = 'static/default.png';
        }

        setTimeout(() => { const el = document.getElementById('violationForm'); if (el) { const offset = 70; const top = el.getBoundingClientRect().top + window.scrollY - offset; window.scrollTo({top: top, behavior: 'smooth'}); } }, 150);
    };

    window.submitViolation = function() {
        if(!window.gc_currentStudentId) return alert(window.LANG && window.LANG.no_student_selected || "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chưa chọn học sinh!' : 'Chưa chọn học sinh!') ?>");
        const btn = document.querySelector('#violationForm button'); const oldText = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<i aria-hidden="true" class="fas fa-spinner fa-spin"></i> ' + (window.LANG && window.LANG.saving || "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang lưu...' : 'Đang lưu...') ?>");
        const fd = new FormData(); fd.append('student_id', window.gc_currentStudentId); fd.append('week', document.getElementById('globalWeekInput').value); fd.append('other_note', document.getElementById('noteInput').value);
        if(document.getElementById('chkTime').checked) fd.append('custom_time', document.getElementById('customTime').value);
        document.querySelectorAll('input[name="v_ids"]:checked').forEach(c => fd.append('violation_ids[]', c.value));

        fetch('gate_check.php', {method:'POST', body:fd}).then(r => r.json()).then(d => {
            btn.disabled = false; btn.innerHTML = oldText;
            if(d.status === 'success') {
                if(typeof Toastify !== 'undefined') Toastify({text: window.LANG && window.LANG.saved_successfully || "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '✅ Đã lưu!' : '✅ Đã lưu!') ?>", style:{background:"#10b981"}}).showToast();
                window.clearSelection();
                const historyList = document.getElementById('historyList'); if(historyList.children.length === 1 && historyList.children[0].innerText.includes(window.LANG && window.LANG.no_data_substr || "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chưa có' : 'Chưa có') ?>")) { historyList.innerHTML = ''; }
                if(d.new_data) { d.new_data.forEach(item => { const div = document.createElement('div'); div.className = 'history-item'; div.id = 'rec_' + item.id; div.innerHTML = `<div><b>${item.student_name}</b> <small>(${item.class_name})</small><br><span style="color:var(--danger-color); font-weight:500;">- ${item.violation_name}</span><br><small style="color:var(--text-muted);">${item.time_str}</small></div><button aria-label="Action button" onclick="window.deleteRecord('${item.id}')" class="btn-delete-record" style="border-radius:4px; padding:5px 8px; cursor:pointer;"><i aria-hidden="true" class="fas fa-trash"></i></button>`; historyList.prepend(div); }); }
            } else { alert((window.LANG && window.LANG.error_prefix || "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi:' : 'Lỗi:') ?>") + d.msg); }
        }).catch(e => { btn.disabled = false; btn.innerHTML = oldText; alert(window.LANG && window.LANG.connection_error || "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi kết nối!' : 'Lỗi kết nối!') ?>"); });
    };

    window.deleteRecord = function(id) {
        // Giữ nguyên giao diện xác nhận của bản _1
        WinUI.confirm(window.LANG && window.LANG.confirm_delete || "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xác nhận xóa' : 'Xác nhận xóa') ?>", window.LANG && window.LANG.confirm_delete_violation || "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn có chắc chắn muốn XÓA lỗi này?' : 'Bạn có chắc chắn muốn XÓA lỗi này?') ?>", function() {
            
            // Lấy lại logic tìm nút bấm và set loading từ bản gốc
            const btn = document.querySelector(`#rec_${id} button`); 
            const originalHtml = btn ? btn.innerHTML : '<i aria-hidden="true" class="fas fa-trash"></i>';
            if(btn) { btn.innerHTML = '<i aria-hidden="true" class="fas fa-spinner fa-spin"></i>'; btn.disabled = true; }
            
            // Trả lại tham số API chuẩn của bản gốc để backend hiểu được
            const fd = new FormData(); 
            fd.append('delete_id', id);

            fetch('gate_check.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
                if(d.status === 'success') { 
                    // Sửa lại tiền tố ID từ 'log_' thành 'rec_' như HTML đang render
                    const el = document.getElementById('rec_' + id); 
                    if(el) { 
                        el.style.transition = 'all 0.3s ease'; 
                        el.style.opacity = '0'; 
                        el.style.transform = 'translateX(20px)'; 
                        setTimeout(() => el.remove(), 300); 
                    } 
                    if(typeof Toastify !== 'undefined') Toastify({text: window.LANG && window.LANG.deleted_successfully || "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '✅ Đã xóa thành công' : '✅ Đã xóa thành công') ?>", style:{background:"#10b981"}}).showToast();
                } else { 
                    alert(d.msg); 
                    if(btn) { btn.innerHTML = originalHtml; btn.disabled = false; } 
                }
            }).catch(e => { 
                alert(window.LANG && window.LANG.server_connection_error || "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi kết nối server' : 'Lỗi kết nối server') ?>"); 
                if(btn) { btn.innerHTML = originalHtml; btn.disabled = false; } 
            });
        });
    };

    window.clearSelection = function() { window.gc_currentStudentId = null; document.getElementById('violationForm').style.display = 'none'; document.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked=false); document.getElementById('noteInput').value = ''; document.getElementById('stuImg').src = 'static/default.png';};
    window.toggleTimeInput = function() { document.getElementById('timeInputArea').style.display = document.getElementById('chkTime').checked ? 'block' : 'none'; };
    window.toggleDropdown = function(e, el) { e.stopPropagation(); window.closeAllSelects(el); el.nextElementSibling.style.display = el.nextElementSibling.style.display==='block'?'none':'block'; el.classList.toggle('active'); };
    window.closeAllSelects = function(except) { document.querySelectorAll('.select-items').forEach(i => { if(i!==except?.nextElementSibling) i.style.display='none'; }); document.querySelectorAll('.select-selected').forEach(e => { if(e!==except) e.classList.remove('active'); }); };
</script>

<?php include 'includes/footer.php'; ?>