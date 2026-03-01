<?php
include 'includes/header.php';
?>

<style>
    /* ... Copy CSS cũ vào đây nếu cần, hoặc để nguyên file cũ chỉ thay đoạn PHP trên ... */
    .custom-select-container { position: relative; width: 150px; }
    .select-selected { background-color: var(--bg-input); border: 1px solid var(--border-color); border-radius: 8px; padding: 0 15px; display: flex; align-items: center; justify-content: space-between; font-size: 14px; height: 42px; box-sizing: border-box; color: var(--text-main); transition: 0.2s; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .select-selected:active { border-color: var(--accent-color); box-shadow: 0 0 0 3px rgba(0,95,186,0.15); }
    .select-arrow { width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid var(--text-muted); transition: 0.2s; }
    .select-selected.active .select-arrow { transform: rotate(180deg); }
    .select-items { position: absolute; top: 110%; left: 0; right: 0; z-index: 1000; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; max-height: 200px; overflow-y: auto; display: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1); animation: fadeIn 0.2s ease; }
    .select-items div { padding: 10px 15px; cursor: pointer; border-bottom: 1px solid var(--border-color); font-size: 14px; color: var(--text-main); }
    .select-items div:hover { background: var(--bg-hover); color: var(--accent-color); }
    .badge-class { background: #e0f2fe; color: #0284c7; padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 12px; }
    .filter-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
    .filter-form { display: flex; gap: 10px; flex: 1; justify-content: flex-end; align-items: center; }
    .badge-red-flag { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-flex; align-items: center; gap: 3px; margin-top: 3px; }
    .table-responsive { width: 100%; overflow-x: auto; }
    .rank-table { width: 100%; border-collapse: collapse; min-width: 600px; }
    .rank-table th { background: var(--bg-hover); text-align: left; padding: 12px; font-weight: 600; color: var(--text-muted); border-bottom: 1px solid var(--border-color); }
    .rank-table td { padding: 12px; border-bottom: 1px solid var(--border-color); color: var(--text-main); font-size: 14px; vertical-align: middle; }
    .rank-table tr:hover { background: var(--bg-hover); }
    .pending-change { color: #059669; font-weight: bold; font-size: 13px; display: block; margin-top: 2px; }
    .old-value { text-decoration: line-through; color: var(--text-muted); font-size: 12px; }
    .btn-approve { background: #10b981; color: white; border: none; width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; margin-left: 5px; }
    .btn-approve:hover { transform: scale(1.1); box-shadow: 0 2px 5px rgba(16, 185, 129, 0.3); }
    .qr-dropdown-container { position: relative; display: inline-block; }
    .qr-btn-main { background-color: #4f46e5; color: white; border: none; display: flex; align-items: center; gap: 8px; padding: 0 15px; height: 42px; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; font-size: 14px; transition: 0.2s; }
    .qr-btn-main:hover { filter: brightness(1.1); transform: translateY(-1px); }
    .qr-dropdown-menu { display: none; position: absolute; top: 115%; right: 0; z-index: 1001; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); min-width: 210px; overflow: hidden; animation: fadeIn 0.2s; }
    .qr-dropdown-item { padding: 12px 15px; cursor: pointer; display: flex; align-items: center; gap: 12px; color: var(--text-main); font-size: 14px; border-bottom: 1px solid var(--border-color); transition: 0.2s; text-decoration: none; }
    .qr-dropdown-item:last-child { border-bottom: none; }
    .qr-dropdown-item:hover { background: var(--bg-hover); color: var(--accent-color); }
    .qr-text { display: none; }
    @media(min-width: 600px) { .qr-text { display: inline; } }
    @media (max-width: 768px) {
        .filter-container { flex-direction: column; align-items: stretch; gap: 15px; }
        .filter-form { width: 100%; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
        .custom-select-container { width: 48% !important; flex: 1; }
        .qr-dropdown-container { order: -1; width: 100%; display: flex; justify-content: center; }
        .qr-btn-main { width: 100%; justify-content: center; }
        .filter-form input { flex: 1; font-size: 13px; height: 42px; margin: 0; min-width: 120px; }
        .filter-form button { width: 42px; height: 42px; padding: 0; display: flex; align-items: center; justify-content: center; }
        .rank-table { display: block; min-width: 0; }
        .rank-table thead { display: none; }
        .rank-table tbody { display: block; }
        .rank-table tr { display: block; position: relative; background: var(--bg-card); margin-bottom: 10px; border-radius: 10px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.02); padding: 10px 10px 10px 60px; min-height: 65px; }
        .rank-table td { display: block; padding: 0; border: none; text-align: left; margin-bottom: 2px; color: var(--text-main); }
        .rank-table td:nth-child(1) { position: absolute; left: 10px; top: 10px; margin: 0; }
        .rank-table td:nth-child(1) img { width: 42px !important; height: 42px !important; border-radius: 50%; border: 1px solid var(--border-color); object-fit: cover; }
        .rank-table td:nth-child(2) { font-size: 10px; color: var(--text-muted); font-weight: 600; background: var(--bg-hover); display: inline-block; padding: 1px 5px; border-radius: 4px; }
        .rank-table td:nth-child(3) { font-size: 14px; font-weight: 700; color: var(--text-main); line-height: 1.3; padding-right: 30px; margin-bottom: 1px; }
        .rank-table td:nth-child(4), .rank-table td:nth-child(5) { display: inline-block; font-size: 12px; color: var(--text-muted); }
        .rank-table td:nth-child(4):after { content: "•"; color: var(--border-color); margin: 0 5px; }
        .rank-table td:nth-child(4) .badge-class { background: none; color: inherit; padding: 0; font-weight: normal; }
        .rank-table td:last-child { position: absolute !important; right: 10px !important; bottom: 10px !important; margin: 0 !important; width: auto !important; display: flex; gap: 5px; }
        .edit-btn-mobile { display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: var(--bg-hover); color: var(--accent-color); border: 1px solid var(--border-color); border-radius: 50%; text-decoration: none; transition: 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.05); font-size: 12px; }
        .edit-btn-mobile:active { transform: scale(0.95); }
        .desktop-edit-btn { display: none; }
    }
    @media (min-width: 769px) { .edit-btn-mobile { display: none; } .desktop-edit-btn { display: inline-flex; } .rank-table td:last-child { text-align: right; } }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="win-card">
    <div class="filter-container">
        <h2 style="color:var(--accent-color); margin:0;">Quản lý Học sinh</h2>
        <form method="GET" class="filter-form" id="searchForm">
            <input type="hidden" name="class_id" id="hiddenClassId" value="<?= $class_id ?>">
            <div class="qr-dropdown-container">
                <div class="qr-btn-main" onclick="toggleQrMenu(event, this)">
                    <i class="fas fa-qrcode"></i><span class="qr-text">Tải QR</span><i class="fas fa-chevron-down" style="font-size: 10px; opacity: 0.8; margin-left: 2px;"></i>
                </div>
                <div class="qr-dropdown-menu" id="qrMenu">
                    <a href="download_qr.php?type=1" class="qr-dropdown-item"><i class="fas fa-file-image" style="color: #64748b; width: 20px;"></i> <span>1. Ảnh gốc (.zip)</span></a>
                    <a href="download_qr.php?type=2" class="qr-dropdown-item"><i class="fas fa-font" style="color: #005fba; width: 20px;"></i> <span>2. Ảnh kèm tên (.zip)</span></a>
                    <a href="download_qr.php?type=3" class="qr-dropdown-item"><i class="fas fa-file-pdf" style="color: #dc2626; width: 20px;"></i> <span>3. In A4 (50 ô/trang)</span></a>
                    <a href="download_qr.php?type=4" class="qr-dropdown-item"><i class="fas fa-print" style="color: #16a34a; width: 20px;"></i> <span>4. In A3 (50 ô/trang)</span></a>
                </div>
            </div>
            <div class="custom-select-container">
                <div class="select-selected" onclick="toggleDropdown(event, this)">
                    <span id="selectedClassText"><?php if ($class_id) { $found = false; foreach ($classes as $c) if ($c['id'] == $class_id) { echo 'Lớp '.$c['name']; $found=true; break; } if (!$found) echo 'Tất cả lớp'; } else echo 'Tất cả lớp'; ?></span>
                    <div class="select-arrow"></div>
                </div>
                <div class="select-items">
                    <div onclick="selectClass('', 'Tất cả lớp')">Tất cả lớp</div>
                    <?php foreach ($classes as $c): ?><div onclick="selectClass('<?= $c['id'] ?>', 'Lớp <?= $c['name'] ?>')">Lớp <?= $c['name'] ?></div><?php endforeach; ?>
                </div>
            </div>
            <input type="text" name="search" class="win-input" placeholder="Tên hoặc Mã HS..." value="<?= htmlspecialchars($search) ?>">
            <button class="win-btn"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="rank-table">
            <thead><tr><th width="60">Ảnh</th><th>Mã HS</th><th>Họ Tên</th><th>Lớp</th><th>Ngày sinh</th><th width="100"></th></tr></thead>
            <tbody>
                <?php if ($students): foreach ($students as $s): ?>
                <tr id="row_<?= $s->code ?>">
                    <td><img src="<?= $s->image_url ? '/'.$s->image_url : '/static/default.png' ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover;"></td>
                    <td style="font-weight:bold;"><?= htmlspecialchars($s->code) ?></td>
                    <td style="text-align:left;">
                        <?php if ($s->has_pending_changes && $s->pending_name): ?>
                            <span class="old-value"><?= htmlspecialchars($s->name) ?></span><br><span class="pending-change"><?= htmlspecialchars($s->pending_name) ?></span>
                        <?php else: ?><?= htmlspecialchars($s->name) ?><?php endif; ?>
                        <?php if (isset($red_flags[$s->code])): ?><br><span class="badge-red-flag"><i class="fas fa-flag"></i> Cờ đỏ <?= htmlspecialchars($red_flags[$s->code]) ?></span><?php endif; ?>
                    </td>
                    <td><span class="badge-class"><?= htmlspecialchars($s->class_name) ?></span></td>
                    <td>
                        <?php if ($s->has_pending_changes && $s->pending_dob): ?>
                            <span class="old-value"><?= htmlspecialchars($s->dob ?: '--') ?></span><br><span class="pending-change"><?= htmlspecialchars($s->pending_dob) ?></span>
                        <?php else: ?><?= htmlspecialchars($s->dob ?: '--/--/----') ?><?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex; align-items:center; gap:5px; justify-content:flex-end;">
                            <?php if ($s->has_pending_changes): ?><button onclick="quickApprove('<?= $s->code ?>')" class="btn-approve" title="Chấp nhận thay đổi"><i class="fas fa-check"></i></button><?php endif; ?>
                            <a href="edit_student.php?id=<?= $s->code ?>" class="win-btn win-btn-secondary desktop-edit-btn" style="padding:5px 10px; font-size:12px;"><i class="fas fa-pen"></i></a>
                            <a href="edit_student.php?id=<?= $s->code ?>" class="edit-btn-mobile"><i class="fas fa-pen"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="6" style="text-align:center; padding:30px; color:var(--text-muted);">Không tìm thấy học sinh nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="margin-top:20px; display:flex; justify-content:center; gap:5px; flex-wrap:wrap;">
        <?php if ($page > 3): ?>
            <a href="manage_students.php?page=1&search=<?= urlencode($search) ?>&class_id=<?= $class_id ?>" class="win-btn win-btn-secondary">1</a>
            <?php if ($page > 4): ?><span style="padding:5px;">...</span><?php endif; ?>
        <?php endif; ?>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <?php if ($i == $page): ?>
                <span class="win-btn" style="background:#005fba; color:white; cursor:default;"><?= $i ?></span>
            <?php else: ?>
                <a href="manage_students.php?page=<?= $i ?>&search=<?= urlencode($search) ?>&class_id=<?= $class_id ?>" class="win-btn win-btn-secondary"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages - 2): ?>
            <?php if ($page < $totalPages - 3): ?><span style="padding:5px;">...</span><?php endif; ?>
            <a href="manage_students.php?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&class_id=<?= $class_id ?>" class="win-btn win-btn-secondary"><?= $totalPages ?></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
    function toggleDropdown(e, el) { e.stopPropagation(); const qrMenu = document.getElementById('qrMenu'); if(qrMenu) qrMenu.style.display = 'none'; closeAllSelects(el); const list = el.nextElementSibling; const arrow = el.querySelector('.select-arrow'); if (list.style.display === 'block') { list.style.display = 'none'; el.classList.remove('active'); if(arrow) arrow.style.transform = 'rotate(0deg)'; } else { list.style.display = 'block'; el.classList.add('active'); if(arrow) arrow.style.transform = 'rotate(180deg)'; } }
    function closeAllSelects(exceptEl) { document.querySelectorAll('.select-items').forEach(i => { if(!exceptEl || i !== exceptEl.nextElementSibling) i.style.display='none'; }); document.querySelectorAll('.select-selected').forEach(el => { if(el !== exceptEl) { el.classList.remove('active'); el.querySelector('.select-arrow').style.transform = 'rotate(0deg)'; } }); }
    function selectClass(id, name) { document.getElementById('hiddenClassId').value = id; document.getElementById('selectedClassText').innerText = name; document.getElementById('searchForm').submit(); }
    function toggleQrMenu(e, btn) { e.stopPropagation(); const menu = document.getElementById('qrMenu'); closeAllSelects(); if (menu.style.display === 'block') { menu.style.display = 'none'; } else { menu.style.display = 'block'; } }
    document.addEventListener('click', () => { closeAllSelects(); const qrMenu = document.getElementById('qrMenu'); if(qrMenu) qrMenu.style.display = 'none'; });
    function quickApprove(code) { if(!confirm("Xác nhận duyệt thay đổi cho học sinh này?")) return; const fd = new FormData(); fd.append('action', 'quick_approve'); fd.append('code', code); fetch('manage_students.php', {method:'POST', body:fd}).then(r => r.json()).then(data => { if(data.status === 'success') { Toastify({text: "✅ Đã duyệt thành công!", style:{background:"#10b981"}}).showToast(); setTimeout(() => location.reload(), 1000); } else { alert(data.msg); } }); }
</script>
<?php include 'includes/footer.php'; ?>