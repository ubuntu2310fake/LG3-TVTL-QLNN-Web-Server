<?php
// 1. NGĂN CHẶN CACHE TỪ TRÌNH DUYỆT ĐIỆN THOẠI (BẮT BUỘC TẢI CODE MỚI NHẤT)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 2. KHỞI TẠO SESSION TRƯỚC TIÊN ĐỂ LẤY THÔNG TIN USER
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/config.php';

// 3. TẠO TOKEN JWT BỌC THÉP
function generate_sso_token($user) {
    // 🔥 FIX: Ưu tiên đọc hằng số -> Đọc biến môi trường .env -> Khóa cứng _secure chuẩn của bạn
    $secret = defined('SSO_SECRET_KEY') ? SSO_SECRET_KEY : (getenv('SSO_SECRET_KEY') ?: "khoa_bi_mat_ket_noi_hai_app_123456_secure"); 
    
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $base_url = $protocol . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    
    $avatar = $user['avatar'] ?? 'static/default.png';
    if ($avatar && strpos($avatar, 'http') === false) $avatar = $base_url . "/" . ltrim($avatar, '/');
    
    // Nới lỏng thời gian Token lên 15 phút (900s) để chống lỗi lệch múi giờ giữa 2 server
    $payload = json_encode([
        'sbd' => $user['username'] ?? 'unknown', 
        'name' => $user['full_name'] ?? (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Khách' : 'Guest'), 
        'role' => $user['role'] ?? 'STUDENT', 
        'avatar' => $avatar, 
        'exp' => time() + 900
    ]);
    
    $b64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $b64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    $signature = hash_hmac('sha256', $b64Header . "." . $b64Payload, $secret, true);
    
    return $b64Header . "." . $b64Payload . "." . str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
}

$jwt_token = generate_sso_token($_SESSION['user'] ?? []);

// 4. ĐIỂM ĐÓN REQUEST TỪ JAVASCRIPT (ĐẶT TRƯỚC HEADER ĐỂ BẢO VỆ JSON)
if (isset($_GET['local_api'])) {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
    require_once 'includes/config.php';
    
    $action = $_GET['local_api'];
    $inputData = json_decode(file_get_contents('php://input'), true) ?: [];
    
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vui lòng đăng nhập để sử dụng tính năng này.' : 'Login required to use this feature.')]);
        exit;
    }
    
    $username = $_SESSION['user']['username'];
    $full_name = $_SESSION['user']['full_name'];
    $current_school_year = $pdo->query("SELECT value FROM config WHERE `key` = 'current_school_year'")->fetchColumn() ?: '2026-2027';
    
    if ($action === 'save_test') {

        
        $test_type = $inputData['test_type'] ?? '';
        $result_data = json_encode($inputData['result_data'] ?? [], JSON_UNESCAPED_UNICODE);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO test_results (username, full_name, user_id, test_type, result_data, school_year) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $full_name, $_SESSION['user']['id'], $test_type, $result_data, $current_school_year]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi lưu DB' : 'DB save error')]);
        }
        exit;
    }
    
    if ($action === 'history') {
        try {
            $stmt = $pdo->prepare("SELECT id, test_type, result_data, DATE_FORMAT(created_at, '%H:%M - %d/%m/%Y') as created_at FROM test_results WHERE username = ? ORDER BY created_at DESC");
            $stmt->execute([$username]);
            $history = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $history]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi truy vấn DB' : 'DB query error')]);
        }
        exit;
    }
    
    if ($action === 'delete') {

        $test_id = $inputData['id'] ?? null;
        if ($test_id) {
            $stmt = $pdo->prepare("DELETE FROM test_results WHERE id = ? AND username = ?");
            $stmt->execute([$test_id, $username]);
            echo json_encode(['success' => $stmt->rowCount() > 0]);
        } else {
            echo json_encode(['success' => false, 'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thiếu ID' : 'Missing ID')]);
        }
        exit;
    }
    
    if ($action === 'delete_all') {

        $stmt = $pdo->prepare("DELETE FROM test_results WHERE username = ?");
        $stmt->execute([$username]);
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'ai_proxy') {
        $user_text = $inputData['user_text'] ?? '';
        if (empty($user_text)) {
            echo json_encode(['success' => false, 'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nội dung trống.' : 'Content is empty.')]);
            exit;
        }
        
        $lang = $inputData['lang'] ?? 'vi';
        if ($lang === 'en') {
            $system_msg = "You are a professional career counselor. Based on the student's career test scores, provide suitable career recommendations and a specific study/learning path. Be concise, logical, and reply in English.";
        } else {
            $system_msg = (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn là chuyên gia tư vấn hướng nghiệp. Dựa vào điểm số bài test tính cách của học sinh, hãy đưa ra các gợi ý nghề nghiệp phù hợp và lộ trình học tập cụ thể. Trả lời ngắn gọn, súc tích.' : 'You are a career counselor. Based on the student\'s personality test results, provide suitable career recommendations and a specific learning path. Be concise and logical.');
        }
        
        $gemini_key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
        $gemini_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent?key=" . $gemini_key;
        $gemini_payload = [
            'contents' => [['parts' => [['text' => $user_text]]]],
            'systemInstruction' => ['parts' => [['text' => $system_msg]]],
            'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 1200]
        ];
        
        $ch = curl_init($gemini_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($gemini_payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $advice = null;
        if ($http_code === 200 && $res) {
            $res_data = json_decode($res, true);
            if (isset($res_data['candidates'][0]['content']['parts'][0]['text'])) {
                $advice = $res_data['candidates'][0]['content']['parts'][0]['text'];
            }
        }
        
        // Fallback Groq
        if (!$advice) {
            $groq_key = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
            $groq_url = "https://api.groq.com/openai/v1/chat/completions";
            $groq_payload = [
                'messages' => [
                    ['role' => 'system', 'content' => $system_msg],
                    ['role' => 'user', 'content' => $user_text]
                ],
                'model' => 'llama-3.3-70b-versatile',
                'max_tokens' => 1200, 'temperature' => 0.7
            ];
            
            $ch = curl_init($groq_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($groq_payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $groq_key]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $res = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code === 200 && $res) {
                $res_data = json_decode($res, true);
                if (isset($res_data['choices'][0]['message']['content'])) {
                    $advice = $res_data['choices'][0]['message']['content'];
                }
            }
        }
        
        if ($advice) {
            try {
                $stmtSave = $pdo->prepare("INSERT INTO career_advice_logs (username, full_name, user_query, ai_response, school_year) VALUES (?, ?, ?, ?, ?)");
                $stmtSave->execute([$username, $full_name, $user_text, $advice, $current_school_year]);
            } catch (Exception $dbEx) {
                // Bỏ qua lỗi lưu DB để vẫn trả về lời khuyên cho học sinh
            }
            echo json_encode(['advice' => $advice]);
        } else {
            echo json_encode(['success' => false, 'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hệ thống tư vấn tâm lý báo lỗi (Mã: 302)' : 'Psychology counseling system error (Code: 302)'), 'code' => 302]);
        }
        exit;
    }
    
    exit;
}

// =====================================================================
// 5. RENDER GIAO DIỆN SAU KHI ĐÃ XỬ LÝ API XONG
// =====================================================================
require_once 'includes/header.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/marked/9.1.6/marked.min.js"></script>

<style>
    :root {
        --win-bg: #f3f3f3; --win-card: #ffffff; --win-border: #e5e5e5;
        --win-primary: #0067c0; --win-primary-hover: #005a9e; --win-text: #1a1a1a;
        --win-text-muted: #616161; --win-bg-secondary: #fafafa; --win-bg-hover: #f0f0f0;
        --win-active-bg: #f3f9ff; --win-active-border: #d0e7ff; --win-group-header: #f9f9f9; --win-track-bg: #eee;
    }
    [data-theme="dark"] {
        --win-bg: #1e1e1e; --win-card: #2d2d2d; --win-border: #444444;
        --win-primary: #3b8ed0; --win-primary-hover: #4ea0e1; --win-text: #ffffff;
        --win-text-muted: #cccccc; --win-bg-secondary: #333333; --win-bg-hover: #3e3e3e;
        --win-active-bg: rgba(59, 142, 208, 0.15); --win-active-border: rgba(59, 142, 208, 0.4); --win-group-header: #383838; --win-track-bg: #444444;
    }
    .win-container { font-family: 'Segoe UI', Tahoma, sans-serif; color: var(--win-text); max-width: 1000px; margin: 0 auto; }
    .win-nav { display: flex; gap: 8px; margin-bottom: 20px; background: var(--win-card); padding: 8px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border: 1px solid var(--win-border); flex-wrap: wrap;}
    .win-nav-item { padding: 10px 20px; cursor: pointer; border-radius: 6px; font-weight: 600; color: var(--win-text-muted); transition: all 0.2s; border: 1px solid transparent; }
    .win-nav-item:hover { background: var(--win-bg-hover); }
    .win-nav-item.active { background: var(--win-active-bg); color: var(--win-primary); border: 1px solid var(--win-active-border); }
    .win-test-section { display: none; background: var(--win-card); padding: 25px; border-radius: 8px; border: 1px solid var(--win-border); box-shadow: 0 4px 12px rgba(0,0,0,0.03); animation: fadeIn 0.3s ease; }
    .win-test-section.active { display: block; }
    .win-title { font-size: 20px; font-weight: 600; margin-bottom: 5px; color: var(--win-primary); }
    .win-subtitle { font-size: 14px; color: var(--win-text-muted); margin-bottom: 20px; }
    .win-quiz-item { display: flex; align-items: flex-start; padding: 12px; margin-bottom: 8px; border-radius: 6px; transition: 0.2s; background: var(--win-bg-secondary); border: 1px solid transparent; height: auto; }
    .win-quiz-item:hover { background: var(--win-bg-hover); border-color: var(--win-border); }
    .win-quiz-item input[type="checkbox"] { margin-top: 4px; margin-right: 12px; transform: scale(1.3); accent-color: var(--win-primary); cursor: pointer; flex-shrink: 0; }
    .win-quiz-item label { cursor: pointer; flex: 1; font-size: 15px; line-height: 1.5; word-wrap: break-word; margin: 0; }
    .win-btn { display: flex; justify-content: center; align-items: center; gap: 8px; width: 100%; padding: 14px; font-size: 15px; font-weight: 600; color: white; background: var(--win-primary); border: none; border-radius: 6px; cursor: pointer; transition: 0.2s; margin-top: 20px; }
    .win-btn:hover { background: var(--win-primary-hover); }
    .win-btn:active { transform: scale(0.98); }
    .win-btn:disabled { opacity: 0.7; cursor: not-allowed; }
    .win-group { margin-bottom: 20px; border: 1px solid var(--win-border); border-radius: 8px; overflow: hidden; background: var(--win-card); }
    .win-group-header { padding: 10px 15px; font-weight: bold; background: var(--win-group-header); border-bottom: 1px solid var(--win-border); }
    .win-group-body { padding: 15px; }
    .mtvt-cols { display: flex; gap: 15px; }
    @media(max-width: 768px) { .mtvt-cols { flex-direction: column; } }
    .mtvt-col { flex: 1; }
    .mtvt-col-title { font-size: 13px; text-transform: uppercase; font-weight: bold; margin-bottom: 10px; color: var(--win-text-muted); }
    .mtvt-col-title.high { color: #2e7d32; }
    .mtvt-col-title.low { color: #c62828; }
    [data-theme="dark"] .mtvt-col-title.high { color: #66bb6a; }
    [data-theme="dark"] .mtvt-col-title.low { color: #e53935; }
    .mtvt-chart-container { margin-top: 30px; overflow-x: auto; }
    .bar-row { display: flex; align-items: center; margin-bottom: 10px; }
    .bar-label { width: 180px; font-weight: 600; font-size: 14px; }
    .bar-track { flex: 1; background: var(--win-track-bg); height: 24px; border-radius: 12px; position: relative; margin: 0 15px; overflow: hidden; }
    .bar-fill { height: 100%; border-radius: 12px; transition: width 1s ease; }
    .bar-score { width: 40px; font-weight: bold; text-align: right; }
    .history-card { background: var(--win-bg-secondary); border: 1px solid var(--win-border); border-radius: 8px; padding: 15px; border-left: 4px solid var(--win-primary); }
    .history-header { display: flex; justify-content: space-between; margin-bottom: 10px; font-weight: bold; }
    .history-time { color: var(--win-text-muted); font-size: 13px; font-weight: normal; }
    .history-body { font-size: 14px; background: var(--win-card); padding: 10px; border-radius: 6px; border: 1px solid var(--win-border); }
    .tag-badge { padding: 3px 8px; background: var(--win-active-bg); color: var(--win-primary); border-radius: 12px; font-size: 12px; font-weight: bold; }
    .loader-spin { border: 3px solid rgba(0,0,0,0.1); border-top: 3px solid #fff; border-radius: 50%; width: 18px; height: 18px; animation: spin 1s linear infinite; display: inline-block; vertical-align: middle;}
    .loader-spin.blue { border-top-color: var(--win-primary); }
    [data-theme="dark"] .loader-spin.blue { border-color: rgba(255,255,255,0.1); border-top-color: var(--win-primary); }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

    /* ==============================================================
       FIX LỖI AMOLED TRẮNG XÓA NÚT BẤM (Không dùng !important)
       ============================================================== */
    html[data-theme="dark"] body .win-btn,
    html[data-theme="dark"] body .tag-badge {
        color: #000000;
        font-weight: 800;
    }
</style>

<div class="win-container page-transition" style="padding: 20px;">
    
    <div class="win-nav">
        <div class="win-nav-item active" onclick="switchTab('holland')"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Holland (RIASEC)' : 'Holland (RIASEC)') ?></div>
        <div class="win-nav-item" onclick="switchTab('mi')"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đa Trí tuệ (MI)' : 'Multiple Intelligences (MI)') ?></div>
        <div class="win-nav-item" onclick="switchTab('disc')"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hành vi (DISC)' : 'Behavior (DISC)') ?></div>
        <div class="win-nav-item" onclick="switchTab('mtvt')"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Động lực (MTVT)' : 'Motivators (MTVT)') ?></div>
        <div class="win-nav-item" onclick="switchTab('history'); loadHistory();"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '🕒 Lịch sử' : '🕒 History') ?></div>
        <div class="win-nav-item" onclick="switchTab('ai')"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '🤖 Tư vấn AI' : '🤖 AI Counseling') ?></div>
    </div>

    <div id="tab-holland" class="win-test-section active">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap;">
            <div>
                <div class="win-title"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bài test nghề nghiệp Holland' : 'Holland Career Test') ?></div>
                <div class="win-subtitle"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Khám phá thiên hướng nghề nghiệp qua 114 câu hỏi.' : 'Discover your career aptitude through 114 questions.') ?></div>
            </div>
            <button class="win-btn" style="width: auto; padding: 8px 15px; margin-top: 0; background: #e67e22; margin-bottom: 15px;" onclick="toggleHollandGuide()">📖 <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hướng dẫn' : 'Guide') ?></button>
        </div>

        <div id="holland-guide" style="display: none; background: var(--win-bg-secondary); border-left: 4px solid #e67e22; padding: 15px; margin-bottom: 20px; border-radius: 4px; border: 1px solid var(--win-border); font-size: 14px; line-height: 1.6;">
            <strong style="color: #e67e22; font-size: 16px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '1. Những lưu ý khi làm bài' : '1. Important notes before taking the test') ?></strong>
            <ul style="margin-top: 5px; margin-bottom: 10px; padding-left: 20px;">
                <li><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chọn một nơi yên tĩnh để tâm trí được thư giãn, thoải mái.' : 'Choose a quiet place to relax your mind.') ?></li>
                <li><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hãy chân thật nhất có thể - đừng đánh giá, chê bai bản thân hay cố gắng mong mình giống một ai khác.' : 'Be as honest as possible - do not judge, criticize yourself, or try to be someone else.') ?></li>
            </ul>
            <strong style="color: #e67e22; font-size: 16px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '2. Cách trả lời câu hỏi' : '2. How to answer') ?></strong>
            <ul style="margin-top: 5px; margin-bottom: 0; padding-left: 20px;">
                <li><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '<strong>Đánh dấu (Check)</strong> nếu quan sát thấy bản thân <strong>GIỐNG</strong> như ở câu mô tả.' : '<strong>Check</strong> if you feel the description matches you <strong>WELL</strong>.') ?></li>
                <li><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '<strong>Bỏ trống</strong> nếu thấy KHÔNG GIỐNG, không rõ hoặc chưa quan sát thấy.' : '<strong>Leave blank</strong> if it doesn\'t match, or you\'re not sure.') ?></li>
            </ul>
        </div>

        <div id="holland-quiz-list"></div>
        <button id="btn-save-holland" class="win-btn" onclick="submitHolland()"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lưu & Xem Kết quả' : 'Save & View Results') ?></button>
        
        <div id="holland-result" style="display:none; margin-top:30px; animation: fadeIn 0.5s;">
            <div style="background:var(--win-active-bg); padding: 20px; border-radius: 8px; border: 1px solid var(--win-active-border); margin-bottom: 20px;">
                <h3 id="holland-user-title" style="margin-top:0; color:var(--win-primary);"></h3>
                <div id="holland-top-traits" style="margin-top: 15px; line-height: 1.6;"></div>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <div style="font-weight: bold; color: var(--win-primary);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trực quan hóa Dữ liệu:' : 'Data Visualization:') ?></div>
                <select id="holland-chart-type" class="win-input" style="width: auto; margin: 0; padding: 5px 10px; font-weight: bold;" onchange="window.renderHollandChart(this.value)">
                    <option value="radar">🕸️ <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Biểu đồ Radar' : 'Radar Chart') ?></option>
                    <option value="bar">📊 <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Biểu đồ Cột' : 'Bar Chart') ?></option>
                </select>
            </div>

            <div style="position: relative; height: 400px; width: 100%; background: var(--win-card); border-radius: 8px; border: 1px solid var(--win-border); padding: 15px; box-sizing: border-box;">
                <canvas id="hollandChart"></canvas>
            </div>
        </div>
    </div>

    <div id="tab-mi" class="win-test-section">
        <div class="win-title"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bài test Đa Trí thông minh (MI)' : 'Multiple Intelligences (MI) Test') ?></div>
        <div class="win-subtitle"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Dựa trên lý thuyết của Howard Gardner, hãy chọn những mô tả đúng với khả năng và thói quen của bạn nhất.' : 'Based on Howard Gardner\'s theory, select the descriptions that best match your abilities and habits.') ?></div>
        <div id="mi-quiz-list"></div>
        <button id="btn-save-mi" class="win-btn" onclick="submitMI()"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lưu & Xem Kết quả' : 'Save & View Results') ?></button>
        
        <div id="mi-result" style="display:none; margin-top:30px; animation: fadeIn 0.5s;">
            <div style="background:var(--win-active-bg); padding: 20px; border-radius: 8px; border: 1px solid var(--win-active-border); margin-bottom: 20px;">
                <h3 id="mi-user-title" style="margin-top:0; color:var(--win-primary);"></h3>
                <div id="mi-top-traits" style="margin-top: 15px; line-height: 1.6;"></div>
            </div>
            <div style="position: relative; height: 400px; width: 100%; background: var(--win-card); border-radius: 8px; border: 1px solid var(--win-border); padding: 15px; box-sizing: border-box;">
                <canvas id="miChart"></canvas>
            </div>
        </div>
    </div>

    <div id="tab-disc" class="win-test-section">
        <div class="win-title"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bài test Hành vi DISC' : 'DISC Behavior Test') ?></div>
        <div class="win-subtitle"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đánh dấu vào những từ khóa mô tả ĐÚNG NHẤT về bạn.' : 'Check the keywords that BEST describe you.') ?></div>
        <form id="discForm"></form>
        <button id="btn-save-disc" class="win-btn" onclick="submitDISC()"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lưu & Xem Kết quả' : 'Save & View Results') ?></button>
        <div id="disc-result" style="display:none; margin-top:30px;">
            <h3 id="disc-user-title" style="text-align:center; color:var(--win-primary);"></h3>
            <div style="position: relative; height: 400px; width: 100%;">
                <canvas id="discChart"></canvas>
            </div>
        </div>
    </div>

    <div id="tab-mtvt" class="win-test-section">
        <div class="win-title"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bài test Động lực' : 'Motivators Test') ?></div>
        <div class="win-subtitle"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đánh giá các yếu tố thúc đẩy bên trong của bạn.' : 'Assess your inner motivating factors.') ?></div>
        <form id="mtvtForm"></form>
        <button id="btn-save-mtvt" class="win-btn" onclick="submitMTVT()"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lưu & Xem Kết quả' : 'Save & View Results') ?></button>
        <div id="mtvt-result" style="display:none; margin-top:30px;">
            <h3 id="mtvt-user-title" style="text-align:center; color:var(--win-primary);"></h3>
            <div id="mtvt-chart-area" class="mtvt-chart-container"></div>
        </div>
    </div>

    <div id="tab-history" class="win-test-section">
        <div class="win-title"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lịch sử Bài test' : 'Test History') ?></div>
        <div class="win-subtitle"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Kết quả bài test được đồng bộ an toàn.' : 'Securely synced test results.') ?></div>
        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <button class="win-btn" onclick="loadHistory()" style="width: auto; padding: 8px 15px; margin-top: 0;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Làm mới' : 'Refresh') ?> 🔄</button>
            <button class="win-btn" onclick="deleteAllHistory()" style="width: auto; padding: 8px 15px; margin-top: 0; background: #c62828;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xóa Tất cả' : 'Delete All') ?> 🗑️</button>
        </div>
        <div id="history-container" style="display: flex; flex-direction: column; gap: 15px;"></div>
    </div>

    <div id="tab-ai" class="win-test-section">
        <div class="win-title"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tư vấn Hướng nghiệp AI' : 'AI Career Counseling') ?></div>
        <div class="win-subtitle"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Kết nối Server-to-Server để phân tích AI.' : 'Server-to-Server connection for AI analysis.') ?></div>
        <button id="btn-analyze-ai" class="win-btn" onclick="analyzeCareerWithAI()"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Gọi Phân tích AI' : 'Call AI Analysis') ?></button>
        <div id="ai-loading" style="display:none; margin-top:20px; font-weight:bold; color:var(--win-primary); text-align:center;">
            <span class="loader-spin blue"></span> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang kết nối máy chủ AI...' : 'Connecting to AI server...') ?>
        </div>
        <div id="ai-result" class="history-body" style="display:none; margin-top:20px; line-height: 1.6; font-size: 15px; padding: 20px; border-left: 4px solid var(--win-primary); word-wrap: break-word;"></div>
    </div>
</div>

<script>
// BIẾN TOÀN CỤC CHỐNG LỖI SPA
window.currentUserInfo = { full_name: "<?= $_SESSION['user']['full_name'] ?? (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Khách' : 'Guest') ?>", username: "<?= $_SESSION['user']['username'] ?? 'unknown' ?>" };
var currentLang = '<?= $_SESSION['lang'] ?? 'vi' ?>'; 
window.discChartInstance = null; 
window.hollandChartInstance = null;
window.miChartInstance = null;
window.discData = {};
window.mtvtDb = {};
window.hollandScores = null; // Lưu điểm Holland để switch biểu đồ

var localHollandDict = currentLang === 'en' ? {
    "R": { n: "Realistic (R)", c: "#0067c0", d: "Prefers interacting with machines, objects. Manual, mechanical, technical skills." },
    "I": { n: "Investigative (I)", c: "#2e7d32", d: "Prefers investigating, observing and creating to understand physical, biological phenomena. Scientific, mathematical skills." },
    "A": { n: "Artistic (A)", c: "#c62828", d: "Prefers free, creative activities using materials, language to create artistic products." },
    "S": { n: "Social (S)", c: "#f39c12", d: "Prefers working with others to train, heal, develop. Educational, communication skills." },
    "E": { n: "Enterprising (E)", c: "#8e44ad", d: "Prefers dominating and influencing others to achieve organizational goals. Leadership, persuasive skills." },
    "C": { n: "Conventional (C)", c: "#34495e", d: "Prefers processing data in an orderly, systematic manner. Organization, office machine operation skills." }
} : {
    "R": { n: "Kỹ Thuật (R)", c: "#0067c0", d: "Ưu tiên các hoạt động tương tác với máy móc, đồ vật rõ ràng. Năng lực thủ công, cơ khí, kỹ thuật." },
    "I": { n: "Nghiên Cứu (I)", c: "#2e7d32", d: "Ưu tiên điều tra, quan sát và sáng tạo để hiểu hiện tượng vật lý, sinh học. Năng lực khoa học, toán học." },
    "A": { n: "Nghệ Thuật (A)", c: "#c62828", d: "Ưu tiên các hoạt động tự do, vận dụng vật liệu, ngôn ngữ để tạo ra sản phẩm nghệ thuật." },
    "S": { n: "Xã Hội (S)", c: "#f39c12", d: "Ưu tiên làm việc với người khác để huấn luyện, chữa lành, phát triển. Năng lực giáo dục, giao tiếp." },
    "E": { n: "Quản Lý (E)", c: "#8e44ad", d: "Ưu tiên chi phối và ảnh hưởng người khác để đạt mục tiêu tổ chức. Năng lực lãnh đạo, thuyết phục." },
    "C": { n: "Nghiệp Vụ (C)", c: "#34495e", d: "Ưu tiên xử lý dữ liệu có trật tự, hệ thống. Năng lực tổ chức, điều hành máy móc văn phòng." }
};

var miNames = currentLang === 'en' ? { 
    "Linguistic": "Linguistic", "Logical": "Logical", "Spatial": "Spatial", "Musical": "Musical", "Kinesthetic": "Kinesthetic", "Interpersonal": "Interpersonal", "Intrapersonal": "Intrapersonal", "Naturalist": "Naturalist" 
} : { 
    "Linguistic": "Ngôn ngữ", "Logical": "Logic - Toán", "Spatial": "Không gian", "Musical": "Âm nhạc", "Kinesthetic": "Vận động", "Interpersonal": "Tương tác", "Intrapersonal": "Nội tâm", "Naturalist": "Thiên nhiên" 
};
var miColors = { "Linguistic": "#0067c0", "Logical": "#c62828", "Spatial": "#8e44ad", "Musical": "#f39c12", "Kinesthetic": "#d35400", "Interpersonal": "#27ae60", "Intrapersonal": "#34495e", "Naturalist": "#2e7d32" };

function switchTab(tabId) {
    document.querySelectorAll('.win-test-section').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.win-nav-item').forEach(el => el.classList.remove('active'));
    const targetSection = document.getElementById(`tab-${tabId}`);
    if (targetSection) targetSection.classList.add('active');
    
    document.querySelectorAll('.win-nav-item').forEach(el => {
        const onclickAttr = el.getAttribute('onclick');
        if (onclickAttr && onclickAttr.includes(`'${tabId}'`)) el.classList.add('active');
    });
}

async function saveTestResult(testType, resultData) {
    try {
        const res = await fetch('?local_api=save_test', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ test_type: testType, result_data: resultData })
        });
        const data = await res.json();
        if(data.success) {
            if (typeof Toastify !== 'undefined') Toastify({ text: window.LANG && window.LANG.sync_success || "Đã đồng bộ thành công!", duration: 3000, gravity: "bottom", position: "center", style: { background: "#10b981" } }).showToast();
            else alert(window.LANG && window.LANG.save_success || "Lưu dữ liệu thành công!");
        } else alert((window.LANG && window.LANG.psychology_server_error || "Máy chủ Tư vấn tâm lý báo lỗi: ") + (data.msg || "Lỗi lưu"));
    } catch(e) { alert(window.LANG && window.LANG.network_save_error || "Lỗi mạng khi lưu dữ liệu qua cầu nối!"); }
}

function toggleHollandGuide() {
    const guide = document.getElementById('holland-guide');
    guide.style.display = guide.style.display === 'none' ? 'block' : 'none';
}

// =====================================================================
// 1. HOLLAND
// =====================================================================
function initHolland() {
    let html = '';
    for (const group in window.hollandData) {
        const groupName = localHollandDict[group] ? localHollandDict[group].n : group;
        html += `<div class="win-group"><div class="win-group-header">${groupName}</div><div class="win-group-body" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap: 12px;">`;
        window.hollandData[group].forEach((item, i) => {
            const id = `hol_${group}_${i}`;
            html += `<div class="win-quiz-item"><input type="checkbox" id="${id}" value="${group}"><label for="${id}">${i+1}. ${item.text}</label></div>`;
        });
        html += `</div></div>`;
    }
    document.getElementById('holland-quiz-list').innerHTML = html;
}

window.renderHollandChart = function(chartType) {
    const canvasEl = document.getElementById('hollandChart');
    if (!canvasEl || !window.hollandScores) return;

    if (window.hollandChartInstance) window.hollandChartInstance.destroy(); 
    if (typeof Chart !== 'undefined' && typeof ChartDataLabels !== 'undefined') Chart.register(ChartDataLabels);

    const scores = window.hollandScores;
    const isRadar = chartType === 'radar';

    let options = {
        responsive: true, maintainAspectRatio: false, 
        plugins: { 
            legend: { display: false },
            datalabels: {
                color: isRadar ? '#0067c0' : '#fff', 
                font: { weight: 'bold', size: 14 },
                align: isRadar ? 'end' : 'bottom', 
                anchor: 'end', 
                offset: 4,
                backgroundColor: isRadar ? 'rgba(255, 255, 255, 0.75)' : 'transparent', 
                borderRadius: 4, padding: 2,
                formatter: function(value) { return value > 0 ? value + 'đ' : ''; }
            }
        }
    };

    if (isRadar) {
        options.scales = { r: { min: 0, max: 19, ticks: { stepSize: 4, display: false } } };
    } else {
        options.scales = { y: { min: 0, max: 19, beginAtZero: true, ticks: { stepSize: 4 } } };
    }

    window.hollandChartInstance = new Chart(canvasEl.getContext('2d'), {
        type: chartType,
        data: { 
            labels: currentLang === 'en' ? ['Realistic (R)', 'Investigative (I)', 'Artistic (A)', 'Social (S)', 'Enterprising (E)', 'Conventional (C)'] : ['Kỹ Thuật (R)', 'Nghiên Cứu (I)', 'Nghệ Thuật (A)', 'Xã Hội (S)', 'Quản Lý (E)', 'Nghiệp Vụ (C)'], 
            datasets: [{ 
                data: [scores.R, scores.I, scores.A, scores.S, scores.E, scores.C], 
                backgroundColor: isRadar ? 'rgba(0, 103, 192, 0.4)' : ['#0067c0', '#2e7d32', '#c62828', '#f39c12', '#8e44ad', '#34495e'], 
                borderColor: isRadar ? '#0067c0' : 'transparent', 
                borderWidth: 2, pointRadius: 4, borderRadius: isRadar ? 0 : 4
            }] 
        },
        options: options
    });
};

async function submitHolland() {
    const checked = document.querySelectorAll('#holland-quiz-list input:checked');
    if(checked.length === 0) return alert(currentLang === 'en' ? 'Please select at least 1 trait!' : 'Vui lòng chọn ít nhất 1 đặc điểm!');
    const btn = document.getElementById('btn-save-holland'); const oldText = btn.innerHTML;
    btn.innerHTML = '<span class="loader-spin"></span> ' + (currentLang === 'en' ? 'Syncing...' : 'Đang đồng bộ...'); btn.disabled = true;

    try {
        const scores = {'R':0,'I':0,'A':0,'S':0,'E':0,'C':0}; 
        checked.forEach(cb => scores[cb.value]++);
        window.hollandScores = scores; // Lưu lại để vẽ biểu đồ
        
        const sorted = Object.entries(scores).sort((a,b) => b[1] - a[1]);
        const code = sorted.slice(0, 3).filter(x => x[1] > 0).map(x => x[0]).join('');
        
        document.getElementById('holland-result').style.display = 'block';
        document.getElementById('holland-user-title').innerText = (currentLang === 'en' ? "Holland Result Analysis of: " : "Phân tích kết quả Holland của: ") + window.currentUserInfo.full_name;
        
        let traitsHtml = `<div style="font-size: 18px; font-weight: bold; margin-bottom: 15px;">` + (currentLang === 'en' ? 'Your dominant Holland code: ' : 'Mật mã Holland ưu thế của bạn: ') + `<span style="color:var(--win-primary); font-size: 24px; letter-spacing: 2px;">${code}</span></div>`;
        sorted.slice(0, 3).forEach((item, idx) => {
            if(item[1] === 0) return; const data = localHollandDict[item[0]];
            traitsHtml += `<div style="margin-bottom: 15px; padding: 15px; background: var(--win-card); border-left: 5px solid ${data.c}; border-radius: 6px; border: 1px solid var(--win-border);">
                    <strong style="font-size: 16px;">🔥 ` + (currentLang === 'en' ? 'Rank' : 'Hạng') + ` ${idx+1}:  ${data.n} - ${item[1]}/19 ` + (currentLang === 'en' ? 'points' : 'điểm') + `</strong><br>
                    <div style="font-size:15px; margin-top:8px;"><em>📝 ` + (currentLang === 'en' ? 'Description:' : 'Đặc tả:') + `</em> ${data.d}</div>
                </div>`;
        });
        document.getElementById('holland-top-traits').innerHTML = traitsHtml;
        
        // Gọi hàm vẽ biểu đồ theo lựa chọn hiện tại
        window.renderHollandChart(document.getElementById('holland-chart-type').value);

        await saveTestResult('HOLLAND', scores);
    } catch (error) { console.error(error); } finally { btn.innerHTML = oldText; btn.disabled = false; }
}

// =====================================================================
// 2. ĐA TRÍ THÔNG MINH (MI)
// =====================================================================
function initMI() {
    let html = '';
    for (const group in window.miData) {
        const groupName = miNames[group] || group; 
        html += `<div class="win-group"><div class="win-group-header">${groupName}</div><div class="win-group-body" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap: 12px;">`;
        window.miData[group].forEach((item, i) => {
            const id = `mi_${group}_${i}`;
            html += `<div class="win-quiz-item"><input type="checkbox" id="${id}" value="${group}"><label for="${id}">${i+1}. ${item.text}</label></div>`;
        });
        html += `</div></div>`;
    }
    document.getElementById('mi-quiz-list').innerHTML = html;
}

async function submitMI() {
    const checked = document.querySelectorAll('#mi-quiz-list input:checked');
    if(checked.length === 0) return alert(currentLang === 'en' ? 'Please select at least 1 trait!' : 'Vui lòng chọn ít nhất 1 đặc điểm!');
    
    const btn = document.getElementById('btn-save-mi'); const oldText = btn.innerHTML;
    btn.innerHTML = '<span class="loader-spin"></span> ' + (currentLang === 'en' ? 'Syncing...' : 'Đang đồng bộ...'); btn.disabled = true;

    try {
        const scores = {"Linguistic":0, "Logical":0, "Spatial":0, "Musical":0, "Kinesthetic":0, "Interpersonal":0, "Intrapersonal":0, "Naturalist":0};
        checked.forEach(cb => scores[cb.value]++);

        const sorted = Object.entries(scores).sort((a,b) => b[1] - a[1]);
        
        document.getElementById('mi-result').style.display = 'block';
        document.getElementById('mi-user-title').innerText = (currentLang === 'en' ? "Multiple Intelligences Profile of: " : "Hồ sơ Đa trí thông minh của: ") + window.currentUserInfo.full_name;
        
        let traitsHtml = '';
        sorted.slice(0, 3).forEach((item, idx) => {
            if(item[1] === 0) return;
            traitsHtml += `<div style="margin-bottom: 10px; padding: 10px 15px; background: var(--win-card); border-left: 5px solid ${miColors[item[0]]}; border-radius: 6px; border: 1px solid var(--win-border);">
                <strong>🌟 ` + (currentLang === 'en' ? `Top ${idx+1}: ${miNames[item[0]]} Intelligence (${item[1]}/5 points)` : `Top ${idx+1}: Trí thông minh ${miNames[item[0]]} (${item[1]}/5 điểm)`) + `</strong>
            </div>`;
        });
        document.getElementById('mi-top-traits').innerHTML = traitsHtml;

        const canvasEl = document.getElementById('miChart');
        if (window.miChartInstance) window.miChartInstance.destroy();
        if (typeof Chart !== 'undefined' && typeof ChartDataLabels !== 'undefined') Chart.register(ChartDataLabels);

        window.miChartInstance = new Chart(canvasEl.getContext('2d'), {
            type: 'bar',
            data: { 
                labels: Object.keys(scores).map(k => miNames[k]), 
                datasets: [{ 
                    data: Object.values(scores), 
                    backgroundColor: Object.keys(scores).map(k => miColors[k]), 
                    borderRadius: 4 
                }] 
            },
            options: { 
                responsive: true, maintainAspectRatio: false, 
                plugins: { 
                    legend: { display: false }, 
                    datalabels: { 
                        color: '#fff', anchor: 'end', align: 'bottom', offset: 4, font: { weight: 'bold', size: 14 },
                        formatter: function(value) { return value > 0 ? value : ''; } 
                    } 
                }, 
                scales: { y: { min: 0, max: 5, ticks: { stepSize: 1 } } } 
            }
        });

        await saveTestResult('MI', scores);
    } catch(error) { console.error(error); } finally { btn.innerHTML = oldText; btn.disabled = false; }
}

// =====================================================================
// 3. DISC
// =====================================================================
function initDISC() {
    if(Object.keys(window.discData).length === 0) return;
    let html = '';
    for(const group in window.discData) {
        const groupHeader = currentLang === 'en' ? `Group ${group}` : `Nhóm ${group}`;
        html += `<div class="win-group"><div class="win-group-header">${groupHeader}</div><div class="win-group-body">`;
        window.discData[group].forEach((txt, i) => { html += `<div class="win-quiz-item"><input type="checkbox" id="disc_${group}_${i}" value="${group}"><label for="disc_${group}_${i}">${txt}</label></div>`; });
        html += `</div></div>`;
    }
    document.getElementById('discForm').innerHTML = html;
}

async function submitDISC() {
    const btn = document.getElementById('btn-save-disc'); const oldText = btn.innerHTML;
    btn.innerHTML = '<span class="loader-spin"></span> ' + (currentLang === 'en' ? 'Syncing...' : 'Đang đồng bộ...'); btn.disabled = true;
    try {
        const scores = { D: 0, I: 0, S: 0, C: 0 };
        document.querySelectorAll('#discForm input:checked').forEach(cb => scores[cb.value] += 10);
        document.getElementById('disc-result').style.display = 'block';
        document.getElementById('disc-user-title').innerText = (currentLang === 'en' ? "DISC Chart of: " : "Biểu đồ DISC của: ") + window.currentUserInfo.full_name;
        
        const canvasEl = document.getElementById('discChart');
        if(window.discChartInstance) window.discChartInstance.destroy();
        if(typeof Chart !== 'undefined' && typeof ChartDataLabels !== 'undefined') Chart.register(ChartDataLabels);

        const discLabels = currentLang === 'en' ? ['Group D', 'Group I', 'Group S', 'Group C'] : ['Nhóm D', 'Nhóm I', 'Nhóm S', 'Nhóm C'];

        window.discChartInstance = new Chart(canvasEl.getContext('2d'), {
            type: 'bar',
            data: { labels: discLabels, datasets: [{ data: [scores.D, scores.I, scores.S, scores.C], backgroundColor: ['#e74c3c', '#f39c12', '#27ae60', '#2980b9'], borderRadius: 4 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, datalabels: { color: '#fff', anchor: 'end', align: 'bottom', offset: 4, font: { weight: 'bold', size: 14 }, formatter: function(value) { return value + '%'; } } }, scales: { y: { max: 100, beginAtZero: true } } }
        });
        await saveTestResult('DISC', scores);
    } catch (error) { console.error(error); } finally { btn.innerHTML = oldText; btn.disabled = false; }
}

// =====================================================================
// 4. MTVT
// =====================================================================
function initMTVT() {
    if(Object.keys(window.mtvtDb).length === 0) return;
    let html = ''; let idx = 0;
    for (const k in window.mtvtDb) {
        const mtvtHeader = currentLang === 'en' ? `Motivator: ${k}` : `Động lực: ${k}`;
        const highTitle = currentLang === 'en' ? 'High Motivation' : 'Động lực Cao';
        const lowTitle = currentLang === 'en' ? 'Low Motivation' : 'Động lực Thấp';
        html += `<div class="win-group"><div class="win-group-header">${mtvtHeader}</div><div class="win-group-body mtvt-cols"><div class="mtvt-col"><div class="mtvt-col-title high">${highTitle}</div>`;
        window.mtvtDb[k].h.forEach((item, i) => { html += `<div class="win-quiz-item"><input type="checkbox" id="mtvt_h_${idx}_${i}" value="h" data-cat="${k}"><label for="mtvt_h_${idx}_${i}">${item}</label></div>`; });
        html += `</div><div class="mtvt-col"><div class="mtvt-col-title low">${lowTitle}</div>`;
        window.mtvtDb[k].l.forEach((item, i) => { html += `<div class="win-quiz-item"><input type="checkbox" id="mtvt_l_${idx}_${i}" value="l" data-cat="${k}"><label for="mtvt_l_${idx}_${i}">${item}</label></div>`; });
        html += `</div></div></div>`; idx++;
    }
    document.getElementById('mtvtForm').innerHTML = html;
}

async function submitMTVT() {
    const mtvtColors = { 
        "Thẩm mỹ": "#8BC34A", "Kinh tế": "#1A237E", "Cá nhân": "#FF7043", "Quyền lực": "#F44336", "Vị tha": "#FFCA28", "Quy tắc": "#212121", "Lý thuyết": "#795548",
        "Aesthetic": "#8BC34A", "Economic": "#1A237E", "Individualistic": "#FF7043", "Power": "#F44336", "Altruistic": "#FFCA28", "Regulatory": "#212121", "Theoretical": "#795548"
    };
    const btn = document.getElementById('btn-save-mtvt'); const oldText = btn.innerHTML;
    btn.innerHTML = '<span class="loader-spin"></span> ' + (currentLang === 'en' ? 'Syncing...' : 'Đang đồng bộ...'); btn.disabled = true;

    try {
        const scores = {}; for (const k in window.mtvtDb) scores[k] = 50; 
        document.querySelectorAll('#mtvtForm input:checked').forEach(cb => {
            if (cb.value === 'h') scores[cb.dataset.cat] += 5;
            if (cb.value === 'l') scores[cb.dataset.cat] -= 5;
        });

        document.getElementById('mtvt-result').style.display = 'block';
        document.getElementById('mtvt-user-title').innerText = (currentLang === 'en' ? "Motivators Analysis of: " : "Phân tích Động lực của: ") + window.currentUserInfo.full_name;
        
        let chartHtml = '';
        Object.entries(scores).sort((a, b) => b[1] - a[1]).forEach(([k, sc]) => {
            const finalSc = sc > 100 ? 100 : (sc < 0 ? 0 : sc);
            chartHtml += `<div class="bar-row"><div class="bar-label">${k}</div><div class="bar-track"><div class="bar-fill" style="width: ${finalSc}%; background: ${mtvtColors[k]};"></div></div><div class="bar-score">${finalSc}</div></div>`;
        });
        document.getElementById('mtvt-chart-area').innerHTML = chartHtml;
        
        await saveTestResult('MTVT', scores);
    } catch(error) { console.error(error); } finally { btn.innerHTML = oldText; btn.disabled = false; }
}

// =====================================================================
// 5. LỊCH SỬ & AI
// =====================================================================
async function loadHistory() {
    const container = document.getElementById('history-container');
    container.innerHTML = '<div style="text-align:center; padding: 20px;"><span class="loader-spin blue"></span> ' + (currentLang === 'en' ? 'Syncing test history from Counseling server...' : 'Đang đồng bộ hóa dữ liệu từ Hệ thống tư vấn tâm lý...') + '</div>';
    try {
        const res = await fetch('?local_api=history'); const data = await res.json();
        if (data.success && data.data && data.data.length > 0) renderHistory(data.data);
        else container.innerHTML = `<i style="color:var(--win-text-muted);">${data.msg || (currentLang === 'en' ? "You haven't taken any tests yet." : "Bạn chưa làm bài kiểm tra nào.")}</i>`;
    } catch (e) { container.innerHTML = '<i style="color:red;">' + (currentLang === 'en' ? 'Proxy Connection Error. Please reload page.' : 'Lỗi kết nối Server Proxy. Vui lòng tải lại trang.') + '</i>'; }
}

function renderHistory(records) {
    let html = '';
    const unit = currentLang === 'en' ? ' pts' : 'đ';
    records.forEach(item => {
        let resultObj = {}; 
        try { resultObj = typeof item.result_data === 'string' ? JSON.parse(item.result_data) : item.result_data; } catch(e) {}
        let displayContent = '';
        if (item.test_type === 'HOLLAND') {
            const maxGroup = Object.keys(resultObj).reduce((a,b) => resultObj[a] > resultObj[b] ? a : b);
            displayContent = (currentLang === 'en' ? `<strong>Dominant Group: ${maxGroup}</strong>` : `<strong>Nổi trội nhất: Nhóm ${maxGroup}</strong>`) + `<br><span style="color:#666">${Object.entries(resultObj).map(([k,v]) => `${k}:${v}`).join(', ')}</span>`;
        } else if (item.test_type === 'DISC') {
            displayContent = `<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 5px;"><div><b style="color:#e74c3c">D:</b> ${resultObj.D}${unit}</div><div><b style="color:#f39c12">I:</b> ${resultObj.I}${unit}</div><div><b style="color:#27ae60">S:</b> ${resultObj.S}${unit}</div><div><b style="color:#2980b9">C:</b> ${resultObj.C}${unit}</div></div>`;
        } else if (item.test_type === 'MTVT') {
            const topTitle = currentLang === 'en' ? 'Top 3 Driving Motivators:' : 'Top 3 động lực dẫn dắt:';
            displayContent = `<strong>${topTitle}</strong><ul style="margin-top: 8px; padding-left: 20px;">${Object.entries(resultObj).sort((a,b) => b[1] - a[1]).slice(0, 3).map(([k,v]) => `<li><b>${k} (${v}${unit})</b></li>`).join('')}</ul>`;
        } else if (item.test_type === 'MI') {
            const miNames = currentLang === 'en' ? { 
                "Linguistic": "Linguistic", "Logical": "Logical", "Spatial": "Spatial", "Musical": "Musical", "Kinesthetic": "Kinesthetic", "Interpersonal": "Interpersonal", "Intrapersonal": "Intrapersonal", "Naturalist": "Naturalist" 
            } : { 
                "Linguistic": "Ngôn ngữ", "Logical": "Logic", "Spatial": "Không gian", "Musical": "Âm nhạc", "Kinesthetic": "Vận động", "Interpersonal": "Tương tác", "Intrapersonal": "Nội tâm", "Naturalist": "Thiên nhiên" 
            };
            const topTitle = currentLang === 'en' ? 'Top 2 Intelligences:' : 'Top 2 Trí thông minh:';
            displayContent = `<strong>${topTitle}</strong><ul style="margin-top: 8px; padding-left: 20px;">${Object.entries(resultObj).sort((a,b) => b[1] - a[1]).slice(0, 2).map(([k,v]) => `<li><b>${miNames[k] || k} (${v}${unit})</b></li>`).join('')}</ul>`;
        }
        html += `<div class="history-card"><div class="history-header"><div><span class="tag-badge">${item.test_type}</span><span class="history-time" style="margin-left: 8px;">🕒 ${item.created_at}</span></div><button onclick="deleteHistoryItem(${item.id})" style="background:none; border:none; color:#c62828; cursor:pointer; font-size:16px; padding:0;">🗑️</button></div><div class="history-body">${displayContent}</div></div>`;
    });
    document.getElementById('history-container').innerHTML = html;
}

async function deleteHistoryItem(id) {
    WinUI.confirm(currentLang === 'en' ? "Confirm Delete" : "Xác nhận xóa", currentLang === 'en' ? "Delete this test result?" : "Xóa kết quả trắc nghiệm này?", async function() {
        const res = await fetch('?local_api=delete', { method: 'POST', body: JSON.stringify({ id: id }) });
        const data = await res.json(); 
        if (data.success) loadHistory(); // Tải lại list bằng AJAX
    });
}

async function deleteAllHistory() {
    if (!confirm(currentLang === 'en' ? 'Delete ALL history from system?' : 'Xóa TẤT CẢ lịch sử khỏi hệ thống?')) return;
    try {
        const res = await fetch('?local_api=delete_all', { method: 'POST', body: JSON.stringify({}) });
        const data = await res.json(); if (data.success) loadHistory();
    } catch (e) {}
}

async function analyzeCareerWithAI() {
    const btnAsk = document.getElementById('btn-analyze-ai');
    const loadingDiv = document.getElementById('ai-loading');
    const resDiv = document.getElementById('ai-result');
    
    btnAsk.disabled = true; resDiv.style.display = 'none'; loadingDiv.style.display = 'block';

    try {
        const resHist = await fetch('?local_api=history');
        let historyPrompt = '';
        if (resHist.ok) {
            const data = await resHist.json();
            if (data.success && data.data && data.data.length > 0) {
                historyPrompt += currentLang === 'en' ? "Below are my career test scores. Please consult/advise me:\n" : "Dưới đây là điểm số trắc nghiệm nghề nghiệp của tôi. Hãy tư vấn cho tôi:\n";
                const holland = data.data.find(i => i.test_type === 'HOLLAND'); const disc = data.data.find(i => i.test_type === 'DISC'); const mtvt = data.data.find(i => i.test_type === 'MTVT'); const mi = data.data.find(i => i.test_type === 'MI');
                if (holland) { try { const resObj = typeof holland.result_data === 'string' ? JSON.parse(holland.result_data) : holland.result_data; const maxGroup = Object.keys(resObj).reduce((a,b) => resObj[a] > resObj[b] ? a : b); historyPrompt += `- Holland: Nhóm cao nhất là ${maxGroup}\n`; } catch(e) {} }
                if (disc) { try { const resObj = typeof disc.result_data === 'string' ? JSON.parse(disc.result_data) : disc.result_data; historyPrompt += `- DISC: D:${resObj.D}, I:${resObj.I}, S:${resObj.S}, C:${resObj.C}\n`; } catch(e) {} }
                if (mtvt) { try { const resObj = typeof mtvt.result_data === 'string' ? JSON.parse(mtvt.result_data) : mtvt.result_data; const top3 = Object.entries(resObj).sort((a,b) => b[1] - a[1]).slice(0, 3); historyPrompt += `- Động lực (MTVT): Top 3 là ${top3.map(([k,v]) => k).join(', ')}\n`; } catch(e) {} }
                if (mi) { try { const resObj = typeof mi.result_data === 'string' ? JSON.parse(mi.result_data) : mi.result_data; const top2 = Object.entries(resObj).sort((a,b) => b[1] - a[1]).slice(0, 2); historyPrompt += `- Đa trí tuệ (MI): Ưu thế là ${top2.map(([k,v]) => k).join(', ')}\n`; } catch(e) {} }
            }
        }

        if (!historyPrompt) historyPrompt = currentLang === 'en' ? "I haven't taken any tests yet. Suggest some careers in the technology and business fields." : "Tôi chưa thực hiện bài kiểm tra nào. Gợi ý vài ngành nghề khối công nghệ kinh tế.";

        const res = await fetch('?local_api=ai_proxy', {
            method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({user_text: historyPrompt, lang: currentLang})
        });
        
        const d = await res.json();
        loadingDiv.style.display = 'none'; resDiv.style.display = 'block';
        
        if (d.advice) resDiv.innerHTML = typeof marked !== 'undefined' ? marked.parse(d.advice) : d.advice;
        else resDiv.innerText = d.msg || (currentLang === 'en' ? "AI response error." : "Lỗi phản hồi từ AI.");
    } catch (e) {
        loadingDiv.style.display = 'none'; resDiv.style.display = 'block';
        resDiv.innerHTML = '<div style="color:red;">' + (currentLang === 'en' ? 'Internal proxy server connection error.' : 'Máy chủ nội bộ (Proxy) đang gặp sự cố kết nối.') + '</div>';
    } finally { btnAsk.disabled = false; }
}

window.pageDestroy = function() {
    if (window.discChartInstance) { try { window.discChartInstance.destroy(); } catch(e) {} window.discChartInstance = null; }
    if (window.hollandChartInstance) { try { window.hollandChartInstance.destroy(); } catch(e) {} window.hollandChartInstance = null; }
    if (window.miChartInstance) { try { window.miChartInstance.destroy(); } catch(e) {} window.miChartInstance = null; }
};

window.pageInit = async function() {
        try {
        const r = await fetch('api/consulting_questions_api.php');
        if(r.ok) {
            const data = await r.json();
            window.discData = data.discData; 
            window.mtvtDb = data.mtvtDb;
            initDISC(); 
            initMTVT();
            window.hollandData = data.hollandData || {};
            window.miData = data.miData || {};
            initHolland();
            initMI();
        }
    } catch(e) { console.error("Lỗi API Câu hỏi DISC/MTVT:", e); }
};

(function() { setTimeout(function() { if (typeof window.pageInit === 'function') window.pageInit(); }, 100); })();
</script>

<style>
/* FIX LỖI AMOLED TRẮNG XÓA NÚT BẤM */
html[data-theme="dark"] body .win-btn,
html[data-theme="dark"] body .tag-badge {
    color: #000000;
    font-weight: bold;
}
</style>
<?php require_once 'includes/footer.php'; ?>