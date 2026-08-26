<?php include 'includes/header.php'; ?>

<style>
    /* BỘ LỌC CÂN BẰNG TUYỆT ĐỐI */
    .filter-bar {
        display: flex; align-items: center; gap: 15px;
        background: var(--bg-hover);
        padding: 8px 15px; border-radius: 12px;
        border: 1px solid var(--border-color);
        flex-wrap: wrap; 
    }
    .filter-element-group { display: flex; align-items: center; gap: 8px; margin: 0; }
    .filter-label { font-size: 14px; font-weight: 600; color: var(--text-muted); }
    
    .filter-input {
        height: 40px; box-sizing: border-box; border-radius: 8px;
        border: 1px solid var(--border-color);
        background: var(--bg-input); margin: 0; outline: none;
        color: var(--text-main); font-weight: 600; font-family: inherit;
        font-size: 14px; padding: 0 10px; transition: 0.2s;
    }
    .filter-input:focus { border-color: var(--primary-color); }
    .filter-input[type="number"] { text-align: center; width: 65px; }
    
    .filter-btn {
        height: 40px; width: 40px; display: flex; align-items: center; justify-content: center;
        background: var(--bg-input); border: 1px solid var(--border-color); border-radius: 8px;
        cursor: pointer; color: var(--text-muted); transition: 0.2s; margin: 0; padding: 0;
    }
    .filter-btn:hover { color: var(--primary-color); background: var(--bg-hover); }
    .filter-divider { width: 1px; height: 24px; background: var(--border-color); }

    /* DROPDOWN CUSTOM */
    .custom-select-container { margin: 0; width: 140px; position: relative; user-select: none; }
    .select-selected {
        background-color: var(--bg-input); border: 1px solid var(--border-color); border-radius: 8px;
        padding: 0 12px; display: flex; align-items: center; justify-content: space-between;
        font-size: 14px; height: 40px; box-sizing: border-box; color: var(--text-main); transition: 0.2s; cursor: pointer;
    }
    .select-selected:active, .select-selected.active { border-color: var(--primary-color); }
    .select-arrow { width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid var(--text-muted); transition: 0.2s; }
    .select-selected.active .select-arrow { transform: rotate(180deg); }
    
    .select-items { position: absolute; top: calc(100% + 5px); left: 0; right: 0; z-index: 1000; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; max-height: 250px; overflow-y: auto; display: none; box-shadow: 0 10px 25px rgba(0,0,0,0.5); animation: fadeIn 0.2s ease; }
    .select-items .optgroup-title { padding: 8px 12px; font-size: 11px; font-weight: bold; color: var(--text-muted); text-transform: uppercase; background: var(--bg-hover); cursor: default; }
    .select-items .opt-item { padding: 10px 15px; cursor: pointer; border-bottom: 1px solid var(--border-color); font-size: 13px; color: var(--text-main); transition: 0.2s; }
    .select-items .opt-item:last-child { border-bottom: none; }
    .select-items .opt-item:hover { background: var(--bg-hover); color: var(--primary-color); font-weight: 600; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

    /* =======================================================
       GHI ĐÈ CÁC MÃ MÀU TRONG THẺ HTML CỦA POPUP (NO IMPORTANT)
       ======================================================= */
    .rank-table .rank-hover-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    }
    .rank-hover-card .popup-header {
        border-bottom: 2px solid var(--border-color);
    }
    .rank-hover-card .popup-title {
        color: var(--primary-color);
    }
    .rank-hover-card .popup-week {
        background: var(--bg-input);
        color: var(--text-main);
        border: 1px solid var(--border-color);
    }
    .rank-hover-card .popup-row {
        border-bottom: 1px dashed var(--border-color);
    }
    .rank-hover-card .popup-label {
        color: var(--text-muted);
    }
    .rank-hover-card .popup-val {
        color: var(--text-main);
    }
    /* Xóa màu inline icon của HTML */
    .rank-hover-card .popup-label i[style] {
        color: inherit !important;
    }
    .rank-hover-card .val-minus {
        color: var(--danger-color);
        background: transparent;
        border: 1px solid var(--danger-color);
    }
    .rank-hover-card .popup-total-area {
        border-top: 2px solid var(--border-color);
    }
</style>

<div style="background: var(--bg-card); padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); margin-bottom: 30px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px; margin-bottom: 25px;">
        <h2 style="color:var(--primary-color); margin:0; font-size: 20px; display:flex; align-items:center; gap:8px;">
            <i class="fas fa-trophy" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bảng xếp hạng' : 'Ranking') ?>
        </h2>

        <div class="filter-bar">
            
            <form method="GET" class="filter-element-group" onsubmit="window.submitRankForm(event)">
                <input type="hidden" name="filter_type" value="week">
                <span class="filter-label"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'Week') ?>:</span>
                <input type="number" name="week" value="<?= $filter_type == 'week' ? $selected_week : '' ?>" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'Week') ?>" class="filter-input">
                <button type="submit" class="filter-btn" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tìm kiếm' : 'Search') ?>"><i class="fas fa-search" aria-hidden="true"></i></button>
            </form>

            <div class="filter-divider"></div>

            <form id="filterForm" method="GET" class="filter-element-group">
                <input type="hidden" name="filter_type" value="month">
                <input type="hidden" name="month" id="hiddenFilter" value="<?= $filter_type == 'month' ? $selected_month : '' ?>">
                
                <span class="filter-label"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tháng' : 'Month') ?>:</span>
                
                <div class="custom-select-container">
                    <div class="select-selected" onclick="toggleDropdown(event, this)" role="button" tabindex="0">
                        <span id="txtSelectedFilter" style="font-weight:600; color:var(--text-main);">
                            <?= $filter_type == 'month' ? $filter_label : (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chọn' : 'Select') ?>
                        </span>
                        <div class="select-arrow"></div>
                    </div>
                    <div class="select-items">
                        <div class="optgroup-title"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Semester Year' : 'Semester Year') ?></div>
                        <div class="opt-item" onclick="submitFilter('hk1', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Semester 1' : 'Semester 1') ?>', this)" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Semester 1' : 'Semester 1') ?></div>
                        <div class="opt-item" onclick="submitFilter('hk2', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Semester 2' : 'Semester 2') ?>', this)" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Semester 2' : 'Semester 2') ?></div>
                        <div class="opt-item" onclick="submitFilter('year', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Whole Year' : 'Whole Year') ?>', this)" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Whole Year' : 'Whole Year') ?></div>
                        
                        <div class="optgroup-title"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Each Month' : 'Each Month') ?></div>
                        <div class="opt-item" onclick="submitFilter('m_8', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 8' : 'Month 8') ?>', this)" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 8' : 'Month 8') ?></div>
                        <div class="opt-item" onclick="submitFilter('m_9', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 9' : 'Month 9') ?>', this)" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 9' : 'Month 9') ?></div>
                        <div class="opt-item" onclick="submitFilter('m_10', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 10' : 'Month 10') ?>', this)" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 10' : 'Month 10') ?></div>
                        <div class="opt-item" onclick="submitFilter('m_11', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 11' : 'Month 11') ?>', this)" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 11' : 'Month 11') ?></div>
                        <div class="opt-item" onclick="submitFilter('m_12', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 12' : 'Month 12') ?>', this)" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 12' : 'Month 12') ?></div>
                        <div class="opt-item" onclick="submitFilter('m_1', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 1' : 'Month 1') ?>', this)" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 1' : 'Month 1') ?></div>
                        <div class="opt-item" onclick="submitFilter('m_2', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 2' : 'Month 2') ?>', this)" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 2' : 'Month 2') ?></div>
                        <div class="opt-item" onclick="submitFilter('m_3', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 3' : 'Month 3') ?>', this)" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 3' : 'Month 3') ?></div>
                        <div class="opt-item" onclick="submitFilter('m_4', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 4' : 'Month 4') ?>', this)" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 4' : 'Month 4') ?></div>
                        <div class="opt-item" onclick="submitFilter('m_5', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 5' : 'Month 5') ?>', this)" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Month 5' : 'Month 5') ?></div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div style="padding:10px 15px; border-radius:8px; border-left:4px solid var(--primary-color); margin-bottom: 25px; font-size: 13px; color:var(--text-main); font-weight:500; background:var(--bg-hover);">
        <?php if($filter_type === 'month'): ?>
            <i class="fas fa-info-circle" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Displaying Average' : 'Displaying Average') ?> <strong><?= $filter_label ?></strong> (<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Summarized From' : 'Summarized From') ?> <strong style="color:var(--primary-color);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'Week') ?> <?= $start_week ?></strong> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'To Word' : 'To Word') ?> <strong style="color:var(--primary-color);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'Week') ?> <?= $end_week ?></strong>).<br>
            <small style="color: var(--danger-color); font-weight: normal; display: block; margin-top: 4px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ranking Note Warning' : 'Ranking Note Warning') ?></small>
        <?php else: ?>
            <i class="fas fa-info-circle" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang hiển thị dữ liệu tuần' : 'Displaying data for week') ?> <strong><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'Week') ?> <?= $selected_week ?></strong>.
        <?php endif; ?>
    </div>

    <div id="rankingGroupsContainer">
    <?php foreach ($grouped_data as $group_id => $items): ?>
    <div class="ranking-group-block" data-group-id="<?= htmlspecialchars($group_id) ?>" style="margin-bottom: 30px;">
        <h3 class="group-header" style="color:var(--primary-color); border-left:4px solid var(--primary-color); padding-left:10px; margin-bottom:15px; font-size:16px;">
            <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhóm thi đua' : 'Competition Group') ?> <?= htmlspecialchars($group_id) ?>
        </h3>
        
        <table class="rank-table ranking-list-table" style="width: 100%;">
            <thead>
                <tr>
                    <th width="60" style="text-align:center;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'STT' : '#') ?></th>
                    <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp' : 'Class') ?></th>
                    <th style="text-align:center;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nền nếp' : 'Discipline') ?></th>
                    <th style="text-align:center;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Học tập' : 'Academic') ?></th>
                    <th style="text-align:center;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm TB' : 'Avg Score') ?></th>
                    <th style="text-align:center;" class="pc-only-rank"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xếp hạng' : 'Rank') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $index => $item): ?>
                <tr class="ranking-row-pc" data-class-name="<?= htmlspecialchars($item['class_name']) ?>" data-rawTotal="<?= $item['_raw_total'] ?>">
                    <td style="text-align:center; color:var(--text-muted);">
                        <span class="pc-stt-number"><?= $index + 1 ?></span>
                        <div class="mobile-rank-badge stt-merged-col">
                            <?php if ($item['rank'] == 1): ?> 🥇 
                            <?php elseif ($item['rank'] == 2): ?> 🥈 
                            <?php elseif ($item['rank'] == 3): ?> 🥉 
                            <?php else: ?> #<?= $item['rank'] ?> <?php endif; ?>
                        </div>
                    </td>

                    <td class="class-name-cell" style="font-weight:bold; color:var(--primary-color); font-size:15px;">
                        <span><?= htmlspecialchars($item['class_name']) ?></span>

                        <div class="rank-hover-card">
                            <div class="popup-header">
                                <span class="popup-title"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp' : 'Class') ?> <?= htmlspecialchars($item['class_name']) ?></span>
                                <span class="popup-week"><?= $filter_type == 'month' ? mb_strtoupper($filter_label, 'UTF-8') : mb_strtoupper((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'Week'), 'UTF-8') . " $selected_week" ?></span>
                            </div>
                            
                            <div class="popup-row">
                                <span class="popup-label"><i class="fas fa-clipboard-list" style="color:var(--primary-color);" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nền nếp' : 'Discipline') ?> (<?= $filter_type=='month'? (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'TB' : 'Avg') : (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'W') ?>):</span>
                                <span class="popup-val"><?= $item['nn'] ?></span>
                            </div>

                            <div class="popup-row">
                                <span class="popup-label"><i class="fas fa-torii-gate" style="color:var(--danger-color);" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'VPBS' : 'Gate check') ?> (<?= $filter_type=='month'? (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'TB' : 'Avg') : (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'W') ?>):</span>
                                <?php if ($item['gate_points'] > 0): ?>
                                    <span class="popup-val val-minus">-<?= $item['gate_points'] ?></span>
                                <?php else: ?>
                                    <span class="popup-val">0</span>
                                <?php endif; ?>
                            </div>

                            <div class="popup-row">
                                <span class="popup-label"><i class="fas fa-book-reader" style="color:#10b981;" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tổng tiết' : 'Total periods') ?>:</span>
                                <span class="popup-val"><?= $item['period_count'] ?> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'tiết' : 'periods') ?></span>
                            </div>

                            <?php if ($item['bonus'] > 0): ?>
                            <div class="popup-row">
                                <span class="popup-label"><i class="fas fa-star" style="color:#f59e0b;" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm thưởng' : 'Bonus points') ?> (<?= $filter_type=='month'? (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'TB' : 'Avg') : (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'W') ?>):</span>
                                <span class="popup-val" style="color:#f59e0b; font-weight:bold;">+<?= round($item['bonus'], 2) ?></span>
                            </div>
                            <?php endif; ?>

                            <div class="popup-total-area">
                                <span style="font-size:13px; font-weight:600;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm Tổng kết' : 'Total score') ?>:</span>
                                <span style="font-size:24px; font-weight:900; color:var(--primary-color);"><?= $item['tb'] ?></span>
                            </div>
                        </div>
                    </td>

                    <td style="text-align:center;" data-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nền nếp' : 'Discipline') ?>"><?= $item['nn'] ?></td>
                    <td style="text-align:center;" data-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Học tập' : 'Academic') ?>"><?= $item['ht'] ?></td>
                    <td style="font-weight:bold; color:var(--text-main); text-align:center;" data-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm TB' : 'Avg Score') ?>">
                        <?= $item['tb'] ?>
                    </td>
                    
                    <td class="pc-only-rank" style="text-align:center;">
                        <?php if ($item['rank'] == 1): ?> <span style="color:var(--text-main); font-weight:bold;">🥇 <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hạng 1' : '1st Rank') ?></span>
                        <?php elseif ($item['rank'] == 2): ?> <span style="color:var(--text-main); font-weight:bold;">🥈 <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hạng 2' : '2nd Rank') ?></span>
                        <?php elseif ($item['rank'] == 3): ?> <span style="color:var(--text-main); font-weight:bold;">🥉 <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hạng 3' : '3rd Rank') ?></span>
                        <?php else: ?> <span style="color:var(--text-main);"><?= $item['rank'] ?></span> <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
    </div><!-- /#rankingGroupsContainer -->

<script>
    window.pageDestroy = function() {
        document.onclick = null;
        if(window.rankingHoverTimeout) clearTimeout(window.rankingHoverTimeout);
    };

    window.pageInit = function() {
        document.onclick = function() { window.closeAllSelects(); };
        if (window.innerWidth > 1024) {
            const rows = document.querySelectorAll('.ranking-row-pc');
            rows.forEach(row => {
                const card = row.querySelector('.rank-hover-card');
                if (!card) return;
                row.onmouseenter = function() {
                    const rowRect = row.getBoundingClientRect(); const viewportHeight = window.innerHeight; const spaceBelow = viewportHeight - rowRect.bottom;
                    card.classList.remove('show-above', 'show-below'); if (spaceBelow < 320) card.classList.add('show-above'); else card.classList.add('show-below'); 
                    clearTimeout(window.rankingHoverTimeout); window.rankingHoverTimeout = setTimeout(() => { card.classList.add('is-visible'); }, 500); 
                };
                row.onmouseleave = function() { clearTimeout(window.rankingHoverTimeout); card.classList.remove('is-visible'); };
            });
        }
    };

    window.toggleDropdown = function(e, el) { e.stopPropagation(); window.closeAllSelects(el); el.nextElementSibling.style.display = el.nextElementSibling.style.display==='block'?'none':'block'; el.classList.toggle('active'); };
    window.closeAllSelects = function(except) { document.querySelectorAll('.select-items').forEach(i => { if(i!==except?.nextElementSibling) i.style.display='none'; }); document.querySelectorAll('.select-selected').forEach(e => { if(e!==except) e.classList.remove('active'); }); };
    
    window.submitRankForm = function(e) {
        if(typeof window.loadPage === 'function') {
            e.preventDefault();
            let params = new URLSearchParams(new FormData(e.target)).toString();
            window.loadPage('ranking.php?' + params, true);
        }
    };

    window.submitFilter = function(value, text, itemEl) { 
        document.getElementById('txtSelectedFilter').innerText = text; 
        document.getElementById('hiddenFilter').value = value; 
        let form = document.getElementById('filterForm');
        if(typeof window.loadPage === 'function') {
            let params = new URLSearchParams(new FormData(form)).toString();
            window.loadPage('ranking.php?' + params, true);
        } else {
            form.submit();
        }
    };

    // === DataManager: Cập nhật bảng xếp hạng trực tiếp, không load lại (mỗi 2 phút) ===
    window.pageInit = (function(_orig) {
        return function() {
            if (_orig) _orig();
            if (!window.DataManager) return;

            const urlParams = new URLSearchParams(window.location.search);
            const currentWeek = urlParams.get('week') || '';
            const filterType  = urlParams.get('filter_type') || 'week';
            if (filterType !== 'week') return; // Chế độ tháng → không poll

            // Thay thế PJAX bằng JSON Data + DOM Sorting để giữ nguyên Hover Cards và tạo hiệu ứng mượt mà
            const updateRankingList = async () => {
                if (filterType !== 'week') return;
                try {
                    let url = window.location.href;
                    url += (url.includes('?') ? '&' : '?') + 'json=1&_t=' + new Date().getTime();
                    const res = await fetch(url, { headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache' }, credentials: 'same-origin' });
                    const data = await res.json();
                    
                    if (data.status === 'success' && data.grouped_data) {
                        for (const groupName in data.grouped_data) {
                            const groupItems = data.grouped_data[groupName];
                            groupItems.forEach(item => {
                                const row = document.querySelector(`.ranking-row-pc[data-class-name="${item.class_name}"]`);
                                if (!row) return;
                                
                                const nnCell = row.cells[2];
                                if (nnCell) nnCell.innerText = item.nn;
                                
                                const htCell = row.cells[3];
                                if (htCell) htCell.innerText = item.ht;
                                
                                const tbCell = row.cells[4];
                                if (tbCell) tbCell.innerText = item.tb;
                                
                                const firstRankText = '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Hạng 1" : "1st Rank") ?>';
                                const secondRankText = '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Hạng 2" : "2nd Rank") ?>';
                                const thirdRankText = '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Hạng 3" : "3rd Rank") ?>';

                                const rankCell = row.querySelector('.pc-only-rank');
                                if (rankCell) {
                                    if (item.rank == 1) rankCell.innerHTML = `<span style="color:var(--text-main); font-weight:bold;">🥇 ${firstRankText}</span>`;
                                    else if (item.rank == 2) rankCell.innerHTML = `<span style="color:var(--text-main); font-weight:bold;">🥈 ${secondRankText}</span>`;
                                    else if (item.rank == 3) rankCell.innerHTML = `<span style="color:var(--text-main); font-weight:bold;">🥉 ${thirdRankText}</span>`;
                                    else rankCell.innerHTML = `<span style="color:var(--text-main);">${item.rank}</span>`;
                                }
                                
                                const mobBadge = row.querySelector('.mobile-rank-badge');
                                if (mobBadge) {
                                    if (item.rank == 1) mobBadge.innerText = '🥇';
                                    else if (item.rank == 2) mobBadge.innerText = '🥈';
                                    else if (item.rank == 3) mobBadge.innerText = '🥉';
                                    else mobBadge.innerText = `#${item.rank}`;
                                }
                                
                                row.dataset.rawTotal = item._raw_total;
                            });
                            
                            const groupBlocks = document.querySelectorAll('.ranking-group-block');
                            groupBlocks.forEach(block => {
                                const header = block.querySelector('.group-header');
                                if (header && header.innerText.includes(groupName)) {
                                    const tbody = block.querySelector('tbody');
                                    if (tbody) {
                                        const rows = Array.from(tbody.querySelectorAll('tr.ranking-row-pc'));
                                        rows.sort((a, b) => {
                                            const scoreA = parseFloat(a.dataset.rawTotal || 0);
                                            const scoreB = parseFloat(b.dataset.rawTotal || 0);
                                            return scoreB - scoreA;
                                        });
                                        rows.forEach(r => tbody.appendChild(r));
                                    }
                                }
                            });
                        }
                    }
                } catch (e) {
                    console.error('Realtime ranking update failed', e);
                }
            };
            if (window.SSEManager) {
                window.SSEManager.on('violation_new', updateRankingList);
                window.SSEManager.on('violation_deleted', updateRankingList);
            }
            // Gọi ngay 1 lần để đè lên cache PJAX cũ nếu người dùng vừa chuyển tab về
            updateRankingList();
        };
    })(window.pageInit);



</script>

<?php include 'includes/footer.php'; ?>