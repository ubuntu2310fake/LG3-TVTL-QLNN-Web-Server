<?php
require_once 'includes/header.php'; 
?>

<style>
    /* CSS GIỮ NGUYÊN NHƯ CŨ */
    .dashboard-grid { display: grid; grid-template-columns: 280px 1fr 340px; gap: 15px; height: 80vh; }
    .dashboard-grid.two-columns { grid-template-columns: 320px 1fr !important; }
    .dashboard-col { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
    .scroll-box { flex: 1; overflow-y: auto; padding-right: 5px; }

    @media (max-width: 1200px) {
        .dashboard-grid { grid-template-columns: 260px 1fr; grid-template-rows: auto auto; height: auto; }
        .col-psychology { grid-column: 1 / -1; height: 400px !important; }
        .dashboard-grid.two-columns { grid-template-columns: 1fr !important; }
    }
    @media (max-width: 768px) {
        .dashboard-grid { display: flex; flex-direction: column; height: auto !important; gap: 15px; }
        .dashboard-col { height: auto !important; min-height: 400px; max-height: 500px; }
        .col-psychology { min-height: 300px; }
    }

    .log-card { background: var(--bg-card); padding: 12px; margin-bottom: 10px; border-radius: 8px; border: 1px solid var(--border-color); font-size: 13px; }
    .card-warning { border-left: 4px solid #f59e0b !important; background-color: #fffbeb !important; }
    .card-danger { border-left: 4px solid #dc2626 !important; background-color: #fef2f2 !important; animation: pulse-red 2s infinite; }
    @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.2); } 70% { box-shadow: 0 0 0 5px rgba(220, 38, 38, 0); } 100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); } }

    .matrix-section { margin-top: 20px; }
    .paper-table { width: 100%; border-collapse: collapse; background: var(--bg-card); border: 1px solid var(--border-color); }
    .paper-table th, .paper-table td { border: 1px solid var(--border-color); padding: 8px 4px; text-align: center; vertical-align: middle; font-size: 13px; }
    .paper-table th { background-color: var(--bg-hover); color: var(--text-muted); font-weight: 700; text-transform: uppercase; font-size: 11px; }
    
    .score-display { width: 100%; border: none; text-align: center; font-size: 14px; font-weight: 500; color: var(--text-main); background: transparent; outline: none; }
    .score-deducted { color: var(--danger-color); font-weight: 800; background: rgba(220, 38, 38, 0.1); border-radius: 4px; }
    .cell-total { color: var(--accent-color); font-weight: 800; font-size: 15px; background-color: rgba(37, 99, 235, 0.05); }

    .note-box { margin-top: 15px; padding: 15px; border-radius: 8px; background: #fff7ed; border: 1px solid #fdba74; color: #9a3412; }
    [data-theme="dark"] .note-box { background: #431407; border-color: #7c2d12; color: #fdba74; }
    .bonus-display { font-size: 11px; color: #10b981; font-weight: 700; margin-top: 2px; font-style: italic; }
    .footnote { margin-top: 15px; font-size: 12px; color: var(--text-muted); font-style: italic; border-top: 1px dashed var(--border-color); padding-top: 10px; }
</style>

<?php if (!$my_class): ?>
<div class="win-card" style="text-align:center; padding:40px;">
    <h3 style="color:var(--text-muted);">Bạn chưa được phân công lớp chủ nhiệm.</h3>
</div>
<?php else: ?>

<div class="dashboard-grid <?php echo ($currentUser['role'] == 'RED_FLAG') ? 'two-columns' : ''; ?>">
    
    <div class="win-card dashboard-col" style="padding: 15px;">
        <h3 style="color:var(--accent-color); margin:0;">Lớp <?php echo htmlspecialchars($my_class['name']); ?></h3>
        <p style="color:var(--text-muted); font-size:13px; margin-bottom:10px;">
            <i class="fas fa-user-tie"></i> GVCN: <strong><?php echo $main_teacher ? htmlspecialchars($main_teacher['full_name']) : '...'; ?></strong>
        </p>
        <div class="scroll-box custom-scroll">
            <?php foreach ($students as $s): ?>
            <div style="border:1px solid var(--border-color); background:var(--bg-card); margin-bottom:8px; border-radius:6px; padding:10px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($s['name']); ?></div>
                        <div style="font-size: 11px; color:var(--text-muted);"><?php echo htmlspecialchars($s['code']); ?></div>
                    </div>
                    <?php if ($s['has_exemption']): ?>
                        <i class="fas fa-shield-alt" style="color:#10b981;" title="<?php echo htmlspecialchars($s['exemption_reason']); ?>"></i>
                    <?php endif; ?>
                </div>
                
                <?php if (in_array($currentUser['role'], ['TEACHER', 'ADMIN'])): ?>
                <div style="margin-top:5px; border-top:1px dashed var(--border-color); padding-top:5px; display:flex; gap:5px; align-items:center;">
                    <input type="checkbox" id="chk_<?php echo $s['id']; ?>" <?php echo $s['has_exemption'] ? 'checked' : ''; ?>>
                    <input type="text" id="rs_<?php echo $s['id']; ?>" value="<?php echo htmlspecialchars($s['exemption_reason'] ?? ''); ?>" placeholder="Lý do..." style="font-size:11px; width:100px; border:1px solid #ddd; padding:2px;">
                    <button onclick="updateExemption('<?php echo $s['id']; ?>')" class="win-btn" style="padding:2px 5px; font-size:10px;">Lưu</button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="win-card dashboard-col">
        <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom: 10px; border-bottom: 1px solid var(--border-color); margin-bottom:10px;">
            <h3 style="margin:0; color:var(--danger-color); font-size:16px;"><i class="fas fa-history"></i> Vi phạm</h3>
            <form method="GET" style="display:flex; align-items:center; gap:5px; background:var(--bg-hover); padding:2px 10px; border-radius:15px;">
                <span style="font-size: 12px; font-weight:bold;">TUẦN</span>
                <input type="number" name="week" value="<?php echo $selected_week; ?>" style="width:35px; border:none; background:transparent; font-weight:bold; color:var(--danger-color); text-align:center;">
                <button style="border:none; background:none; cursor:pointer;"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <div class="scroll-box custom-scroll">
            <?php if (!empty($violations)): ?>
                <?php foreach ($violations as $v): ?>
                <div id="vio_<?php echo $v['id']; ?>" style="background:var(--bg-card); padding:10px; margin-bottom:10px; border-left:3px solid var(--danger-color); border-radius:4px; border:1px solid var(--border-color);">
                    <div style="display:flex; justify-content:space-between;">
                        <span style="font-weight:700;"><?php echo $v['student_name'] ? htmlspecialchars($v['student_name']) : 'Tập thể'; ?></span>
                        <span style="color:var(--danger-color); font-weight:bold;">-<?php echo $v['recorded_points']; ?></span>
                    </div>
                    <div style="font-size:13px; margin:2px 0;"><?php echo htmlspecialchars($v['recorded_violation_name']); ?></div>
                    <div style="font-size:11px; color:var(--text-muted); display:flex; justify-content:space-between; margin-top:5px;">
                        <span><?php echo date('H:i d/m', strtotime($v['date_created'])); ?> - <?php echo htmlspecialchars($v['reporter']); ?></span>
                        <?php if (in_array($currentUser['role'], ['TEACHER', 'ADMIN'])): ?>
                        <button onclick="deleteViolation('<?php echo $v['id']; ?>')" style="border:none; background:none; color:#999; cursor:pointer;"><i class="fas fa-trash"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; color:var(--text-muted); margin-top:50px;">Không có vi phạm.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($currentUser['role'] != 'RED_FLAG'): ?>
    <div class="win-card dashboard-col col-psychology">
        <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 10px; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin:0; color:#8b5cf6; font-size:16px;">
                    <i class="fas fa-brain"></i> Góc Tâm lý
                </h3>
                <p style="font-size:10px; color:var(--text-muted); margin:0;">AI giám sát 24/7</p>
            </div>
            <div style="font-size:10px; text-align:right;">
                <span style="color:#f59e0b;">● Căng thẳng</span> <span style="color:#dc2626;">● SOS</span>
            </div>
        </div>

        <div class="scroll-box custom-scroll">
            <?php if (!empty($psychology_logs)): ?>
                <?php foreach ($psychology_logs as $log): ?>
                <?php 
                    $risk_cls = '';
                    if ($log['risk_level'] == 'DANGER') $risk_cls = 'card-danger';
                    elseif ($log['risk_level'] == 'WARNING') $risk_cls = 'card-warning';
                ?>
                <div class="log-card <?php echo $risk_cls; ?>">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <strong style="color:var(--text-main); font-size:13px;"><?php echo htmlspecialchars($log['student_name'] ?? 'Ẩn danh'); ?></strong>
                        <?php if ($log['risk_level'] == 'DANGER'): ?>
                            <span style="color:#dc2626; font-weight:bold; font-size:10px;">SOS</span>
                        <?php endif; ?>
                    </div>
                    <div style="background:rgba(0,0,0,0.03); padding:8px; border-radius:6px; margin-bottom:6px;">
                        <i class="far fa-comment-dots"></i> <?php echo htmlspecialchars($log['question']); ?>
                    </div>
                    <details>
                        <summary style="cursor:pointer; color:#8b5cf6; font-weight:500; font-size:11px;">Xem AI tư vấn</summary>
                        <div style="margin-top:5px; padding:5px; border-left:2px solid #8b5cf6; font-size:12px; color:var(--text-muted); white-space: pre-line;">
                            <?php echo htmlspecialchars($log['advice']); ?>
                        </div>
                    </details>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center; padding:30px 10px; color:var(--text-muted);">
                    <i class="fas fa-smile-beam" style="font-size:30px; margin-bottom:10px; color:#10b981; opacity:0.6;"></i>
                    <p style="font-size:13px;">Tâm lý học sinh ổn định.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="win-card matrix-section">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <h3 style="margin:0; color:var(--text-main); font-size:18px;">
            <i class="fas fa-clipboard-check"></i> Sổ đoàn trường
        </h3>
        <span style="font-size:13px; color:var(--text-muted); font-style:italic;">* Dữ liệu chỉ xem. Chỉnh sửa tại mục "Kiểm tra lớp".</span>
    </div>

    <div style="overflow-x:auto;">
        <table class="paper-table">
            <thead>
                <tr>
                    <th style="min-width:60px;">Thứ</th>
                    <th>Sĩ số<br>T.Trung</th>
                    <th>Vệ<br>sinh</th>
                    <th>BVCSVC<br>Sau giờ</th>
                    <th>Truy<br>bài</th>
                    <th>Xe<br>ATGT</th>
                    <th>Đồng<br>phục</th>
                    <th>Sơ<br>vin</th>
                    <th>Thẻ<br>HS</th>
                    <th>Tóc<br>Dép</th>
                    <th style="min-width:60px;">Tổng</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matrix_data as $row): ?>
                <tr>
                    <td style="font-weight:700;"><?php echo $row['label']; ?></td>
                    <?php foreach ($row['scores'] as $item): ?>
                    <td>
                        <input type="text" 
                               class="score-display <?php echo ($item['val'] < $item['max']) ? 'score-deducted' : ''; ?>" 
                               value="<?php echo $item['val']; ?>" 
                               readonly>
                    </td>
                    <?php endforeach; ?>
                    <td class="cell-total"><?php echo $row['total']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="10" style="text-align:right; padding-right:15px; font-weight:700; color:var(--text-muted);">TỔNG ĐIỂM TUẦN:</td>
                    <td style="vertical-align: middle;">
                        <div style="font-size:16px; font-weight:800; color:var(--accent-color); line-height:1;"><?php echo $matrix_total; ?></div>
                        <?php if ($bonus_score > 0): ?>
                        <div class="bonus-display">+<?php echo $bonus_score; ?> <i>(*)</i></div>
                        <?php endif; ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if ($matrix_note): ?>
    <div class="note-box">
        <div style="font-weight:bold; margin-bottom:5px; display:flex; align-items:center; gap:8px;">
            <i class="fas fa-sticky-note"></i> Ghi chú của Sao đỏ / Giáo viên:
        </div>
        <div style="white-space: pre-line; font-size:14px;"><?php echo htmlspecialchars($matrix_note); ?></div>
    </div>
    <?php else: ?>
    <div style="margin-top:15px; font-style:italic; color:var(--text-muted); text-align:center; font-size:13px;">
        (Chưa có ghi chú nào cho tuần này)
    </div>
    <?php endif; ?>
    
    <div class="footnote">
        (*): Điểm cộng sau khi tổng kết tuần
    </div>
</div>
<?php endif; // Kết thúc if (!$my_class) ?>

<script>
    function updateExemption(sid) {
        fetch('teacher_dashboard.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body:JSON.stringify({
                action: 'update_exemption',
                student_id: sid, 
                is_exempt: document.getElementById('chk_'+sid).checked, 
                reason: document.getElementById('rs_'+sid).value
            })
        })
        .then(r=>r.json())
        .then(d=>{ 
            if(d.status==='success') {
                if(typeof Toastify !== 'undefined') Toastify({text:"Đã lưu!", style:{background:"green"}}).showToast();
                else alert("Đã lưu!");
            } else {
                alert(d.msg);
            }
        });
    }

    function deleteViolation(id) {
        if(confirm('Xóa lỗi này?')) {
            const fd = new FormData();
            fd.append('action', 'delete_violation');
            fd.append('id', id);

            fetch('teacher_dashboard.php', {
                method:'POST', 
                body: fd
            })
            .then(r=>r.json())
            .then(d=>{
                if(d.status==='success') document.getElementById('vio_'+id).remove();
                else alert(d.msg);
            });
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>