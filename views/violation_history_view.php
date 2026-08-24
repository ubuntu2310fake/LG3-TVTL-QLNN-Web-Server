<?php
include 'includes/header.php';
// Lấy role ra để check UI
$role = $_SESSION['user']['role'] ?? '';
?>

<style>
    /* CSS CHO TABS */
    .history-tabs { display: flex; border-bottom: 2px solid var(--border-color); margin-bottom: 20px; gap: 20px; }
    .tab-btn { background: none; border: none; padding: 10px 5px; font-size: 16px; font-weight: 600; color: var(--text-muted); cursor: pointer; position: relative; transition: 0.3s; }
    .tab-btn:hover { color: var(--primary-color); }
    .tab-btn.active { color: var(--primary-color); }
    .tab-btn.active::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 100%; height: 3px; background: var(--primary-color); border-radius: 3px 3px 0 0; }
    .tab-content { display: none; animation: fadeIn 0.3s ease; }
    .tab-content.active { display: block; }

    /* TABLE STYLE */
    .log-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .log-table th { text-align: left; padding: 10px; background: var(--bg-hover); color: var(--text-muted); font-size: 12px; text-transform: uppercase; }
    .log-table td { padding: 12px 10px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
    .log-table tr:hover { background: var(--bg-hover); }
    
    .badge-points { background: #fee2e2; color: #ef4444; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
    .reporter-info { display: flex; flex-direction: column; }
    .reporter-name { font-weight: 600; color: var(--text-main); font-size: 13px; }
    .reporter-user { font-size: 11px; color: var(--text-muted); }
    
    .time-badge { background: var(--bg-input); padding: 4px 8px; border-radius: 6px; font-family: monospace; font-size: 12px; color: var(--text-main); border: 1px solid var(--border-color); }
    .status-deleted { text-decoration: line-through; opacity: 0.6; }
    .tag-deleted { background: #f3f4f6; color: #6b7280; padding: 2px 6px; border-radius: 4px; font-size: 10px; border: 1px solid #d1d5db; }

    /* NÚT THAO TÁC */
    .btn-action { border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 12px; transition: 0.2s; margin-right: 4px; color: white;}
    .btn-edit { background-color: #3b82f6; }
    .btn-edit:hover { background-color: #2563eb; }
    .btn-delete { background-color: #ef4444; }
    .btn-delete:hover { background-color: #dc2626; }

    /* MODAL SỬA LỖI */
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; justify-content: center; align-items: center; z-index: 1000; }
    .modal-box { background: var(--bg-card, #fff); padding: 20px; border-radius: 10px; width: 400px; max-width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
    .modal-box h3 { margin-top: 0; margin-bottom: 15px; color: var(--primary-color); }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 600; color: var(--text-main); }
    .form-group input { width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; outline: none; background: var(--bg-input); color: var(--text-main); }
    .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    .btn-cancel { background: #6b7280; color: white; padding: 8px 15px; border-radius: 6px; border: none; cursor: pointer; }
    .btn-save { background: #005fba; color: #ffffff !important; padding: 8px 15px; border-radius: 6px; border: none; cursor: pointer; }

    /* CUSTOM SELECT STYLE (Lấy từ Gate Check) */
    .custom-select-container { position: relative; width: 100%; }
    .select-selected {
        background-color: var(--bg-input); border: 1px solid var(--border-color); border-radius: 6px;
        padding: 8px 12px; display: flex; align-items: center; justify-content: space-between;
        font-size: 14px; box-sizing: border-box; color: var(--text-main);
        transition: 0.2s; cursor: pointer;
    }
    .select-selected:active { border-color: var(--accent-color); box-shadow: 0 0 0 3px rgba(0,95,186,0.15); }
    .select-arrow { width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid var(--text-muted); transition: 0.2s; flex-shrink: 0; }
    .select-selected.active .select-arrow { transform: rotate(180deg); }
    .select-items {
        position: absolute; top: 110%; left: 0; right: 0; z-index: 1000;
        background: var(--bg-card, #fff); border: 1px solid var(--border-color); border-radius: 6px;
        max-height: 200px; overflow-y: auto; display: none;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1); animation: fadeIn 0.2s ease;
    }
    .select-items div { padding: 10px 12px; cursor: pointer; border-bottom: 1px solid var(--border-color); font-size: 14px; color: var(--text-main); }
    .select-items div:hover { background: var(--bg-hover); color: var(--primary-color); font-weight: 500; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

    /* CSS PHÂN TRANG */
    .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; flex-wrap:wrap; padding-bottom: 10px; }
    .page-btn { padding: 6px 12px; border: 1px solid var(--border-color); background: var(--bg-card); border-radius: 6px; color: var(--text-main); text-decoration: none; transition: 0.2s; font-size: 13px; font-weight: 500; }
    .page-btn:hover { background: var(--bg-hover); color: var(--primary-color); }
    .page-btn.active { background: #005fba; color: #ffffff !important; border-color: #005fba; pointer-events: none; }

    /* FIX LỖI AMOLED TRẮNG XÓA NÚT BẤM */
    html[data-theme="dark"] body .btn-action,
    html[data-theme="dark"] body .btn-cancel,
    html[data-theme="dark"] body .btn-save,
    html[data-theme="dark"] body .page-btn.active {
        color: #000000;
        font-weight: bold;
    }
</style>

<div class="grid-sidebar-layout">
    <div class="win-card" style="grid-column: span 2;">
        <h3 style="margin-top:0; color:var(--primary-color); display:flex; align-items:center; gap:10px;">
            <i class="fas fa-history" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'LỊCH SỬ GHI NHẬN & TRỪ ĐIỂM' : 'RECORD & DEDUCTION HISTORY') ?>
        </h3>

        <div class="history-tabs">
            <button class="tab-btn <?= $active_tab == 'tabGate' ? 'active' : '' ?>" onclick="openTabSafe(event, 'tabGate')">
                <i class="fas fa-torii-gate" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Kiểm tra Cổng (Gate)' : 'Gate Check') ?>
            </button>
            <button class="tab-btn <?= $active_tab == 'tabClass' ? 'active' : '' ?>" onclick="openTabSafe(event, 'tabClass')">
                <i class="fas fa-chalkboard" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Kiểm tra Lớp (Class)' : 'Class Check') ?>
            </button>
        </div>

        <div id="tabGate" class="tab-content <?= $active_tab == 'tabGate' ? 'active' : '' ?>">
            <div style="overflow-x:auto;">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thời gian' : 'Time') ?></th>
                            <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'Week') ?></th>
                            <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Học sinh / Lớp' : 'Student / Class') ?></th>
                            <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi vi phạm' : 'Violation Error') ?></th>
                            <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm trừ' : 'Deducted Points') ?></th>
                            <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Người báo cáo' : 'Reporter') ?></th>
                            <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trạng thái' : 'Status') ?></th>
                            <?php if($role === 'ADMIN'): ?>
                            <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thao tác' : 'Action') ?></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="gateLogsTableBody">
                        <?php if(empty($gateLogs)): ?>
                            <tr id="emptyGateLog"><td colspan="8" style="text-align:center; padding:20px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Không có dữ liệu.' : 'No data available.') ?></td></tr>
                        <?php else: foreach($gateLogs as $log): 
                            $is_deleted = (isset($log['is_deleted']) && $log['is_deleted'] == 1);
                        ?>
                        <tr id="log_row_<?= $log['id'] ?>" class="<?= $is_deleted ? 'status-deleted' : '' ?>">
                            <td>
                                <div class="time-badge">
                                    <?= date('H:i', strtotime($log['date_created'])) ?>
                                </div>
                                <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">
                                    <?= date('d/m/Y', strtotime($log['date_created'])) ?>
                                </div>
                            </td>
                            <td>
                                <span style="background:var(--bg-hover); padding:2px 6px; border-radius:4px; font-size:12px; font-weight:bold;">
                                    <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'Week') ?> <?= htmlspecialchars($log['week_number']) ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-weight:bold; color:var(--primary-color);"><?= htmlspecialchars($log['student_name']) ?></div>
                                <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($log['class_name']) ?> (<?= $log['student_code'] ?>)</div>
                            </td>
                            <td>
                                <?php 
                                    $currentLang = $_SESSION['lang'] ?? 'vi';
                                    if ($currentLang === 'en') {
                                        $vNameDisplay = !empty($log['recorded_violation_name_en']) ? $log['recorded_violation_name_en'] : (!empty($log['violation_name_vi']) ? $log['violation_name_vi'] : $log['recorded_violation_name']);
                                    } else {
                                        $vNameDisplay = !empty($log['violation_name_vi']) ? $log['violation_name_vi'] : $log['recorded_violation_name'];
                                    }
                                ?>
                                <div><?= htmlspecialchars($vNameDisplay) ?></div>
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
                                <?php if($is_deleted): ?>
                                    <span class="tag-deleted"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Không hiệu lực' : 'Invalid') ?></span>
                                <?php else: ?>
                                    <span style="color:var(--success); font-size:12px; font-weight:600;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hiệu lực' : 'Valid') ?></span>
                                <?php endif; ?>
                            </td>
                            
                            <?php if($role === 'ADMIN'): ?>
                            <td>
                                <?php if(!$is_deleted): ?>
                                    <button class="btn-action btn-edit" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Sửa' : 'Edit') ?>" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Sửa' : 'Edit') ?>"
                                        onclick="openEditModal(
                                            <?= $log['id'] ?>, 
                                            <?= $log['violation_type_id'] ?>, 
                                            <?= $log['week_number'] ?>, 
                                            '<?= date('Y-m-d\TH:i', strtotime($log['date_created'])) ?>'
                                        )">
                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                    </button>
                                    <button class="btn-action btn-delete" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xóa' : 'Delete') ?>" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xóa' : 'Delete') ?>" onclick="deleteRecord(<?= $log['id'] ?>)">
                                        <i class="fas fa-trash" aria-hidden="true"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>

                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
                
                <?php if($total_pages_gate > 1): ?>
                <div class="pagination">
                    <?php for($i = 1; $i <= $total_pages_gate; $i++): ?>
                        <a href="violation_history.php?tab=tabGate&page_gate=<?= $i ?>&page_class=<?= $page_class ?>" 
                           class="page-btn <?= $i === $page_gate ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="tabClass" class="tab-content <?= $active_tab == 'tabClass' ? 'active' : '' ?>">
             <div style="margin-bottom:10px; padding:10px; background:var(--bg-light); border-left:4px solid var(--accent-color); font-size:13px;">
                <i class="fas fa-info-circle" aria-hidden="true"></i> <b><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lưu ý:' : 'Note:') ?></b> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thời gian hiển thị bên dưới là thời điểm Giáo viên/Sao đỏ bấm nút' : 'The time displayed below is when the Teacher/Red Flag pressed the') ?> <b>"<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lưu' : 'Save') ?>"</b> (`submitted_at`).
            </div>
            <div style="overflow-x:auto;">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thời gian Submit' : 'Submit Time') ?></th>
                            <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp' : 'Class') ?></th>
                            <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'Week') ?></th>
                            <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chi tiết lỗi' : 'Violation Details') ?></th>
                            <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm trừ' : 'Deducted Points') ?></th>
                            <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Người chấm' : 'Evaluator') ?></th>
                            <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trạng thái' : 'Status') ?></th> 
                        </tr>
                    </thead>
                    <tbody id="classLogsTableBody">
                        <?php if(empty($classLogs)): ?>
                            <tr id="emptyClassLog"><td colspan="7" style="text-align:center; padding:20px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Không có dữ liệu.' : 'No data available.') ?></td></tr>
                        <?php else: foreach($classLogs as $log): ?>
                        <tr id="log_row_<?= $log['id'] ?>" class="<?= (isset($log['is_deleted']) && $log['is_deleted'] == 1) ? 'status-deleted' : '' ?>">
                            <td>
                                <?php if($log['submitted_at']): ?>
                                    <div class="time-badge" style="border-color:var(--primary-color);">
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
                            <td><span style="background:var(--bg-hover); padding:2px 6px; border-radius:4px; font-size:12px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'Week') ?> <?= $log['week_number'] ?></span></td>
                            <?php 
                                $currentLang = $_SESSION['lang'] ?? 'vi';
                                $vNameClassDisplay = $log['recorded_violation_name'];
                                if ($currentLang === 'en' && !empty($log['recorded_violation_name_en']) && !empty($log['violation_name_vi'])) {
                                    $vNameClassDisplay = str_replace($log['violation_name_vi'], $log['recorded_violation_name_en'], $log['recorded_violation_name']);
                                } elseif ($currentLang === 'vi' && !empty($log['recorded_violation_name_en']) && !empty($log['violation_name_vi'])) {
                                    $vNameClassDisplay = str_replace($log['recorded_violation_name_en'], $log['violation_name_vi'], $log['recorded_violation_name']);
                                }
                            ?>
                            <td><?= htmlspecialchars($vNameClassDisplay) ?></td>
                            <td><span class="badge-points">-<?= $log['recorded_points'] ?></span></td>
                            <td>
                                <div class="reporter-info">
                                    <span class="reporter-name"><?= htmlspecialchars(getReporterName($log)) ?></span>
                                    <span class="reporter-user">@<?= htmlspecialchars($log['reporter_username']) ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if(isset($log['is_deleted']) && $log['is_deleted'] == 1): ?>
                                    <span class="tag-deleted"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Không hiệu lực' : 'Invalid') ?></span>
                                <?php else: ?>
                                    <span style="color:var(--success); font-size:12px; font-weight:600;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hiệu lực' : 'Valid') ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>

                <?php if($total_pages_class > 1): ?>
                <div class="pagination">
                    <?php for($i = 1; $i <= $total_pages_class; $i++): ?>
                        <a href="violation_history.php?tab=tabClass&page_gate=<?= $page_gate ?>&page_class=<?= $i ?>" 
                           class="page-btn <?= $i === $page_class ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<div class="modal-overlay" id="editModal" role="dialog" aria-modal="true" aria-labelledby="edit_modal_title">
    <div class="modal-box">
        <h3 id="edit_modal_title"><i class="fas fa-edit" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Sửa Lỗi Vi Phạm' : 'Edit Violation Error') ?></h3>
        <form id="editForm">
            <input type="hidden" id="edit_id" name="edit_id">
            <input type="hidden" name="action" value="edit">

            <div class="form-group">
                <label><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Loại vi phạm' : 'Violation Type') ?></label>
                <div class="custom-select-container">
                    <input type="hidden" id="edit_violation_id" name="violation_type_id" required>
                    <div class="select-selected" onclick="toggleDropdown(event, this)" onkeydown="if(event.key==='Enter' || event.key===' ') toggleDropdown(event, this)" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Loại vi phạm' : 'Violation Type') ?>">
                        <span id="txtEditSelectedViolation"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '-- Chọn lỗi vi phạm --' : '-- Select violation error --') ?></span>
                        <div class="select-arrow" aria-hidden="true"></div>
                    </div>
                    <div class="select-items" role="listbox">
                        <?php 
                        $lang = $_SESSION['lang'] ?? 'vi';
                        $pts_unit = $lang === 'en' ? 'pts' : 'đ';
                        foreach($all_gate_violations as $v): 
                            $disp = ($lang === 'en' && !empty($v['content_en'])) ? $v['content_en'] : $v['content'];
                        ?>
                            <div onclick="selectEditViolation('<?= $v['id'] ?>', '<?= htmlspecialchars($disp, ENT_QUOTES) ?> (-<?= $v['points'] ?><?= $pts_unit ?>)', this)" onkeydown="if(event.key==='Enter' || event.key===' ') selectEditViolation('<?= $v['id'] ?>', '<?= htmlspecialchars($disp, ENT_QUOTES) ?> (-<?= $v['points'] ?><?= $pts_unit ?>)', this)" data-vid="<?= $v['id'] ?>" role="option" tabindex="0">
                                <?= htmlspecialchars($disp) ?> (-<?= $v['points'] ?><?= $pts_unit ?>)
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần chấm' : 'Week') ?></label>
                <input type="number" id="edit_week" name="week_number" min="1" max="52" required>
            </div>

            <div class="form-group">
                <label><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ngày giờ vi phạm' : 'Violation Time') ?></label>
                <input type="datetime-local" id="edit_time" name="date_created" required>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEditModal()"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hủy' : 'Cancel') ?></button>
                <button type="submit" class="btn-save"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lưu thay đổi' : 'Save Changes') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
    // Xử lý Tabs
    window.openTabSafe = function(evt, tabName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) { tabcontent[i].style.display = "none"; tabcontent[i].classList.remove("active"); }
        tablinks = document.getElementsByClassName("tab-btn");
        for (i = 0; i < tablinks.length; i++) { tablinks[i].className = tablinks[i].className.replace(" active", ""); }
        document.getElementById(tabName).style.display = "block";
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.className += " active";
        
        // Cập nhật tab url hash hoặc query param ảo để biết mà share link (tùy chọn)
        if(typeof window.history.replaceState === 'function') {
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', url);
        }
    };

    // --- LOGIC CUSTOM DROPDOWN ---
    window.toggleDropdown = function(e, el) { 
        e.stopPropagation(); 
        window.closeAllSelects(el); 
        el.nextElementSibling.style.display = el.nextElementSibling.style.display === 'block' ? 'none' : 'block'; 
        el.classList.toggle('active'); 
    };
    
    window.closeAllSelects = function(except) { 
        document.querySelectorAll('.select-items').forEach(i => { if(i !== except?.nextElementSibling) i.style.display = 'none'; }); 
        document.querySelectorAll('.select-selected').forEach(e => { if(e !== except) e.classList.remove('active'); }); 
    };

    // Click ra ngoài để đóng dropdown
    document.addEventListener('click', function() {
        window.closeAllSelects();
    });

    // Chọn giá trị từ dropdown
    window.selectEditViolation = function(id, name, itemEl) {
        document.getElementById('edit_violation_id').value = id;
        document.getElementById('txtEditSelectedViolation').innerText = name;
        itemEl.parentElement.style.display = 'none';
        itemEl.parentElement.previousElementSibling.classList.remove('active');
    };

    function deleteRecord(id, btn) {
        WinUI.confirm((<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xác nhận xóa' : 'Xác nhận xóa') ?>), (<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn có chắc chắn muốn xóa bản ghi này không?' : 'Bạn có chắc chắn muốn xóa bản ghi này không?') ?>), function() {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('delete_id', id);

            fetch('violation_history.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    // Tìm và xóa hàng (tr) chứa nút bấm
                    const row = btn ? btn.closest('tr') : null;
                    if(row) {
                        row.style.transition = 'all 0.3s ease';
                        row.style.opacity = '0';
                        row.style.background = 'rgba(239, 68, 68, 0.1)';
                        setTimeout(() => row.remove(), 300);
                    } else {
                        location.reload(); // Chỉ reload nếu không xác định được hàng
                    }
                    if(typeof Toastify !== 'undefined') Toastify({text: (<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '✅ Đã xóa bản ghi!' : '✅ Đã xóa bản ghi!') ?>), style: {background: "#10b981"}}).showToast();
                } else {
                    alert((<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi: ' : 'Lỗi: ') ?>) + data.msg);
                }
            }).catch(err => alert(<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi kết nối máy chủ!' : 'Lỗi kết nối máy chủ!') ?>));
        });
    }

    // --- LOGIC SỬA (ADMIN) ---
    function openEditModal(id, v_id, week, timeStr) {
        // Gán ID bản ghi
        document.getElementById('edit_id').value = id;
        
        // Cập nhật giá trị và text cho custom dropdown
        document.getElementById('edit_violation_id').value = v_id;
        const items = document.querySelectorAll('#editModal .select-items div');
        let foundName = (<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '-- Chọn lỗi vi phạm --' : '-- Select violation error --') ?>);
        items.forEach(item => {
            if (item.getAttribute('data-vid') == v_id) {
                foundName = item.innerText.trim();
            }
        });
        document.getElementById('txtEditSelectedViolation').innerText = foundName;

        // Gán tuần và thời gian
        document.getElementById('edit_week').value = week;
        document.getElementById('edit_time').value = timeStr;
        
        // Hiển thị modal
        document.getElementById('editModal').style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
        window.closeAllSelects(); // Đóng dropdown nếu nó đang mở
    }

    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate xem đã chọn lỗi từ custom dropdown chưa
        if(!document.getElementById('edit_violation_id').value) {
            alert(<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vui lòng chọn loại vi phạm!' : 'Vui lòng chọn loại vi phạm!') ?>);
            return;
        }

        const formData = new FormData(this);

        fetch('violation_history.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                location.reload();
            } else {
                alert((<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi: ' : 'Lỗi: ') ?>) + data.msg);
            }
        }).catch(err => alert(<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi kết nối máy chủ!' : 'Lỗi kết nối máy chủ!') ?>));
    });

    // --- REALTIME SSE UPDATES ---
    window.currentUserRole = '<?= $role ?>';
    window.pageInit = function() {
        if (window.SSEManager) {
            window.SSEManager.on('violation_new', (data) => {
                const tbody = document.getElementById('gateLogsTableBody');
                if (!tbody) return;

                // Remove empty data placeholder if present
                const emptyRow = document.getElementById('emptyGateLog');
                if (emptyRow) emptyRow.remove();

                if (document.getElementById('log_row_' + data.id)) return;

                // Format dates
                const dateObj = new Date(data.date_created || Date.now());
                const hours = String(dateObj.getHours()).padStart(2, '0');
                const minutes = String(dateObj.getMinutes()).padStart(2, '0');
                const day = String(dateObj.getDate()).padStart(2, '0');
                const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                const year = dateObj.getFullYear();

                const timeHtml = `<div class="time-badge" style="border-color:var(--primary-color);">${hours}:${minutes}</div>
                                  <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">${day}/${month}/${year}</div>`;

                const lang = window.currentLangCode || 'vi';
                const name = (lang === 'en' && data.display_name_en) ? data.display_name_en : data.display_name;

                const reporterName = data.reporter_fullname ? data.reporter_fullname : (data.reporter || 'Hệ thống');
                
                const tr = document.createElement('tr');
                tr.id = 'log_row_' + data.id;
                
                let noteHtml = '';
                if (data.note) {
                    noteHtml = `<br><small style="color:var(--text-muted); font-style:italic;">"${data.note}"</small>`;
                }

                let actionHtml = '';
                if (window.currentUserRole === 'ADMIN') {
                    // Create formatted date string for HTML datetime-local input (YYYY-MM-DDThh:mm)
                    const isoStr = dateObj.getFullYear() + '-' + 
                                   String(dateObj.getMonth() + 1).padStart(2, '0') + '-' + 
                                   String(dateObj.getDate()).padStart(2, '0') + 'T' + 
                                   hours + ':' + minutes;
                                   
                    actionHtml = `
                    <td>
                        <button class="btn-action btn-edit" title="${<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Sửa' : 'Edit') ?>}" aria-label="${<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Sửa' : 'Edit') ?>}"
                            onclick="openEditModal(${data.id}, ${data.violation_type_id || 0}, ${data.week_number || 1}, '${isoStr}')">
                            <i class="fas fa-edit" aria-hidden="true"></i>
                        </button>
                        <button class="btn-action btn-delete" title="${<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xóa' : 'Delete') ?>}" aria-label="${<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xóa' : 'Delete') ?>}" onclick="deleteRecord(${data.id})">
                            <i class="fas fa-trash" aria-hidden="true"></i>
                        </button>
                    </td>`;
                }

                tr.innerHTML = `
                    <td>
                        <div class="time-badge">${hours}:${minutes}</div>
                        <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">${day}/${month}/${year}</div>
                    </td>
                    <td>
                        <span style="background:var(--bg-hover); padding:2px 6px; border-radius:4px; font-size:12px; font-weight:bold;">
                            ${<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'Week') ?>} ${data.week_number || 1}
                        </span>
                    </td>
                    <td>
                        <div style="font-weight:bold; color:var(--primary-color);">${data.student_name || ''}</div>
                        <div style="font-size:12px; color:var(--text-muted);">${data.class_name || ''} (${data.student_code || ''})</div>
                    </td>
                    <td>
                        <div>${name || ''}</div>
                        ${noteHtml}
                    </td>
                    <td><span class="badge-points">-${data.recorded_points || '0'}</span></td>
                    <td>
                        <div class="reporter-info">
                            <span class="reporter-name">${reporterName}</span>
                            <span class="reporter-user">@${data.reporter || ''}</span>
                        </div>
                    </td>
                    <td>
                        <span style="color:var(--success); font-size:12px; font-weight:600;">${<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hiệu lực' : 'Valid') ?>}</span>
                    </td>
                    ${actionHtml}
                `;

                tbody.insertBefore(tr, tbody.firstChild);
            });

            window.SSEManager.on('violation_deleted', (data) => {
                const row = document.getElementById('log_row_' + data.id);
                if (row) {
                    row.classList.add('status-deleted');
                    const statusCell = row.cells[6];
                    if (statusCell) {
                        statusCell.innerHTML = `<span class="tag-deleted">${<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Không hiệu lực' : 'Invalid') ?>}</span>`;
                    }
                }
            });

            window.SSEManager.on('violation_class_new', (data) => {
                const tbody = document.getElementById('classLogsTableBody');
                if (!tbody) return;

                const emptyRow = document.getElementById('emptyClassLog');
                if (emptyRow) emptyRow.remove();

                const dateObj = new Date(data.submitted_at);
                const hours = String(dateObj.getHours()).padStart(2, '0');
                const minutes = String(dateObj.getMinutes()).padStart(2, '0');
                const day = String(dateObj.getDate()).padStart(2, '0');
                const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                const year = dateObj.getFullYear();
                
                const reporterName = data.reporter_fullname ? data.reporter_fullname : (data.reporter || 'Hệ thống');

                const tr = document.createElement('tr');
                tr.id = 'log_row_' + data.id;
                
                tr.innerHTML = `
                    <td>
                        <div class="time-badge" style="border-color:var(--primary-color);">${hours}:${minutes}</div>
                        <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">${day}/${month}/${year}</div>
                    </td>
                    <td>
                        <span style="font-weight:bold; font-size:15px; color:var(--text-main);">
                            ${data.class_name || ''}
                        </span>
                    </td>
                    <td><span style="background:var(--bg-hover); padding:2px 6px; border-radius:4px; font-size:12px;">${<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'Week') ?>} ${data.week_number || 1}</span></td>
                    <td>${data.display_name || ''}</td>
                    <td><span class="badge-points">-${data.recorded_points || '0'}</span></td>
                    <td>
                        <div class="reporter-info">
                            <span class="reporter-name">${reporterName}</span>
                            <span class="reporter-user">@${data.reporter || ''}</span>
                        </div>
                    </td>
                    <td>
                        <span style="color:var(--success); font-size:12px; font-weight:600;">${<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hiệu lực' : 'Valid') ?>}</span>
                    </td>
                `;
                tbody.insertBefore(tr, tbody.firstChild);
            });

            window.SSEManager.on('violation_class_updated', (data) => {
                const row = document.getElementById('log_row_' + data.id);
                if (row) {
                    const pointsCell = row.cells[4];
                    if (pointsCell) {
                        pointsCell.innerHTML = `<span class="badge-points">-${data.recorded_points}</span>`;
                    }
                    row.classList.remove('status-deleted');
                    const statusCell = row.cells[6];
                    if (statusCell) {
                        statusCell.innerHTML = `<span style="color:var(--success); font-size:12px; font-weight:600;">${<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hiệu lực' : 'Valid') ?>}</span>`;
                    }
                }
            });
        }
    };
</script>

<?php include 'includes/footer.php'; ?>