<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* --- CSS BỐ CỤC CHUNG --- */
    .filter-tabs-container { display: flex; gap: 10px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 10px; border-bottom: 1px solid var(--border-color); scrollbar-width: none; }
    .filter-tabs-container::-webkit-scrollbar { display: none; }

    .news-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
    .news-card-compact { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; cursor: pointer; transition: transform 0.25s, box-shadow 0.25s; display: flex; flex-direction: column; }
    .news-card-compact:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); border-color: var(--accent-color); }
    .news-thumb { width: 100%; height: 160px; object-fit: cover; background: var(--bg-hover); border-bottom: 1px solid var(--border-color); }
    .news-card-body { padding: 16px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
    .news-title-compact { font-weight: 700; font-size: 16px; color: var(--text-main); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5; }

    #news_detail_view { display: none; animation: fadeIn 0.3s ease-out forwards; }
    .detail-header { border-bottom: 2px solid var(--primary-color); padding-bottom: 15px; margin-bottom: 20px; }
    .detail-title { font-size: 24px; font-weight: 800; color: var(--text-main); line-height: 1.4; margin-bottom: 10px; }
    .detail-meta { font-size: 13px; color: var(--text-muted); display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
    
    .news-content-display, .tvtl-editor-content { font-size: 15px; line-height: 1.6; color: var(--text-main); font-family: 'Be Vietnam Pro', sans-serif; word-break: break-word; }
    .news-content-display img, .tvtl-editor-content img { max-width: 100% !important; height: auto !important; border-radius: 6px; box-sizing: border-box; vertical-align: top; }
    .tvtl-editor-content img { cursor: pointer; transition: outline 0.1s; }
    .news-content-display table, .tvtl-editor-content table { border-collapse: collapse; width: 100%; margin: 15px 0; }
    .news-content-display td, .news-content-display th, .tvtl-editor-content td, .tvtl-editor-content th { border: 1px solid var(--border-color); padding: 8px; min-width: 50px; }
    .news-content-display::after, .tvtl-editor-content::after { content: ""; display: table; clear: both; }
    .news-content-display iframe { width: 100%; height: 400px; border-radius: 8px; margin: 15px 0; }
    
    /* MODAL */
    .win-modal-overlay { 
        position: fixed !important; 
        top: 0 !important; 
        left: 0 !important; 
        right: 0 !important; 
        bottom: 0 !important; 
        height: 100dvh !important; 
        background: rgba(0,0,0,0.5); 
        backdrop-filter: blur(4px); 
        z-index: 99999 !important; 
        display: none; 
        justify-content: center !important; 
        align-items: center !important; 
        padding: 20px; 
        animation: fadeIn 0.2s ease-out forwards; 
    }
    .win-modal-content { 
        background: var(--bg-card); 
        width: 100%; 
        max-width: 1200px; 
        max-height: 90dvh !important; 
        margin: auto !important; 
        border-radius: 16px; 
        box-shadow: 0 20px 50px rgba(0,0,0,0.2); 
        display: flex; 
        flex-direction: column; 
        overflow: hidden; 
    }
    .win-modal-header { padding: 15px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; background: var(--bg-card); z-index: 10; }
    .win-modal-body { padding: 20px; overflow-y: auto; flex: 1; }
    .win-btn-close { background: none; border: none; color: var(--danger-color); font-size: 20px; cursor: pointer; padding: 5px; line-height: 1; }
    .thumb-dropzone { border: 2px dashed var(--border-color); border-radius: 8px; padding: 20px; text-align: center; background: var(--bg-hover); cursor: pointer; position: relative; transition: all 0.2s; outline: none; }
    .thumb-dropzone:hover, .thumb-dropzone.dragover { border-color: var(--primary-color); background: rgba(0, 95, 186, 0.05); }
    .file-list-item { display: flex; justify-content: space-between; align-items: center; background: var(--bg-hover); padding: 8px 12px; border-radius: 6px; margin-top: 5px; border: 1px solid var(--border-color); font-size: 13px;}
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    /* DROPDOWN & EDITOR */
    .custom-select-container { position: relative; flex: 1; min-width: 140px; margin-bottom: 0; }
    .select-selected { background-color: var(--bg-input); border: 1px solid var(--border-color); padding: 0 12px; height: 42px; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; color: var(--text-main); cursor: pointer; width: 100%; box-sizing: border-box; transition: 0.2s; }
    .select-items { position: absolute; top: calc(100% + 5px); left: 0; right: 0; z-index: 1000; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; max-height: 350px; overflow-y: auto; display: none; box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
    .select-selected:active, .select-selected.active { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(0, 95, 186, 0.15); }
    .select-arrow { width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid var(--text-muted); transition: 0.2s; flex-shrink: 0; }
    .select-selected.active .select-arrow { transform: rotate(180deg); }
    .select-items div { padding: 12px 15px; cursor: pointer; border-bottom: 1px solid var(--border-color); font-size: 14px; color: var(--text-main); transition: 0.2s; display: flex; align-items: center; }
    .select-items div:last-child { border-bottom: none; }
    .select-items div:hover { background: rgba(0, 95, 186, 0.1); color: var(--primary-color); padding-left: 20px; }
    .toolbar-select .select-selected { height: 32px; border-radius: 4px; font-size: 13px; background: var(--bg-card); }
    .toolbar-select { min-width: 120px; flex: none; }

    .tvtl-editor-wrapper { border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card); display: flex; flex-direction: column; margin-top: 10px; position: relative; height: 50vh; min-height: 350px; overflow: hidden; }
    .tvtl-toolbar { padding: 8px; background: var(--bg-hover); border-bottom: 1px solid var(--border-color); display: flex; gap: 5px; flex-wrap: wrap; align-items: center; flex-shrink: 0; z-index: 10; }
    .tvtl-toolbar button, .tvtl-toolbar input[type="color"] { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 4px; cursor: pointer; padding: 5px 8px; color: var(--text-main); font-size: 14px; display: inline-flex; align-items: center; justify-content: center; height: 32px; transition: 0.2s; outline: none; }
    .tvtl-toolbar button:hover { background: #005fba; color: #ffffff !important; border-color: #005fba; }
    .tvtl-toolbar .seperator { width: 1px; height: 24px; background: var(--border-color); margin: 0 5px; }
    .tvtl-editor-content { flex-grow: 1; height: 0; padding: 20px 20px 60px 20px; outline: none; overflow-y: auto; overflow-x: hidden; background: var(--bg-input); transition: background 0.3s; }
    .tvtl-editor-content:focus { background: var(--bg-card); }
    .tvtl-editor-content[placeholder]:empty:before { content: attr(placeholder); color: var(--text-muted); pointer-events: none; display: block; }
    
    #tvtl-image-toolbar { display: none; position: fixed; z-index: 100000; background: var(--bg-card); border: 1px solid var(--primary-color); border-radius: 8px; padding: 6px 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); align-items: center; gap: 8px; animation: fadeIn 0.15s ease-out; }
    .img-tb-btn { background: transparent; border: none; font-size: 16px; color: var(--text-main); cursor: pointer; padding: 6px 8px; border-radius: 4px; transition: 0.2s; outline:none; display: flex; align-items: center; justify-content: center; }
    .img-tb-btn:hover { background: var(--bg-hover); color: var(--primary-color); }

    /* =======================================================
       GIAO DIỆN WINUI 3.0 & BADGE PC
       ======================================================= */
    .win11-spinner {
        width: 18px; height: 18px;
        border: 2.5px solid rgba(0, 120, 212, 0.2);
        border-top-color: currentColor;
        border-radius: 50%;
        animation: win11-spin 0.8s infinite cubic-bezier(0.53, 0.21, 0.29, 0.67);
        display: inline-block; vertical-align: middle;
    }
    @keyframes win11-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

    .winui-swal-popup { border-radius: 12px !important; background: var(--bg-card) !important; color: var(--text-main) !important; box-shadow: 0 32px 64px rgba(0,0,0,0.3) !important; font-family: 'Segoe UI', Tahoma, sans-serif !important; border: 1px solid var(--border-color) !important; }
    .winui-swal-title { color: var(--text-main) !important; }
    .swal2-backdrop-show { backdrop-filter: blur(5px); background: rgba(0,0,0,0.4) !important; }
    .swal2-container { 
        position: fixed !important; 
        top: 0 !important; 
        left: 0 !important; 
        right: 0 !important; 
        bottom: 0 !important; 
        height: 100dvh !important; 
        z-index: 99999 !important; 
    }

    .tool-pc-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; background: var(--bg-hover); border: 1px solid var(--border-color); color: var(--text-main); margin-left: 15px; vertical-align: middle; transition: 0.3s; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .status-dot.online { background-color: #10b981; box-shadow: 0 0 8px #10b981; }
    .status-dot.offline { background-color: #ef4444; }

    /* Player */
    .win-player-wrapper { position: relative; border-radius: 8px; overflow: hidden; background: #000; display: flex; align-items: center; justify-content: center; width: 100%; aspect-ratio: 16/9; margin: 15px 0; box-shadow: 0 8px 25px rgba(0,0,0,0.2); }
    .win-video-el { width: 100%; height: 100%; object-fit: contain; cursor: pointer; }
    .win-video-loading { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 46px; height: 46px; z-index: 20; display: none; pointer-events: none; color: var(--primary-color); }
    .win-video-loading .win11-spinner { width: 100%; height: 100%; border-width: 4px; }
    .win-player-controls { position: absolute; bottom: 12px; left: 12px; right: 12px; background: rgba(30, 30, 30, 0.5); backdrop-filter: blur(20px) saturate(125%); -webkit-backdrop-filter: blur(20px) saturate(125%); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 8px; padding: 8px 15px; display: flex; flex-direction: column; gap: 8px; opacity: 0; transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1); color: #fff; z-index: 10; }
    .win-player-wrapper:hover .win-player-controls, .win-player-wrapper.force-show-controls .win-player-controls { opacity: 1; }
    .win-progress-container { width: 100%; height: 5px; background: rgba(255,255,255,0.3); border-radius: 3px; cursor: pointer; position: relative; transition: height 0.15s; }
    .win-progress-container:hover { height: 7px; }
    .win-progress-filled { height: 100%; background: var(--primary-color, #0078D4); border-radius: 3px; width: 0%; pointer-events: none; }
    .win-progress-thumb { width: 14px; height: 14px; background: #fff; border-radius: 50%; position: absolute; top: 50%; left: 0%; transform: translate(-50%, -50%) scale(0); transition: transform 0.15s; box-shadow: 0 0 5px rgba(0,0,0,0.3); pointer-events: none; }
    .win-progress-container:hover .win-progress-thumb { transform: translate(-50%, -50%) scale(1); }
    .win-controls-row { display: flex; align-items: center; justify-content: space-between; gap: 15px; }
    .win-player-btn { background: none; border: none; color: #fff; font-size: 16px; cursor: pointer; transition: 0.2s; width: 34px; height: 34px; border-radius: 4px; display: flex; align-items: center; justify-content: center; }
    .win-player-btn:hover { background: rgba(255,255,255,0.15); }
    .win-time-display { font-size: 13px; font-weight: 500; font-family: 'Segoe UI', Tahoma, sans-serif; letter-spacing: 0.5px; opacity: 0.9; }
    .win-quality-wrapper { position: relative; }
    .win-quality-menu { position: absolute; bottom: calc(100% + 15px); right: -10px; background: rgba(30, 30, 30, 0.75); backdrop-filter: blur(25px) saturate(125%); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; padding: 6px 0; display: none; flex-direction: column; min-width: 120px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); }
    .win-quality-menu.active { display: flex; animation: slideUpFade 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
    .win-quality-menu div { padding: 10px 18px; cursor: pointer; font-size: 13px; transition: 0.2s; display: flex; align-items: center; justify-content: space-between; }
    .win-quality-menu div:hover { background: rgba(255,255,255,0.1); }
    .win-quality-menu div.active { color: #60A5FA; font-weight: bold; }
    .win-quality-menu div.active::after { content: '\f00c'; font-family: 'Font Awesome 5 Free'; font-weight: 900; font-size: 11px; }
    @keyframes slideUpFade { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="win-card" id="news_main_container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px;">
            <h2 class="heading-class" style="color: var(--primary-color); margin: 0; display: inline-block;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'TIN TỨC & THÔNG BÁO' : 'NEWS & ANNOUNCEMENTS') ?></h2>
            <div id="tool_pc_badge" class="tool-pc-badge">
                <span class="status-dot offline"></span> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tool PC: Ngoại tuyến' : 'PC Tool: Offline') ?>
            </div>
            <a href="static/LG3VIDEOTOOL.exe" download class="win-btn win-btn-secondary" style="padding: 4px 12px; border-radius: 20px; font-size: 12px; margin: 0; text-decoration: none; font-weight: bold;">
                <i class="fas fa-download" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tải Tool PC' : 'Download PC Tool') ?>
            </a>
        </div>
        <button id="btn_back_to_list" class="win-btn win-btn-secondary" style="display: none; padding: 6px 15px; margin: 0;" onclick="window.backToList()"><i class="fas fa-arrow-left" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Quay lại' : 'Back to list') ?></button>
        
        <div id="btn_group_master" style="display: flex; gap: 10px;">
            <?php if (isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['ADMIN', 'TEACHER'])): ?>
                <button onclick="window.showZaloGuide()" class="win-btn win-btn-danger" style="margin: 0; font-weight: bold; animation: pulse 2s infinite;">
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'LƯU Ý GỬI ẢNH/VIDEO ZALO' : 'ZALO UPLOAD NOTICE') ?>
                </button>
                <button onclick="window.openComposeModal()" class="win-btn" style="margin: 0;">
                    <i class="fas fa-plus" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đăng bài' : 'Create post') ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div id="news_master_view">
        <div class="filter-tabs-container" id="news_filter_tabs">
            <button class="win-btn news-filter-btn" data-category="all" style="border-radius: 20px; padding: 6px 16px; font-weight: bold;"><i class="fas fa-layer-group" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tất cả' : 'All') ?></button>
            <button class="win-btn win-btn-secondary news-filter-btn" data-category="thong_bao" style="border-radius: 20px; padding: 6px 16px;"><i class="fas fa-bullhorn" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thông báo' : 'Announcements') ?></button>
            <button class="win-btn win-btn-secondary news-filter-btn" data-category="tin_tuc" style="border-radius: 20px; padding: 6px 16px;"><i class="fas fa-newspaper" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tin tức sự kiện' : 'News & Events') ?></button>
            <button class="win-btn win-btn-secondary news-filter-btn" data-category="tuyen_sinh" style="border-radius: 20px; padding: 6px 16px;"><i class="fas fa-graduation-cap" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuyển sinh' : 'Admissions') ?></button>
        </div>
        <div id="news_list_wrapper" class="news-grid" style="min-height: 200px;"></div>
    </div>

    <div id="news_detail_view">
        <div class="detail-header">
            <div id="detail_category_badge" style="margin-bottom: 10px;"></div>
            <div class="detail-title" id="detail_title"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tiêu đề bài viết' : 'Article Title') ?></div>
            <div class="detail-meta">
                <span id="detail_date"><i class="far fa-clock" aria-hidden="true"></i> dd/mm/yyyy</span>
                <span id="detail_author"><i class="fas fa-user-edit" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tác giả' : 'Author') ?></span>
                <div id="detail_action_btns" style="margin-left: auto;"></div>
            </div>
        </div>
        <div class="news-content-display" id="detail_content"></div>
        <h4 style="margin-top: 30px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; color: var(--primary-color); display: none;" id="attachment_title"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tệp đính kèm:' : 'Attachments:') ?></h4>
        <div id="detail_attachment_area" style="display: flex; flex-direction: column; gap: 15px;"></div>
    </div>
</div>

<div id="news_compose_modal" class="win-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="title_news_compose">
    <div class="win-modal-content">
        <div class="win-modal-header">
            <h3 id="title_news_compose" style="margin: 0; font-size: 18px; color: var(--primary-color);"><i class="fas fa-edit" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Soạn bản tin mới' : 'Compose new bulletin') ?></h3>
            <button class="win-btn-close" onclick="window.closeComposeModal()"><i class="fas fa-times-circle" aria-hidden="true"></i></button>
        </div>
        
        <div class="win-modal-body" id="modal_body_scroll" style="display: flex; flex-direction: column; gap: 15px;">
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <label for="inp_news_title" style="display: none;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập tiêu đề bài viết...' : 'Enter article title...') ?></label>
                <input type="text" id="inp_news_title" class="win-input" style="flex: 2; min-width: 250px; margin-bottom: 0;" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập tiêu đề bài viết...' : 'Enter article title...') ?>">
                
                <div class="custom-select-container">
                    <div class="select-selected" onclick="window.toggleDropdown(event, this)" role="button" tabindex="0" aria-haspopup="listbox" onkeypress="if(event.key==='Enter'||event.key===' ') this.click();">
                        <span id="txtSelectedCategory" style="font-weight:600;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thông báo' : 'Announcement') ?></span>
                        <div class="select-arrow"></div>
                    </div>
                    <div class="select-items" role="listbox">
                        <div onclick="window.selectCategory('thong_bao', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thông báo' : 'Announcement') ?>', this)" role="option" tabindex="0" onkeypress="if(event.key==='Enter'||event.key===' ') this.click();"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thông báo' : 'Announcement') ?></div>
                        <div onclick="window.selectCategory('tin_tuc', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tin tức sự kiện' : 'News & Event') ?>', this)" role="option" tabindex="0" onkeypress="if(event.key==='Enter'||event.key===' ') this.click();"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tin tức sự kiện' : 'News & Event') ?></div>
                        <div onclick="window.selectCategory('tuyen_sinh', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuyển sinh' : 'Admission') ?>', this)" role="option" tabindex="0" onkeypress="if(event.key==='Enter'||event.key===' ') this.click();"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tuyển sinh' : 'Admission') ?></div>
                    </div>
                </div>
                <input type="hidden" id="sel_news_category" value="thong_bao">
            </div>

            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div id="thumbnail_dropzone" class="thumb-dropzone" style="flex: 1; min-width: 250px; margin: 0;">
                    <i class="fas fa-image fa-2x" style="color: var(--text-muted); margin-bottom: 10px;" aria-hidden="true"></i>
                    <div style="font-weight: 600; color: var(--text-main);"><label for="inp_thumbnail_file" style="cursor: pointer;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ảnh đại diện (Thumbnail)' : 'Thumbnail') ?></label></div>
                    <input type="file" id="inp_thumbnail_file" style="display: none;" accept=".jpg,.jpeg,.png,.webp">
                    <img id="thumbnail_preview" src="" style="display: none; max-width: 100%; max-height: 120px; border-radius: 6px; margin-top: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);" alt="Thumbnail Preview">
                    <button id="btn_remove_thumb" class="win-btn win-btn-danger" style="display: none; position: absolute; top: 10px; right: 10px; padding: 4px 8px; font-size: 12px; margin: 0;"><i class="fas fa-times" aria-hidden="true"></i></button>
                </div>

                <div style="flex: 1; min-width: 250px; background: var(--bg-hover); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
                    <button type="button" onclick="window.handleUnifiedAttachmentClick()" class="win-btn win-btn-secondary" style="margin: 0; font-size: 13px; cursor: pointer; text-align: center; justify-content: center;">
                        <i class="fas fa-paperclip" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đính kèm Tài liệu / Video HD' : 'Attach Document / HD Video') ?>
                    </button>
                    <input type="file" id="inp_news_files" multiple style="display: none;" accept=".pdf,.doc,.docx,.xls,.xlsx,.mp4,.avi,.mov">
                    <div style="font-size: 11px; color: var(--danger-color); font-weight: bold; text-align: center; margin-top: 5px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hệ thống cắt HLS tự động, Tốc độ tải siêu mượt.' : 'Automatic HLS processing, super smooth streaming.') ?></div>
                    <div id="multi_file_list" style="margin-top: 10px; display: flex; flex-direction: column; gap: 5px; overflow-y: auto; max-height: 100px;"></div>
                </div>
            </div>

            <div class="tvtl-editor-wrapper">
                <div class="tvtl-toolbar">
                    <button type="button" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'In Đậm' : 'Bold') ?>" onclick="window.execCmd('bold')"><i class="fas fa-bold" aria-hidden="true"></i></button>
                    <button type="button" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'In Nghiêng' : 'Italic') ?>" onclick="window.execCmd('italic')"><i class="fas fa-italic" aria-hidden="true"></i></button>
                    <button type="button" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Gạch chân' : 'Underline') ?>" onclick="window.execCmd('underline')"><i class="fas fa-underline" aria-hidden="true"></i></button>
                    <button type="button" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Gạch ngang' : 'Strikethrough') ?>" onclick="window.execCmd('strikeThrough')"><i class="fas fa-strikethrough" aria-hidden="true"></i></button>
                    <div class="seperator"></div>
                    <div class="custom-select-container toolbar-select">
                        <div class="select-selected" onclick="window.toggleDropdown(event, this)" role="button" tabindex="0" aria-haspopup="listbox" onkeypress="if(event.key==='Enter'||event.key===' ') this.click();">
                            <span id="txtSelectedFontSize" style="font-weight:600;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cỡ chữ' : 'Font size') ?></span>
                            <div class="select-arrow"></div>
                        </div>
                        <div class="select-items" style="max-height: 250px;" role="listbox">
                            <div onclick="window.selectFontSize('1', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Rất nhỏ' : 'Very small') ?>', this)" style="font-size: 10px; line-height: 1;" role="option" tabindex="0" onkeypress="if(event.key==='Enter'||event.key===' ') this.click();"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Rất nhỏ' : 'Very small') ?></div>
                            <div onclick="window.selectFontSize('3', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bình thường' : 'Normal') ?>', this)" style="font-size: 15px; line-height: 1;" role="option" tabindex="0" onkeypress="if(event.key==='Enter'||event.key===' ') this.click();"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bình thường' : 'Normal') ?></div>
                            <div onclick="window.selectFontSize('5', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớn' : 'Large') ?>', this)" style="font-size: 20px; line-height: 1; font-weight: bold;" role="option" tabindex="0" onkeypress="if(event.key==='Enter'||event.key===' ') this.click();"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớn' : 'Large') ?></div>
                            <div onclick="window.selectFontSize('7', '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Rất lớn' : 'Very large') ?>', this)" style="font-size: 28px; line-height: 1; font-weight: bold;" role="option" tabindex="0" onkeypress="if(event.key==='Enter'||event.key===' ') this.click();"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Rất lớn' : 'Very large') ?></div>
                        </div>
                    </div>
                    <div class="seperator"></div>
                    <div style="display:flex; align-items:center; gap:5px; background:var(--bg-card); padding:0 5px; border-radius:4px; border:1px solid var(--border-color);">
                        <i class="fas fa-palette" style="color:var(--text-muted); font-size:12px;" aria-hidden="true"></i>
                        <input type="color" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Màu chữ' : 'Text color') ?>" onchange="window.execCmd('foreColor', this.value)" style="border:none; width:24px; padding:0; background:none;">
                    </div>
                    <div class="seperator"></div>
                    <button type="button" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Căn trái' : 'Align left') ?>" onclick="window.execCmd('justifyLeft')"><i class="fas fa-align-left" aria-hidden="true"></i></button>
                    <button type="button" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Căn giữa' : 'Align center') ?>" onclick="window.execCmd('justifyCenter')"><i class="fas fa-align-center" aria-hidden="true"></i></button>
                    <button type="button" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Căn phải' : 'Align right') ?>" onclick="window.execCmd('justifyRight')"><i class="fas fa-align-right" aria-hidden="true"></i></button>
                    <div class="seperator"></div>
                    <button type="button" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Danh sách chấm' : 'Bullet list') ?>" onclick="window.execCmd('insertUnorderedList')"><i class="fas fa-list-ul" aria-hidden="true"></i></button>
                    <button type="button" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Danh sách số' : 'Number list') ?>" onclick="window.execCmd('insertOrderedList')"><i class="fas fa-list-ol" aria-hidden="true"></i></button>
                    <div class="seperator"></div>
                    <button type="button" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xóa định dạng' : 'Clear formatting') ?>" onclick="window.execCmd('removeFormat')"><i class="fas fa-eraser" aria-hidden="true"></i></button>
                    <button type="button" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chèn Link' : 'Insert Link') ?>" onclick="window.insertLink()"><i class="fas fa-link" aria-hidden="true"></i></button>
                    <div class="seperator"></div>
                    <button type="button" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chèn Bảng' : 'Insert Table') ?>" style="color:var(--primary-color); border-color:var(--primary-color);" onclick="window.insertCustomTable()"><i class="fas fa-table" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bảng' : 'Table') ?></button>
                    <button type="button" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chèn Ảnh' : 'Insert Image') ?>" style="color:#10b981; border-color:#10b981;" onclick="document.getElementById('tvtl_editor_img_input').click()"><i class="fas fa-image" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ảnh' : 'Image') ?></button>
                    <input type="file" id="tvtl_editor_img_input" style="display:none;" accept=".jpg,.jpeg,.png,.webp" onchange="window.insertEditorImage(this)">
                </div>
                <div id="tvtl_editor" class="tvtl-editor-content" contenteditable="true" role="textbox" aria-multiline="true" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Soạn thảo nội dung chi tiết tại đây...' : 'Compose details here...') ?>" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Soạn thảo nội dung chi tiết tại đây...' : 'Compose details here...') ?>"></div>
            </div>
            
            <div id="tvtl-image-toolbar">
                <button type="button" class="img-tb-btn" onclick="window.alignImage('left')" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Căn trái' : 'Align left') ?>"><i class="fas fa-align-left" aria-hidden="true"></i></button>
                <button type="button" class="img-tb-btn" onclick="window.alignImage('center')" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Căn giữa' : 'Align center') ?>"><i class="fas fa-align-center" aria-hidden="true"></i></button>
                <button type="button" class="img-tb-btn" onclick="window.alignImage('right')" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Căn phải' : 'Align right') ?>"><i class="fas fa-align-right" aria-hidden="true"></i></button>
                <div class="seperator" style="width:1px; height:20px; background:var(--border-color);"></div>
                <button type="button" class="img-tb-btn" onclick="window.resizeImageStep(-10)" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thu nhỏ ảnh' : 'Zoom out image') ?>"><i class="fas fa-search-minus" aria-hidden="true"></i></button>
                <span id="img-resizer-val" style="font-size:13px; font-weight:bold; color:var(--text-main); width:45px; text-align:center;">100%</span>
                <button type="button" class="img-tb-btn" onclick="window.resizeImageStep(10)" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Phóng to ảnh' : 'Zoom in image') ?>"><i class="fas fa-search-plus" aria-hidden="true"></i></button>
                <div class="seperator" style="width:1px; height:20px; background:var(--border-color);"></div>
                <button type="button" class="img-tb-btn" onclick="window.deleteImage()" style="color:var(--danger-color);" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xóa ảnh' : 'Delete image') ?>"><i class="fas fa-trash" aria-hidden="true"></i></button>
            </div>
            
        </div>

        <div class="win-modal-footer" style="padding: 15px 20px; border-top: 1px solid var(--border-color); text-align: right; background: var(--bg-card); flex-shrink: 0; z-index: 10;">
            <button onclick="window.closeComposeModal()" class="win-btn win-btn-secondary" style="margin-bottom: 0;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hủy' : 'Cancel') ?></button>
            <button id="btn_post_news" class="win-btn" style="padding: 6px 20px; margin-bottom: 0;"><i class="fas fa-paper-plane" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đăng bài ngay' : 'Post now') ?></button>
        </div>
    </div>
</div>

<script>
    // =========================================================
    // KHAI BÁO CÁC HÀM TOÀN CỤC CHỐNG LỖI SPA (KHÔNG DÙNG CONST/LET/VAR Ở ROOT)
    // =========================================================
    window.WINUI_SWAL_CLASS = { popup: 'winui-swal-popup', title: 'winui-swal-title', confirmButton: 'win-btn', cancelButton: 'win-btn win-btn-secondary', denyButton: 'win-btn' };
    
    window.myCurrentId = <?= isset($_SESSION['user']) ? $_SESSION['user']['id'] : 0 ?>;
    window.myCurrentRole = '<?= isset($_SESSION['user']) ? $_SESSION['user']['role'] : "" ?>';

    // CÁC HÀM TIỆN ÍCH DROPDOWN VÀ FORMAT
    window.toggleDropdown = function(e, el) { e.stopPropagation(); window.closeAllSelects(el); el.nextElementSibling.style.display = el.nextElementSibling.style.display === 'block' ? 'none' : 'block'; el.classList.toggle('active'); };
    window.closeAllSelects = function(except) { document.querySelectorAll('.select-items').forEach(i => { if(i !== except?.nextElementSibling) i.style.display = 'none'; }); document.querySelectorAll('.select-selected').forEach(e => { if(e !== except) e.classList.remove('active'); }); };
    window.selectCategory = function(val, text, el) { document.getElementById('txtSelectedCategory').innerText = text; document.getElementById('sel_news_category').value = val; el.parentElement.style.display = 'none'; };
    window.savedSelection = null;
    window.selectFontSize = function(val, text, el) {
        document.getElementById('txtSelectedFontSize').innerText = text; el.parentElement.style.display = 'none';
        const editor = document.getElementById('tvtl_editor'); editor.focus();
        if (window.savedSelection) { let sel = window.getSelection(); sel.removeAllRanges(); sel.addRange(window.savedSelection); }
        document.execCommand('fontSize', false, val); setTimeout(() => { document.getElementById('txtSelectedFontSize').innerText = '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cỡ chữ' : 'Font size') ?>'; }, 1500);
    };
    document.addEventListener('click', function(e) { window.closeAllSelects(null); });

    window.formatTime = function(seconds) { const m = Math.floor(seconds / 60); const s = Math.floor(seconds % 60); return (m < 10 ? "0" + m : m) + ":" + (s < 10 ? "0" + s : s); };

    // HÀM KHỞI TẠO PLAYER
    window.initWinUIPlayers = function() {
        if (typeof Hls === 'undefined') return;
        document.querySelectorAll('.hls-player').forEach(video => {
            if (video.dataset.initialized) return;
            video.dataset.initialized = 'true';
            const sourceEl = video.querySelector('source'); if (!sourceEl) return;
            const m3u8Url = sourceEl.src;
            video.removeAttribute('style'); video.style.display = 'block'; video.removeAttribute('controls'); video.className = 'win-video-el';

            const wrapper = document.createElement('div'); wrapper.className = 'win-player-wrapper';
            video.parentNode.insertBefore(wrapper, video); wrapper.appendChild(video);

            wrapper.insertAdjacentHTML('beforeend', `
                <div class="win-video-loading"><div class="win11-spinner"></div></div>
                <div class="win-player-controls">
                    <div class="win-progress-container"><div class="win-progress-filled"></div><div class="win-progress-thumb"></div></div>
                    <div class="win-controls-row">
                        <button class="win-player-btn win-play-btn"><i class="fas fa-play" aria-hidden="true"></i></button>
                        <div class="win-time-display">00:00 / 00:00</div>
                        <div style="flex-grow: 1;"></div>
                        <div class="win-quality-wrapper"><button class="win-player-btn win-quality-btn" title="Chất lượng"><i class="fas fa-cog" aria-hidden="true"></i></button><div class="win-quality-menu"></div></div>
                        <button class="win-player-btn win-fullscreen-btn"><i class="fas fa-expand" aria-hidden="true"></i></button>
                    </div>
                </div>
            `);

            const loadingSpinner = wrapper.querySelector('.win-video-loading');
            video.addEventListener('waiting', () => loadingSpinner.style.display = 'block');
            video.addEventListener('playing', () => loadingSpinner.style.display = 'none');
            video.addEventListener('canplay', () => loadingSpinner.style.display = 'none');

            const playBtn = wrapper.querySelector('.win-play-btn'); const timeDisplay = wrapper.querySelector('.win-time-display'); const progressContainer = wrapper.querySelector('.win-progress-container'); const progressFilled = wrapper.querySelector('.win-progress-filled'); const progressThumb = wrapper.querySelector('.win-progress-thumb'); const qualityBtn = wrapper.querySelector('.win-quality-btn'); const qualityMenu = wrapper.querySelector('.win-quality-menu'); const fullscreenBtn = wrapper.querySelector('.win-fullscreen-btn');

            if (Hls.isSupported()) {
                let hls = new Hls({ maxBufferLength: 30, maxMaxBufferLength: 600 }); hls.loadSource(m3u8Url); hls.attachMedia(video);
                hls.on(Hls.Events.MANIFEST_PARSED, function(event, data) {
                    qualityMenu.innerHTML = '<div data-level="-1" class="active">' + (<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Tự động (Auto)'" : "'Auto'") ?>) + '</div>';
                    if (data.levels && data.levels.length > 0) {
                        let sortedLevels = data.levels.map((l, i) => ({...l, index: i})).sort((a,b) => b.height - a.height);
                        sortedLevels.forEach(level => { qualityMenu.insertAdjacentHTML('beforeend', `<div data-level="${level.index}">${level.height ? level.height + 'p' : (<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Mặc định'" : "'Default'") ?>)}</div>`); });
                    } else qualityMenu.insertAdjacentHTML('beforeend', `<div data-level="0">${<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Mặc định gốc'" : "'Original default'") ?>}</div>`);
                    qualityMenu.querySelectorAll('div').forEach(item => { item.addEventListener('click', (e) => { e.stopPropagation(); hls.currentLevel = parseInt(item.dataset.level); qualityMenu.querySelectorAll('div').forEach(el => el.classList.remove('active')); item.classList.add('active'); qualityMenu.classList.remove('active'); }); });
                });
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) video.src = m3u8Url;

            const togglePlay = () => { if (video.paused || video.ended) { if (video.ended) video.currentTime = 0; const p = video.play(); if (p !== undefined) p.then(() => playBtn.innerHTML = '<i class="fas fa-pause" aria-hidden="true"></i>').catch(()=>{}); } else { video.pause(); playBtn.innerHTML = '<i class="fas fa-play" aria-hidden="true"></i>'; } };
            playBtn.addEventListener('click', togglePlay); video.addEventListener('click', togglePlay);
            video.addEventListener('ended', () => { playBtn.innerHTML = '<i class="fas fa-play" aria-hidden="true"></i>'; wrapper.classList.add('force-show-controls'); });
            video.addEventListener('timeupdate', () => { const percent = (video.currentTime / video.duration) * 100 || 0; progressFilled.style.width = percent + '%'; progressThumb.style.left = percent + '%'; timeDisplay.innerText = window.formatTime(video.currentTime) + ' / ' + window.formatTime(video.duration || 0); });
            progressContainer.addEventListener('click', (e) => { const rect = progressContainer.getBoundingClientRect(); const pos = (e.clientX - rect.left) / rect.width; video.currentTime = pos * video.duration; });
            qualityBtn.addEventListener('click', (e) => { e.stopPropagation(); qualityMenu.classList.toggle('active'); }); document.addEventListener('click', () => { qualityMenu.classList.remove('active'); });
            fullscreenBtn.addEventListener('click', () => { if (!document.fullscreenElement) { if (wrapper.requestFullscreen) wrapper.requestFullscreen(); else if (wrapper.webkitRequestFullscreen) wrapper.webkitRequestFullscreen(); fullscreenBtn.innerHTML = '<i class="fas fa-compress" aria-hidden="true"></i>'; } else { if (document.exitFullscreen) document.exitFullscreen(); else if (document.webkitExitFullscreen) document.webkitExitFullscreen(); fullscreenBtn.innerHTML = '<i class="fas fa-expand" aria-hidden="true"></i>'; } });
            let timeout; wrapper.addEventListener('mousemove', () => { wrapper.classList.add('force-show-controls'); clearTimeout(timeout); timeout = setTimeout(() => { if (!video.paused) wrapper.classList.remove('force-show-controls'); }, 2500); }); wrapper.addEventListener('mouseleave', () => { if (!video.paused) wrapper.classList.remove('force-show-controls'); });
        });
    };

    // HÀM EDITOR
    window.validateImageClientSide = async function(file) {
        return new Promise((resolve, reject) => {
            if (!file.type.startsWith('image/')) { resolve(true); return; }
            if (file.size > 2 * 1024 * 1024) { resolve(true); return; } 
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const bpp = file.size / (img.width * img.height);
                    if ((file.type === 'image/jpeg' || file.type === 'image/jpg') && bpp < 0.18) { reject((<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Hệ thống chặn ảnh nén mờ'" : "'System blocks blurred images'") ?>) + ' (Mật độ: ' + bpp.toFixed(2) + ' BPP). ' + (<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Vui lòng gửi ảnh mục Tài liệu!'" : "'Please send image via Documents!'") ?>)); return; }
                    resolve(e.target.result); 
                }; img.src = e.target.result;
            }; reader.readAsDataURL(file);
        });
    };
    
    window.focusEditor = function() { document.getElementById('tvtl_editor').focus(); };
    window.execCmd = function(command, value = null) {
        window.focusEditor();
        if (window.savedSelection) {
            let sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(window.savedSelection);
        }
        document.execCommand(command, false, value);
    };
    window.insertLink = function() { let url = prompt(<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Nhập đường dẫn URL:'" : "'Enter URL:'") ?>, "https://"); if (url) { window.execCmd('createLink', url); } };

    window.insertCustomTable = function() {
        window.focusEditor(); let sel = window.getSelection(); let range = sel.rangeCount > 0 ? sel.getRangeAt(0) : null;
        Swal.fire({
            target: document.getElementById('news_compose_modal'), customClass: window.WINUI_SWAL_CLASS, title: (<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Tạo Bảng Mới'" : "'Create New Table'") ?>),
            html: `<div style="display: flex; gap: 20px; justify-content: center; margin-top: 15px;"><div style="text-align: left;"><label style="font-size: 13px; font-weight: bold; color: #64748b; margin-bottom: 5px; display: block;">${<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Số Cột (Ngang)'" : "'Cols'") ?>}</label><input id="swal-cols" type="number" value="3" min="1" max="15" style="width: 100px; text-align: center; padding: 8px; border: 1px solid var(--border-color); background: var(--bg-input); color: var(--text-main); border-radius: 6px; outline: none; font-size: 15px;"></div><div style="text-align: left;"><label style="font-size: 13px; font-weight: bold; color: #64748b; margin-bottom: 5px; display: block;">${<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Số Hàng (Dọc)'" : "'Rows'") ?>}</label><input id="swal-rows" type="number" value="3" min="1" max="30" style="width: 100px; text-align: center; padding: 8px; border: 1px solid var(--border-color); background: var(--bg-input); color: var(--text-main); border-radius: 6px; outline: none; font-size: 15px;"></div></div>`,
            showCancelButton: true, confirmButtonText: '<i class="fas fa-check" aria-hidden="true"></i> ' + (<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Chèn Bảng'" : "'Insert Table'") ?>), cancelButtonText: (<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Hủy'" : "'Cancel'") ?>)
        }).then((result) => {
            if (result.isConfirmed) {
                window.focusEditor(); if (range) { sel.removeAllRanges(); sel.addRange(range); }
                const rows = parseInt(document.getElementById('swal-rows').value) || 3; const cols = parseInt(document.getElementById('swal-cols').value) || 3;
                let tableHTML = '<br><table style="width: 100%; border-collapse: collapse; margin: 15px 0;" border="1"><tbody>';
                for(let r=0; r<rows; r++){ tableHTML += '<tr>'; for(let c=0; c<cols; c++) tableHTML += '<td style="border: 1px solid var(--border-color); padding: 8px;"><br></td>'; tableHTML += '</tr>'; }
                tableHTML += '</tbody></table><br>'; document.execCommand('insertHTML', false, tableHTML);
            }
        });
    };

    window.insertEditorImage = async function(input) {
        if (!input.files || input.files.length === 0) return;
        window.focusEditor();
        for (let i = 0; i < input.files.length; i++) {
            try {
                const validBase64 = await window.validateImageClientSide(input.files[i]);
                let imgHTML = '<img src="' + validBase64 + '" align="left" width="100%" style="max-width: 100%; display: inline-block; vertical-align: top; margin: 5px 15px 5px 0; border-radius: 6px; float: left;" />';
                document.execCommand('insertHTML', false, imgHTML + '&nbsp;');
            } catch (err) { Toastify({ text: err, duration: 5000, gravity: "bottom", position: "center", style: { background: "var(--danger-color)" } }).showToast(); }
        }
        input.value = ''; 
    };

    window.currentSelectedImage = null; window.currentImageSize = 100;
    window.hideImageToolbar = function() { if (window.currentSelectedImage) window.currentSelectedImage.style.outline = 'none'; window.currentSelectedImage = null; const tb = document.getElementById('tvtl-image-toolbar'); if(tb) tb.style.display = 'none'; };
    window.showImageToolbar = function(img) {
        window.currentSelectedImage = img; const editor = document.getElementById('tvtl_editor'); editor.querySelectorAll('img').forEach(i => i.style.outline = 'none'); img.style.outline = '3px solid var(--primary-color)';
        let w = img.style.width; window.currentImageSize = w && w.includes('%') ? parseInt(w) : 100; document.getElementById('img-resizer-val').innerText = window.currentImageSize + '%';
        const tb = document.getElementById('tvtl-image-toolbar'); tb.style.display = 'flex'; window.updateImageToolbarPosition();
    };
    window.updateImageToolbarPosition = function() {
        if (!window.currentSelectedImage) return;
        const tb = document.getElementById('tvtl-image-toolbar'); const imgRect = window.currentSelectedImage.getBoundingClientRect(); const editorRect = document.getElementById('tvtl_editor').getBoundingClientRect();
        let topPos = imgRect.top + 15; let leftPos = imgRect.left + (imgRect.width / 2) - (tb.offsetWidth / 2);
        if (topPos < editorRect.top + 10) topPos = editorRect.top + 10; if (topPos + tb.offsetHeight > editorRect.bottom - 10) topPos = editorRect.bottom - tb.offsetHeight - 10;
        if (leftPos < editorRect.left + 10) leftPos = editorRect.left + 10; if (leftPos + tb.offsetWidth > editorRect.right - 10) leftPos = editorRect.right - tb.offsetWidth - 10;
        tb.style.top = topPos + 'px'; tb.style.left = leftPos + 'px';
    };
    window.alignImage = function(align) {
        if (!window.currentSelectedImage) return;
        if (align === 'center') { window.currentSelectedImage.style.display = 'block'; window.currentSelectedImage.style.margin = '10px auto'; window.currentSelectedImage.style.float = 'none'; window.currentSelectedImage.removeAttribute('align'); } 
        else if (align === 'left') { window.currentSelectedImage.style.display = 'inline-block'; window.currentSelectedImage.style.margin = '5px 15px 5px 0'; window.currentSelectedImage.style.float = 'left'; window.currentSelectedImage.setAttribute('align', 'left'); } 
        else if (align === 'right') { window.currentSelectedImage.style.display = 'inline-block'; window.currentSelectedImage.style.margin = '5px 0 5px 15px'; window.currentSelectedImage.style.float = 'right'; window.currentSelectedImage.setAttribute('align', 'right'); }
        setTimeout(window.updateImageToolbarPosition, 50); 
    };
    window.resizeImageStep = function(step) {
        if (!window.currentSelectedImage) return;
        window.currentImageSize += step; if (window.currentImageSize < 10) window.currentImageSize = 10; if (window.currentImageSize > 100) window.currentImageSize = 100;
        window.currentSelectedImage.style.width = window.currentImageSize + '%'; window.currentSelectedImage.setAttribute('width', window.currentImageSize + '%'); window.currentSelectedImage.style.height = 'auto';
        document.getElementById('img-resizer-val').innerText = window.currentImageSize + '%'; window.updateImageToolbarPosition();
    };
    window.deleteImage = function() { if (!window.currentSelectedImage) return; window.currentSelectedImage.remove(); window.hideImageToolbar(); };

    // HÀM NGHIỆP VỤ 
    window.deleteHlsFromServer = function(url) {
        const match = url.match(/vid_[a-zA-Z0-9_]+/);
        if (match) { const fd = new FormData(); fd.append('task_id', match[0]); fetch('api/news_api.php?action=delete_hls', {method: 'POST', body: fd}).catch(e=>{}); }
    };

    window.showZaloGuide = function() {
        Swal.fire({
            title: (window.LANG && window.LANG.zalo_guide_title || 'CẨM NANG GỬI ẢNH GỐC (CHỐNG MỜ)'),
            customClass: window.WINUI_SWAL_CLASS,
            html: `
            <style>
                .guide-container { text-align: left; font-size: 14px; max-height: 72vh; overflow-y: auto; padding-right: 8px; }
                .guide-container::-webkit-scrollbar { width: 6px; }
                .guide-container::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 4px; }
                .guide-desc { margin-top: 0; margin-bottom: 20px; color: var(--text-muted); font-size: 15px;}
                .guide-card { background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 12px; padding: 18px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
                .guide-card.ios { border-top: 4px solid #3b82f6; }
                .guide-card.android { border-top: 4px solid #10b981; }
                .guide-card.pc { border-top: 4px solid #64748b; }
                .guide-title { font-weight: 800; font-size: 16px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; }
                .guide-title.ios { color: #3b82f6; } .guide-title.android { color: #10b981; } .guide-title.pc { color: #64748b; }
                .step-line { margin-bottom: 12px; display: flex; align-items: flex-start; gap: 10px; line-height: 1.5; color: var(--text-main); }
                .step-num { background: #005fba; color: #ffffff !important; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 12px; font-weight: bold; flex-shrink: 0; margin-top: 2px; }
                .guide-img-wrapper { display: flex; gap: 10px; margin-top: 10px; margin-bottom: 15px; justify-content: center; }
                .guide-img { max-width: 100%; max-height: 350px; border-radius: 8px; border: 1px solid var(--border-color); box-shadow: 0 4px 10px rgba(0,0,0,0.08); object-fit: contain; background: #fff; }
            </style>
            <div class="guide-container">
                <p class="guide-desc">${window.LANG && window.LANG.zalo_guide_subtitle || 'Hệ thống sẽ chặn tự động ảnh nén mờ để đảm bảo thẩm mỹ cho Web trường. Vui lòng làm theo hướng dẫn sau:'}</p>
                <div class="guide-card ios">
                    <div class="guide-title ios"><i class="fab fa-apple" style="font-size: 20px;" aria-hidden="true"></i> ${window.LANG && window.LANG.zalo_ios_title || 'ĐỐI VỚI IPHONE / IPAD (iOS)'}</div>
                    <div class="step-line"><span class="step-num">1</span> <div>${window.LANG && window.LANG.zalo_ios_step_1 || '<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mở ứng dụng Ảnh, chọn ảnh cần gửi, bấm Chia sẻ và chọn <b>Lưu vào tệp</b>.' : 'Open the Photos app, select the image to send, tap Share and choose <b>Save to Files</b>.') ?>'}</div></div>
                    <div class="guide-img-wrapper"><img src="/static/guide/ios_save_file.jpg" class="guide-img" alt="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lưu vào Tệp iOS' : 'Save to Files iOS') ?>"></div>
                    <div class="step-line"><span class="step-num">2</span> <div>${window.LANG && window.LANG.zalo_ios_step_2 || 'Trong khung chat Zalo, bấm <b>Dấu cộng (+)</b>.'}</div></div>
                    <div class="step-line"><span class="step-num">3</span> <div>${window.LANG && window.LANG.zalo_ios_step_3 || 'Chọn <b>Tài liệu</b> (hoặc File) &rarr; Tìm đến ảnh vừa lưu trong Tệp để gửi.'}</div></div>
                    <div class="guide-img-wrapper"><img src="/static/guide/ios_files_app.jpg" class="guide-img" alt="Chọn file Zalo iOS"></div>
                </div>
                <div class="guide-card android">
                    <div class="guide-title android"><i class="fab fa-android" style="font-size: 20px;" aria-hidden="true"></i> ${window.LANG && window.LANG.zalo_android_title || 'ĐỐI VỚI ĐIỆN THOẠI ANDROID'}</div>
                    <div class="step-line"><span class="step-num">1</span> <div>${window.LANG && window.LANG.zalo_android_step_1 || 'Trong khung chat Zalo, bấm <b>Dấu cộng (+)</b> hoặc <b>3 chấm (...)</b>.'}</div></div>
                    <div class="step-line"><span class="step-num">2</span> <div>${window.LANG && window.LANG.zalo_android_step_2 || 'Chọn mục <b>Tài liệu</b> (Biểu tượng kẹp giấy/thư mục).'}</div></div>
                    <div class="step-line"><span class="step-num">3</span> <div>${window.LANG && window.LANG.zalo_android_step_3 || 'Chọn ảnh cần gửi để gửi dưới dạng File gốc.'}</div></div>
                    <div class="guide-img-wrapper"><img src="/static/guide/zalo_android.jpg" class="guide-img" alt="Tài liệu Zalo Android"></div>
                </div>
                <div class="guide-card pc" style="margin-bottom: 0;">
                    <div class="guide-title pc"><i class="fas fa-desktop" aria-hidden="true"></i> ${window.LANG && window.LANG.zalo_pc_title || 'ĐỐI VỚI MÁY TÍNH (ZALO PC)'}</div>
                    <div class="step-line"><span class="step-num">1</span> <div>${window.LANG && window.LANG.zalo_pc_step_1 || 'Bấm vào biểu tượng <b>Đính kèm file</b> (Hình ghim giấy).'}</div></div>
                    <div class="step-line"><span class="step-num">2</span> <div>${window.LANG && window.LANG.zalo_pc_step_2 || 'Chọn <b>Chọn File</b> và tìm đến ảnh cần gửi.'}</div></div>
                    <div class="guide-img-wrapper"><img src="/static/guide/files_pc.jpg" class="guide-img" alt="File Zalo PC"></div>
                </div>
            </div>`,
            width: '750px', confirmButtonText: (window.LANG && window.LANG.got_it || 'Đã hiểu')
        });
    };

    window.openComposeModal = () => { window.hideImageToolbar(); document.getElementById('news_compose_modal').style.display = 'flex'; };
    window.closeComposeModal = () => { 
        window.hideImageToolbar(); document.getElementById('news_compose_modal').style.display = 'none'; 
        window.tvtl_uploadedHlsVideos.forEach(url => window.deleteHlsFromServer(url));
        window.tvtl_uploadedHlsVideos = []; window.tvtl_selectedAttachments = []; 
        document.getElementById('inp_news_title').value = ''; 
        if(document.getElementById('tvtl_editor')) document.getElementById('tvtl_editor').innerHTML = ''; 
        if(window.btnRemoveThumb) window.btnRemoveThumb.click();
        window.renderFileList();
    };

    window.renderFileList = function() {
        const fileListUi = document.getElementById('multi_file_list'); if(!fileListUi) return;
        fileListUi.innerHTML = '';
        window.tvtl_selectedAttachments.forEach((file, index) => {
            const isVideo = file.type.startsWith('video/'); const icon = isVideo ? '<i class="fas fa-file-video" style="color:var(--danger-color);" aria-hidden="true"></i>' : '<i class="fas fa-file-word" style="color:var(--primary-color);" aria-hidden="true"></i>';
            fileListUi.innerHTML += `<div class="file-list-item"><span>${icon} ${file.name} <small>(${(file.size/1024/1024).toFixed(2)} MB)</small></span><button class="win-btn-close" onclick="window.removeAttachment(${index})" type="button"><i class="fas fa-times" aria-hidden="true"></i></button></div>`;
        });
        window.tvtl_uploadedHlsVideos.forEach((url, index) => {
            fileListUi.innerHTML += `<div class="file-list-item"><span><i class="fas fa-satellite-dish" style="color:#10b981;" aria-hidden="true"></i> Video HLS HD (Đã nén xong)</span><button class="win-btn-close" type="button" onclick="window.removeLocalVideo(${index})"><i class="fas fa-times" aria-hidden="true"></i></button></div>`;
        });
    };

    window.removeAttachment = function(index) { window.tvtl_selectedAttachments.splice(index, 1); window.renderFileList(); };
    window.removeLocalVideo = function(index) { window.deleteHlsFromServer(window.tvtl_uploadedHlsVideos[index]); window.tvtl_uploadedHlsVideos.splice(index, 1); window.renderFileList(); };

    // --- ĐÃ BỔ SUNG LOGIC APP FLUTTER & PC TOOL Ở ĐÂY ---
    window.handleUnifiedAttachmentClick = function() {
        window.tvtl_isAppMode = typeof window.LG3Bridge !== 'undefined';
        
        // KIỂM TRA THÊM: Có phải App Mode KHÔNG VÀ có được Hardware hỗ trợ KHÔNG?
        if (window.tvtl_isAppMode && window.tvtl_hardware_supported === true) {
            Swal.fire({
                title: (window.LANG && window.LANG.choose_attachment_method || 'Chọn phương thức đính kèm'), text: (window.LANG && window.LANG.system_running_on_app || 'Hệ thống đang chạy trên LG3 App.'),
                customClass: window.WINUI_SWAL_CLASS, background: 'var(--bg-card)', color: 'var(--text-main)',
                showCancelButton: true, showDenyButton: true,
                confirmButtonText: '<i class="fas fa-mobile-alt" aria-hidden="true"></i> ' + (window.LANG && window.LANG.compress_hls_mobile || 'Nén HLS (Bằng Điện thoại)'), confirmButtonColor: '#10b981',
                denyButtonText: '<i class="fas fa-file-alt" aria-hidden="true"></i> ' + (window.LANG && window.LANG.static_file_web || 'Tệp tĩnh / Video (Web)'), denyButtonColor: 'var(--primary-color)',
                cancelButtonText: (window.LANG && window.LANG.cancel || 'Hủy')
            }).then((result) => {
                if (result.isConfirmed) { 
                    window.LG3Bridge.postMessage(JSON.stringify({ action: "START_HLS_RENDER" }));
                } 
                else if (result.isDenied) { document.getElementById('inp_news_files').click(); }
            });
        } 
        // NẾU LÀ APP NHƯNG MÁY CÙI (arm-v7a / x86), HOẶC CÓ TOOL PC
        else if (window.tvtl_isLocalToolOnline) {
            Swal.fire({
                title: (window.LANG && window.LANG.choose_attachment_method || 'Chọn phương thức đính kèm'), text: (window.LANG && window.LANG.system_detected_pc_tool || 'Hệ thống phát hiện LG3 Video Tool đang chạy.'),
                customClass: window.WINUI_SWAL_CLASS, background: 'var(--bg-card)', color: 'var(--text-main)',
                showCancelButton: true, showDenyButton: true,
                confirmButtonText: '<i class="fas fa-desktop" aria-hidden="true"></i> ' + (window.LANG && window.LANG.video_hls_pc || 'Video HLS (Tool PC)'), confirmButtonColor: '#10b981',
                denyButtonText: '<i class="fas fa-file-alt" aria-hidden="true"></i> ' + (window.LANG && window.LANG.static_file_web || 'Tệp tĩnh / Video (Web)'), denyButtonColor: 'var(--primary-color)',
                cancelButtonText: (window.LANG && window.LANG.cancel || 'Hủy')
            }).then((result) => {
                if (result.isConfirmed) { window.triggerLocalRenderTool(); } else if (result.isDenied) { document.getElementById('inp_news_files').click(); }
            });
        } else { 
            // VÀO ĐÂY NẾU: Máy yếu KHÔNG HỖ TRỢ và Tool PC cũng KHÔNG CÓ
            document.getElementById('inp_news_files').click(); 
        }
    };

    // --- CÁC HÀM NÀY FLUTTER SẼ GỌI NGƯỢC LẠI WEB ---
    window.onFlutterRenderStart = function() {
        Swal.fire({
            title: (window.LANG && window.LANG.processing_video_app || `Đang xử lý Video trên App...`), customClass: window.WINUI_SWAL_CLASS,
            html: `<div style="font-size:13px; color:var(--text-muted); margin-bottom:8px;" id="local-status-text">${window.LANG && window.LANG.analyzing_video || 'Đang phân tích Video...'}</div>
                   <div style="width: 100%; background: var(--bg-hover); border-radius: 8px; overflow: hidden; height: 15px; border: 1px solid var(--border-color);">
                   <div id="local-video-progress" style="width: 0%; height: 100%; background: var(--primary-color); transition: width 0.3s;"></div></div>
                   <div id="local-video-progress-percent" style="margin-top: 8px; font-weight: bold; color: var(--primary-color);">0%</div>`,
            allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false
        });
    };

    window.onFlutterRenderProgress = function(percent, statusText) {
        if(document.getElementById('local-video-progress')) {
            document.getElementById('local-status-text').innerText = statusText;
            document.getElementById('local-video-progress').style.width = percent + '%';
            document.getElementById('local-video-progress-percent').innerText = percent + '%';
            if (percent === 100) {
                 document.getElementById('local-video-progress').style.background = '#10b981'; 
                 document.getElementById('local-video-progress-percent').style.color = '#10b981';
            }
        }
    };

    window.onFlutterRenderComplete = function(finalUrl) {
        Swal.close();
        window.tvtl_uploadedHlsVideos.push(finalUrl); 
        window.renderFileList();
        Toastify({text: window.LANG && window.LANG.processing_upload_success || "Xử lý & Tải lên từ App thành công!", style: {background: "#10b981"}}).showToast();
    };

    window.onFlutterRenderError = function(errorMsg) {
        Swal.close();
        Swal.fire({title: window.LANG && window.LANG.error || 'Lỗi', text: errorMsg, icon: 'error', customClass: window.WINUI_SWAL_CLASS});
    };
    // -------------------------------------------------------------

    window.triggerLocalRenderTool = async function() {
        try {
            const renderRes = await fetch('http://127.0.0.1:19000/start-render', { method: 'POST', body: JSON.stringify({ domain: window.location.hostname }) });
            const renderData = await renderRes.json();
            if(renderData.status === 'started') {
                Swal.fire({
                    title: (window.LANG && window.LANG.processing_video_pc || `Đang xử lý Video trên PC...`), customClass: window.WINUI_SWAL_CLASS,
                    html: `<div style="font-size:13px; color:var(--text-muted); margin-bottom:8px;" id="local-status-text">${window.LANG && window.LANG.init_hardware || 'Đang khởi tạo phần cứng...'}</div><div style="width: 100%; background: var(--bg-hover); border-radius: 8px; overflow: hidden; height: 15px; border: 1px solid var(--border-color);"><div id="local-video-progress" style="width: 0%; height: 100%; background: var(--primary-color); transition: width 0.3s;"></div></div><div id="local-video-progress-percent" style="margin-top: 8px; font-weight: bold; color: var(--primary-color);">0%</div>`,
                    allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false
                });

                let isDone = false;
                while(!isDone) {
                    await new Promise(r => setTimeout(r, 1000));
                    try {
                        const statRes = await fetch('http://127.0.0.1:19000/status'); const statData = await statRes.json();
                        if(statData.status === 'rejected') { isDone = true; Swal.fire({title: (window.LANG && window.LANG.cancelled || 'Đã hủy'), text: (window.LANG && window.LANG.permission_denied_or_file_error || 'Từ chối quyền hoặc lỗi file.'), icon: 'error', customClass: window.WINUI_SWAL_CLASS}); break; }
                        
                        document.getElementById('local-status-text').innerText = window.LANG && window.LANG.encoding_m3u8 || 'Đang Encode m3u8...';
                        document.getElementById('local-video-progress').style.width = statData.percent + '%';
                        document.getElementById('local-video-progress-percent').innerText = statData.percent + '%';

                        if(statData.status === 'completed') {
                            const taskId = statData.task_id; const files = statData.files;
                            document.getElementById('local-video-progress').style.background = '#3b82f6'; document.getElementById('local-video-progress-percent').style.color = '#3b82f6';
                            let finalUrl = ''; const MAX_CONCURRENT = 4; let currentIndex = 0; let uploadedCount = 0; let totalBytes = 0; const startTime = Date.now();

                            document.getElementById('local-status-text').innerHTML = (window.LANG && window.LANG.syncing_vps || `Đang đồng bộ VPS: `) + `<span id="up-speed" style="color:#10b981; font-weight:bold;">0.0 MB/s</span> | ETA: <span id="up-eta" style="color:#f59e0b; font-weight:bold;">${window.LANG && window.LANG.calculating || 'Đang tính...'}</span>`;

                            const uploadWorker = async () => {
                                while (currentIndex < files.length) {
                                    const i = currentIndex++; const fileName = files[i];
                                    try {
                                        const fileBlob = await fetch('http://127.0.0.1:19000/download?task=' + taskId + '&file=' + fileName).then(r => r.blob());
                                        const fd = new FormData(); fd.append('task_id', taskId); fd.append('files[]', fileBlob, fileName);
                                        const uploadRes = await fetch('api/upload_hls_pc.php', { method: 'POST', body: fd }); const uploadJson = await uploadRes.json();
                                        if(uploadJson.status === 'success') finalUrl = uploadJson.url;
                                        
                                        uploadedCount++; totalBytes += fileBlob.size;
                                        const elapsedSec = (Date.now() - startTime) / 1000; const speedBps = totalBytes / elapsedSec; const speedMbps = (speedBps / (1024 * 1024)).toFixed(1);
                                        const avgFileSize = totalBytes / uploadedCount; const remainingFiles = files.length - uploadedCount; const etaSec = Math.round((remainingFiles * avgFileSize) / (speedBps || 1));
                                        
                                        const upPercent = Math.round((uploadedCount / files.length) * 100);
                                        document.getElementById('local-video-progress').style.width = upPercent + '%'; document.getElementById('local-video-progress-percent').innerText = `${upPercent}% (${uploadedCount}/${files.length} file)`;
                                        document.getElementById('up-speed').innerText = `${speedMbps} MB/s`;
                                        if (etaSec > 60) document.getElementById('up-eta').innerText = `${Math.floor(etaSec/60)}p ${etaSec%60}s`; else document.getElementById('up-eta').innerText = `${etaSec}s`;
                                    } catch (err) { console.error(err); }
                                }
                            };

                            const workers = []; for (let w = 0; w < Math.min(MAX_CONCURRENT, files.length); w++) workers.push(uploadWorker());
                            await Promise.all(workers);

                            isDone = true; Swal.close(); window.tvtl_uploadedHlsVideos.push(finalUrl); window.renderFileList();
                            Toastify({text: window.LANG && window.LANG.render_upload_success || "Render & Upload Thành công!", style: {background: "#10b981"}}).showToast();
                            fetch('http://127.0.0.1:19000/cleanup?task=' + taskId);
                        }
                    } catch(e) {}
                }
            } else if (renderData.status === 'rejected') { Toastify({text: window.LANG && window.LANG.cancelled_or_refused_connection || "Đã hủy hoặc từ chối kết nối", style: {background: "var(--danger-color)"}}).showToast(); }
        } catch (e) { Toastify({text: window.LANG && window.LANG.lost_connection_to_pc_app || "Mất kết nối với App PC", style: {background: "var(--danger-color)"}}).showToast(); }
    };

    window.openNewsDetail = function(id, pushUrl = true) {
        const item = window.tvtl_cachedNewsData.find(x => x.id == id); if (!item) return;
        document.getElementById('news_master_view').style.display = 'none'; document.getElementById('btn_group_master').style.display = 'none'; document.getElementById('btn_back_to_list').style.display = 'inline-block'; document.getElementById('news_detail_view').style.display = 'block';
        const catColors = { 'thong_bao': '#ef4444', 'tin_tuc': '#10b981', 'tuyen_sinh': '#f59e0b' }; const catLabels = { 'thong_bao': (window.LANG && window.LANG.notifications || 'Thông báo'), 'tin_tuc': (window.LANG && window.LANG.news_events || 'Tin tức'), 'tuyen_sinh': (window.LANG && window.LANG.admissions || 'Tuyển sinh') };
        document.getElementById('detail_category_badge').innerHTML = `<span style="background: ${catColors[item.category] || '#0ea5e9'}; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase;">${catLabels[item.category] || (window.LANG && window.LANG.bulletin || 'Bản tin')}</span>`;
        document.getElementById('detail_title').innerText = item.title; document.getElementById('detail_date').innerHTML = `<i class="far fa-clock" aria-hidden="true"></i> ${item.created_at}`; document.getElementById('detail_author').innerHTML = `<i class="fas fa-user-edit" aria-hidden="true"></i> ${item.full_name}`;
        
        let actionBtns = ''; if (window.myCurrentRole === 'ADMIN' || window.myCurrentId == item.created_by) { actionBtns = `<button onclick="window.deleteNews(${item.id})" class="win-btn win-btn-danger" style="padding: 4px 12px; font-size: 12px; margin:0;"><i class="fas fa-trash" aria-hidden="true"></i> ${window.LANG && window.LANG.delete_post || 'Xóa bài'}</button>`; }
        document.getElementById('detail_action_btns').innerHTML = actionBtns; document.getElementById('detail_content').innerHTML = item.content;

        const attachArea = document.getElementById('detail_attachment_area'); const attachTitle = document.getElementById('attachment_title'); attachArea.innerHTML = '';
        let files = []; if (item.attachment_url) { try { files = JSON.parse(item.attachment_url); } catch(e) { files = [item.attachment_url]; } }
        if (files && files.length > 0) {
            attachTitle.style.display = 'block';
            files.forEach(url => {
                const fileExt = url.split('.').pop().toLowerCase();
                if (fileExt === 'pdf') { attachArea.innerHTML += `<iframe src="${url}" width="100%" height="600px" style="border:1px solid var(--border-color); border-radius:8px;"></iframe>`; } 
                else if (['doc', 'docx', 'xls', 'xlsx'].includes(fileExt)) { attachArea.innerHTML += `<div style="border:1px solid var(--border-color); border-radius:8px; padding:15px; background:var(--bg-hover); display: flex; justify-content: space-between; align-items: center;"><div><strong style="color: var(--primary-color);"><i class="fas fa-file-word" aria-hidden="true"></i> ${url.split('/').pop()}</strong></div><a href="${url}" download class="win-btn" style="margin: 0;"><i class="fas fa-download" aria-hidden="true"></i> ${window.LANG && window.LANG.download || 'Tải xuống'}</a></div>`; } 
            });
        } else { attachTitle.style.display = 'none'; }
        
        setTimeout(() => { window.initWinUIPlayers(); }, 150); window.scrollTo({ top: 0, behavior: 'smooth' });
        if (pushUrl) { const newUrl = window.location.pathname + '?id=' + id; window.history.pushState({ url: window.location.origin + newUrl }, '', newUrl); }
    };

    window.backToList = function(pushUrl = true) {
        document.getElementById('news_detail_view').style.display = 'none'; document.getElementById('btn_back_to_list').style.display = 'none'; document.getElementById('btn_group_master').style.display = 'flex'; document.getElementById('news_master_view').style.display = 'block';
        if (pushUrl) { const newUrl = window.location.pathname; window.history.pushState({ url: window.location.origin + newUrl }, '', newUrl); }
    };

    window.deleteNews = async function(newsId) {
        Swal.fire({
            title: (window.LANG && window.LANG.confirm_delete || 'Xác nhận xóa'), text: `${window.LANG && window.LANG.delete_post || 'Xóa bài'} ${window.currentLang === 'en' ? 'this post? All attached Videos/Images will be cleared.' : 'viết này? Toàn bộ Video/Ảnh đi kèm sẽ bị dọn sạch.'}`, icon: 'warning',
            customClass: window.WINUI_SWAL_CLASS, showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: (window.LANG && window.LANG.yes_delete || 'Có, Xóa'), cancelButtonText: (<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Hủy'" : "'Cancel'") ?>)
        }).then(async (result) => {
            if (result.isConfirmed) {
                const formData = new FormData(); formData.append('news_id', newsId);
                try { const res = await fetch('api/news_api.php?action=delete', { method: 'POST', body: formData }); if ((await res.json()).status === 'success') { window.backToList(); window.loadNews(); } } catch(e) {}
            }
        });
    };

    window.loadNews = async () => {
        try {
            const res = await fetch(`api/news_api.php?action=list&category=${window.tvtl_currentCategoryFilter}`); const json = await res.json();
            if (json.status === 'success') {
                window.tvtl_cachedNewsData = json.data; let html = '';
                const catColors = { 'thong_bao': '#ef4444', 'tin_tuc': '#10b981', 'tuyen_sinh': '#f59e0b' }; const catLabels = { 'thong_bao': (window.LANG && window.LANG.notifications || 'Thông báo'), 'tin_tuc': (window.LANG && window.LANG.news_events || 'Tin tức'), 'tuyen_sinh': (window.LANG && window.LANG.admissions || 'Tuyển sinh') };
                json.data.forEach(item => {
                    let thumb = item.thumbnail_url; if (!thumb) { const div = document.createElement('div'); div.innerHTML = item.content; const img = div.querySelector('img'); if (img && img.src) thumb = img.src; else { const fallbacks = { 'thong_bao': 'https://placehold.co/600x400/1e293b/ffffff?text=TH%C3%94NG+B%C3%81O', 'tin_tuc': 'https://placehold.co/600x400/0ea5e9/ffffff?text=TIN+T%E1%BB%A8C', 'tuyen_sinh': 'https://placehold.co/600x400/f59e0b/ffffff?text=TUY%E1%BB%82N+SINH' }; thumb = fallbacks[item.category] || fallbacks['tin_tuc']; } }
                    html += `<div class="news-card-compact" onclick="window.openNewsDetail(${item.id})"><img src="${thumb}" class="news-thumb" alt="Thumbnail"><div class="news-card-body"><span style="font-size: 11px; font-weight: 800; color: ${catColors[item.category] || '#0ea5e9'}; text-transform: uppercase;"><i class="fas fa-tags" aria-hidden="true"></i> ${catLabels[item.category] || (window.LANG && window.LANG.bulletin || 'Bản tin')}</span><div class="news-title-compact">${item.title}</div><div style="font-size: 12px; color: var(--text-muted); margin-top: auto;"><i class="far fa-clock" aria-hidden="true"></i> ${item.created_at.split(' ')[0]} | ${item.views || 0} ${window.LANG && window.LANG.views || 'lượt xem'}</div></div></div>`;
                });
                const listWrapper = document.getElementById('news_list_wrapper'); if(listWrapper) listWrapper.innerHTML = html === '' ? `<div style="grid-column: 1/-1; text-align:center; padding:40px; color:var(--text-muted);"><i class="fas fa-box-open fa-3x" style="opacity:0.3; margin-bottom:15px;" aria-hidden="true"></i><br>${window.LANG && window.LANG.no_articles_in_category || 'Chưa có bài viết nào trong mục này!'}</div>` : html;
                const urlParams = new URLSearchParams(window.location.search); const articleId = urlParams.get('id'); if (articleId) { window.openNewsDetail(articleId, false); }
            }
        } catch (e) {}
    };

    // =========================================================
    // VÒNG ĐỜI TRANG (LIFECYCLE SPA) ĐÃ ĐƯỢC FIX LỖI "ĐƠ NÚT BẤM"
    // =========================================================
    window.pageDestroy = function() {
        if (window.pcToolPingInterval) { clearInterval(window.pcToolPingInterval); window.pcToolPingInterval = null; }
        const btnPost = document.getElementById('btn_post_news'); if (btnPost) btnPost.onclick = null;
        if (window.hideImageToolbar) window.hideImageToolbar();
        const editor = document.getElementById('tvtl_editor'); if (editor) editor.innerHTML = ''; 
    };

    window.pageInit = function() {
        // Luôn đặt lại Array trống khi load trang mới để tránh rác
        window.tvtl_selectedAttachments = [];
        window.tvtl_uploadedHlsVideos = [];
        window.tvtl_isLocalToolOnline = false;
        window.tvtl_pastedThumbnailFile = null;
        window.tvtl_cachedNewsData = [];
        window.tvtl_currentCategoryFilter = 'all';

        // XỬ LÝ BADGE & PING THÔNG MINH
        const badge = document.getElementById('tool_pc_badge');
        const btnDownload = document.querySelector('a[href="static/LG3VIDEOTOOL.exe"]');
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

        const checkPcStatus = async () => {
            if (window.tvtl_isAppMode) return; // Nếu App Flutter đã tiếp quản thì cấm ping
            if (!badge) return;
            try {
                const res = await fetch('http://127.0.0.1:19000/ping');
                if (res.ok) { 
                    const data = await res.json(); 
                    badge.innerHTML = `<span class="status-dot online"></span> ${data.version || 'Đã kết nối App PC'}`; 
                    window.tvtl_isLocalToolOnline = true; 
                    badge.style.display = 'inline-flex';
                } else throw new Error();
            } catch (e) { 
                badge.innerHTML = '<span class="status-dot offline"></span> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tool PC: Ngoại tuyến' : 'PC Tool: Offline') ?>'; 
                window.tvtl_isLocalToolOnline = false; 
            }
            badge.className = 'tool-pc-badge'; 
        };

        if (isMobile) {
            // ĐANG DÙNG ĐIỆN THOẠI (Web hoặc App)
            // 1. Ẩn nút tải .exe vì đt không chạy được
            if (btnDownload) btnDownload.style.display = 'none';
            
            // 2. Tạm ẩn Badge để không bị hiện chữ "Ngoại tuyến" xấu xí.
            if (badge) {
                badge.style.display = 'none';
                
                // Mở luồng theo dõi: Nếu Flutter tiêm biến "tvtl_isAppMode" vào, lập tức hiện lại Badge Android
                let checkAppMode = setInterval(() => {
                    if (window.tvtl_isAppMode) {
                        badge.style.display = 'inline-flex';
                        clearInterval(checkAppMode);
                    }
                }, 500);
                setTimeout(() => clearInterval(checkAppMode), 5000); // Hủy theo dõi sau 5s nếu chỉ là trình duyệt Web thường
            }
            // HOÀN TOÀN KHÔNG CHẠY INTERVAL PING! (Trị dứt điểm vụ 5s bị đè mất chữ)
        } else {
            // ĐANG DÙNG MÁY TÍNH: Kích hoạt Ping bình thường
            checkPcStatus();
            window.pcToolPingInterval = setInterval(checkPcStatus, 3000);
        }

        // Kích hoạt Editor (Giữ nguyên phần dưới của ông từ đoạn này trở đi)
        const customEditorNode = document.getElementById('tvtl_editor');
        if (customEditorNode) {
            customEditorNode.addEventListener('blur', () => { let sel = window.getSelection(); if (sel.rangeCount > 0) window.savedSelection = sel.getRangeAt(0); });
            customEditorNode.addEventListener('paste', async (e) => {
                const items = (e.clipboardData || window.clipboardData).items;
                for (let index in items) {
                    const item = items[index];
                    if (item.kind === 'file' && item.type.startsWith('image/')) {
                        e.preventDefault(); 
                        try {
                            const validBase64 = await window.validateImageClientSide(item.getAsFile());
                            let imgHTML = '<img src="' + validBase64 + '" align="left" width="100%" style="max-width: 100%; display: inline-block; vertical-align: top; margin: 5px 15px 5px 0; border-radius: 6px; float: left;" />';
                            document.execCommand('insertHTML', false, imgHTML + '&nbsp;');
                        } catch (err) { Toastify({ text: err, duration: 5000, gravity: "bottom", position: "center", style: { background: "var(--danger-color)" } }).showToast(); }
                    }
                }
            });
            customEditorNode.addEventListener('click', function(e) { if (e.target.tagName === 'IMG') { window.showImageToolbar(e.target); } else { window.hideImageToolbar(); } });
            customEditorNode.addEventListener('scroll', function() { if(window.currentSelectedImage) window.updateImageToolbarPosition(); });
            customEditorNode.addEventListener('input', window.hideImageToolbar);
        }

        const modalBody = document.getElementById('modal_body_scroll');
        if (modalBody) modalBody.addEventListener('scroll', function() { if(window.currentSelectedImage) window.updateImageToolbarPosition(); });

        // Kích hoạt Dropzone Ảnh đại diện
        const dropzone = document.getElementById('thumbnail_dropzone');
        if (dropzone) {
            dropzone.onclick = (e) => { if(e.target.id !== 'btn_remove_thumb') document.getElementById('inp_thumbnail_file').click(); };
            document.getElementById('inp_thumbnail_file').onchange = (e) => { if(e.target.files[0]) handleThumbnailFile(e.target.files[0]); };
            dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.classList.add('dragover'); });
            dropzone.addEventListener('dragleave', (e) => { e.preventDefault(); dropzone.classList.remove('dragover'); });
            dropzone.addEventListener('drop', async (e) => { e.preventDefault(); dropzone.classList.remove('dragover'); const file = e.dataTransfer.files[0]; if (file && file.type.startsWith('image/')) handleThumbnailFile(file); });
        }

        async function handleThumbnailFile(file) {
            try {
                if (file.name && /^z\d{10,}_/i.test(file.name)) throw window.LANG && window.LANG.blurred_zalo_file || "File Zalo mờ. Hãy gửi dạng Tài liệu!";
                window.tvtl_pastedThumbnailFile = file;
                const reader = new FileReader(); reader.onload = (e) => { const previewThumb = document.getElementById('thumbnail_preview'); previewThumb.src = e.target.result; previewThumb.style.display = 'inline-block'; document.getElementById('btn_remove_thumb').style.display = 'inline-block'; }; reader.readAsDataURL(file);
            } catch(err) { Toastify({ text: err, duration: 5000, gravity: "bottom", position: "center", style: { background: "var(--danger-color)" } }).showToast(); document.getElementById('inp_thumbnail_file').value = ''; }
        }
        
        const btnRemoveThumb = document.getElementById('btn_remove_thumb');
        if(btnRemoveThumb) { btnRemoveThumb.onclick = (e) => { e.stopPropagation(); window.tvtl_pastedThumbnailFile = null; document.getElementById('inp_thumbnail_file').value = ''; document.getElementById('thumbnail_preview').style.display = 'none'; btnRemoveThumb.style.display = 'none'; }; }

        const inpFiles = document.getElementById('inp_news_files');
        if (inpFiles) {
            inpFiles.onchange = async (e) => { for (let i = 0; i < e.target.files.length; i++) window.tvtl_selectedAttachments.push(e.target.files[i]); window.renderFileList(); inpFiles.value = ''; };
        }

        // Filter danh mục tin tức
        const filterBtns = document.querySelectorAll('.news-filter-btn');
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterBtns.forEach(b => { b.classList.remove('win-btn'); b.classList.add('win-btn-secondary'); b.style.fontWeight = 'normal'; });
                this.classList.remove('win-btn-secondary'); this.classList.add('win-btn'); this.style.fontWeight = 'bold';
                window.tvtl_currentCategoryFilter = this.getAttribute('data-category');
                const listWrapper = document.getElementById('news_list_wrapper');
                if (listWrapper) listWrapper.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:30px;"><div class="win11-spinner" style="width: 30px; height: 30px; border-width: 4px; margin-bottom:15px; color: var(--primary-color);"></div></div>';
                window.loadNews();
            });
        });

        // Xử lý Gửi bài viết
        const btnPost = document.getElementById('btn_post_news');
        if (btnPost) {
            btnPost.onclick = async () => {
                const title = document.getElementById('inp_news_title').value.trim(); const category = document.getElementById('sel_news_category').value;
                const editorNode = document.getElementById('tvtl_editor'); let contentHTML = editorNode ? editorNode.innerHTML.trim() : '';

                if (!title || contentHTML === '' || contentHTML === '<br>') { Toastify({ text: window.LANG && window.LANG.enter_title_and_content || "Nhập tiêu đề và nội dung bài viết!", duration: 2500, gravity: "bottom", position: "center", style: { background: "var(--danger-color)" } }).showToast(); return; }
                if (contentHTML && !contentHTML.startsWith('<')) contentHTML = '<p>' + contentHTML + '</p>';

                let normalFiles = []; let videoFilesToChunk = [];
                window.tvtl_selectedAttachments.forEach(f => { if (f.type.startsWith('video/')) videoFilesToChunk.push(f); else normalFiles.push(f); });

                btnPost.disabled = true; btnPost.innerHTML = '<div class="win11-spinner" style="width:16px; height:16px; border-width:2px; border-top-color:#fff; margin-right:6px;"></div> ' + (window.LANG && window.LANG.processing || 'Đang xử lý...');

                // TẢI LÊN VIDEO WEB FALLBACK NẾU APP PC OFFLINE
                if (videoFilesToChunk.length > 0) {
                    for (let v = 0; v < videoFilesToChunk.length; v++) {
                        const file = videoFilesToChunk[v]; const chunkSize = 8 * 1024 * 1024; const totalChunks = Math.ceil(file.size / chunkSize); const uniqueFileName = Date.now() + '_' + file.name.replace(/[^a-zA-Z0-9.]/g, ''); 
                        
                        Swal.fire({
                            title: ((window.LANG && window.LANG.sending_video) || `Đang gửi Video `) + `${v+1}/${videoFilesToChunk.length}`, customClass: window.WINUI_SWAL_CLASS,
                            html: `<div style="font-size:13px; color:var(--text-muted); margin-bottom:8px;" id="upload-status-text">${window.LANG && window.LANG.uploading_to_server || 'Đang tải lên Server...'}</div><div style="width: 100%; background: var(--bg-hover); border-radius: 8px; overflow: hidden; height: 15px; border: 1px solid var(--border-color);"><div id="video-progress" style="width: 0%; height: 100%; background: var(--primary-color); transition: width 0.3s;"></div></div><div id="video-progress-percent" style="margin-top: 8px; font-weight: bold; color: var(--primary-color);">0%</div>`,
                            allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false
                        });

                        let taskIdFromServer = null;
                        for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                            const start = chunkIndex * chunkSize; const chunk = file.slice(start, Math.min(start + chunkSize, file.size));
                            const fd = new FormData(); fd.append('chunk_data', chunk); fd.append('file_name', uniqueFileName); fd.append('chunk_index', chunkIndex); fd.append('total_chunks', totalChunks);
                            let uploadedSuccess = false;
                            while (!uploadedSuccess) {
                                try {
                                    const res = await fetch('api/upload_chunk_video.php', { method: 'POST', body: fd }); const chunkJson = await res.json();
                                    if (chunkJson.status === 'success' || chunkJson.status === 'processing') {
                                        uploadedSuccess = true; const percent = Math.round(((chunkIndex + 1) / totalChunks) * 100);
                                        document.getElementById('video-progress').style.width = percent + '%'; document.getElementById('video-progress-percent').innerText = percent + '%';
                                        if (chunkIndex === totalChunks - 1) taskIdFromServer = chunkJson.task_id;
                                    } else { throw new Error(window.LANG && window.LANG.data_chunk_error || "Lỗi khối dữ liệu"); }
                                } catch (e) { document.getElementById('upload-status-text').innerHTML = `<span style="color:var(--danger-color)">${window.LANG && window.LANG.network_disconnected_reconnecting || 'Mất kết nối mạng! Đang kết nối lại...'}</span>`; await new Promise(r => setTimeout(r, 3000)); }
                            }
                        }

                        if (taskIdFromServer) {
                            document.getElementById('upload-status-text').innerHTML = `<b>${window.LANG && window.LANG.exporting_hls || 'Đang xuất Bản HLS Đa Luồng...'}</b>`;
                            document.getElementById('video-progress').style.background = '#10b981'; document.getElementById('video-progress-percent').style.color = '#10b981'; document.getElementById('video-progress').style.width = '0%'; document.getElementById('video-progress-percent').innerText = '0%';

                            let isRenderDone = false;
                            while (!isRenderDone) {
                                await new Promise(r => setTimeout(r, 2500));
                                try {
                                    const pollRes = await fetch('api/check_video_progress.php?task_id=' + taskIdFromServer); const pollData = await pollRes.json();
                                    if (pollData.status === 'processing') {
                                        document.getElementById('video-progress').style.width = pollData.percent + '%'; document.getElementById('video-progress-percent').innerText = pollData.percent + '%';
                                    } else if (pollData.status === 'done') {
                                        document.getElementById('video-progress').style.width = '100%'; document.getElementById('video-progress-percent').innerText = window.LANG && window.LANG.completed_100_percent || '100% (Hoàn tất)';
                                        window.tvtl_uploadedHlsVideos.push(pollData.url); isRenderDone = true; await new Promise(r => setTimeout(r, 1000)); 
                                    }
                                } catch (e) { }
                            }
                        }
                    }
                    Swal.close(); 
                }

                btnPost.innerHTML = '<div class="win11-spinner" style="width:16px; height:16px; border-width:2px; border-top-color:#fff; margin-right:6px;"></div> ' + (window.LANG && window.LANG.saving_post || 'Đang lưu bài...');
                
                let finalContent = contentHTML;
                if (window.tvtl_uploadedHlsVideos.length > 0) {
                    finalContent += '<br><div class="hls-video-container">';
                    window.tvtl_uploadedHlsVideos.forEach(m3u8Url => { finalContent += `<video class="hls-player"><source src="${m3u8Url}" type="application/x-mpegURL"></video>`; });
                    finalContent += '</div>';
                }

                const mainFormData = new FormData();
                mainFormData.append('title', title); mainFormData.append('content', finalContent); mainFormData.append('category', category);
                normalFiles.forEach(f => mainFormData.append('attachments[]', f)); if (window.tvtl_pastedThumbnailFile) mainFormData.append('thumbnail', window.tvtl_pastedThumbnailFile);

                try {
                    const res = await fetch('api/news_api.php?action=add', { method: 'POST', body: mainFormData }); const json = await res.json();
                    if (json.status === 'success') {
                        window.tvtl_uploadedHlsVideos = []; 
                        document.getElementById('inp_news_title').value = ''; if (editorNode) editorNode.innerHTML = ''; if (window.btnRemoveThumb) window.btnRemoveThumb.click(); window.tvtl_selectedAttachments = []; window.renderFileList(); 
                        document.getElementById('news_compose_modal').style.display = 'none'; document.querySelector('[data-category="all"]').click(); Toastify({ text: "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đăng bài' : 'Create post') ?> thành công!", duration: 3000, style: { background: "#10b981" } }).showToast();
                    } else { Toastify({ text: json.msg, duration: 6000, gravity: "bottom", position: "center", style: { background: "var(--danger-color)" } }).showToast(); }
                } catch(e) {}
                btnPost.disabled = false; btnPost.innerHTML = '<i class="fas fa-paper-plane" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đăng bài ngay' : 'Post now') ?>';
            };
        }

        window.loadNews();
    };
</script>