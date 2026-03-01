<?php include 'includes/header.php'; ?>

<style>
    /* BỘ LỌC CÂN BẰNG TUYỆT ĐỐI */
    .filter-bar {
        display: flex;
        align-items: center;
        gap: 15px;
        background: var(--bg-hover, #f8fafc);
        padding: 8px 15px;
        border-radius: 12px;
        border: 1px solid var(--border-color, #e2e8f0);
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        flex-wrap: wrap; /* Tự động rớt dòng nếu xem trên đt */
    }
    .filter-element-group {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }
    .filter-label {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-muted, #64748b);
    }
    .filter-input {
        height: 40px; /* CỐ ĐỊNH CHIỀU CAO 40px */
        box-sizing: border-box;
        border-radius: 8px;
        border: 1px solid var(--border-color, #cbd5e1);
        background: #fff;
        margin: 0;
        outline: none;
        color: var(--text-main);
        font-weight: 600;
        font-family: inherit;
        font-size: 14px;
        padding: 0 10px;
    }
    .filter-input:focus {
        border-color: var(--primary-color, #3b82f6);
        box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
    }
    .filter-input[type="number"] { text-align: center; width: 65px; }
    
    .filter-btn {
        height: 40px; /* CỐ ĐỊNH CHIỀU CAO 40px */
        width: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        border: 1px solid var(--border-color, #cbd5e1);
        border-radius: 8px;
        cursor: pointer;
        color: var(--text-muted, #64748b);
        transition: 0.2s;
        margin: 0;
        padding: 0;
    }
    .filter-btn:hover {
        color: var(--primary-color, #3b82f6);
        background: var(--bg-hover, #f1f5f9);
    }
    .filter-divider {
        width: 1px;
        height: 24px;
        background: var(--border-color, #cbd5e1);
    }

    /* DROPDOWN CUSTOM ĐƯỢC CHUẨN HÓA LẠI CHIỀU CAO */
    .custom-select-container { margin: 0; width: 140px; position: relative; user-select: none; }
    .select-selected {
        background-color: #fff; border: 1px solid var(--border-color, #cbd5e1); border-radius: 8px;
        padding: 0 12px; display: flex; align-items: center; justify-content: space-between;
        font-size: 14px; height: 40px; /* CỐ ĐỊNH CHIỀU CAO 40px */
        box-sizing: border-box; color: var(--text-main); transition: 0.2s; cursor: pointer;
    }
    .select-selected:active, .select-selected.active { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
    .select-arrow { width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid var(--text-muted); transition: 0.2s; }
    .select-selected.active .select-arrow { transform: rotate(180deg); }
    .select-items { position: absolute; top: calc(100% + 5px); left: 0; right: 0; z-index: 1000; background: var(--bg-card, #fff); border: 1px solid var(--border-color); border-radius: 8px; max-height: 250px; overflow-y: auto; display: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1); animation: fadeIn 0.2s ease; }
    .select-items .optgroup-title { padding: 8px 12px; font-size: 11px; font-weight: bold; color: var(--text-muted); text-transform: uppercase; background: var(--bg-hover); cursor: default; }
    .select-items .opt-item { padding: 10px 15px; cursor: pointer; border-bottom: 1px solid var(--border-color); font-size: 13px; color: var(--text-main); transition: 0.2s; }
    .select-items .opt-item:last-child { border-bottom: none; }
    .select-items .opt-item:hover { background: rgba(59,130,246,0.05); color: var(--primary-color); font-weight: 600; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div style="background: var(--bg-card); padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); margin-bottom: 30px;">
    
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px; margin-bottom: 25px;">
        <h2 style="color:var(--primary-color); margin:0; font-size: 20px; display:flex; align-items:center; gap:8px;">
            <i class="fas fa-trophy"></i> BẢNG XẾP HẠNG
        </h2>

        <div class="filter-bar">
            
            <form method="GET" class="filter-element-group">
                <input type="hidden" name="filter_type" value="week">
                <span class="filter-label">Tuần:</span>
                <input type="number" name="week" value="<?= $filter_type == 'week' ? $selected_week : '' ?>" class="filter-input">
                <button type="submit" class="filter-btn"><i class="fas fa-search"></i></button>
            </form>

            <div class="filter-divider"></div>

            <form id="filterForm" method="GET" class="filter-element-group">
                <input type="hidden" name="filter_type" value="month">
                <input type="hidden" name="month" id="hiddenFilter" value="<?= $filter_type == 'month' ? $selected_month : '' ?>">
                
                <span class="filter-label">Tháng:</span>
                
                <div class="custom-select-container">
                    <div class="select-selected" onclick="toggleDropdown(event, this)">
                        <span id="txtSelectedFilter" style="font-weight:600; color:var(--text-main);">
                            <?= $filter_type == 'month' ? $filter_label : '-- Chọn --' ?>
                        </span>
                        <div class="select-arrow"></div>
                    </div>
                    <div class="select-items">
                        <div class="optgroup-title">Học kỳ / Năm học</div>
                        <div class="opt-item" onclick="submitFilter('hk1', 'Học kỳ 1', this)">Học kỳ 1</div>
                        <div class="opt-item" onclick="submitFilter('hk2', 'Học kỳ 2', this)">Học kỳ 2</div>
                        <div class="opt-item" onclick="submitFilter('year', 'Cả năm học', this)">Cả năm học</div>
                        
                        <div class="optgroup-title">Từng Tháng</div>
                        <div class="opt-item" onclick="submitFilter('m_8', 'Tháng 8', this)">Tháng 8</div>
                        <div class="opt-item" onclick="submitFilter('m_9', 'Tháng 9', this)">Tháng 9</div>
                        <div class="opt-item" onclick="submitFilter('m_10', 'Tháng 10', this)">Tháng 10</div>
                        <div class="opt-item" onclick="submitFilter('m_11', 'Tháng 11', this)">Tháng 11</div>
                        <div class="opt-item" onclick="submitFilter('m_12', 'Tháng 12', this)">Tháng 12</div>
                        <div class="opt-item" onclick="submitFilter('m_1', 'Tháng 1', this)">Tháng 1</div>
                        <div class="opt-item" onclick="submitFilter('m_2', 'Tháng 2', this)">Tháng 2</div>
                        <div class="opt-item" onclick="submitFilter('m_3', 'Tháng 3', this)">Tháng 3</div>
                        <div class="opt-item" onclick="submitFilter('m_4', 'Tháng 4', this)">Tháng 4</div>
                        <div class="opt-item" onclick="submitFilter('m_5', 'Tháng 5', this)">Tháng 5</div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div style="padding:10px 15px; border-radius:8px; border-left:4px solid var(--primary-color); margin-bottom: 25px; font-size: 13px; color:var(--text-main); font-weight:500;">
        <?php if($filter_type === 'month'): ?>
            <i class="fas fa-info-circle"></i> Đang hiển thị trung bình <strong><?= $filter_label ?></strong> (Tổng hợp từ <strong style="color:var(--primary-color);">Tuần <?= $start_week ?></strong> đến <strong style="color:var(--primary-color);">Tuần <?= $end_week ?></strong>).<br>
            <small style="color: var(--danger-color); font-weight: normal; display: block; margin-top: 4px;">* Tuần nào lớp không nhập sổ đầu bài sẽ tự động bị giáng 0đ học tập cho tuần đó.</small>
        <?php else: ?>
            <i class="fas fa-info-circle"></i> Đang hiển thị dữ liệu của riêng <strong>Tuần <?= $selected_week ?></strong>.
        <?php endif; ?>
    </div>

    <?php foreach ($grouped_data as $group_id => $items): ?>
    <div style="margin-bottom: 30px;">
        <h3 style="color:var(--primary-color); border-left:4px solid var(--primary-color); padding-left:10px; margin-bottom:15px; font-size:16px;">
            Nhóm Thi Đua <?= htmlspecialchars($group_id) ?>
        </h3>
        
        <table class="rank-table ranking-list-table" style="width: 100%;">
            <thead>
                <tr>
                    <th width="60" style="text-align:center;">STT</th>
                    <th>Lớp</th>
                    <th style="text-align:center;">Nền nếp</th>
                    <th style="text-align:center;">Học tập</th>
                    <th style="text-align:center;">Tổng kết</th>
                    <th style="text-align:center;" class="pc-only-rank">Xếp hạng</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $index => $item): ?>
                <tr class="ranking-row-pc">
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
                                <span class="popup-title">Lớp <?= htmlspecialchars($item['class_name']) ?></span>
                                <span class="popup-week"><?= $filter_type == 'month' ? mb_strtoupper($filter_label, 'UTF-8') : "TUẦN $selected_week" ?></span>
                            </div>
                            
                            <div class="popup-row">
                                <span class="popup-label"><i class="fas fa-clipboard-list" style="color:#3b82f6;"></i> Nền nếp (<?= $filter_type=='month'?'TB':'Tuần'?>):</span>
                                <span class="popup-val"><?= $item['nn'] ?></span>
                            </div>

                            <div class="popup-row">
                                <span class="popup-label"><i class="fas fa-torii-gate" style="color:#ef4444;"></i> VPBS (<?= $filter_type=='month'?'TB':'Tuần'?>):</span>
                                <?php if ($item['gate_points'] > 0): ?>
                                    <span class="popup-val val-minus">-<?= $item['gate_points'] ?></span>
                                <?php else: ?>
                                    <span class="popup-val" style="color:#94a3b8;">0</span>
                                <?php endif; ?>
                            </div>

                            <div class="popup-row">
                                <span class="popup-label"><i class="fas fa-book-reader" style="color:#10b981;"></i> Tiết học (Tổng):</span>
                                <span class="popup-val"><?= $item['period_count'] ?> tiết</span>
                            </div>

                            <?php if ($item['bonus'] > 0): ?>
                            <div class="popup-row">
                                <span class="popup-label"><i class="fas fa-star" style="color:#f59e0b;"></i> Điểm thưởng (<?= $filter_type=='month'?'TB':'Tuần'?>):</span>
                                <span class="popup-val" style="color:#f59e0b; font-weight:bold;">+<?= round($item['bonus'], 2) ?></span>
                            </div>
                            <?php endif; ?>

                            <div class="popup-total-area">
                                <span style="font-size:13px; color:#64748b; font-weight:600;">ĐIỂM TỔNG KẾT:</span>
                                <span style="font-size:24px; font-weight:900; color:#2563eb;"><?= $item['tb'] ?></span>
                            </div>
                        </div>
                    </td>

                    <td style="text-align:center;" data-label="Nền nếp"><?= $item['nn'] ?></td>
                    <td style="text-align:center;" data-label="Học tập"><?= $item['ht'] ?></td>
                    <td style="font-weight:bold; color:var(--text-main); text-align:center;" data-label="Tổng kết">
                        <?= $item['tb'] ?>
                    </td>
                    
                    <td class="pc-only-rank" style="text-align:center;">
                        <?php if ($item['rank'] == 1): ?> <span style="color:#b45309; font-weight:bold;">🥇 Nhất</span>
                        <?php elseif ($item['rank'] == 2): ?> <span style="color:#475569; font-weight:bold;">🥈 Nhì</span>
                        <?php elseif ($item['rank'] == 3): ?> <span style="color:#b45309; font-weight:bold;">🥉 Ba</span>
                        <?php else: ?> <?= $item['rank'] ?> <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
</div>

<script>
    // JS CHUẨN XỊN CHO DROPDOWN BÊN PHẢI
    function toggleDropdown(e, el) { 
        e.stopPropagation(); 
        closeAllSelects(el); 
        el.nextElementSibling.style.display = el.nextElementSibling.style.display==='block'?'none':'block'; 
        el.classList.toggle('active'); 
    }
    function closeAllSelects(except) { 
        document.querySelectorAll('.select-items').forEach(i => { if(i!==except?.nextElementSibling) i.style.display='none'; }); 
        document.querySelectorAll('.select-selected').forEach(e => { if(e!==except) e.classList.remove('active'); }); 
    }
    document.addEventListener('click', () => closeAllSelects());

    function submitFilter(value, text, itemEl) {
        document.getElementById('txtSelectedFilter').innerText = text;
        document.getElementById('hiddenFilter').value = value;
        document.getElementById('filterForm').submit();
    }

    // HOVER THẺ RANKING
    document.addEventListener("DOMContentLoaded", function() {
        if (window.innerWidth > 1024) {
            const rows = document.querySelectorAll('.ranking-row-pc');
            let hoverTimeout;
            rows.forEach(row => {
                const card = row.querySelector('.rank-hover-card');
                if (!card) return;
                row.addEventListener('mouseenter', () => {
                    const rowRect = row.getBoundingClientRect();
                    const viewportHeight = window.innerHeight;
                    const requiredSpace = 320; 
                    const spaceBelow = viewportHeight - rowRect.bottom;
                    card.classList.remove('show-above', 'show-below');
                    if (spaceBelow < requiredSpace) card.classList.add('show-above'); 
                    else card.classList.add('show-below'); 
                    clearTimeout(hoverTimeout);
                    hoverTimeout = setTimeout(() => { card.classList.add('is-visible'); }, 500); 
                });
                row.addEventListener('mouseleave', () => {
                    clearTimeout(hoverTimeout);
                    card.classList.remove('is-visible');
                });
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>