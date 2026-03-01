<?php
include 'includes/header.php';
?>

<style>
    /* CHỐNG TRÀN VIỀN TUYỆT ĐỐI */
    .win-card-custom {
        max-width: 1000px;
        margin: 0 auto;
        background: var(--bg-card, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 12px;
        padding: 25px;
        /* box-sizing là "thần chú" để padding không làm phình kích thước thẻ */
        box-sizing: border-box !important; 
        width: 100% !important; 
        overflow: hidden; /* Cắt mọi phần tử con cố tình tràn ra ngoài thẻ */
    }

    .table-responsive-wrapper {
        width: 100%;
        display: block;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch; /* Hỗ trợ vuốt mượt trên iOS */
        margin-top: 20px;
        padding-bottom: 10px;
    }

    /* Tinh chỉnh cho màn hình điện thoại */
    @media (max-width: 768px) {
        .win-card-custom {
            padding: 15px;
            border-radius: 8px;
        }
        .win-card-form {
            flex-direction: column;
            align-items: stretch !important;
            gap: 15px;
        }
        .form-group-date {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .form-group-btns {
            width: 100%;
            display: flex;
            gap: 10px;
        }
        .form-group-btns button {
            flex: 1;
            justify-content: center;
            padding: 12px 0; /* Cho nút to ra dễ bấm trên đt */
        }
        .win-table th, .win-table td {
            white-space: nowrap; /* Giữ chữ trong bảng không rớt dòng lung tung */
        }
    }
</style>

<div class="win-card-custom">
    <div style="text-align:center; margin-bottom:20px; word-wrap: break-word;">
        <h2 style="color:var(--accent-color); margin:0 0 8px 0; font-size: 22px; display:flex; align-items:center; justify-content:center; gap:8px;">
            <i class="fas fa-file-excel"></i> <span style="white-space: normal;">Xuất Báo Cáo VPBS</span>
        </h2>
        <p style="color:var(--text-muted); font-size:14px; margin:0;">Xem trước và tải xuống báo cáo vi phạm cổng (GATE) theo tuần.</p>
    </div>

    <?php if(!empty($msg)): ?>
        <div style="background:#fee2e2; color:#991b1b; padding:12px; border-radius:8px; margin-bottom:15px; font-size:13px; text-align:center; box-sizing: border-box; width: 100%;">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="win-card-form" style="background: var(--bg-hover, #f8fafc); padding: 15px; border-radius: 10px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; box-sizing: border-box; width: 100%; border: 1px solid var(--border-color, #e2e8f0);">
        <div class="form-group-date">
            <label style="font-weight:600; color:var(--text-main);">Chọn Tuần:</label>
            <input type="number" name="week" class="win-input" value="<?= $selected_week ?? 1 ?>" min="1" max="52" style="width:80px; text-align:center; margin:0; box-sizing: border-box;" onchange="this.form.submit()"> 
        </div>

        <div class="form-group-btns">
            <button type="submit" name="action" value="preview" class="win-btn win-btn-secondary">
                <i class="fas fa-eye"></i> Xem trước
            </button>
            <button type="submit" name="action" value="export" class="win-btn" style="background: var(--accent-color, #10b981); color: #fff; border: none;">
                <i class="fas fa-download"></i> Tải Excel
            </button>
        </div>
    </form>

    <div class="table-responsive-wrapper">
        <?php if (empty($preview_data)): ?>
            <div style="text-align:center; padding:30px; color:var(--text-muted); font-style:italic;">
                Không có dữ liệu vi phạm cổng nào trong Tuần <?= $selected_week ?? 1 ?>.
            </div>
        <?php else: ?>
            <h4 style="margin:0 0 10px 0; color:var(--text-main); font-size: 15px;">
                <i class="fas fa-table"></i> Dữ liệu xem trước (<?= count($preview_data) ?> dòng)
            </h4>
            <table class="win-table" style="font-size:13px; width: 100%; border-collapse: collapse; min-width: 700px;">
                <thead>
                    <tr style="background: var(--bg-hover, #f1f5f9); border-bottom: 2px solid var(--border-color, #cbd5e1);">
                        <th style="padding:12px 10px; text-align:center;">STT</th>
                        <th style="padding:12px 10px; text-align:center;">Thời gian</th>
                        <th style="padding:12px 10px; text-align:left;">Họ tên</th>
                        <th style="padding:12px 10px; text-align:center;">Lớp</th>
                        <th style="padding:12px 10px; text-align:center;">Điểm</th>
                        <th style="padding:12px 10px; text-align:left;">Chi tiết lỗi</th>
                        <th style="padding:12px 10px; text-align:left;">Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    foreach ($preview_data as $row): 
                        $d = new DateTime($row['date_created']);
                        
                        $h_str = $d->format('H');
                        $m = $d->format('i');
                        $dateStr = "$h_str:$m " . $d->format('d/m');
                        
                        $details = [];
                        if(!empty($row['details']) && is_array($row['details'])) {
                            foreach($row['details'] as $code => $pt) {
                                $details[] = "$code ($pt)";
                            }
                        }
                    ?>
                    <tr style="border-bottom: 1px solid var(--border-color, #e2e8f0);">
                        <td style="padding:12px 10px; text-align:center;"><?= $i++ ?></td>
                        <td style="padding:12px 10px; text-align:center; color:var(--text-muted, #64748b);"><?= $dateStr ?></td>
                        <td style="padding:12px 10px; font-weight:600; color:var(--text-main, #1e293b);"><?= htmlspecialchars($row['student_name'] ?? '') ?></td>
                        <td style="padding:12px 10px; text-align:center; color:var(--primary-color, #3b82f6); font-weight:bold;"><?= htmlspecialchars($row['class_name'] ?? '') ?></td>
                        <td style="padding:12px 10px; text-align:center; color:#ef4444; font-weight:bold;"><?= $row['total'] ?? 0 ?></td>
                        <td style="padding:12px 10px; color:var(--text-main, #334155);"><?= implode(", ", $details) ?></td>
                        <td style="padding:12px 10px; color:var(--text-muted, #94a3b8); font-style:italic;"><?= htmlspecialchars(implode(", ", $row['notes'] ?? [])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>