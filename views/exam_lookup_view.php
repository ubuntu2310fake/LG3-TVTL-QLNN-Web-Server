<?php
// Lấy thông tin người dùng hiện tại từ Session để soi khớp hiển thị
$currSbd = $_SESSION['user']['username'] ?? '';
$currRole = $_SESSION['user']['role'] ?? '';
?>
<style>
    /* CSS WINDOWS 11 SPINNER */
    .win11-spinner {
        width: 24px; height: 24px;
        border: 3px solid rgba(0, 120, 212, 0.2);
        border-top-color: #0078d4;
        border-radius: 50%;
        animation: win11-spin 0.8s infinite cubic-bezier(0.53, 0.21, 0.29, 0.67);
        display: inline-block; vertical-align: middle;
    }
    @keyframes win11-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

    /* CSS DROPDOWN TÙY CHỈNH */
    .custom-select-container { position: relative; min-width: 200px; flex: 1; }
    .select-selected { background-color: var(--bg-input); border: 1px solid var(--border-color); padding: 0 12px; height: 42px; display: flex; align-items: center; justify-content: space-between; color: var(--text-main); cursor: pointer; width: 100%; border-radius: 8px; transition: all 0.2s; }
    .select-items { position: absolute; top: 105%; left: 0; right: 0; z-index: 1000; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; max-height: 300px; overflow-y: auto; display: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    .select-selected:active, .select-selected.active { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(0,95,186,0.15); }
    .select-arrow { width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid var(--text-muted); transition: 0.2s; flex-shrink: 0; }
    .select-selected.active .select-arrow { transform: rotate(180deg); }
    .select-items div { padding: 10px 15px; cursor: pointer; border-bottom: 1px solid var(--border-color); font-size: 14px; color: var(--text-main); }
    .select-items div:hover { background: var(--bg-hover); color: var(--primary-color); font-weight: 600; }

    /* CSS BẢNG TÌM KIẾM & KHÓA CỘT */
    html, body { overflow-x: hidden !important; width: 100%; max-width: 100vw; }
    .table-responsive { width: 100%; max-width: 100%; max-height: 65vh; overflow-x: auto !important; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 8px; position: relative; -webkit-overflow-scrolling: touch; }
    .lookup-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 800px; }
    .lookup-table th, .lookup-table td { padding: 8px 10px; text-align: left; border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color); font-size: 13px; color: var(--text-main); background: var(--bg-card); white-space: nowrap; }
    .lookup-table thead th { position: sticky; top: 0; z-index: 10; background: var(--bg-hover); border-bottom: 2px solid var(--border-color); }
    .sticky-col-1 { position: sticky !important; left: 0; width: 100px; min-width: 100px; max-width: 100px; z-index: 5; overflow: hidden; text-overflow: ellipsis; }
    .sticky-col-2 { position: sticky !important; left: 100px; width: 200px; min-width: 200px; max-width: 200px; z-index: 5; overflow: hidden; text-overflow: ellipsis; }
    .sticky-col-3 { position: sticky !important; left: 300px; width: 90px; min-width: 90px; max-width: 90px; z-index: 5; overflow: hidden; text-overflow: ellipsis; border-right: 2px solid var(--primary-color) !important; }
    .lookup-table thead .sticky-col-1, .lookup-table thead .sticky-col-2, .lookup-table thead .sticky-col-3 { z-index: 15 !important; background: var(--bg-hover) !important; }
    [data-theme="dark"] .lookup-table th, [data-theme="dark"] .lookup-table td { background: var(--bg-card); }
    [data-theme="dark"] .lookup-table thead th { background: #0f172a; }
    [data-theme="dark"] .lookup-table thead .sticky-col-1, [data-theme="dark"] .lookup-table thead .sticky-col-2, [data-theme="dark"] .lookup-table thead .sticky-col-3 { background: #0f172a !important; }

    /* CSS CHẾ ĐỘ CARD */
    .card-mode-active.table-responsive { border: none !important; overflow: visible !important; max-height: none !important; }
    .card-mode-active .lookup-table { display: block; width: 100%; min-width: 0; }
    .card-mode-active .lookup-table thead { display: none; } 
    .card-mode-active .lookup-table tbody { display: flex; flex-direction: column; gap: 12px; }
    .card-mode-active .lookup-table tr { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex; flex-direction: column; }
    .card-mode-active .lookup-table td { display: flex; justify-content: space-between; align-items: center; border: none !important; padding: 6px 0; border-bottom: 1px dashed var(--border-color) !important; white-space: normal !important; }
    .card-mode-active .lookup-table td:last-child { border-bottom: none !important; }
    .card-mode-active .lookup-table td::before { content: attr(data-label); color: var(--text-muted); font-weight: 600; margin-right: 10px; }
    .card-mode-active .sticky-col-1, .card-mode-active .sticky-col-2, .card-mode-active .sticky-col-3 { position: static !important; width: auto !important; min-width: 0 !important; max-width: none !important; }

    /* CSS PHÂN TRANG */
    .page-btn { padding: 6px 12px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.2s; }
    .page-btn:hover { background: var(--bg-hover); border-color: var(--primary-color); color: var(--primary-color); }
    .page-btn.active { background: #005fba; color: #ffffff !important; border-color: #005fba; }
    [data-theme="dark"] .page-btn.active {
        /* Chuyển màu chữ thành màu tối (hoặc dùng var(--bg-card)) để tương phản với nền sáng */
        color: #0f172a !important; 
        font-weight: 700;
    }
    .page-btn:disabled { opacity: 0.5; cursor: not-allowed; }

    @media screen and (max-width: 768px) {
        .lookup-table { min-width: 500px; }
        .lookup-table th, .lookup-table td { font-size: 12px !important; padding: 8px 6px !important; }
        .sticky-col-1 { left: 0 !important; width: 75px !important; min-width: 75px !important; max-width: 75px !important; }
        .sticky-col-2 { left: 75px !important; width: 140px !important; min-width: 140px !important; max-width: 140px !important; white-space: normal !important; line-height: 1.4; }
        .sticky-col-3 { left: 215px !important; width: 55px !important; min-width: 55px !important; max-width: 55px !important; }
    }

    /* ĐỒNG BỘ CÁC CHẾ ĐỘ ANIMATION VỚI APP_SHELL_PRO */
    .fx-off .win11-spinner { animation: none !important; }
    .fx-off .select-selected, .fx-off .select-items, .fx-off .select-arrow, .fx-off .page-btn { transition: none !important; animation: none !important; }
</style>

<div class="win-card page-transition">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="color: var(--primary-color); margin: 0;"><i class="fas fa-shield-alt" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tra cứu điểm thi bảo mật' : 'Secure Exam Score Lookup') ?></h2>
        <button class="win-btn win-btn-secondary" id="btnToggleLayout" style="display: none;">
            <i class="fas fa-mobile-alt" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chuyển đổi giao diện' : 'Toggle View Layout') ?>
        </button>
    </div>

    <div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
        <div class="custom-select-container" id="customExamSelector">
            <div class="select-selected" onclick="window.toggleDropdown(event, this)" role="button" tabindex="0" aria-haspopup="listbox" onkeypress="if(event.key==='Enter'||event.key===' ') this.click();">
                <span id="txtSelectedExam" style="font-weight:600;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang tải danh sách...' : 'Loading exams...') ?></span>
                <div class="select-arrow"></div>
            </div>
            <div class="select-items" id="examDropdownItems"></div>
        </div>

        <div style="flex: 2; display: flex; gap: 8px; min-width: 250px;">
            <label for="searchScore" style="display: none;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tìm kiếm...' : 'Search...') ?></label>
            <input type="text" id="searchScore" class="win-input" style="flex: 1; margin-bottom: 0;" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tìm kiếm...' : 'Search...') ?>">
            <button class="win-btn" id="btnSearchAction" style="margin: 0; padding: 0 25px; white-space: nowrap;">
                <i class="fas fa-search" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tìm kiếm' : 'Search') ?>
            </button>
        </div>
    </div>

    <div id="searchPromptMsg" style="text-align: center; padding: 60px 20px; border: 2px dashed var(--border-color); border-radius: 12px; margin-bottom: 20px;">
        <div id="promptStatusContent">
            <i class="fas fa-fingerprint" style="font-size: 50px; color: var(--primary-color); opacity: 0.5; margin-bottom: 15px;" aria-hidden="true"></i>
            <h3><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Yêu cầu xác thực' : 'Authentication Required') ?></h3>
            <p style="color: var(--text-muted);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vui lòng đăng nhập để xem thông tin.' : 'Please login to view information.') ?></p>
            
            <?php if (in_array($currRole, ['ADMIN', 'TEACHER'])): ?>
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px dashed var(--border-color);">
                <button class="win-btn win-btn-danger" id="btnShowAllStaff" style="margin: 0; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);">
                    <i class="fas fa-unlock-alt" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mở khóa toàn bộ (Giáo viên)' : 'Unlock all (Teacher only)') ?>
                </button>
            </div>
            <?php endif; ?>
        </div>
        <div id="searchLoadingContent" style="display: none;">
            <div class="win11-spinner" style="width: 40px; height: 40px; margin-bottom: 15px;"></div>
            <h3 style="color: var(--primary-color);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang kết nối cơ sở dữ liệu...' : 'Connecting to database...') ?></h3>
            <p><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vui lòng đợi trong giây lát...' : 'Please wait a moment...') ?></p>
        </div>
    </div>

    <div id="appealContainer" style="display: none; margin-bottom: 20px; background: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b; padding: 15px; border-radius: 8px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div style="color: #b45309; font-size: 14px;"><strong><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Phát hiện dữ liệu cá nhân' : 'Personal data detected') ?></strong> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn có thể yêu cầu phúc khảo' : 'You can submit an appeal') ?></div>
            <button class="win-btn" style="background: #f59e0b;" id="btnGlobalAppeal"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Phúc khảo ngay' : 'Appeal now') ?></button>
        </div>
    </div>

    <div id="scoreContainer" class="table-responsive" style="display: none;">
        <table class="lookup-table" id="tableScore">
            <thead id="scoreHeader"></thead>
            <tbody id="scoreBody"></tbody>
        </table>
    </div>
    
    <div id="pagination" style="margin-top: 20px; justify-content: flex-end; gap: 5px; flex-wrap: wrap; display: none;"></div>
</div>

<script>
window.pageInit = function() {
    const searchInput = document.getElementById('searchScore');
    const btnSearch = document.getElementById('btnSearchAction');
    const examDropdownItems = document.getElementById('examDropdownItems');
    const txtSelectedExam = document.getElementById('txtSelectedExam');
    
    const scoreContainer = document.getElementById('scoreContainer');
    const paginationBox = document.getElementById('pagination');
    const promptBox = document.getElementById('searchPromptMsg');
    const statusPrompt = document.getElementById('promptStatusContent');
    const statusLoading = document.getElementById('searchLoadingContent');
    const btnToggleLayout = document.getElementById('btnToggleLayout');
    const btnShowAllStaff = document.getElementById('btnShowAllStaff');
    
    const currentLang = "<?= $_SESSION['lang'] ?? 'vi' ?>";
    const CURRENT_USER_SBD = "<?= htmlspecialchars($currSbd) ?>";
    const CURRENT_USER_ROLE = "<?= htmlspecialchars($currRole) ?>";
    let currentExamId = 0;

    let allScores = []; let filteredScores = []; let subjectMap = {};
    let currentSort = { column: 'class_name', order: 'asc' };
    let currentPage = 1; const itemsPerPage = 50; 

    window.toggleDropdown = function(e, el) { e.stopPropagation(); window.closeAllSelects(el); el.nextElementSibling.style.display = el.nextElementSibling.style.display === 'block' ? 'none' : 'block'; el.classList.toggle('active'); };
    window.closeAllSelects = function(except) { document.querySelectorAll('.select-items').forEach(i => { if(i !== except?.nextElementSibling) i.style.display = 'none'; }); document.querySelectorAll('.select-selected').forEach(e => { if(e !== except) e.classList.remove('active'); }); };
    document.onclick = function(e) { window.closeAllSelects(null); };

    window.selectExam = function(id, name, el) {
        txtSelectedExam.innerText = name; el.parentElement.style.display = 'none'; document.querySelector('.select-selected').classList.remove('active');
        fetchScores(id);
    };

    const loadExamList = async () => {
        try {
            const res = await fetch('api/exam_data_api.php?action=list'); const json = await res.json();
            if(json.status === 'success' && json.data.length > 0) {
                let optionsHtml = ''; json.data.forEach(ex => { optionsHtml += `<div onclick="window.selectExam('${ex.id}', '${ex.exam_name}', this)" role="option" tabindex="0" onkeypress="if(event.key==='Enter'||event.key===' ') this.click();">${ex.exam_name}</div>`; });
                examDropdownItems.innerHTML = optionsHtml; txtSelectedExam.innerText = json.data[0].exam_name;
                fetchScores(json.data[0].id);
            }
        } catch(e) { console.error(e); }
    };

    const fetchScores = async (id) => {
        currentExamId = id; document.getElementById('appealContainer').style.display = 'none'; 
        // Reset giao diện
        scoreContainer.style.display = 'none'; paginationBox.style.display = 'none'; btnToggleLayout.style.display = 'none';
        promptBox.style.display = 'block'; statusPrompt.style.display = 'block'; statusLoading.style.display = 'none';
        searchInput.value = '';

        try {
            const res = await fetch(`api/exam_data_api.php?exam_id=${id}`); const json = await res.json();
            if(json.status === 'success') {
                allScores = json.data; subjectMap = json.config;
                if (CURRENT_USER_ROLE === 'STUDENT' || CURRENT_USER_ROLE === 'RED_FLAG') {
                    const myScore = allScores.find(s => s.sbd === CURRENT_USER_SBD);
                    if (myScore) {
                        document.getElementById('appealContainer').style.display = 'block';
                        document.getElementById('btnGlobalAppeal').onclick = () => window.gc_appeal(myScore.sbd, currentExamId);
                    }
                }
            }
        } catch(e) { console.error(e); }
    };

    const getDelay = () => {
        if (document.body.classList.contains('fx-off')) return 0;
        if (document.body.classList.contains('fx-lite')) return 100;
        return 200;
    };

    // LOGIC KIỂM TRA MÃ SBD (ĐÚNG 8 SỐ) HOẶC HỌ TÊN (KHÔNG GIỚI HẠN)
    const triggerSearchAction = () => {
        const kw = searchInput.value.toLowerCase().trim();
        
        if (kw === '') {
            scoreContainer.style.display = 'none'; paginationBox.style.display = 'none'; btnToggleLayout.style.display = 'none';
            promptBox.style.display = 'block'; statusPrompt.style.display = 'block'; statusLoading.style.display = 'none';
            filteredScores = [];
            return;
        }

        // --- KHÚC LÒE GIÁM KHẢO CHỖ NÀY ---
        // Nếu người dùng CHỈ nhập toàn số (nghĩa là đang tìm SBD)
        const isNumeric = /^\d+$/.test(kw);
        if (isNumeric && kw.length !== 8) {
            Toastify({ text: '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'SBD phải bao gồm ĐÚNG 8 CHỮ SỐ. Vui lòng kiểm tra lại!' : 'Student ID must be exactly 8 digits. Please check again!') ?>', duration: 4000, gravity: "top", position: "center", style: { background: "var(--danger-color)" } }).showToast();
            return; // Chặn không cho chạy Spinner
        }
        // Nếu có nhập chữ (nghĩa là đang tìm Tên) thì bỏ qua, cho đi tiếp

        scoreContainer.style.display = 'none'; paginationBox.style.display = 'none'; btnToggleLayout.style.display = 'none';
        promptBox.style.display = 'block'; statusPrompt.style.display = 'none'; statusLoading.style.display = 'block';
        
        setTimeout(() => {
            filteredScores = allScores.filter(s => (s.sbd && s.sbd.toLowerCase().includes(kw)) || (s.student_name && s.student_name.toLowerCase().includes(kw)));
            sortData(currentSort.column, currentSort.order);
            
            promptBox.style.display = 'none'; statusLoading.style.display = 'none';
            scoreContainer.style.display = ''; paginationBox.style.display = 'flex'; btnToggleLayout.style.display = 'block';
        }, getDelay()); 
    };

    // ĐẶC QUYỀN GIÁO VIÊN: BUNG TOÀN BỘ BẢNG
    if (btnShowAllStaff) {
        btnShowAllStaff.onclick = () => {
            scoreContainer.style.display = 'none'; paginationBox.style.display = 'none'; btnToggleLayout.style.display = 'none';
            promptBox.style.display = 'block'; statusPrompt.style.display = 'none'; statusLoading.style.display = 'block';
            
            setTimeout(() => {
                filteredScores = [...allScores]; // Copy nguyên cái mảng gốc ra
                sortData(currentSort.column, currentSort.order);
                
                promptBox.style.display = 'none'; statusLoading.style.display = 'none';
                scoreContainer.style.display = ''; paginationBox.style.display = 'flex'; btnToggleLayout.style.display = 'block';
            }, getDelay());
        };
    }

    btnSearch.onclick = triggerSearchAction;
    searchInput.onkeydown = (e) => { if(e.key === 'Enter') triggerSearchAction(); };

    const sortData = (column, order) => {
        currentSort = { column, order };
        filteredScores.sort((a, b) => {
            const valA = a[column] || ''; const valB = b[column] || ''; let comparison = 0;
            if (typeof valA === 'string' && typeof valB === 'string') { comparison = valA.localeCompare(valB, 'vi', { numeric: true, sensitivity: 'base' }); } 
            else { comparison = valA > valB ? 1 : (valA < valB ? -1 : 0); }
            if (comparison === 0 && column === 'class_name') { comparison = (a.sbd || '').localeCompare((b.sbd || ''), 'vi', { numeric: true }); }
            return order === 'asc' ? comparison : -comparison;
        });
        currentPage = 1; render();
    };

    window.gc_sortColumn = (column) => { const newOrder = (currentSort.column === column && currentSort.order === 'asc') ? 'desc' : 'asc'; sortData(column, newOrder); };
    window.gc_changePage = (page) => { currentPage = page; render(); };

    const render = () => {
        const thead = document.getElementById('scoreHeader'); const tbody = document.getElementById('scoreBody');
        const getSortIcon = (col) => { if (currentSort.column !== col) return '<i class="fas fa-sort" style="color:#ccc; font-size:11px; margin-left:6px;" aria-hidden="true"></i>'; return currentSort.order === 'asc' ? '<i class="fas fa-sort-up" style="color:var(--primary-color); margin-left:6px;" aria-hidden="true"></i>' : '<i class="fas fa-sort-down" style="color:var(--primary-color); margin-left:6px;" aria-hidden="true"></i>'; };

        const subjectTranslations = currentLang === 'en' ? {
            'Toán': 'Math',
            'Văn': 'Literature',
            'Anh': 'English',
            'Lý': 'Physics',
            'Hóa': 'Chemistry',
            'Sinh': 'Biology',
            'Sử': 'History',
            'Địa': 'Geography',
            'GDCD': 'Civic Education',
            'KTPL': 'Civic Education',
            'CNCN': 'Technology (Ind)',
            'CNNN': 'Technology (Agr)',
            'Tin': 'Informatics'
        } : {};

        let headHtml = `<tr>
            <th class="sticky-col-1" style="cursor:pointer;" tabindex="0" role="button" aria-label="Sort by SBD" onkeypress="if(event.key==='Enter'||event.key===' ') window.gc_sortColumn('sbd');" onclick="window.gc_sortColumn('sbd')">${'<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'SBD' : 'Student ID') ?>'} ${getSortIcon('sbd')}</th>
            <th class="sticky-col-2" style="cursor:pointer;" tabindex="0" role="button" aria-label="Sort by Name" onkeypress="if(event.key==='Enter'||event.key===' ') window.gc_sortColumn('student_name');" onclick="window.gc_sortColumn('student_name')">${'<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Họ Tên' : 'Full Name') ?>'} ${getSortIcon('student_name')}</th>
            <th class="sticky-col-3" style="cursor:pointer;" tabindex="0" role="button" aria-label="Sort by Class" onkeypress="if(event.key==='Enter'||event.key===' ') window.gc_sortColumn('class_name');" onclick="window.gc_sortColumn('class_name')">${'<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp' : 'Class') ?>'} ${getSortIcon('class_name')}</th>`;
        for (const sub in subjectMap) headHtml += `<th>${subjectTranslations[sub] || sub}</th>`;
        thead.innerHTML = headHtml + `</tr>`;

        const totalItems = filteredScores.length; const totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
        if(currentPage < 1) currentPage = 1; if(currentPage > totalPages) currentPage = totalPages;
        const startIndex = (currentPage - 1) * itemsPerPage; const paginatedData = filteredScores.slice(startIndex, startIndex + itemsPerPage);

        tbody.innerHTML = paginatedData.map(row => {
            let cells = `<td class="sticky-col-1" data-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'SBD' : 'Student ID') ?>"><b>${row.sbd}</b></td><td class="sticky-col-2" data-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Họ Tên' : 'Full Name') ?>">${row.student_name}</td><td class="sticky-col-3" data-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp' : 'Class') ?>">${row.class_name}</td>`;
            for (const subName in subjectMap) {
                const cfg = subjectMap[subName];
                const transSubName = subjectTranslations[subName] || subName;
                if (cfg.TN && cfg.TL && cfg.TONG) { cells += `<td data-label="${transSubName}"><div style="display:flex; gap:4px; font-size:11px; justify-content:center; align-items:center;"><span>${row[cfg.TN]}</span> + <span>${row[cfg.TL]}</span> = <b style="color:var(--primary-color); font-size:13px;">${row[cfg.TONG]}</b></div></td>`; } 
                else if(cfg.TONG) { cells += `<td data-label="${transSubName}"><b>${row[cfg.TONG]}</b></td>`; } 
                else { cells += `<td data-label="${transSubName}">-</td>`; }
            }
            return `<tr>${cells}</tr>`;
        }).join('');
        
        if (paginatedData.length === 0) tbody.innerHTML = `<tr><td colspan="30" style="text-align:center; padding:20px; color:var(--text-muted);">${'<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Không tìm thấy dữ liệu phù hợp' : 'No matching data found') ?>'}</td></tr>`;

        let pagHTML = '';
        if (totalPages > 1) {
            pagHTML += `<button class="page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="window.gc_changePage(${currentPage - 1})"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>`;
            let startBtn = Math.max(1, currentPage - 2); let endBtn = Math.min(totalPages, currentPage + 2);
            if (startBtn > 1) pagHTML += `<button class="page-btn" onclick="window.gc_changePage(1)">1</button>${startBtn > 2 ? '<span style="padding: 5px;">...</span>' : ''}`;
            for (let i = startBtn; i <= endBtn; i++) { pagHTML += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="window.gc_changePage(${i})">${i}</button>`; }
            if (endBtn < totalPages) pagHTML += `${endBtn < totalPages - 1 ? '<span style="padding: 5px;">...</span>' : ''}<button class="page-btn" onclick="window.gc_changePage(${totalPages})">${totalPages}</button>`;
            pagHTML += `<button class="page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="window.gc_changePage(${currentPage + 1})"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>`;
        }
        paginationBox.innerHTML = pagHTML;
    };

    window.gc_appeal = async (sbd, examId) => {
        const reason = prompt('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập lý do phúc khảo:' : 'Enter appeal reason:') ?>');
        if(reason && reason.trim() !== '') {
            const fd = new FormData(); fd.append('sbd', sbd); fd.append('exam_id', examId); fd.append('reason', reason);
            try {
                const res = await fetch('api/exam_appeal_api.php', { method: 'POST', body: fd }); const json = await res.json();
                if(json.status === 'success') { Toastify({ text: '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Gửi yêu cầu phúc khảo thành công!' : 'Appeal submitted successfully!') ?>', duration: 4000, style: { background: "#10b981" } }).showToast(); } 
                else { alert('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'LỖI:\n' : 'ERROR:\n') ?>' + json.msg); }
            } catch(e) {}
        }
    };

    document.getElementById('btnToggleLayout').onclick = () => document.getElementById('scoreContainer').classList.toggle('card-mode-active');
    if(window.innerWidth < 992) document.getElementById('scoreContainer').classList.add('card-mode-active');

    loadExamList();
};

window.pageDestroy = function() {
    document.onclick = null; window.toggleDropdown = null; window.closeAllSelects = null; window.selectExam = null; window.gc_sortColumn = null; window.gc_changePage = null; window.gc_appeal = null;
};
</script>