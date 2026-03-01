<?php
include 'includes/header.php';
?>

<style>
    /* CSS CHO TABS */
    .history-tabs { display: flex; border-bottom: 2px solid var(--border-color); margin-bottom: 20px; gap: 20px; }
    .tab-btn {
        background: none; border: none; padding: 10px 5px; font-size: 16px; 
        font-weight: 600; color: var(--text-muted); cursor: pointer; position: relative;
        transition: 0.3s;
    }
    .tab-btn:hover { color: var(--primary); }
    .tab-btn.active { color: var(--primary); }
    .tab-btn.active::after {
        content: ''; position: absolute; bottom: -2px; left: 0; width: 100%; 
        height: 3px; background: var(--primary); border-radius: 3px 3px 0 0;
    }
    
    .tab-content { display: none; animation: fadeIn 0.3s ease; }
    .tab-content.active { display: block; }

    /* TABLE STYLE */
    .log-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .log-table th { text-align: left; padding: 10px; background: var(--bg-hover); color: var(--text-muted); font-size: 12px; text-transform: uppercase; }
    .log-table td { padding: 12px 10px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
    .log-table tr:hover { background: var(--bg-hover); }
    
    .badge-points {
        background: #fee2e2; color: #ef4444; padding: 2px 8px; 
        border-radius: 4px; font-weight: bold; font-size: 12px;
    }
    .reporter-info { display: flex; flex-direction: column; }
    .reporter-name { font-weight: 600; color: var(--text-main); font-size: 13px; }
    .reporter-user { font-size: 11px; color: var(--text-muted); }
    
    .time-badge {
        background: var(--bg-input); padding: 4px 8px; border-radius: 6px; 
        font-family: monospace; font-size: 12px; color: var(--text-main); border: 1px solid var(--border-color);
    }
    .status-deleted { text-decoration: line-through; opacity: 0.6; }
    .tag-deleted { background: #f3f4f6; color: #6b7280; padding: 2px 6px; border-radius: 4px; font-size: 10px; border: 1px solid #d1d5db; }
</style>

<div class="grid-sidebar-layout">
    <div class="win-card" style="grid-column: span 2;">
        <h3 style="margin-top:0; color:var(--primary); display:flex; align-items:center; gap:10px;">
            <i class="fas fa-history"></i> LỊCH SỬ GHI NHẬN & TRỪ ĐIỂM
        </h3>

        <div class="history-tabs">
            <button class="tab-btn active" onclick="openTab(event, 'tabGate')">
                <i class="fas fa-torii-gate"></i> Kiểm tra Cổng (Gate)
            </button>
            <button class="tab-btn" onclick="openTab(event, 'tabClass')">
                <i class="fas fa-chalkboard"></i> Kiểm tra Lớp (Class)
            </button>
        </div>

        <div id="tabGate" class="tab-content active">
            <div style="overflow-x:auto;">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Học sinh / Lớp</th>
                            <th>Lỗi vi phạm</th>
                            <th>Điểm trừ</th>
                            <th>Người báo cáo (Reporter)</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($gateLogs)): ?>
                            <tr><td colspan="6" style="text-align:center; padding:20px;">Không có dữ liệu.</td></tr>
                        <?php else: foreach($gateLogs as $log): ?>
                        <tr>
                            <td>
                                <div class="time-badge">
                                    <?= date('H:i', strtotime($log['date_created'])) ?>
                                </div>
                                <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">
                                    <?= date('d/m/Y', strtotime($log['date_created'])) ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:bold; color:var(--primary);"><?= htmlspecialchars($log['student_name']) ?></div>
                                <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($log['class_name']) ?> (<?= $log['student_code'] ?>)</div>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($log['recorded_violation_name']) ?></div>
                                <?php if($log['note']): ?>
                                    <small style="color:var(--text-muted); font-style:italic;">"<?= htmlspecialchars($log['note']) ?>"</small>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge-points">-<?= $log['recorded_points'] ?></span></td>
                            <td>
                                <div class="reporter-info">
                                    <span class="reporter-name"><?= htmlspecialchars(getReporterName($log)) ?></span>
                                    <span class="reporter-user">@<?= htmlspecialchars($log['reporter_username']) ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if(isset($log['is_deleted']) && $log['is_deleted'] == 1): ?>
                                    <span class="tag-deleted">Đã xóa</span>
                                <?php else: ?>
                                    <span style="color:var(--success); font-size:12px; font-weight:600;">Hiệu lực</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="tabClass" class="tab-content">
            <div style="margin-bottom:10px; padding:10px; background:var(--bg-light); border-left:4px solid var(--accent-color); font-size:13px;">
                <i class="fas fa-info-circle"></i> <b>Lưu ý:</b> Thời gian hiển thị bên dưới là thời điểm Giáo viên/Sao đỏ bấm nút <b>"Lưu"</b> (`submitted_at`).
            </div>
            <div style="overflow-x:auto;">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Thời gian Submit</th>
                            <th>Lớp</th>
                            <th>Tuần</th>
                            <th>Chi tiết lỗi</th>
                            <th>Điểm trừ</th>
                            <th>Người chấm (Reporter)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($classLogs)): ?>
                            <tr><td colspan="6" style="text-align:center; padding:20px;">Không có dữ liệu.</td></tr>
                        <?php else: foreach($classLogs as $log): ?>
                        <tr>
                            <td>
                                <?php if($log['submitted_at']): ?>
                                    <div class="time-badge" style="border-color:var(--primary);">
                                        <?= date('H:i', strtotime($log['submitted_at'])) ?>
                                    </div>
                                    <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">
                                        <?= date('d/m/Y', strtotime($log['submitted_at'])) ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:12px;">--:--</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-weight:bold; font-size:15px; color:var(--text-main);">
                                    <?= htmlspecialchars($log['class_name']) ?>
                                </span>
                            </td>
                            <td><span style="background:var(--bg-hover); padding:2px 6px; border-radius:4px; font-size:12px;">Tuần <?= $log['week_number'] ?></span></td>
                            <td><?= htmlspecialchars($log['recorded_violation_name']) ?></td>
                            <td><span class="badge-points">-<?= $log['recorded_points'] ?></span></td>
                            <td>
                                <div class="reporter-info">
                                    <span class="reporter-name"><?= htmlspecialchars(getReporterName($log)) ?></span>
                                    <span class="reporter-user">@<?= htmlspecialchars($log['reporter_username']) ?></span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    function openTab(evt, tabName) {
        // Ẩn tất cả tab
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
            tabcontent[i].classList.remove("active");
        }

        // Bỏ active button
        tablinks = document.getElementsByClassName("tab-btn");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }

        // Hiện tab được chọn
        document.getElementById(tabName).style.display = "block";
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.className += " active";
    }
</script>

<?php include 'includes/footer.php'; ?>