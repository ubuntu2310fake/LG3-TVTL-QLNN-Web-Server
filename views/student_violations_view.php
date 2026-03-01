<?php
include 'includes/header.php';
?>

<style>
    .paper-table { width: 100%; border-collapse: collapse; background: #fff; margin-bottom: 20px; }
    .paper-table th, .paper-table td { border: 1px solid #cbd5e1; padding: 8px 4px; text-align: center; vertical-align: middle; }
    .paper-table th { background-color: #f8fafc; color: #334155; font-weight: 700; font-size: 11px; text-transform: uppercase; }
    .cell-total { color: #2563eb; font-weight: 800; font-size: 14px; background-color: #f0f9ff; }
    
    /* Input giả để hiển thị số */
    .score-display {
        width: 100%; border: none; text-align: center; font-size: 14px; font-weight: 500; 
        color: #1e293b; background: transparent; outline: none;
    }
    .score-changed { color: #dc2626; font-weight: 800; background: #fef2f2; border-radius: 4px; }
    
    /* Bonus Score Style */
    .bonus-display {
        font-size: 11px; 
        color: #166534; /* Green */
        font-weight: 700; 
        margin-top: 2px;
        font-style: italic;
    }
    .footnote {
        margin-top: 5px;
        font-size: 12px;
        color: #64748b;
        font-style: italic;
    }
    
    /* Rank Table CSS (Thêm vào để hiển thị list lỗi) */
    .rank-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .rank-table th { background: var(--bg-hover); padding: 10px; text-align: left; font-size: 13px; color: var(--text-muted); border-bottom: 2px solid var(--border-color); }
    .rank-table td { padding: 12px 10px; border-bottom: 1px solid var(--border-color); font-size: 14px; color: var(--text-main); }

    @media (max-width: 768px) {
        .paper-table th { font-size: 9px; padding: 2px; }
        .score-display { font-size: 12px; }
    }
    
    /* PC Optimization (Optional: Nếu bạn muốn PC rộng hơn) */
    @media (min-width: 992px) {
        .win-card { max-width: 1200px !important; margin: 0 auto; }
    }
</style>

<div class="win-card">
    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; margin-bottom:20px; gap:10px;">
        <h2 style="margin:0; color:var(--danger-color); font-size:20px;">Lỗi Vi Phạm Của Tôi</h2>
        <div style="background:var(--bg-hover); color:var(--danger-color); border:1px solid var(--border-color); padding:8px 15px; border-radius:20px; font-weight:bold; font-size:14px;">
            Tổng trừ: -<?= $total_minus ?>
        </div>
    </div>
    
    <div style="display:flex; justify-content:center; align-items:center; gap:15px; margin-bottom:25px; background:var(--bg-hover); padding:10px; border-radius:8px; border:1px solid var(--border-color);">
        <a href="?week=<?= $week - 1 ?>" class="win-btn win-btn-secondary" style="width:35px; height:35px; padding:0; display:flex; align-items:center; justify-content:center;"><i class="fas fa-chevron-left"></i></a>
        <span style="font-weight:bold; font-size:16px; color:var(--text-main);">Tuần <?= $week ?></span>
        <a href="?week=<?= $week + 1 ?>" class="win-btn win-btn-secondary" style="width:35px; height:35px; padding:0; display:flex; align-items:center; justify-content:center;"><i class="fas fa-chevron-right"></i></a>
    </div>

    <h4 style="margin:0 0 15px 0; color:var(--text-muted); border-bottom:1px solid var(--border-color); padding-bottom:10px;">
        <i class="fas fa-user-tag"></i> Chi tiết cá nhân
    </h4>
    <?php if ($my_vios): ?>
        <table class="rank-table">
            <thead>
                <tr>
                    <th width="120">Thời gian</th>
                    <th>Nội dung lỗi</th>
                    <th width="100" style="text-align:center;">Điểm trừ</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($my_vios as $v): ?>
                <tr>
                    <td style="color:var(--text-muted);"><?= date('d/m H:i', strtotime($v['date_created'])) ?></td>
                    <td style="font-weight:600; color:var(--text-main);"><?= htmlspecialchars($v['recorded_violation_name']) ?></td>
                    <td style="color:var(--danger-color); font-weight:bold; text-align:center;">-<?= $v['recorded_points'] ?></td>
                    <td style="font-style:italic; color:var(--text-muted);"><?= htmlspecialchars($v['note']??'') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="text-align:center; padding:30px; background:var(--bg-hover); border-radius:8px; color:#166534; border:1px dashed #22c55e;">
            <i class="fas fa-check-circle" style="font-size:30px; margin-bottom:10px;"></i><br>
            Tuyệt vời! Bạn không có vi phạm nào.
        </div>
    <?php endif; ?>

    <div style="margin-top:40px;">
        <h4 style="margin:0 0 15px 0; color:var(--text-muted); border-bottom:1px solid var(--border-color); padding-bottom:10px;">
            <i class="fas fa-users"></i> Vi phạm chung của lớp
        </h4>
        <?php if ($class_vios): ?>
        <table class="rank-table">
            <thead>
                <tr>
                    <th width="150">Học sinh</th>
                    <th>Nội dung lỗi</th>
                    <th width="100" style="text-align:center;">Điểm trừ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($class_vios as $v): ?>
                <tr>
                    <td style="font-weight:500;">
                        <?php if (!empty($v['student_name'])): ?> 
                            <span style="color:var(--accent-color);"><?= htmlspecialchars($v['student_name']) ?></span>
                        <?php else: ?> 
                            <span style="color:#d97706; font-style:italic;">Tập thể lớp</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($v['violation_name']??$v['recorded_violation_name']) ?></td>
                    <td style="color:var(--danger-color); text-align:center;">-<?= $v['recorded_points'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="color:var(--text-muted); text-align:center; padding:20px;">Lớp sạch lỗi.</p>
        <?php endif; ?>
    </div>

    <div style="margin-top:40px;">
        <h4 style="margin:0 0 15px 0; color:var(--text-muted); border-bottom:1px solid var(--border-color); padding-bottom:10px;">
            <i class="fas fa-clipboard-check"></i> Sổ đoàn trường
        </h4>
        
        <div style="overflow-x:auto; border-radius:8px; border:1px solid var(--border-color);">
            <table class="paper-table">
                <thead>
                    <tr>
                        <th>Thứ</th>
                        <th>Sĩ số<br>T.Trung</th>
                        <th>Vệ<br>sinh</th>
                        <th>BVCSVC<br>Sau giờ</th>
                        <th>Truy<br>bài</th>
                        <th>Xe<br>ATGT</th>
                        <th>Đồng<br>phục</th>
                        <th>Sơ<br>vin</th>
                        <th>Thẻ<br>HS</th>
                        <th>Tóc<br>Dép</th>
                        <th>Tổng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matrix_data as $row): ?>
                    <tr>
                        <td style="font-weight:700; background:#f9fafb;"><?= $row['label'] ?></td>
                        <?php foreach ($row['scores'] as $item): ?>
                        <td>
                            <input type="text" class="score-display <?= ($item['val'] < $item['max']) ? 'score-changed' : '' ?>" 
                                   value="<?= $item['val'] ?>" readonly>
                        </td>
                        <?php endforeach; ?>
                        <td class="cell-total"><?= $row['total'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="10" style="text-align:right; padding-right:10px; font-weight:700; color:#64748b;">TỔNG ĐIỂM TUẦN:</td>
                        <td style="vertical-align: middle;">
                            <div style="font-size:16px; color:#2563eb; font-weight:800; background:#fff; line-height:1;"><?= $matrix_total ?></div>
                            <?php if ($bonus_score > 0): ?>
                            <div class="bonus-display">+<?= $bonus_score ?> <i>(*)</i></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if ($matrix_note): ?>
        <div style="margin-top:15px; padding:15px; background:#fffbeb; border:1px solid #fcd34d; border-radius:8px;">
            <strong style="color:#92400e;"><i class="fas fa-comment-alt"></i> Ghi chú của Sao đỏ/GVCN:</strong>
            <p style="margin:5px 0 0 0; white-space: pre-line; color:#b45309;"><?= htmlspecialchars($matrix_note) ?></p>
        </div>
        <?php endif; ?>
        
        <div class="footnote">
            (*): Điểm cộng sau khi tổng kết tuần
        </div>
    </div>

</div>
<?php include 'includes/footer.php'; ?>