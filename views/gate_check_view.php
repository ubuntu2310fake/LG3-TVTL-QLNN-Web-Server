<?php
include 'includes/header.php';
?>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<style>
    /* CSS GIỮ NGUYÊN */
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
    .st-code { font-size: 11px; color: var(--text-muted); background: var(--bg-hover); padding: 2px 6px; border-radius: 4px; width: fit-content; }
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
            <span><i class="fas fa-torii-gate"></i> Kiểm Tra Cổng</span>
            <div style="display:flex; align-items:center; background:var(--bg-hover); padding:5px 15px; border-radius:20px;">
                <span style="font-size:13px; color:var(--text-muted); margin-right:5px;">Tuần:</span>
                <input type="number" id="globalWeekInput" value="<?= $default_week ?>" min="1" 
                       style="width:40px; border:none; background:transparent; font-weight:bold; color:var(--accent-color); text-align:center; font-size:15px; outline:none;">
            </div>
        </h3>

        <button onclick="toggleQRScanner()" class="win-btn" style="width: 100%; margin-bottom: 15px; justify-content: center; background-color: var(--text-main); color: var(--bg-body);">
            <i class="fas fa-qrcode"></i> QUÉT MÃ QR HỌC SINH
        </button>

        <div id="qrBox" style="display: none; margin-bottom: 20px; animation: fadeIn 0.3s;">
            <div id="cameraDropdownWrapper" class="custom-select-container" style="display:none; margin-bottom: 10px;">
                <div class="select-selected" onclick="toggleDropdown(event, this)">
                    <span id="txtSelectedCamera">Đang chọn camera...</span>
                    <div class="select-arrow"></div>
                </div>
                <div id="cameraListItems" class="select-items"></div>
            </div>
            <div style="text-align: center; margin-bottom: 5px; font-size: 13px; color: var(--text-muted);">Đưa mã vào khung hình</div>
            <div id="qr-reader"></div>
            <button onclick="stopQRScanner()" class="win-btn win-btn-secondary" style="width: 100%; margin-top: 10px; justify-content: center;">
                <i class="fas fa-stop-circle"></i> Tắt Camera
            </button>
        </div>

        <div class="search-wrapper">
            <div class="search-input-box">
                <input type="text" id="inpSearch" placeholder="🔍 Nhập tên hoặc mã HS..." autocomplete="off">
            </div>
            <div id="resultBox" class="search-results"></div>
        </div>

        <div style="text-align:center; margin-bottom:15px; font-size:12px; color:var(--text-muted); font-weight:600;">— HOẶC CHỌN LỚP —</div>

        <div class="custom-select-container">
            <div class="select-selected" onclick="toggleDropdown(event, this)">
                <span id="txtSelectedClass" style="font-weight:600;">-- Chọn lớp --</span>
                <div class="select-arrow"></div>
            </div>
            <div class="select-items">
                <?php foreach ($classes as $c): ?>
                <div onclick="selectClassItem('<?= $c['id'] ?>', '<?= $c['name'] ?>', this)"><?= $c['name'] ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="studentListContainer" style="display:none; margin-top:15px; border-top:1px solid var(--border-color); padding-top:10px;"></div>
    </div>

    <div style="display:flex; flex-direction:column; gap:20px;">
        <div class="win-card" id="violationForm" style="display:none; border:2px solid var(--accent-color); animation: fadeIn 0.3s;">
            <div id="selectedStudentInfo" style="display:flex; gap:10px; align-items:center; margin-bottom:15px; background:var(--bg-hover); padding:10px; border-radius:8px;">
                <img src="/static/default.png" id="stuImg" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border: 2px solid #fff;">
                <div>
                    <div style="font-weight:bold; color:var(--accent-color); font-size:15px;" id="stuName">...</div>
                    <div style="font-size:12px; color:var(--text-muted);" id="stuClass">...</div>
                </div>
                <button onclick="clearSelection()" style="margin-left:auto; border:none; background:var(--bg-input); width:30px; height:30px; border-radius:50%; cursor:pointer; color:var(--text-main);"><i class="fas fa-times"></i></button>
            </div>

            <div style="max-height:350px; overflow-y:auto; padding-right:5px;">
                <?php foreach ($violations as $v): ?>
                <label class="chk-label">
                    <input type="checkbox" name="v_ids" value="<?= $v['id'] ?>">
                    <span style="flex:1; font-weight:500; font-size:14px; color:var(--text-main);"><?= $v['content'] ?></span>
                    <b style="color:var(--danger-color); font-size:13px; background:var(--bg-hover); padding:2px 6px; border-radius:4px;">-<?= $v['points'] ?></b>
                </label>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top:15px; background:var(--bg-hover); padding:10px; border-radius:8px; border:1px dashed var(--border-color);">
                <label class="chk-label" style="border:none; padding:0; background:none; margin-bottom:5px;">
                    <input type="checkbox" id="chkTime" onchange="toggleTimeInput()"> 
                    <span style="font-weight:500; color:var(--text-main);">Chấm bù / Sửa ngày giờ cũ</span>
                </label>
                <div id="timeInputArea" style="display:none; margin-top:5px;">
                    <input type="datetime-local" id="customTime" class="win-input" style="margin:0;">
                </div>
            </div>

            <input type="text" id="noteInput" class="win-input" placeholder="Ghi chú thêm..." style="margin-top:10px;">
            
            <button onclick="submitViolation()" class="win-btn win-btn-danger" style="width:100%; margin-top:10px; font-weight:bold; padding:12px;">
                <i class="fas fa-save"></i> LƯU VI PHẠM
            </button>
        </div>

        <div class="win-card">
            <h4 style="margin-top:0; color:var(--text-muted); border-bottom:1px solid var(--border-color); padding-bottom:5px;">
                <i class="fas fa-history"></i> Vừa chấm xong
            </h4>
            <div id="historyList">
                <?php if (!empty($recent_violations)): ?>
                    <?php foreach ($recent_violations as $r): ?>
                    <div class="history-item" id="rec_<?= $r['id'] ?>">
                        <div>
                            <b><?= $r['student_name'] ?? 'Tập thể' ?></b> <small>(<?= $r['class_name'] ?? '' ?>)</small><br>
                            <span style="color:var(--danger-color); font-weight:500;">- <?= $r['recorded_violation_name'] ?></span><br>
                            <small style="color:var(--text-muted);"><?= date('H:i d/m', strtotime($r['date_created'])) ?></small>
                        </div>
                        <button onclick="deleteRecord('<?= $r['id'] ?>')" class="btn-delete-record" style="border-radius:4px; padding:5px 8px; cursor:pointer;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align:center; color:var(--text-muted); padding:20px;">Chưa có dữ liệu mới.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // --- KHỞI TẠO BIẾN ---
    let currentStudentId = null;
    let html5QrcodeScanner = null;
    let isScanning = false;

    // --- (Phần QR Code giữ nguyên) ---
    function toggleQRScanner() { const box = document.getElementById('qrBox'); if (box.style.display === 'none') { box.style.display = 'block'; initCameraAndStart(); } else { stopQRScanner(); } }
    function initCameraAndStart() { if (html5QrcodeScanner === null) { html5QrcodeScanner = new Html5Qrcode("qr-reader"); } Html5Qrcode.getCameras().then(devices => { if (devices && devices.length) { const wrapper = document.getElementById('cameraDropdownWrapper'); const listItems = document.getElementById('cameraListItems'); listItems.innerHTML = ""; if (devices.length > 1) wrapper.style.display = "block"; else wrapper.style.display = "none"; devices.forEach(device => { const label = device.label || `Camera ${listItems.children.length + 1}`; const div = document.createElement('div'); div.innerText = label; div.onclick = function() { restartQRWithNewCamera(device.id, label); wrapper.querySelector('.select-selected').click(); }; listItems.appendChild(div); }); const lastCam = devices[devices.length - 1]; document.getElementById('txtSelectedCamera').innerText = lastCam.label || "Camera sau"; startScanningWithId(lastCam.id); } else { alert("Không tìm thấy camera!"); stopQRScanner(); } }).catch(err => { alert("Lỗi camera: " + err); stopQRScanner(); }); }
    function startScanningWithId(cameraId) { html5QrcodeScanner.start(cameraId, { fps: 10, qrbox: { width: 250, height: 250 } }, onScanSuccess).then(() => { isScanning = true; }).catch(err => console.error(err)); }
    function restartQRWithNewCamera(newCamId, labelName) { document.getElementById('txtSelectedCamera').innerText = labelName; if (html5QrcodeScanner && isScanning) { html5QrcodeScanner.stop().then(() => { isScanning = false; startScanningWithId(newCamId); }); } else { startScanningWithId(newCamId); } }
    function stopQRScanner() { if (html5QrcodeScanner && isScanning) { html5QrcodeScanner.stop().then(() => { isScanning = false; document.getElementById('qrBox').style.display = 'none'; }).catch(e=>console.log(e)); } else { document.getElementById('qrBox').style.display = 'none'; } }
    
    // XỬ LÝ KHI QUÉT ĐƯỢC QR
    function onScanSuccess(decodedText) { 
        let code = decodedText.includes("Ma_HS_") ? decodedText.split("Ma_HS_")[1] : decodedText; 
        stopQRScanner(); 
        if(typeof Toastify !== 'undefined') Toastify({text: "Đang tìm: " + code, style:{background:"#3b82f6"}}).showToast(); 
        
        const fd = new FormData();
        fd.append('suggest_query', code);
        
        fetch('gate_check.php', {method: 'POST', body: fd})
        .then(r => r.json())
        .then(d => { 
            if (d.status === 'success' && d.results.length > 0) { 
                const target = d.results.find(s => s.code === code) || d.results[0]; 
                selectStudent(target.id, target.name, target.class_name, target.code); 
                if(typeof Toastify !== 'undefined') Toastify({text: "✅ Đã tìm thấy: " + target.name, style:{background:"#10b981"}}).showToast(); 
            } else { 
                alert("❌ Không tìm thấy HS: " + code); 
            } 
        }); 
    }

    // --- Search Logic ---
    document.getElementById('inpSearch').addEventListener('input', function(e){ 
        const q = e.target.value.trim(); 
        const box = document.getElementById('resultBox'); 
        if(q.length < 2) { box.style.display = 'none'; return; } 
        
        const fd = new FormData();
        fd.append('suggest_query', q);

        fetch('gate_check.php', {method:'POST', body: fd})
        .then(r=>r.json())
        .then(d=>{ 
            let html = ''; 
            if (d.results.length > 0) { 
                d.results.forEach(s => { 
                    html += `<div class="result-item" onclick="selectStudent('${s.id}', '${s.name}', '${s.class_name}', '${s.code}')"><div style="font-weight:bold;">${s.name}</div><div style="font-size:12px; color:var(--text-muted);">Lớp: ${s.class_name}</div></div>`; 
                }); 
                box.innerHTML = html; box.style.display = 'block'; 
            } else { box.style.display = 'none'; } 
        }); 
    });

    function selectClassItem(id, name, itemEl) { 
        document.getElementById('txtSelectedClass').innerText = name; 
        itemEl.parentElement.style.display = 'none'; 
        const container = document.getElementById('studentListContainer'); 
        container.style.display = 'block'; 
        container.innerHTML = '<div style="padding:20px; text-align:center; color:var(--text-muted);"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>'; 
        
        const fd = new FormData();
        fd.append('get_class_students', 1);
        fd.append('class_id', id);

        fetch('gate_check.php', {method:'POST', body: fd})
        .then(r=>r.json())
        .then(d=>{ 
            let html = '<div class="student-grid">'; 
            d.students.forEach(s => { 
                html += `<div class="student-card" onclick="selectStudent('${s.id}', '${s.name}', '${s.class_name}', '${s.code}')"><div class="st-name">${s.name}</div><div class="st-code">${s.code}</div></div>`; 
            }); 
            html += '</div>'; container.innerHTML = html; 
        }); 
    }

    // --- CHỌN HỌC SINH ---
    function selectStudent(id, name, cls, code) {
        currentStudentId = id;
        document.getElementById('resultBox').style.display = 'none'; 
        document.getElementById('violationForm').style.display = 'block';
        document.getElementById('stuName').innerText = name;
        document.getElementById('stuClass').innerText = cls + (code ? ` (${code})` : '');
        scrollToFormStart('violationForm');
    }

    function scrollToFormStart(id) {
        setTimeout(() => {
            const element = document.getElementById(id);
            if (!element) return;
            const scrollContainer = document.querySelector('.main-content');
            const header = document.querySelector('.mobile-header');
            const headerHeight = (header && header.offsetHeight > 0) ? header.offsetHeight : 60;
            const offset = headerHeight + 10;
            const isMobileScroll = window.getComputedStyle(document.body).overflow === 'hidden';

            if (isMobileScroll && scrollContainer) {
                const containerRect = scrollContainer.getBoundingClientRect();
                const elementRect = element.getBoundingClientRect();
                const targetScrollTop = scrollContainer.scrollTop + (elementRect.top - containerRect.top) - offset;
                scrollContainer.scrollTo({ top: targetScrollTop, behavior: "smooth" });
            } else {
                const elementRect = element.getBoundingClientRect();
                const target = window.scrollY + elementRect.top - offset;
                window.scrollTo({ top: target, behavior: "smooth" });
            }
        }, 150);
    }

    // --- SUBMIT VIOLATION (CÓ DEBUG PUSH) ---
    function submitViolation() { 
        if(!currentStudentId) return alert('Chưa chọn học sinh!'); 
        const btn = document.querySelector('#violationForm button'); 
        const oldText = btn.innerHTML; 
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...'; 
        
        const fd = new FormData(); 
        fd.append('student_id', currentStudentId); 
        fd.append('week', document.getElementById('globalWeekInput').value); 
        fd.append('other_note', document.getElementById('noteInput').value); 
        if(document.getElementById('chkTime').checked) fd.append('custom_time', document.getElementById('customTime').value); 
        
        // Thêm dấu [] vào tên key để PHP hiểu đây là mảng
        document.querySelectorAll('input[name="v_ids"]:checked').forEach(c => fd.append('violation_ids[]', c.value));
        
        fetch('gate_check.php', {method:'POST', body:fd})
        .then(r => r.json())
        .then(d => { 
            btn.disabled = false; btn.innerHTML = oldText; 
            
            // [DEBUG] HIỂN THỊ LOG PUSH RA CONSOLE
            if (d.push_debug && d.push_debug.length > 0) {
                console.group("🔍 DEBUG PUSH NOTIFICATION");
                d.push_debug.forEach(line => {
                    if (line.includes("✅")) console.log("%c" + line, "color: green; font-weight: bold;");
                    else if (line.includes("❌") || line.includes("💥")) console.log("%c" + line, "color: red; font-weight: bold;");
                    else console.log(line);
                });
                console.groupEnd();
            }

            if(d.status === 'success') { 
                if(typeof Toastify !== 'undefined') Toastify({text:"✅ Đã lưu!", style:{background:"#10b981"}}).showToast(); 
                clearSelection(); 
                
                const historyList = document.getElementById('historyList'); 
                if(historyList.children.length === 1 && historyList.children[0].innerText.includes('Chưa có')) { historyList.innerHTML = ''; } 
                
                if(d.new_data) { 
                    d.new_data.forEach(item => { 
                        const div = document.createElement('div'); div.className = 'history-item'; div.id = 'rec_' + item.id; 
                        div.innerHTML = `<div><b>${item.student_name}</b> <small>(${item.class_name})</small><br><span style="color:var(--danger-color); font-weight:500;">- ${item.violation_name}</span><br><small style="color:var(--text-muted);">${item.time_str}</small></div><button onclick="deleteRecord('${item.id}')" class="btn-delete-record" style="border-radius:4px; padding:5px 8px; cursor:pointer;"><i class="fas fa-trash"></i></button>`; 
                        historyList.prepend(div); 
                    }); 
                } 
            } else { alert("Lỗi: " + d.msg); } 
        }).catch(e => { btn.disabled = false; btn.innerHTML = oldText; alert("Lỗi kết nối!"); console.error(e); }); 
    }

    // --- DELETE VIOLATION ---
    function deleteRecord(id) { 
        if(!confirm('Bạn có chắc muốn xóa lỗi này?')) return; 
        const btn = document.querySelector(`#rec_${id} button`); 
        const originalHtml = btn ? btn.innerHTML : '<i class="fas fa-trash"></i>'; 
        if(btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; btn.disabled = true; } 
        
        const fd = new FormData();
        fd.append('delete_id', id);

        fetch('gate_check.php', {method:'POST', body: fd})
        .then(r=>r.json())
        .then(d=>{ 
            if(d.status==='success') { 
                const el = document.getElementById('rec_'+id); 
                if(el) { el.style.transition = 'all 0.3s ease'; el.style.opacity = '0'; el.style.transform = 'translateX(20px)'; setTimeout(() => el.remove(), 300); } 
                if(typeof Toastify !== 'undefined') Toastify({text:"✅ Đã xóa", style:{background:"#10b981"}}).showToast(); 
            } else { 
                alert(d.msg); if(btn) { btn.innerHTML = originalHtml; btn.disabled = false; } 
            } 
        }).catch(e => { alert('Lỗi kết nối'); if(btn) { btn.innerHTML = originalHtml; btn.disabled = false; } }); 
    }

    function clearSelection() { currentStudentId = null; document.getElementById('violationForm').style.display = 'none'; document.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked=false); document.getElementById('noteInput').value = ''; }
    function toggleTimeInput() { document.getElementById('timeInputArea').style.display = document.getElementById('chkTime').checked ? 'block' : 'none'; }
    function toggleDropdown(e, el) { e.stopPropagation(); closeAllSelects(el); el.nextElementSibling.style.display = el.nextElementSibling.style.display==='block'?'none':'block'; el.classList.toggle('active'); }
    function closeAllSelects(except) { document.querySelectorAll('.select-items').forEach(i => { if(i!==except?.nextElementSibling) i.style.display='none'; }); document.querySelectorAll('.select-selected').forEach(e => { if(e!==except) e.classList.remove('active'); }); }
    document.addEventListener('click', () => closeAllSelects());
</script>

<?php include 'includes/footer.php'; ?>