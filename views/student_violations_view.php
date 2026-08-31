<?php
include 'includes/header.php';
?>

<style>
    /* Dùng hoàn toàn biến hệ thống, không gán cứng màu */
    .paper-table { width: 100%; border-collapse: collapse; background: var(--bg-card); margin-bottom: 20px; transition: 0.3s; }
    .paper-table th, .paper-table td { border: 1px solid var(--border-color); padding: 8px 4px; text-align: center; vertical-align: middle; }
    .paper-table th { background-color: var(--bg-hover); color: var(--text-muted); font-weight: 700; font-size: 11px; text-transform: uppercase; }
    .cell-total { color: var(--primary-color); font-weight: 800; font-size: 14px; background-color: var(--bg-hover); }
    
    /* Input giả để hiển thị số */
    .score-display {
        width: 100%; border: none; text-align: center; font-size: 14px; font-weight: 500; 
        color: var(--text-main); background: transparent; outline: none;
    }
    
    /* Nền đỏ nhạt, tương thích cả sáng lẫn AMOLED (dùng rgba) */
    .score-changed { color: var(--danger-color); font-weight: 800; background: rgba(220, 38, 38, 0.1); border-radius: 4px; }
    
    /* Bonus Score Style */
    .bonus-display {
        font-size: 11px; 
        color: #10b981; /* Giữ mã hex xanh lá do đây là màu semantic đặc thù */
        font-weight: 700; 
        margin-top: 2px;
        font-style: italic;
    }
    .footnote {
        margin-top: 5px;
        font-size: 12px;
        color: var(--text-muted);
        font-style: italic;
    }
    
    /* Rank Table CSS */
    .rank-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .rank-table th { background: var(--bg-hover); padding: 10px; text-align: left; font-size: 13px; color: var(--text-muted); border-bottom: 2px solid var(--border-color); }
    .rank-table td { padding: 12px 10px; border-bottom: 1px solid var(--border-color); font-size: 14px; color: var(--text-main); }

    @media (max-width: 768px) {
        .paper-table th { font-size: 9px; padding: 2px; }
        .score-display { font-size: 12px; }
    }
    
    /* PC Optimization */
    @media (min-width: 992px) {
        .win-card { max-width: 1200px; margin: 0 auto; }
    }
</style>

<div class="win-card">
    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; margin-bottom:20px; gap:10px;">
        <h2 style="margin:0; color:var(--danger-color); font-size:20px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vi phạm của tôi' : 'My Violations') ?></h2>
        <div style="background:var(--bg-hover); color:var(--danger-color); border:1px solid var(--border-color); padding:8px 15px; border-radius:20px; font-weight:bold; font-size:14px;">
            <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tổng điểm trừ' : 'Total Minus') ?>: -<?= $total_minus ?>
        </div>
    </div>
    
    <div style="display:flex; justify-content:center; align-items:center; gap:15px; margin-bottom:25px; background:var(--bg-hover); padding:10px; border-radius:8px; border:1px solid var(--border-color);">
        <a href="?week=<?= $week - 1 ?>" class="win-btn win-btn-secondary" style="width:35px; height:35px; padding:0; display:flex; align-items:center; justify-content:center;" aria-label="Tuần trước"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
        <span style="font-weight:bold; font-size:16px; color:var(--text-main);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'Week') ?> <?= $week ?></span>
        <a href="?week=<?= $week + 1 ?>" class="win-btn win-btn-secondary" style="width:35px; height:35px; padding:0; display:flex; align-items:center; justify-content:center;" aria-label="Tuần sau"><i class="fas fa-chevron-right" aria-hidden="true"></i></a>
    </div>

    <h4 style="margin:0 0 15px 0; color:var(--text-muted); border-bottom:1px solid var(--border-color); padding-bottom:10px;">
        <i class="fas fa-user-tag" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chi tiết cá nhân' : 'Personal Details') ?>
    </h4>
    <?php if ($my_vios): ?>
        <table class="rank-table">
            <thead>
                <tr>
                    <th width="120"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thời gian' : 'Time') ?></th>
                    <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nội dung vi phạm' : 'Violation Content') ?></th>
                    <th width="100" style="text-align:center;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm trừ' : 'Minus Points') ?></th>
                    <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ghi chú' : 'Note') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($my_vios as $v): ?>
                <tr>
                    <td style="color:var(--text-muted);"><?= date('d/m H:i', strtotime($v['date_created'])) ?></td>
                    <?php 
                    $lang = $_SESSION['lang'] ?? 'vi';
                    $dispMyName = ($lang === 'en' && !empty($v['recorded_violation_name_en'])) ? $v['recorded_violation_name_en'] : $v['recorded_violation_name'];
                    ?>
                    <td style="font-weight:600; color:var(--text-main);"><?= htmlspecialchars($dispMyName) ?></td>
                    <td style="color:var(--danger-color); font-weight:bold; text-align:center;">-<?= $v['recorded_points'] ?></td>
                    <td style="font-style:italic; color:var(--text-muted);"><?= htmlspecialchars($v['note']??'') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="text-align:center; padding:30px; background:rgba(16, 185, 129, 0.1); border-radius:8px; color:#10b981; border:1px dashed #10b981;">
            <i class="fas fa-check-circle" aria-hidden="true" style="font-size:30px; margin-bottom:10px;"></i><br>
            <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuyệt vời! Bạn không có vi phạm nào.' : 'Great! You have no violations.') ?>
        </div>
    <?php endif; ?>

    <div style="margin-top:40px;">
        <h4 style="margin:0 0 15px 0; color:var(--text-muted); border-bottom:1px solid var(--border-color); padding-bottom:10px;">
            <i class="fas fa-users" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vi phạm chung của lớp' : 'Class General Violations') ?>
        </h4>
        <?php if ($class_vios): ?>
        <table class="rank-table">
            <thead>
                <tr>
                    <th width="150"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Học sinh' : 'Student') ?></th>
                    <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nội dung vi phạm' : 'Violation Content') ?></th>
                    <th width="100" style="text-align:center;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm trừ' : 'Minus Points') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($class_vios as $v): ?>
                <tr>
                    <td style="font-weight:500;">
                        <?php if (!empty($v['student_name'])): ?> 
                            <span style="color:var(--accent-color);"><?= htmlspecialchars($v['student_name']) ?></span>
                        <?php else: ?> 
                            <span style="color:#d97706; font-style:italic;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tập thể lớp' : 'Class Collective') ?></span>
                        <?php endif; ?>
                    </td>
                    <?php
                    $lang = $_SESSION['lang'] ?? 'vi';
                    $vName = $v['violation_name'] ?? $v['recorded_violation_name'];
                    $vNameEn = $v['violation_name_en'] ?? $v['recorded_violation_name_en'];
                    $dispClsName = ($lang === 'en' && !empty($vNameEn)) ? $vNameEn : $vName;
                    ?>
                    <td><?= htmlspecialchars($dispClsName) ?></td>
                    <td style="color:var(--danger-color); text-align:center;">-<?= $v['recorded_points'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="color:var(--text-muted); text-align:center; padding:20px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp không có vi phạm nào.' : 'Class has no violations.') ?></p>
        <?php endif; ?>
    </div>

    <div style="margin-top:40px;">
        <h4 style="margin:0 0 15px 0; color:var(--text-muted); border-bottom:1px solid var(--border-color); padding-bottom:10px;">
            <i class="fas fa-clipboard-check" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Sổ Đoàn Trường' : 'School Union Book') ?>
        </h4>
        
        <div style="overflow-x:auto; border-radius:8px; border:1px solid var(--border-color);">
            <table class="paper-table">
                <thead>
                    <tr>
                        <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thứ' : 'Day of Week') ?></th>
                        <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Sĩ số' : 'Attendance') ?><br><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vắng' : 'Abs') ?></th>
                        <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vệ sinh' : 'Clean') ?></th>
                        <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'C.S.V.C' : 'Facil') ?></th>
                        <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'C.B.B' : 'Prep') ?></th>
                        <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'ATGT' : 'Traff') ?></th>
                        <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đồng phục' : 'Unif') ?></th>
                        <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Sơ vin' : 'Tuck') ?></th>
                        <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thẻ HS' : 'Card') ?></th>
                        <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tóc/Giày' : 'Hair/Shoes') ?></th>
                        <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tổng' : 'Total') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matrix_data as $row): ?>
                    <tr>
                        <td style="font-weight:700; background:var(--bg-hover);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? $row['label'] : $row['label']) ?></td>
                        <?php foreach ($row['scores'] as $item): ?>
                        <td>
                            <input type="text" class="score-display <?= ($item['val'] < $item['max']) ? 'score-changed' : '' ?>" 
                                   value="<?= $item['val'] ?>" readonly aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm' : 'Score') ?>">
                        </td>
                        <?php endforeach; ?>
                        <td class="cell-total"><?= $row['total'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="10" style="text-align:right; padding-right:10px; font-weight:700; color:var(--text-muted);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'TỔNG ĐIỂM TUẦN:' : 'TOTAL WEEK SCORE:') ?></td>
                        <td style="vertical-align: middle;">
                            <div style="font-size:16px; color:var(--primary-color); font-weight:800; background:transparent; line-height:1;"><?= $matrix_total ?></div>
                            <?php if ($bonus_score > 0): ?>
                            <div class="bonus-display">+<?= $bonus_score ?> <i>(*)</i></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if ($matrix_note): ?>
        <div style="margin-top:15px; padding:15px; background:rgba(245, 158, 11, 0.1); border:1px solid #f59e0b; border-radius:8px;">
            <strong style="color:#f59e0b;"><i class="fas fa-comment-alt" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ghi chú của Cờ đỏ/Giáo viên' : 'Notes from Red Flag/Teacher') ?>:</strong>
            <p style="margin:5px 0 0 0; white-space: pre-line; color:var(--text-main);"><?= htmlspecialchars($matrix_note) ?></p>
        </div>
        <?php endif; ?>
        
        <div class="footnote">
            <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '(*) Điểm cộng sẽ được tính vào tổng điểm cuối tuần' : '(*) Bonus points will be added to the final weekend score') ?>
        </div>
    </div>
</div>

<script>
    // Tự động tải lại phần dữ liệu HTML của thẻ .win-card (PJAX) khi có sự thay đổi dữ liệu vi phạm từ server
    window.updateStudentViolations = async function() {
        try {
            let url = window.location.href;
            url += (url.includes('?') ? '&' : '?') + '_t=' + new Date().getTime();
            const res = await fetch(url, { headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache' }, credentials: 'same-origin' });
            const text = await res.text();
            
            const parser = new DOMParser();
            const doc = parser.parseFromString(text, 'text/html');
            const newCard = doc.querySelector('.win-card');
            
            if (newCard) {
                const currentCard = document.querySelector('.win-card');
                if (currentCard) {
                    currentCard.innerHTML = newCard.innerHTML;
                }
            }
        } catch (e) {
            console.error('Realtime update failed:', e);
        }
    };

    window.addEventListener('violation_data_changed', window.updateStudentViolations);

    if (window.SSEManager) {
        window.SSEManager.on('violation_new', window.updateStudentViolations);
        window.SSEManager.on('violation_deleted', window.updateStudentViolations);
        window.SSEManager.on('violation_class_new', window.updateStudentViolations);
        window.SSEManager.on('violation_class_updated', window.updateStudentViolations);
    }
</script>

<?php include 'includes/footer.php'; ?>