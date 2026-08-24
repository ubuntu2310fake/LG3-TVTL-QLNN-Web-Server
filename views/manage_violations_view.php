<?php include 'includes/header.php'; ?>



<style>
    .win-card h3 { font-size: 16px; font-weight: 700; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
    .config-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; }
    .code-editor { font-family: 'Consolas', 'Monaco', monospace; font-size: 13px; line-height: 1.5; background: var(--bg-input); color: var(--text-main); border: 1px solid var(--border-color); border-radius: 4px; padding: 10px; width: 100%; height: 120px; resize: vertical; }
    .badge-scope { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
    .badge-gate { background: rgba(2, 132, 199, 0.1); color: #0284c7; }
    .badge-class { background: rgba(147, 51, 234, 0.1); color: #9333ea; }
    .table-actions .btn-icon { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; margin-left: 5px; border-radius: 4px; border: none; cursor: pointer; }
    .btn-edit { background: rgba(67, 56, 202, 0.1); color: #4338ca; }
    .btn-delete { background: rgba(220, 38, 38, 0.1); color: var(--danger-color); }
    
    .ticker-box { background: var(--bg-hover); border: 1px dashed var(--primary-color); padding: 12px; border-radius: 6px; margin-bottom: 15px; transition: all 0.3s ease; }
    .ticker-title { margin: 0 0 8px; font-size: 13px; color: var(--primary-color); display: flex; align-items: center; gap: 5px; font-weight: bold; border-bottom: 1px dashed var(--border-color); padding-bottom: 5px; }
    .ticker-input-wrap { display: flex; gap: 5px; }
    .ticker-input { flex: 1; background: var(--bg-input); color: var(--text-main); border: 1px solid var(--border-color); border-radius: 4px; padding: 8px; font-size: 13px; transition: all 0.2s; }
    .ticker-input:focus { border-color: var(--primary-color); outline: none; }
    .btn-dot { background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-main); padding: 0 12px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 16px; line-height: 1; transition: all 0.2s; }
    .main-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
    .timeline-group { margin-bottom: 12px; }
    .timeline-label { font-size: 13px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px; }

    /* Custom Dropdown Styling (Ported from gate_check_view.php) */
    .custom-select-container { position: relative; width: 100%; margin-bottom: 15px; }
    .select-selected {
        background-color: var(--bg-input); border: 1px solid var(--border-color); border-radius: 8px;
        padding: 0 15px; display: flex; align-items: center; justify-content: space-between;
        font-size: 14px; height: 38px; box-sizing: border-box; color: var(--text-main);
        transition: 0.2s; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .select-selected:active { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(0,95,186,0.15); }
    .select-arrow { width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid var(--text-muted); transition: 0.2s; flex-shrink: 0; }
    .select-selected.active .select-arrow { transform: rotate(180deg); }
    .select-items {
        position: absolute; top: 110%; left: 0; right: 0; z-index: 1000;
        background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px;
        max-height: 300px; overflow-y: auto; display: none;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1); animation: fadeInDropdown 0.2s ease;
    }
    .select-items div { padding: 10px 15px; cursor: pointer; border-bottom: 1px solid var(--border-color); font-size: 14px; color: var(--text-main); }
    .select-items div:last-child { border-bottom: none; }
    .select-items div:hover { background: var(--bg-hover); color: var(--primary-color); font-weight: 500; }
    @keyframes fadeInDropdown { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

    /* WinUI 3 Fluent Design Date picker inputs */
    input.win-date-picker {
        background-color: #ffffff !important;
        border: 1px solid #e5e5e5 !important;
        border-bottom: 2px solid #cccccc !important;
        border-radius: 4px !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
        font-family: 'Be Vietnam Pro', sans-serif !important;
        font-size: 14px !important;
        height: 38px !important;
        box-sizing: border-box !important;
        cursor: pointer !important;
        padding: 0 12px !important;
        color: #111111 !important;
        width: 100% !important;
        min-width: 0 !important;
        display: block !important;
        transition: all 0.15s ease !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23666666' class='bi bi-calendar' viewBox='0 0 16 16'%3E%3Cpath d='M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 12px center !important;
        padding-right: 36px !important;
    }
    input.win-date-picker:hover {
        border-color: #bbbbbb !important;
        background-color: #f9f9f9 !important;
    }
    input.win-date-picker:focus {
        outline: none !important;
        border: 2px solid #0067b8 !important;
        border-bottom-width: 2px !important;
        box-shadow: 0 0 0 3px rgba(0, 103, 184, 0.15) !important;
    }
    
    [data-theme="dark"] input.win-date-picker {
        background-color: #202020 !important;
        border: 1px solid #333333 !important;
        border-bottom: 2px solid #444444 !important;
        color: #ffffff !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23cccccc' class='bi bi-calendar' viewBox='0 0 16 16'%3E%3Cpath d='M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z'/%3E%3C/svg%3E") !important;
    }
    [data-theme="dark"] input.win-date-picker:hover {
        background-color: #2d2d2d !important;
        border-color: #555555 !important;
    }

    /* WinUI 3 Fluent calendar view dropdown flyout */
    .winui-calendar-flyout {
        position: absolute;
        z-index: 10000;
        background: var(--bg-card, #ffffff);
        border: 1px solid var(--border-color, #e5e5e5);
        border-radius: 8px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.14);
        padding: 12px;
        width: 280px;
        font-family: 'Be Vietnam Pro', sans-serif;
        opacity: 0;
        transform: translateY(-8px);
        pointer-events: none;
        transition: opacity 0.2s cubic-bezier(0.1, 0.9, 0.2, 1), transform 0.2s cubic-bezier(0.1, 0.9, 0.2, 1);
        user-select: none;
    }
    .winui-calendar-flyout.active {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }
    .winui-calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    .winui-calendar-month-year {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-main);
    }
    .winui-calendar-nav-btn {
        background: transparent;
        border: none;
        cursor: pointer;
        font-size: 13px;
        color: var(--text-main);
        padding: 4px 8px;
        border-radius: 4px;
        transition: background 150ms;
    }
    .winui-calendar-nav-btn:hover {
        background: var(--bg-hover, #f3f3f3);
    }
    .winui-calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 2px;
        margin-bottom: 10px;
    }
    .winui-calendar-day-header {
        font-size: 11px;
        font-weight: 600;
        color: var(--text-muted, #808080);
        text-align: center;
        padding: 4px 0;
    }
    .winui-calendar-day {
        font-size: 12px;
        text-align: center;
        padding: 6px 0;
        cursor: pointer;
        border-radius: 4px;
        color: var(--text-main);
        transition: background 150ms;
    }
    .winui-calendar-day:hover {
        background: var(--bg-hover, #f3f3f3);
    }
    .winui-calendar-day.other-month {
        color: var(--text-muted, #cccccc);
        opacity: 0.5;
    }
    .winui-calendar-day.today {
        border: 1px solid var(--primary-color, #0067b8);
    }
    .winui-calendar-day.selected {
        background: var(--primary-color, #0067b8) !important;
        color: #ffffff !important;
    }
    .winui-calendar-footer {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        border-top: 1px solid var(--border-color, #e5e5e5);
        padding-top: 8px;
        margin-top: 4px;
    }
    .winui-calendar-footer-btn {
        background: transparent;
        border: none;
        cursor: pointer;
        font-size: 13px;
        color: var(--primary-color, #0067b8);
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 4px;
    }
    .winui-calendar-footer-btn:hover {
        background: var(--bg-hover, #f3f3f3);
    }

    @media (max-width: 768px) {
        .main-grid { grid-template-columns: 1fr; }
        .modal-content { width: 95% !important; margin: 10px auto; }
        .rank-table thead { display: none; }
        .rank-table tr { display: block; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 15px; padding: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .rank-table td { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px dashed var(--border-color); text-align: right; font-size: 13px; }
        .rank-table td:last-child { border-bottom: none; padding-bottom: 0; }
        .rank-table td::before { content: attr(data-label); font-weight: bold; color: var(--text-muted); text-align: left; margin-right: 15px; }
        .table-actions .btn-icon { width: 36px; height: 36px; font-size: 14px; }
    }
</style>

<div class="dashboard-container" style="max-width: 1200px; margin: 0 auto; padding-bottom: 50px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0; color: var(--text-main); display: flex; align-items: center; gap: 10px; font-size: 18px;">
            <i aria-hidden="true" class="fas fa-gavel" style="color: var(--danger-color);"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'QUẢN LÝ VI PHẠM' : 'MANAGE VIOLATIONS') ?>
        </h2>
    </div>

    <div class="main-grid">
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div class="win-card" style="border-top: 4px solid var(--primary-color);">
                <h3 style="color: var(--primary-color);"><i aria-hidden="true" class="fas fa-calendar-alt"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cấu hình Năm Học' : 'School Year Config') ?></h3>
                
                <form class="ajax-form" id="schoolYearForm" style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px dashed var(--border-color);">
                    <div class="timeline-group">
                        <label for="start_date_picker" class="timeline-label" style="font-weight: bold; color: var(--primary-color);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Năm học hiện tại:' : 'Current School Year:') ?></label>
                        <div class="custom-select-container" style="margin-top: 5px; margin-bottom: 0;">
                            <div role="button" tabindex="0" class="select-selected" onclick="window.toggleDropdown(event, this)">
                                <span id="txtSelectedSchoolYear" style="font-weight:600;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Năm học' : 'School Year') ?> <?= htmlspecialchars($current_school_year) ?></span>
                                <div class="select-arrow"></div>
                            </div>
                            <div class="select-items" style="top: 105%;">
                                <div role="button" tabindex="0" onclick="window.selectSchoolYearItem('2025-2026', (<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Năm học'" : "'School Year'") ?>)+' 2025-2026', this)"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Năm học 2025-2026' : 'School Year 2025-2026') ?></div>
                                <div role="button" tabindex="0" onclick="window.selectSchoolYearItem('2026-2027', (<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Năm học'" : "'School Year'") ?>)+' 2026-2027', this)"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Năm học 2026-2027' : 'School Year 2026-2027') ?></div>
                                <div role="button" tabindex="0" onclick="window.selectSchoolYearItem('2027-2028', (<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Năm học'" : "'School Year'") ?>)+' 2027-2028', this)"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Năm học 2027-2028' : 'School Year 2027-2028') ?></div>
                            </div>
                        </div>
                    </div>
                </form>

                <form class="ajax-form">
                    <input type="hidden" name="action" value="update_timeline">
                    <div class="timeline-group"><label class="timeline-label"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Khai giảng Học kỳ 1:' : 'Start of Semester 1:') ?></label>
<input type="text" id="start_date_picker" name="start_date" value="<?= htmlspecialchars($current_start_date) ?>" class="win-input win-date-picker" readonly required></div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 12px;">
                        <div class="timeline-group" style="margin-bottom: 0;"><label for="hk1_picker" class="timeline-label"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Kết thúc HK1:' : 'End of Semester 1:') ?></label>
<input type="text" id="hk1_picker" name="end_hk1_date" value="<?= htmlspecialchars($end_hk1_date) ?>" class="win-input win-date-picker" readonly></div>
                        <div class="timeline-group" style="margin-bottom: 0;"><label for="year_picker" class="timeline-label"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Kết thúc Năm học:' : 'End of School Year:') ?></label>
<input type="text" id="year_picker" name="end_year_date" value="<?= htmlspecialchars($end_year_date) ?>" class="win-input win-date-picker" readonly></div>
                    </div>
                    <div class="timeline-group">
                        <label for="excluded_dates_hidden" class="timeline-label"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ngày nghỉ (Không tính tuần):' : 'Days Off (Excluded from week count):') ?> <i aria-hidden="true" class="fas fa-info-circle" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Các ngày này sẽ bị trừ ra khi tính tuần học. VD: Nghỉ Tết, Nghỉ lễ...' : 'These days will be excluded when calculating the school week. E.g.: Holidays, Tet...') ?>" style="cursor: help;"></i></label>
<input type="hidden" id="excluded_dates_hidden" name="excluded_dates" value="<?= htmlspecialchars($excluded_dates_string) ?>">
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="add_excluded_date_input" class="win-input win-date-picker" readonly style="flex: 1;">
                            <button aria-label="Action button" type="button" class="win-btn" onclick="window.addExcludedDate()" style="padding: 0 12px; height: 38px; display: inline-flex; align-items: center; justify-content: center;"><i aria-hidden="true" class="fas fa-plus"></i></button>
                        </div>
                        <div id="excluded_tags_container" style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px;"></div>
                    </div>
                    <div style="background: var(--bg-hover); padding: 10px; border-radius: 6px; font-size: 13px;">
                        <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hiện tại:' : 'Currently:') ?> <strong style="color: var(--primary-color); font-size: 15px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuần' : 'Week') ?> <?= $current_week ?></strong>
                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '(Tính theo tuần đốc lịch thực tế)' : '(Calculated based on real weeks)') ?></div>
                    </div>
                    <button type="submit" class="win-btn" style="width: 100%; margin-top: 10px; justify-content: center;"><i aria-hidden="true" class="fas fa-sync-alt"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cập nhật Thời gian' : 'Update Time') ?></button>
                </form>
            </div>
        </div>

        <div class="win-card" style="border-top: 4px solid #8b5cf6;">
            <h3 style="color: #8b5cf6;"><i aria-hidden="true" class="fas fa-cogs"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cấu Hình & Ticker' : 'Config & Ticker') ?></h3>
            <form class="ajax-form">
                <input type="hidden" name="action" value="save_rules">
                <div class="ticker-box">
                    <h4 class="ticker-title"><i aria-hidden="true" class="fas fa-bullhorn"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thông báo chạy Header' : 'Header Ticker') ?></h4>
                    <div class="ticker-group" style="margin-bottom: 0;">
                        <label for="tk_sch" style="font-size: 12px; font-weight: bold; color: var(--text-muted); display:block; margin-bottom:4px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ticker Trường (Riêng):' : 'School Ticker (Private):') ?></label>
                        <div class="ticker-input-wrap">
                            <input type="text" id="tk_sch" name="ticker_school" class="ticker-input" value="<?= htmlspecialchars($ticker_school) ?>" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tin riêng của trường...' : 'Private school news...') ?>">
                            <button type="button" class="btn-dot" onclick="window.addDot('tk_sch')" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chèn dấu chấm giữa' : 'Insert Middle Dot') ?>">·</button>
                        </div>
                    </div>
                </div>

                <p style="font-size: 11.5px; color: var(--text-muted); margin-top: 15px; margin-bottom: 5px; font-style: italic; line-height: 1.4;">
                    <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '* Điểm gốc và hệ số chia tương ứng với tuần chuẩn 6 ngày học, hệ thống tự động tính toán chia tỷ lệ theo số ngày đi học thực tế khi có kỳ nghỉ lễ/ngày nghỉ.' : '* Base score and division factor correspond to a standard 6-day week. The system automatically calculates proportionally based on actual school days.') ?>
                </p>
                <div class="config-grid" style="margin-bottom: 15px;">
                    <div><label style="font-size: 12px; font-weight: bold;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm Gốc' : 'Base Score') ?></label><input type="number" step="0.5" name="max_base" value="<?= $rules['max_base'] ?>" class="win-input" required></div>
                    <div><label style="font-size: 12px; font-weight: bold;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hệ số chia' : 'Division Factor') ?> <input type="number" step="0.1" name="divisor" value="<?= $rules['divisor'] ?>" class="win-input" required></label></div>
                    <div><label style="font-size: 12px; font-weight: bold;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '% Học Tập' : '% Academic') ?> <input type="number" step="0.1" max="1" min="0" name="weight_aca" value="<?= $rules['weight_aca'] ?>" class="win-input" required></label></div>
                    <div><label style="font-size: 12px; font-weight: bold;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '% Nền Nếp' : '% Discipline') ?> <input type="number" step="0.1" max="1" min="0" name="weight_con" value="<?= $rules['weight_con'] ?>" class="win-input" required></label></div>
                </div>

                <!-- Cấu hình cột chấm JSON đã được phẳng hóa vào database -->
                <button type="submit" class="win-btn" style="width: 100%; background: #8b5cf6; justify-content: center;"><i aria-hidden="true" class="fas fa-save"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lưu Cấu Hình' : 'Save Config') ?></button>
            </form>
        </div>
    </div>

    <div class="win-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="color:var(--primary-color);"><i aria-hidden="true" class="fas fa-list-ul"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Danh Mục Lỗi' : 'Violation Categories') ?></h3>
            <button onclick="window.openModal('add')" class="win-btn" style="padding: 6px 12px; font-size: 13px;"><i aria-hidden="true" class="fas fa-plus"></i> <span class="hidden-xs"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thêm Lỗi' : 'Add Violation') ?></span></button>
        </div>

        <div class="table-responsive">
            <table class="rank-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nội dung lỗi' : 'Violation Content') ?></th><th width="80" class="text-center"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mã' : 'Code') ?></th><th width="80" class="text-center"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm' : 'Score') ?></th><th width="100" class="text-center"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Phạm vi' : 'Scope') ?></th><th width="110" class="text-center"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm tối đa (CLASS)' : 'Max Score (CLASS)') ?></th><th width="80" class="text-right"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hành động' : 'Action') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $lang = $_SESSION['lang'] ?? 'vi';
                    foreach ($violations as $v): 
                        $displayContent = ($lang === 'en' && !empty($v['content_en'])) ? $v['content_en'] : $v['content'];
                    ?>
                    <tr>
                        <td data-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nội dung' : 'Content') ?>"><?= htmlspecialchars($displayContent) ?></td>
                        <td data-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mã viết tắt' : 'Short Code') ?>" class="text-center"><code style="background: var(--bg-hover); padding:2px 4px; border-radius:3px; color:#c026d3;"><?= htmlspecialchars($v['short_code']) ?></code></td>
                        <td data-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm trừ' : 'Deduction Points') ?>" class="text-center" style="font-weight:bold; color:var(--danger-color);">-<?= $v['points'] ?></td>
                        <td data-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Phạm vi' : 'Scope') ?>" class="text-center"><span class="badge-scope <?= $v['scope'] === 'GATE' ? 'badge-gate' : 'badge-class' ?>"><?= $v['scope'] === 'GATE' ? (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cổng' : 'Gate') : (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp' : 'Class') ?></span></td>
                        <td data-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm tối đa' : 'Max Score') ?>" class="text-center"><?= $v['scope'] === 'CLASS' ? ($v['max_penalty_points'] !== null ? $v['max_penalty_points'] : (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Không giới hạn' : 'Unlimited')) : '-' ?></td>
                        <td data-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thao tác' : 'Operation') ?>" class="text-right table-actions">
                            <button aria-label="Edit" class="btn-icon btn-edit" onclick='window.openModal("edit", <?= json_encode($v) ?>)'><i aria-hidden="true" class="fas fa-edit"></i></button>
                            <button aria-label="Delete" class="btn-icon btn-delete" onclick="window.deleteViolation(<?= $v['id'] ?>)"><i aria-hidden="true" class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="violationModal" class="modal-overlay" style="display: none; position: fixed; inset:0; background:rgba(0,0,0,0.8); z-index:99999; align-items:center; justify-content:center;" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-content win-card" style="max-width: 450px; width: 90%; margin:0;">
        <h3 id="modalTitle" style="margin-top: 0; margin-bottom: 15px; color:var(--primary-color);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thêm Lỗi Vi Phạm' : 'Add Violation') ?></h3>
        <form class="ajax-form" id="violationForm">
            <input type="hidden" name="action" id="formAction" value="add"><input type="hidden" name="id" id="violationId">
            <div class="form-group" style="margin-bottom:15px;"><label for="vContent" style="font-size:13px; color:var(--text-muted); font-weight:bold;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nội dung lỗi:' : 'Violation Content:') ?></label>
<input type="text" name="content" id="vContent" class="win-input" required></div>
            <div class="form-group" style="margin-bottom:15px;"><label for="vContentEn" style="font-size:13px; color:var(--text-muted); font-weight:bold;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nội dung lỗi (Tiếng Anh):' : 'Violation Content (English):') ?></label>
<input type="text" name="content_en" id="vContentEn" class="win-input"></div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom:15px;">
                <div class="form-group"><label for="vCode" style="font-size:13px; color:var(--text-muted); font-weight:bold;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mã (Code):' : 'Code:') ?></label>
<input type="text" name="short_code" id="vCode" class="win-input" required></div>
                <div class="form-group"><label for="vPoints" style="font-size:13px; color:var(--text-muted); font-weight:bold;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm trừ:' : 'Deduction Points:') ?></label>
<input type="number" step="0.1" name="points" id="vPoints" class="win-input" required></div>
            </div>
            <div class="form-group" style="margin-bottom:15px;"><label for="vScope" style="font-size:13px; color:var(--text-muted); font-weight:bold;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Phạm vi áp dụng:' : 'Applicable Scope:') ?></label>
<select name="scope" id="vScope" class="win-input" style="padding:10px;"><option value="GATE"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cổng trường' : 'School Gate') ?></option><option value="CLASS"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trong lớp' : 'In Class') ?></option></select></div>
            <div class="form-group" id="maxPenaltyGroup" style="margin-bottom:15px; display: none;"><label for="vMaxPenalty" style="font-size:13px; color:var(--text-muted); font-weight:bold;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm tối đa (CLASS):' : 'Max Score (CLASS):') ?></label>
<input type="number" step="0.1" name="max_penalty_points" id="vMaxPenalty" class="win-input" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bỏ trống nếu không giới hạn' : 'Leave empty if unlimited') ?>"></div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="win-btn win-btn-secondary" onclick="window.closeModal()"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hủy' : 'Cancel') ?></button>
                <button type="submit" class="win-btn"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lưu Lại' : 'Save') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
    window.pageDestroy = function() {
        if(window.vModal) window.vModal.onclick = null; window.vModal = null;
        const flyout = document.querySelector('.winui-calendar-flyout');
        if (flyout) flyout.remove();
    };
    window.changeSchoolYear = function(year) {
        const fd = new FormData();
        fd.append('action', 'change_school_year');
        fd.append('school_year', year);
        fetch('manage_violations.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if(d.status === 'success') {
                if(typeof Toastify !== 'undefined') Toastify({ text: "✅ " + d.msg, style: { background: "#10b981" } }).showToast();
                setTimeout(() => {
                    if(window.loadPage) window.loadPage(window.location.href, false, {force: true});
                    else location.reload();
                }, 500);
            } else {
                alert("❌ " + d.msg);
            }
        }).catch(err => {
            alert(<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'❌ Lỗi kết nối!'" : "'❌ Connection error!'") ?>);
        });
    };
    window.renderExcludedTags = function() {
        const container = document.getElementById('excluded_tags_container');
        if (!container) return;
        container.innerHTML = '';
        window.exDates.sort();
        window.exDates.forEach((d, idx) => {
            const tag = document.createElement('div');
            tag.style.cssText = 'display: inline-flex; align-items: center; background: var(--bg-hover); border: 1px solid var(--border-color); padding: 4px 10px; border-radius: 4px; font-size: 12px; color: var(--text-main); font-weight: 500;';
            tag.innerHTML = `
                <span>${d}</span>
                <span role="button" tabindex="0" onclick="window.removeExcludedDate(${idx})" style="margin-left: 8px; cursor: pointer; color: var(--danger-color); font-weight: bold; font-size: 14px; line-height: 1;" title=<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Xóa'" : "'Delete'") ?>>&times;</span>
            `;
            container.appendChild(tag);
        });
        document.getElementById('excluded_dates_hidden').value = window.exDates.join(', ');
    };
    window.addExcludedDate = function() {
        const input = document.getElementById('add_excluded_date_input');
        if (!input || !input.value) return;
        const dateVal = input.value;
        if (!window.exDates.includes(dateVal)) {
            window.exDates.push(dateVal);
            window.renderExcludedTags();
        }
        input.value = '';
    };
    window.removeExcludedDate = function(idx) {
        window.exDates.splice(idx, 1);
        window.renderExcludedTags();
    };
    window.toggleDropdown = function(e, el) { 
        e.stopPropagation(); 
        window.closeAllSelects(el); 
        const items = el.nextElementSibling;
        items.style.display = items.style.display === 'block' ? 'none' : 'block'; 
        el.classList.toggle('active'); 
    };
    window.closeAllSelects = function(except) { 
        document.querySelectorAll('.select-items').forEach(i => { 
            if(i !== except?.nextElementSibling) i.style.display = 'none'; 
        }); 
        document.querySelectorAll('.select-selected').forEach(e => { 
            if(e !== except) e.classList.remove('active'); 
        }); 
    };
    window.selectSchoolYearItem = function(year, label, itemEl) {
        document.getElementById('txtSelectedSchoolYear').innerText = label;
        itemEl.parentElement.style.display = 'none';
        window.changeSchoolYear(year);
    };

    /* WinUI 3 Custom Vanilla DatePicker Engine */
    (function() {
        let activeInput = null;
        let currentYear = new Date().getFullYear();
        let currentMonth = new Date().getMonth();
        let flyout = null;

        function createFlyout() {
            if (flyout) return;
            flyout = document.createElement('div');
            flyout.className = 'winui-calendar-flyout';
            document.body.appendChild(flyout);

            document.addEventListener('click', function(e) {
                if (activeInput && !activeInput.contains(e.target) && !flyout.contains(e.target) && document.body.contains(e.target)) {
                    hideFlyout();
                }
            });
        }

        function showFlyout(input) {
            activeInput = input;
            createFlyout();

            const rect = input.getBoundingClientRect();
            flyout.style.left = (rect.left + window.scrollX) + 'px';
            flyout.style.top = (rect.bottom + window.scrollY + 4) + 'px';

            let valDate = new Date();
            if (input.value) {
                valDate = new Date(input.value);
                if (isNaN(valDate.getTime())) valDate = new Date();
            }
            currentYear = valDate.getFullYear();
            currentMonth = valDate.getMonth();

            renderCalendar();
            
            requestAnimationFrame(() => {
                flyout.classList.add('active');
            });
        }

        function hideFlyout() {
            if (flyout) flyout.classList.remove('active');
            activeInput = null;
        }

        function renderCalendar() {
            const monthNames = [<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'" : "'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'") ?>];
            
            let html = `
                <div class="winui-calendar-header">
                    <button aria-label="Action button" class="winui-calendar-nav-btn" type="button" id="winui-cal-prev"><i aria-hidden="true" class="fas fa-chevron-left"></i></button>
                    <div class="winui-calendar-month-year">${monthNames[currentMonth]} ${currentYear}</div>
                    <button aria-label="Action button" class="winui-calendar-nav-btn" type="button" id="winui-cal-next"><i aria-hidden="true" class="fas fa-chevron-right"></i></button>
                </div>
                <div class="winui-calendar-grid">
                    <div class="winui-calendar-day-header">T2</div>
                    <div class="winui-calendar-day-header">T3</div>
                    <div class="winui-calendar-day-header">T4</div>
                    <div class="winui-calendar-day-header">T5</div>
                    <div class="winui-calendar-day-header">T6</div>
                    <div class="winui-calendar-day-header">T7</div>
                    <div class="winui-calendar-day-header">CN</div>
            `;

            let firstDay = new Date(currentYear, currentMonth, 1).getDay();
            let startOffset = firstDay === 0 ? 6 : firstDay - 1;

            let prevMonthEnd = new Date(currentYear, currentMonth, 0).getDate();
            let currentMonthEnd = new Date(currentYear, currentMonth + 1, 0).getDate();

            for (let i = startOffset - 1; i >= 0; i--) {
                html += `<div class="winui-calendar-day other-month" data-date="${currentYear}-${currentMonth}-${prevMonthEnd - i}">${prevMonthEnd - i}</div>`;
            }

            const today = new Date();
            const inputValStr = activeInput ? activeInput.value : '';
            const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

            for (let day = 1; day <= currentMonthEnd; day++) {
                const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                let classes = ['winui-calendar-day'];
                if (dateStr === todayStr) classes.push('today');
                if (dateStr === inputValStr) classes.push('selected');
                html += `<div class="${classes.join(' ')}" data-date="${dateStr}">${day}</div>`;
            }

            let totalCells = startOffset + currentMonthEnd;
            let nextDays = totalCells > 35 ? 42 - totalCells : 35 - totalCells;
            for (let i = 1; i <= nextDays; i++) {
                html += `<div class="winui-calendar-day other-month" data-date="${currentYear}-${currentMonth + 2}-${i}">${i}</div>`;
            }

            html += `
                </div>
                <div class="winui-calendar-footer">
                    <button class="winui-calendar-footer-btn" type="button" id="winui-cal-clear">Clear</button>
                    <button class="winui-calendar-footer-btn" type="button" id="winui-cal-today">Today</button>
                </div>
            `;

            flyout.innerHTML = html;

            document.getElementById('winui-cal-prev').onclick = function() {
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
                renderCalendar();
            };

            document.getElementById('winui-cal-next').onclick = function() {
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
                renderCalendar();
            };

            document.getElementById('winui-cal-clear').onclick = function() {
                if (activeInput) {
                    activeInput.value = '';
                    activeInput.dispatchEvent(new Event('change'));
                }
                hideFlyout();
            };

            document.getElementById('winui-cal-today').onclick = function() {
                if (activeInput) {
                    const now = new Date();
                    activeInput.value = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
                    activeInput.dispatchEvent(new Event('change'));
                }
                hideFlyout();
            };

            flyout.querySelectorAll('.winui-calendar-day').forEach(el => {
                el.onclick = function() {
                    const dateStr = this.getAttribute('data-date');
                    const parts = dateStr.split('-');
                    let y = parseInt(parts[0]);
                    let m = parseInt(parts[1]);
                    let d = parseInt(parts[2]);
                    if (m > 12) { m = 1; y++; }
                    if (m < 1) { m = 12; y--; }
                    const formatted = `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                    if (activeInput) {
                        activeInput.value = formatted;
                        activeInput.dispatchEvent(new Event('change'));
                    }
                    hideFlyout();
                };
            });
        }

        window.initWinUIDatePickers = function() {
            document.querySelectorAll('input.win-date-picker').forEach(input => {
                input.setAttribute('autocomplete', 'off');
                input.onclick = function() {
                    showFlyout(this);
                };
            });
        };
    })();

    window.pageInit = function() {
        window.vModal = document.getElementById('violationModal'); window.vForm = document.getElementById('violationForm');
        document.onclick = () => window.closeAllSelects();
        window.initWinUIDatePickers();
        
        window.exDates = [];
        const initVal = document.getElementById('excluded_dates_hidden') ? document.getElementById('excluded_dates_hidden').value : '';
        if (initVal) {
            window.exDates = initVal.split(',').map(s => s.trim()).filter(s => s);
        }
        window.renderExcludedTags();

        document.querySelectorAll('.ajax-form').forEach(form => {
            if (form.id === 'schoolYearForm') return;
            form.onsubmit = function(e) { e.preventDefault(); const btn = this.querySelector('button[type="submit"]'); const oldText = btn.innerHTML; btn.innerHTML = '<i aria-hidden="true" class="fas fa-spinner fa-spin"></i>'; btn.disabled = true; fetch('manage_violations.php', { method: 'POST', body: new FormData(this) }).then(r => r.json()).then(d => { btn.innerHTML = oldText; btn.disabled = false; if(d.status === 'success') { if(typeof Toastify !== 'undefined') Toastify({ text: "✅ " + d.msg, style: { background: "#10b981" } }).showToast(); if (this.querySelector('input[name="start_date"]') || this.id === 'violationForm') { if(window.loadPage) window.loadPage(window.location.href, false, {force: true}); else location.reload(); } } else alert("❌ " + d.msg); }).catch(err => { alert(<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'❌ Lỗi kết nối!'" : "'❌ Connection error!'") ?>); btn.innerHTML = oldText; btn.disabled = false; }); };
        });
        const scopeSelect = document.getElementById('vScope');
        const maxPenaltyGroup = document.getElementById('maxPenaltyGroup');
        if (scopeSelect && maxPenaltyGroup) {
            scopeSelect.onchange = function() {
                if (this.value === 'CLASS') {
                    maxPenaltyGroup.style.display = 'block';
                } else {
                    maxPenaltyGroup.style.display = 'none';
                }
            };
        }
        if(window.vModal) window.vModal.onclick = function(e) { if (e.target === window.vModal) window.closeModal(); };
    };
    window.addDot = function(inputId) { const input = document.getElementById(inputId); const char = " · "; if (input.selectionStart || input.selectionStart == '0') { const startPos = input.selectionStart; const endPos = input.selectionEnd; input.value = input.value.substring(0, startPos) + char + input.value.substring(endPos, input.value.length); input.selectionStart = startPos + char.length; input.selectionEnd = startPos + char.length; } else { input.value += char; } input.focus(); };
    window.openModal = function(mode, data = null) {
        if(!window.vModal || !window.vForm) return;
        window.vModal.style.display = 'flex';
        const maxPenaltyGroup = document.getElementById('maxPenaltyGroup');
        const vMaxPenalty = document.getElementById('vMaxPenalty');
        if (mode === 'add') {
            document.getElementById('modalTitle').innerText = <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Thêm Lỗi Mới'" : "'Add New Violation'") ?>;
            document.getElementById('formAction').value = "add";
            window.vForm.reset();
            if (maxPenaltyGroup) maxPenaltyGroup.style.display = 'none';
        } else {
            document.getElementById('modalTitle').innerText = <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Sửa Lỗi'" : "'Edit Violation'") ?>;
            document.getElementById('formAction').value = "edit";
            document.getElementById('violationId').value = data.id;
            document.getElementById('vContent').value = data.content;
            document.getElementById('vContentEn').value = data.content_en || '';
            document.getElementById('vCode').value = data.short_code;
            document.getElementById('vPoints').value = data.points;
            document.getElementById('vScope').value = data.scope;
            if (vMaxPenalty) vMaxPenalty.value = data.max_penalty_points !== null ? data.max_penalty_points : '';
            if (maxPenaltyGroup) {
                maxPenaltyGroup.style.display = data.scope === 'CLASS' ? 'block' : 'none';
            }
        }
    };
    window.closeModal = function() { if(window.vModal) window.vModal.style.display = 'none'; };
    window.deleteViolation = function(id) { if(!confirm(<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Xóa lỗi này?'" : "'Delete this violation?'") ?>)) return; const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id); fetch('manage_violations.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => { if(d.status === 'success') { if(typeof Toastify !== 'undefined') Toastify({ text: <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'✅ Đã xóa!'" : "'✅ Deleted successfully!'") ?>, style: { background: "#10b981" } }).showToast(); setTimeout(() => { if(window.loadPage) window.loadPage(window.location.href, false, {force: true}); else location.reload(); }, 500); } else alert("❌ " + d.msg); }); };
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', window.pageInit); } else { setTimeout(window.pageInit, 50); }
</script>
<?php include 'includes/footer.php'; ?>