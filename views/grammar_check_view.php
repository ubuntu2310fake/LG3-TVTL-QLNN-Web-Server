<style>
/* --- BỐ CỤC PC & ĐỒNG BỘ WINUI 3.0 --- */
.gc-wrapper { 
    background: var(--bg-card, #ffffff); padding: 25px; border-radius: 12px; 
    box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid var(--border-color, #e5e5e5); 
    position: relative; font-family: 'Segoe UI Variable', 'Segoe UI', sans-serif;
}

/* HEADER NGANG (PC Style) */
.gc-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }

/* TIÊU ĐỀ MÀU BLUE */
.gc-title { margin: 0; color: #005fb8; font-weight: 600; font-size: 18px; display: flex; align-items: center; gap: 8px; }
[data-theme="dark"] .gc-title { color: #60a5fa; }

/* KHUNG NHẬP LIỆU (KHÔNG KHÓA - 1 LAYER) */
.gc-editor { 
    width: 100%; min-height: 200px; background: var(--bg-input, #ffffff); color: var(--text-main, #111); 
    border: 1px solid var(--border-color, #d1d5db); padding: 16px; border-radius: 8px; 
    font-size: 15px; outline: none; transition: 0.2s; line-height: 1.6;
    white-space: pre-wrap; word-wrap: break-word; box-sizing: border-box;
}
.gc-editor:focus { border-color: #005fb8; box-shadow: 0 0 0 3px rgba(0, 95, 186, 0.15); }
.gc-editor[contenteditable]:empty::before { content: attr(data-placeholder); color: var(--text-muted, #9ca3af); pointer-events: none; display: block; }

/* FOOTER NGANG (PC Style) */
.gc-footer-row { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; flex-wrap: wrap; gap: 15px; }

/* NÚT QUÉT LỖI (MÀU GREEN CHUẨN) */
.btn-ai-scan { 
    padding: 10px 20px; background: #10b981; color: white; border: none; 
    border-radius: 6px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: 0.15s; 
}
.btn-ai-scan:hover { filter: brightness(1.05); transform: translateY(-1px); }
.btn-ai-scan:active { transform: scale(0.97); }
.btn-ai-scan:disabled { background: var(--bg-input); color: var(--text-muted); border: 1px solid var(--border-color); cursor: not-allowed; transform: none; }

/* GẠCH ĐỎ BÁO LỖI */
.grammar-error {
    border-bottom: 2px wavy #ef4444; background-color: rgba(239, 68, 68, 0.08); 
    cursor: pointer; transition: 0.2s; border-radius: 2px; padding: 0 2px;
}
.grammar-error:hover, .grammar-error.active { background-color: rgba(239, 68, 68, 0.2); }

/* POPUP SỬA LỖI */
.grammarly-popup {
    position: absolute; background: var(--bg-card, #ffffff); border: 1px solid var(--border-color, #e5e5e5); border-radius: 12px; 
    box-shadow: 0 10px 30px rgba(0,0,0,0.15); width: 320px; z-index: 99999;
    display: none; flex-direction: column; overflow: hidden; animation: popupFadeIn 0.2s ease-out;
}
.gp-header { display: flex; justify-content: space-between; padding: 12px 15px; border-bottom: 1px solid var(--border-color, #e5e5e5); font-size: 13px; color: var(--text-muted); font-weight: 600; }
.gp-body { padding: 15px; }
.gp-correction { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; font-size: 16px; }
.gp-wrong { text-decoration: line-through; color: #ef4444; }
.gp-right { color: #10b981; font-weight: 700; }
.gp-explanation { font-size: 13px; color: var(--text-main); margin-bottom: 15px; line-height: 1.5; }
.gp-actions { display: flex; gap: 10px; padding: 0 15px 15px 15px; }
.gp-btn-accept { flex: 1; background: #005fb8; color: #fff; border: none; padding: 8px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.2s;}
.gp-btn-accept:hover { background: #004c99; }
.gp-btn-dismiss { background: transparent; color: var(--text-muted); border: none; padding: 8px 12px; cursor: pointer; font-weight: 600; }
.gp-btn-dismiss:hover { color: var(--text-main); }

/* DROPDOWN CHỌN NGÔN NGỮ */
.custom-select-container { position: relative; width: 220px; z-index: 50;}
.select-selected {
    background-color: var(--bg-input, #ffffff); border: 1px solid var(--border-color, #d1d5db); border-radius: 8px; 
    padding: 0 15px; display: flex; align-items: center; justify-content: space-between;
    font-size: 14px; height: 42px; box-sizing: border-box; color: var(--text-main);
    transition: 0.2s; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}
.select-selected:active { border-color: #005fb8; }
.select-arrow { width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid var(--text-muted); transition: 0.2s; flex-shrink: 0; }
.select-selected.active .select-arrow { transform: rotate(180deg); }
.select-items {
    position: absolute; top: 110%; left: 0; right: 0; z-index: 1000;
    background: var(--bg-card, #ffffff); border: 1px solid var(--border-color, #e5e5e5); border-radius: 8px;
    overflow: hidden; display: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1); animation: fadeIn 0.2s ease;
}
.select-items div { padding: 12px 15px; cursor: pointer; border-bottom: 1px solid var(--border-color, #e5e5e5); font-size: 14px; color: var(--text-main); display: flex; align-items: center; gap: 8px;}
.select-items div:hover { background: rgba(0, 95, 186, 0.05); color: #005fb8; font-weight: 500; }
.select-items div:last-child { border-bottom: none; }
</style>

<div class="gc-wrapper">
    <div class="gc-header-row">
        <h3 class="gc-title"><i class="fas fa-spell-check" aria-hidden="true"></i> <?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Kiểm tra ngữ pháp AI" : "Grammar AI Check") ?></h3>
        <div class="custom-select-container">
            <div id="lang-select-box" class="select-selected" role="button" tabindex="0" aria-haspopup="listbox" onkeypress="if(event.key==='Enter'||event.key===' ') this.click();">
                <span id="txtSelectedLang">
                    <?php if (($_SESSION['lang'] ?? 'vi') === 'en'): ?>
                        <i class="fas fa-language" aria-hidden="true"></i> <?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Tiếng Anh" : "English") ?>
                    <?php else: ?>
                        <i class="fas fa-language" aria-hidden="true"></i> <?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Tiếng Việt" : "Vietnamese") ?>
                    <?php endif; ?>
                </span>
                <div class="select-arrow"></div>
            </div>
            <div id="lang-items" class="select-items" role="listbox">
                <div data-lang="en" data-placeholder="<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Nhập văn bản tiếng Anh..." : "Enter English text...") ?>" role="option" tabindex="0" onkeypress="if(event.key==='Enter'||event.key===' ') this.click();">
                    <i class="fas fa-language" aria-hidden="true"></i> <?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Tiếng Anh" : "English") ?>
                </div>
                <div data-lang="vi" data-placeholder="<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Nhập văn bản tiếng Việt..." : "Enter Vietnamese text...") ?>" role="option" tabindex="0" onkeypress="if(event.key==='Enter'||event.key===' ') this.click();">
                    <i class="fas fa-language" aria-hidden="true"></i> <?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Tiếng Việt" : "Vietnamese") ?>
                </div>
            </div>
        </div>
    </div>

    <div id="gc-editor" class="gc-editor" contenteditable="true" spellcheck="false" data-placeholder="<?= ($_SESSION['lang'] ?? 'vi') === 'en' ? (($_SESSION["lang"] ?? "vi") === "vi" ? "Nhập văn bản tiếng Anh..." : "Enter English text...") : (($_SESSION["lang"] ?? "vi") === "vi" ? "Nhập văn bản tiếng Việt..." : "Enter Vietnamese text...") ?>" role="textbox" aria-multiline="true" aria-label="<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Kiểm tra ngữ pháp AI" : "Grammar AI Check") ?>"></div>

    <div class="gc-footer-row">
        <div id="scan-status" style="font-size: 14px; color: var(--text-muted); font-weight: 500;"><?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Sẵn sàng phân tích" : "Ready to analyze") ?></div>
        <button id="btn-submit-gc" class="btn-ai-scan"><i class="fas fa-magic" aria-hidden="true"></i> <?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Quét lỗi" : "Scan Errors") ?></button>
    </div>

    <div id="grammarly-popup" class="grammarly-popup">
        <div class="gp-header">
            <span><i class="fas fa-tools" aria-hidden="true"></i> <?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Đề xuất sửa" : "Suggested Correction") ?></span>
            <i id="gp-btn-close" class="fas fa-times" style="cursor: pointer; padding: 2px;" role="button" tabindex="0" aria-label="<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Bỏ qua" : "Dismiss") ?>" onkeypress="if(event.key==='Enter'||event.key===' ') this.click();" aria-hidden="false"></i>
        </div>
        <div class="gp-body">
            <div class="gp-correction">
                <span id="gp-wrong-text" class="gp-wrong"></span>
                <i class="fas fa-arrow-right" style="color: var(--text-muted); font-size: 12px;" aria-hidden="true"></i>
                <span id="gp-right-text" class="gp-right"></span>
            </div>
            <div id="gp-explanation-text" class="gp-explanation"></div>
        </div>
        <div class="gp-actions">
            <button id="gp-btn-dismiss" class="gp-btn-dismiss"><?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Bỏ qua" : "Dismiss") ?></button>
            <button id="gp-btn-accept" class="gp-btn-accept"><?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Sửa lỗi" : "Fix Error") ?></button>
        </div>
    </div>
</div>

<script>
const GCApp = (function() {
    let currentIssues = [];
    let activeErrorIndex = null;
    let activeSpanElement = null;
    let currentLangCode = '<?= ($_SESSION['lang'] ?? 'vi') === 'en' ? 'en' : 'vi' ?>';

    const escapeHTML = str => str.replace(/[&<>'"]/g, tag => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'}[tag]));
    const escapeRegExp = string => string.replace(/[.*+?^${}()|[\]\]/g, '\$&');

    return {
        init: function() {
            // FIX PASTE: Xóa mọi thẻ HTML rác khi user copy/paste từ web khác vào
            const editor = document.getElementById('gc-editor');
            if (editor && !editor.dataset.pasteHandled) {
                editor.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const text = (e.originalEvent || e).clipboardData.getData('text/plain');
                    document.execCommand('insertText', false, text);
                });
                editor.dataset.pasteHandled = "true";
            }

            if (!window.gcEventsAttached) {
                window.addEventListener('beforeunload', () => this.saveState());
                document.addEventListener('click', async (e) => {
                    // Nút quét
                    const scanBtn = e.target.closest('#btn-submit-gc');
                    if (scanBtn) { e.preventDefault(); this.handleScan(scanBtn); return; }

                    // Dropdown
                    const selectBox = e.target.closest('#lang-select-box');
                    const langItem = e.target.closest('#lang-items div');
                    const selectItems = document.getElementById('lang-items');
                    const sBox = document.getElementById('lang-select-box');

                    if (selectBox) {
                        const isBlock = selectItems.style.display === 'block';
                        selectItems.style.display = isBlock ? 'none' : 'block';
                        if (isBlock) sBox.classList.remove('active'); else sBox.classList.add('active');
                        return;
                    }

                    if (langItem) {
                        currentLangCode = langItem.getAttribute('data-lang');
                        document.getElementById('txtSelectedLang').innerHTML = langItem.innerHTML;
                        const ed_in = document.getElementById('gc-editor');
                        if(ed_in) ed_in.setAttribute('data-placeholder', langItem.getAttribute('data-placeholder'));
                        if(selectItems) selectItems.style.display = 'none';
                        if(sBox) sBox.classList.remove('active');
                        return;
                    }

                    if (!e.target.closest('.custom-select-container') && selectItems) {
                        selectItems.style.display = 'none';
                        if(sBox) sBox.classList.remove('active');
                    }

                    // Popup logic
                    if (e.target.classList.contains('grammar-error')) { this.showPopup(e.target); return; }
                    if (e.target.closest('#gp-btn-accept')) { this.acceptFix(); return; }
                    if (e.target.closest('#gp-btn-dismiss')) { this.removeHighlight(activeSpanElement); this.hidePopup(); return; }
                    if (e.target.closest('#gp-btn-close')) { this.hidePopup(); return; }

                    const popup = document.getElementById('grammarly-popup');
                    if (popup && popup.style.display === 'flex' && !e.target.closest('#grammarly-popup')) {
                        this.hidePopup();
                    }
                });
                window.gcEventsAttached = true; 
            }
        },

        handleScan: async function(btn) {
            const editor = document.getElementById('gc-editor');
            const statusTxt = document.getElementById('scan-status');
            
            // Xóa rác ZWSP (Zero Width Space) để lấy text siêu sạch
            const txt = editor ? editor.innerText.replace(/​/g, '').trim() : ''; 
            
            if(!txt) {
                const pleaseEnterText = (window.LANG && window.LANG.please_enter_text) || 'Vui lòng nhập văn bản!';
                if(typeof Toastify === 'function') Toastify({text: pleaseEnterText, duration: 2000, style: {background: '#ef4444'}}).showToast();
                return;
            }
            
            const scanningText = (window.LANG && window.LANG.scanning) || 'Đang quét...';
            btn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> ' + scanningText;
            btn.disabled = true;
            
            const aiCheckingText = (window.LANG && window.LANG.ai_checking) || 'AI đang rà soát...';
            statusTxt.innerHTML = '<span style="color: #005fb8;"><i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i> ' + aiCheckingText + '</span>';
            this.hidePopup();
            
            try {
                const res = await fetch('grammar_check.php?local_api=1', {
                    method: 'POST', headers: {'Content-Type': 'application/json'}, 
                    body: JSON.stringify({
                        user_text: txt, 
                        language: currentLangCode,
                        app_lang: '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "vi" : "en") ?>'
                    })
                });
                const d = await res.json();
                
                if (d.status === 'success') {
                    currentIssues = d.issues || [];
                    if (currentIssues.length === 0) {
                        const perfectNoErrors = (window.LANG && window.LANG.perfect_no_errors) || 'Hoàn hảo! Không phát hiện lỗi nào.';
                        statusTxt.innerHTML = '<span style="color: #10b981;"><i class="fas fa-check-circle" aria-hidden="true"></i> ' + perfectNoErrors + '</span>';
                        editor.innerHTML = escapeHTML(txt).replace(/\n/g, '<br>');
                    } else {
                        const errsDet1 = (window.LANG && window.LANG.errors_detected_1) || 'Phát hiện';
                        const errsDet2 = (window.LANG && window.LANG.errors_detected_2) || 'lỗi. Bấm vào chữ gạch đỏ để sửa.';
                        statusTxt.innerHTML = `<span style="color: #ef4444;"><i class="fas fa-exclamation-circle" aria-hidden="true"></i> ${errsDet1} ${currentIssues.length} ${errsDet2}</span>`;
                        
                        // Kích hoạt thuật toán BẤT TỬ
                        this.highlightErrors(editor, txt); 
                    }
                } else {
                    statusTxt.innerHTML = `<span style="color: #ef4444;">${d.msg}</span>`;
                }
            } catch(e) { 
                const connError = (window.LANG && window.LANG.connection_error) || 'Lỗi kết nối.';
                statusTxt.innerHTML = '<span style="color: #ef4444;"><i class="fas fa-wifi" aria-hidden="true"></i> ' + connError + '</span>'; 
            } finally {
                const scanErrorsText = (window.LANG && window.LANG.scan_errors) || 'Quét lỗi (Scan)';
                btn.innerHTML = '<i class="fas fa-magic" aria-hidden="true"></i> ' + scanErrorsText;
                btn.disabled = false;
            }
        },
        
        // ==============================================================
        // THUẬT TOÁN DUYỆT CÂY DOM (TreeWalker) - ANTI HTML GARBAGE 100%
        // ==============================================================
        highlightErrors: function(editor, rawText) {
            // Bước 1: Clear sạch mọi thẻ span cũ, trả về Text tinh khiết
            editor.innerHTML = escapeHTML(rawText).replace(/\n/g, '<br>');
            
            // Bước 2: Chui vào từng đoạn văn bản để tìm và chèn thẻ span
            currentIssues.forEach((issue, index) => {
                this.highlightTextNodes(editor, issue.wrong, index);
            });
        },

        highlightTextNodes: function(node, word, index) {
            // Nếu là Node văn bản (Tuyệt đối ko chạm vào thẻ HTML)
            if (node.nodeType === 3) { 
                const text = node.nodeValue;
                // Regex chuẩn: Chỉ match đúng từ đó, không dính vào từ khác (VD: 'i' ko dính vào 'likes')
                const regex = new RegExp('(^|[^\\p{L}\\p{N}_])(' + escapeRegExp(word) + ')(?=[^\\p{L}\\p{N}_]|$)', 'gui');
                let match;
                let lastIndex = 0;
                let fragment = document.createDocumentFragment();
                let found = false;

                while ((match = regex.exec(text)) !== null) {
                    found = true;
                    const beforeText = text.substring(lastIndex, match.index + match[1].length);
                    const matchedText = match[2];

                    if (beforeText) fragment.appendChild(document.createTextNode(beforeText));

                    // Tạo thẻ span bọc chữ lỗi
                    const span = document.createElement('span');
                    span.className = 'grammar-error';
                    span.setAttribute('data-index', index);
                    span.textContent = matchedText;
                    fragment.appendChild(span);

                    lastIndex = match.index + match[1].length + matchedText.length;
                }

                if (found) {
                    const afterText = text.substring(lastIndex);
                    if (afterText) fragment.appendChild(document.createTextNode(afterText));
                    node.parentNode.replaceChild(fragment, node);
                }
            } 
            // Nếu là Element Node (Thẻ br, div...) thì duyệt tiếp vào trong
            else if (node.nodeType === 1 && !node.classList.contains('grammar-error')) {
                Array.from(node.childNodes).forEach(child => this.highlightTextNodes(child, word, index));
            }
        },

        showPopup: function(spanElement) {
            document.querySelectorAll('.grammar-error').forEach(el => el.classList.remove('active'));
            spanElement.classList.add('active');
            activeSpanElement = spanElement;
            activeErrorIndex = spanElement.getAttribute('data-index');
            const issueData = currentIssues[activeErrorIndex];
            if(!issueData) return;

            document.getElementById('gp-wrong-text').innerText = spanElement.innerText; 
            document.getElementById('gp-right-text').innerText = issueData.correct;
            document.getElementById('gp-explanation-text').innerText = issueData.explanation;

            const popup = document.getElementById('grammarly-popup');
            const rect = spanElement.getBoundingClientRect();
            popup.style.display = 'flex';
            let topPos = rect.bottom + window.scrollY + 10;
            let leftPos = rect.left + window.scrollX;
            if (leftPos + 320 > window.innerWidth) leftPos = window.innerWidth - 340; 
            popup.style.top = topPos + 'px';
            popup.style.left = leftPos + 'px';
        },

        hidePopup: function() {
            const popup = document.getElementById('grammarly-popup');
            if (popup) popup.style.display = 'none';
            document.querySelectorAll('.grammar-error').forEach(el => el.classList.remove('active'));
            activeSpanElement = null;
            activeErrorIndex = null;
        },

        // CHẤP NHẬN SỬA LỖI
        acceptFix: function() {
            if(!activeSpanElement || activeErrorIndex === null) return;
            const issueData = currentIssues[activeErrorIndex];
            
            // Thay thẻ span gạch đỏ bằng đoạn Text sạch sẽ
            const textNode = document.createTextNode(issueData.correct);
            const parent = activeSpanElement.parentNode;
            parent.replaceChild(textNode, activeSpanElement);

            this.hidePopup();
            const remaining = parent.querySelectorAll('.grammar-error').length;
            if(remaining === 0) {
                document.getElementById('scan-status').innerHTML = '<span style="color: #10b981;"><i class="fas fa-check-circle" aria-hidden="true"></i> ' + ('<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Đã sửa xong toàn bộ lỗi!" : "All errors fixed!") ?>') + '</span>';
            } else {
                document.getElementById('scan-status').innerHTML = `<span style="color: #005fb8;">${'<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Còn lại" : "Remaining") ?>'} ${remaining} ${'<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "lỗi cần xử lý." : "errors to fix.") ?>'}</span>`;
            }
        },

        removeHighlight: function(spanElement) {
            if(!spanElement) return;
            const textNode = document.createTextNode(spanElement.innerText);
            spanElement.parentNode.replaceChild(textNode, spanElement);
        },

        // ==========================================
        // SPA STATE MANAGER: ĐÓNG BĂNG GIAO DIỆN
        // ==========================================
        saveState: function() {
            const editor = document.getElementById('gc-editor');
            if (editor) {
                sessionStorage.setItem('gc_draft_html', editor.innerHTML);
                sessionStorage.setItem('gc_draft_issues', JSON.stringify(currentIssues));
                sessionStorage.setItem('gc_draft_lang', currentLangCode);
                sessionStorage.setItem('gc_draft_system_lang', '<?= $_SESSION['lang'] ?? 'vi' ?>');
                const statusEl = document.getElementById('scan-status');
                if(statusEl) sessionStorage.setItem('gc_draft_status', statusEl.innerHTML);
            }
        },

        restoreState: function() {
            const savedSystemLang = sessionStorage.getItem('gc_draft_system_lang');
            const currentSystemLang = '<?= $_SESSION['lang'] ?? 'vi' ?>';
            if (savedSystemLang && savedSystemLang !== currentSystemLang) {
                sessionStorage.removeItem('gc_draft_html');
                sessionStorage.removeItem('gc_draft_issues');
                sessionStorage.removeItem('gc_draft_lang');
                sessionStorage.removeItem('gc_draft_status');
                sessionStorage.removeItem('gc_draft_system_lang');
                return;
            }

            const editor = document.getElementById('gc-editor');
            if (editor) {
                const draftHtml = sessionStorage.getItem('gc_draft_html');
                if (draftHtml !== null) editor.innerHTML = draftHtml; 
                
                const draftIssues = sessionStorage.getItem('gc_draft_issues');
                if (draftIssues) currentIssues = JSON.parse(draftIssues);
                
                const draftLang = sessionStorage.getItem('gc_draft_lang');
                if (draftLang) {
                    currentLangCode = draftLang;
                    const txtLang = document.getElementById('txtSelectedLang');
                    if (draftLang === 'vi') {
                        if(txtLang) txtLang.innerHTML = '<i class="fas fa-language" aria-hidden="true"></i> ' + ('<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Tiếng Việt" : "Vietnamese") ?>');
                        editor.setAttribute('data-placeholder', '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Nhập đoạn văn Tiếng Việt của bạn vào đây (Ví dụ: hôm lay tôi nàm bài suất xắc)..." : "Enter Vietnamese text here...") ?>');
                    } else {
                        if(txtLang) txtLang.innerHTML = '<i class="fas fa-language" aria-hidden="true"></i> ' + ('<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "English" : "English") ?>');
                        editor.setAttribute('data-placeholder', '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Enter your English text here (E.g: I loves Duong)..." : "Enter English text here...") ?>');
                    }
                }
                const draftStatus = sessionStorage.getItem('gc_draft_status');
                const statusEl = document.getElementById('scan-status');
                if (statusEl) {
                    if (draftStatus) {
                        if (draftStatus.includes('<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Sẵn sàng phân tích" : "Ready to analyze") ?>') || draftStatus.includes('Ready to analyze') || draftStatus.trim() === '') {
                            statusEl.innerHTML = '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Sẵn sàng phân tích" : "Ready to analyze") ?>';
                        } else {
                            statusEl.innerHTML = draftStatus;
                        }
                    } else {
                        statusEl.innerHTML = '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Sẵn sàng phân tích" : "Ready to analyze") ?>';
                    }
                }
            }
        }
    };
})();

// KÍCH HOẠT VÒNG ĐỜI SPA
window.pageDestroy = function() { GCApp.hidePopup(); GCApp.saveState(); };
window.pageInit = function() { GCApp.init(); GCApp.restoreState(); };
</script>
