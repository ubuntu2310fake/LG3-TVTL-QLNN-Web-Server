<?php 
// File: views/banned_ips_history_view.php
include 'includes/header.php'; 
?>

<style>
    /* CSS Mặc định cho Desktop */
    .banned-table { width: 100%; border-collapse: collapse; }
    
    /* CSS Mobile Responsive */
    @media (max-width: 768px) {
        /* Ẩn tiêu đề bảng */
        .banned-table thead { display: none; }
        
        /* Biến mỗi dòng tr thành 1 cái thẻ card */
        .banned-table tr {
            display: block;
            background: var(--bg-card, #fff); /* Hỗ trợ dark mode nếu có biến này */
            border: 1px solid var(--border-color, #ddd);
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 10px;
        }

        /* Chỉnh lại từng ô td */
        .banned-table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
            text-align: right;
            font-size: 13px;
        }
        
        .banned-table td:last-child { border-bottom: none; }

        /* Hiện tiêu đề cột bằng thuộc tính data-label */
        .banned-table td::before {
            content: attr(data-label);
            font-weight: bold;
            color: var(--text-muted, #666);
            text-align: left;
            margin-right: 15px;
            flex-shrink: 0; /* Không cho tiêu đề bị co lại */
        }

        /* Tùy chỉnh riêng cho User-Agent trên mobile */
        .ua-cell {
            display: block !important; /* Xuống dòng */
            text-align: left !important;
        }
        .ua-cell::before { display: block; margin-bottom: 5px; }
        
        /* Tùy chỉnh nút hành động */
        .action-cell { justify-content: flex-end; }
    }
</style>

<div class="win-card" style="border-top: 4px solid var(--danger-color);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <h2 style="color: var(--danger-color); margin: 0; font-size: 20px; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-shield-virus"></i>
            <span>Sổ Đen (Banned IPs)</span>
        </h2>
        
        <button onclick="location.reload()" class="win-btn win-btn-secondary" style="font-size: 13px;">
            <i class="fas fa-sync-alt"></i> Làm mới
        </button>
    </div>

    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">
        Danh sách IP bị khóa do tấn công (Brute-force, SQLi...). Tự động mở khi hết hạn.
    </p>

    <div class="table-responsive">
        <table class="rank-table banned-table">
            <thead>
                <tr>
                    <th style="width: 5%;">ID</th>
                    <th style="width: 15%;">IP Public</th>
                    <th style="width: 25%;">Thiết bị (User-Agent)</th>
                    <th style="width: 20%;">Lý do phạt</th>
                    <th style="width: 15%;">Bị khóa lúc</th>
                    <th style="width: 10%;">Hết hạn</th>
                    <th style="width: 10%; text-align: right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($banned_list)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            <i class="fas fa-check-circle" style="font-size: 30px; color: #10b981; margin-bottom: 10px; display: block;"></i>
                            Không có IP nào đang bị khóa. Hệ thống an toàn!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($banned_list as $row): ?>
                        <?php 
                            $is_expired = strtotime($row['expires_at']) < time(); 
                        ?>
                        <tr style="<?= $is_expired ? 'opacity: 0.6;' : '' ?>">
                            <td data-label="ID">#<?= $row['id'] ?></td>
                            
                            <td data-label="IP Address" style="font-family: monospace; font-size: 14px; font-weight: bold; color: <?= $is_expired ? 'var(--text-muted)' : 'var(--danger-color)' ?>;">
                                <?= htmlspecialchars($row['ip_address']) ?>
                            </td>
                            
                            <td class="ua-cell" data-label="Thiết bị" style="font-size: 11px; color: var(--text-muted); line-height: 1.4; word-break: break-all;">
                                <?= htmlspecialchars($row['user_agent'] ?? 'Không xác định') ?>
                            </td>
                            
                            <td data-label="Lý do">
                                <span style="background: rgba(217, 48, 37, 0.1); color: var(--danger-color); padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-block;">
                                    <?= htmlspecialchars($row['reason']) ?>
                                </span>
                            </td>
                            
                            <td data-label="Bị khóa lúc" style="font-size: 13px;">
                                <?= date('H:i - d/m/Y', strtotime($row['banned_at'])) ?>
                            </td>
                            
                            <td data-label="Hết hạn" style="font-size: 13px; font-weight: bold; color: <?= $is_expired ? '#10b981' : '#f59e0b' ?>;">
                                <?php if ($is_expired): ?>
                                    Đã hết hạn
                                <?php else: ?>
                                    <i class="fas fa-clock"></i> <?= date('H:i', strtotime($row['expires_at'])) ?>
                                <?php endif; ?>
                            </td>
                            
                            <td class="action-cell" data-label="Thao tác" style="text-align: right;">
                                <?php if (!$is_expired): ?>
                                    <a href="?unban=<?= $row['id'] ?>" class="win-btn" style="background: var(--danger-color); padding: 6px 10px; font-size: 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px;" onclick="return confirm('Mở khóa ngay cho IP này?');">
                                        <i class="fas fa-unlock-alt"></i> Mở khóa
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: var(--text-muted);">Đã thả</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
include 'includes/footer.php'; 
?>