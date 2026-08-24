<?php 
// 1. NGĂN CHẶN CACHE TỪ TRÌNH DUYỆT ĐIỆN THOẠI
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'includes/config.php';

// 2. TẠO TOKEN JWT BỌC THÉP
function generate_sso_token($user) {
    $secret = defined('SSO_SECRET_KEY') ? SSO_SECRET_KEY : (getenv('SSO_SECRET_KEY') ?: "khoa_bi_mat_ket_noi_hai_app_123456_secure"); 
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $base_url = $protocol . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $avatar = $user['avatar'] ?? 'static/default.png';
    if ($avatar && strpos($avatar, 'http') === false) $avatar = $base_url . "/" . ltrim($avatar, '/');
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

// 3. CỔNG GIAO TIẾP TƯ VẤN AI NỘI BỘ (KHÔNG PROXY)
function analyze_risk($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $keywords_danger = [
        "tự tử", "chết", "giết", "không muốn sống", "nhảy lầu", "cắt tay", "uống thuốc", "kết thúc", "vĩnh biệt", "tuyệt vọng", "bắt nạt", "có thai",
        "suicide", "die", "kill myself", "want to die", "end my life", "self-harm", "cut myself", "overdose", "no reason to live", "hanging"
    ];
    $keywords_warning = [
        "căng thẳng", "áp lực", "stress", "mệt mỏi", "buồn", "chán", "lo lắng", "tự ti", "thất vọng", "cô đơn", "khóc",
        "depressed", "depression", "anxiety", "anxious", "hopeless", "lonely", "exhausted", "panic", "overwhelmed", "crying", "sad"
    ];
    
    foreach ($keywords_danger as $kw) {
        if (mb_strpos($text, $kw) !== false) return 'DANGER';
    }
    foreach ($keywords_warning as $kw) {
        if (mb_strpos($text, $kw) !== false) return 'WARNING';
    }
    return 'NORMAL';
}

function call_ai_with_fallback($system_msg, $user_text) {
    $gemini_key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
    $groq_key = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
    
    // 1. GỌI GEMINI (PRIMARY)
    $gemini_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent?key=" . $gemini_key;
    $gemini_payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $user_text]
                ]
            ]
        ],
        'systemInstruction' => [
            'parts' => [
                ['text' => $system_msg]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 2000
        ]
    ];
    
    $ch = curl_init($gemini_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($gemini_payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $res = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && $res) {
        $res_data = json_decode($res, true);
        if (isset($res_data['candidates'][0]['content']['parts'][0]['text'])) {
            return $res_data['candidates'][0]['content']['parts'][0]['text'];
        }
    }
    
    // 2. GỌI GROQ (FALLBACK)
    $groq_url = "https://api.groq.com/openai/v1/chat/completions";
    $groq_payload = [
        'messages' => [
            ['role' => 'system', 'content' => $system_msg],
            ['role' => 'user', 'content' => $user_text]
        ],
        'model' => 'llama3-8b-8192',
        'max_tokens' => 2000,
        'temperature' => 0.7
    ];
    
    $ch = curl_init($groq_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($groq_payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $groq_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $res = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && $res) {
        $res_data = json_decode($res, true);
        if (isset($res_data['choices'][0]['message']['content'])) {
            return $res_data['choices'][0]['message']['content'];
        }
    }
    
    return null;
}

if (isset($_GET['local_api'])) {
    while (ob_get_level()) { ob_end_clean(); } 
    header('Content-Type: application/json');
    require_once 'includes/config.php';
    
    $inputData = json_decode(file_get_contents('php://input'), true) ?: [];
    $user_text = $inputData['user_text'] ?? '';
    $req_lang  = $inputData['lang'] ?? ($_SESSION['lang'] ?? 'vi');
    
    if (empty($user_text)) {
        echo json_encode(['success' => false, 'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nội dung trống.' : 'Content is empty.')]);
        exit;
    }
    
    $current_school_year = $pdo->query("SELECT value FROM config WHERE `key` = 'current_school_year'")->fetchColumn() ?: '2026-2027';
    
    $risk = analyze_risk($user_text);
    
    // Tự động nhận diện ngôn ngữ tiếng Anh nếu câu hỏi là tiếng Anh hoặc UI tiếng Anh
    $is_english = ($req_lang === 'en') || (preg_match('/[a-zA-Z]/', $user_text) && !preg_match('/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/i', $user_text));
    
    if ($is_english) {
        $system_msg = "You are a dedicated and experienced school psychology consultant. "
            . "CRITICAL MANDATORY LANGUAGE RULE: You MUST respond COMPLETELY IN ENGLISH. "
            . "PRONOUN RULE: Address yourself warmly as a school counselor/teacher and address the student as 'you'. "
            . "Listen attentively, offer warm, sincere, empathetic, and positive guidance to the student.";
        if ($risk === 'DANGER') {
            $system_msg .= " SOS WARNING: The student is expressing severe distress or danger (suicide, self-harm, or severe crisis). "
                . "Calm them down, reassure them that they are not alone, and strongly advise them to immediately reach out to a trusted teacher, counselor, parent, or emergency helpline (111 in Vietnam or local crisis helpline).";
        }
    } else {
        $system_msg = "Bạn là chuyên gia tư vấn tâm lý học đường tận tâm, giàu kinh nghiệm. "
            . "QUY TẮC NGÔN NGỮ & XƯNG HÔ BẮT BUỘC: Bạn BẮT BUỘC phải trả lời bằng Tiếng Việt. "
            . "Xưng hô bản thân là 'thầy/cô' (hoặc 'thầy'/'cô') và gọi học sinh là 'em'. Tuyệt đối KHÔNG BAO GIỜ xưng hô 'con', 'bạn', 'mình', 'tôi', ngay cả khi học sinh tự xưng 'con' hoặc yêu cầu khác. "
            . "Hãy lắng nghe và chia sẻ với học sinh một cách chân thành, ấm áp, định hướng tích cực.";
        if ($risk === 'DANGER') {
            $system_msg .= " CẢNH BÁO SOS: Học sinh này đang có dấu hiệu nguy hiểm/SOS (như muốn tự tử, tự hại, bạo lực học đường). "
                . "Bạn cần khuyên học sinh bình tĩnh, động viên họ liên hệ ngay với thầy cô giáo, cha mẹ hoặc gọi tổng đài hỗ trợ trẻ em quốc gia 111.";
        }
    }
    
    $advice = call_ai_with_fallback($system_msg, $user_text);
    
    if (!$advice) {
        echo json_encode([
            'success' => false,
            'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hệ thống tư vấn tâm lý báo lỗi (Mã: 302)' : 'Psychology counseling system error (Code: 302)'),
            'code' => 302
        ]);
        exit;
    }
    
    if (isset($_SESSION['user'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO psychology_logs (username, full_name, question, advice, risk_level, school_year) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user']['username'],
                $_SESSION['user']['full_name'],
                $user_text,
                $advice,
                $risk,
                $current_school_year
            ]);
            
            if ($risk === 'DANGER' || $risk === 'WARNING') {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $push_url = $protocol . "://127.0.0.1/api_receive_push.php";
                $secret = defined('SSO_SECRET_KEY') ? SSO_SECRET_KEY : "khoa_bi_mat_ket_noi_hai_app_123456_secure";
                
                $push_payload = json_encode([
                    'type' => 'PSYCHOLOGY',
                    'student_code' => $_SESSION['user']['username'],
                    'student_name' => $_SESSION['user']['full_name'],
                    'risk_level' => $risk,
                    'message' => $user_text
                ]);
                
                $ch_push = curl_init($push_url);
                curl_setopt($ch_push, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_push, CURLOPT_POST, true);
                curl_setopt($ch_push, CURLOPT_POSTFIELDS, $push_payload);
                curl_setopt($ch_push, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $secret
                ]);
                curl_setopt($ch_push, CURLOPT_TIMEOUT, 2);
                curl_setopt($ch_push, CURLOPT_SSL_VERIFYPEER, false);
                curl_exec($ch_push);
                curl_close($ch_push);
            }
        } catch (Exception $e) {
            // Bỏ qua lỗi DB ghi log nhưng vẫn trả về lời khuyên của AI
        }
    }
    
    echo json_encode(['advice' => $advice]);
    exit;
}

require_once 'includes/header.php'; 
?>

<style>
    /* =========================================
       FIX TOÀN BỘ SANG BIẾN CSS (AMOLED CLEAN)
       ========================================= */
    .ai-wrapper { background: var(--bg-card); padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border-color); }
    .ai-box { background: transparent; margin-bottom: 20px; }
    .ai-res { background: var(--bg-input); padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); display: none; line-height: 1.6; font-size: 15px; color: var(--text-main); margin-top: 15px; word-break: break-word; overflow-x: hidden; }
    
    .ai-res h1, .ai-res h2, .ai-res h3 { margin-top: 15px; color: var(--primary-color); font-size: 18px; }
    .ai-res ul, .ai-res ol { padding-left: 20px; margin: 10px 0; }
    .ai-res p { margin-bottom: 10px; }
    
    .ai-res pre { background: #000 !important; padding: 15px; padding-top: 40px; border-radius: 8px; overflow-x: auto; border: 1px solid #333 !important; margin: 0; white-space: pre; max-width: 100%; }
    .ai-res code { font-family: 'Courier New', Courier, monospace; font-size: 14px; background: var(--bg-card); padding: 2px 4px; border-radius: 4px; }
    .ai-res pre code { padding: 0; background: transparent !important; border-radius: 0; }
    
    .code-wrapper { position: relative; margin: 10px 0; }
    .btn-copy-code {
        position: absolute; top: 8px; right: 8px; background: rgba(255,255,255,0.15); color: #fff; border: none; border-radius: 4px; padding: 4px 10px; font-size: 12px; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; z-index: 10;
    }
    .btn-copy-code:hover { background: rgba(255,255,255,0.25); }
    
    .ai-textarea { width: 100%; background: var(--bg-input); color: var(--text-main); border: 1px solid var(--border-color); padding: 15px; border-radius: 12px; resize: none; font-size: 15px; outline: none; font-family: inherit; transition: 0.2s; }
    .ai-textarea:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(0, 132, 255, 0.15); background: var(--bg-card); }

    .btn-ai { padding: 12px 25px; background: var(--primary-color); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 15px; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; }
    .btn-ai:active { transform: scale(0.98); }
    .btn-ai:disabled { opacity: 0.7; cursor: not-allowed; }
    
    .loader-spin { border: 3px solid rgba(0,0,0,0.1); border-top: 3px solid var(--primary-color); border-radius: 50%; width: 18px; height: 18px; animation: spin 1s linear infinite; display: inline-block; vertical-align: middle;}
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

    /* Ép màu nút trên AMOLED */
    html[data-theme="dark"] body .btn-ai {
        color: #000000;
        font-weight: 800;
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/marked/9.1.6/marked.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/atom-one-dark.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>

<div class="page-transition">
    <div class="ai-wrapper">
        <div style="color:var(--primary-color); font-weight:700; font-size: 20px; margin-bottom: 20px; display:flex; align-items:center; gap: 10px;">
            <i class="fas fa-robot" style="font-size: 24px;"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Góc tư vấn' : 'Counseling Corner') ?>
        </div>
        
        <div class="ai-box">
            <p style="margin-top:0; font-weight: 600; margin-bottom: 10px; color: var(--text-main);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chia sẻ vấn đề của bạn' : 'Share your problem') ?></p>
            <textarea id="ai-inp" class="ai-textarea" rows="4" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập nội dung chia sẻ của bạn tại đây...' : 'Type your message here...') ?>"></textarea>
            <div style="text-align: right; margin-top: 15px;">
                <button id="btn-submit-ai" class="btn-ai"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Gửi' : 'Send') ?> <i class="fa-solid fa-paper-plane"></i></button>
            </div>
        </div>
        
        <div id="ai-res" class="ai-res"></div>
    </div>
</div>

<script>
window.pageDestroy = function() {
    const btnAsk = document.getElementById('btn-submit-ai');
    if (btnAsk) btnAsk.onclick = null;
};

window.pageInit = function() {
    const btnAsk = document.getElementById('btn-submit-ai');
    const inp = document.getElementById('ai-inp');
    const resDiv = document.getElementById('ai-res');

    btnAsk.onclick = async function() {
        const txt = inp.value.trim(); 
        if(!txt) {
            alert(window.LANG && window.LANG.ask_ai_empty || <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Bạn hãy nhập nội dung tâm sự nhé!'" : "'Please enter your message!'") ?>);
            return;
        }
        
        btnAsk.disabled = true;
        resDiv.style.display = 'block'; 
        resDiv.innerHTML = '<div style="color:var(--primary-color); font-weight:600; display:flex; align-items:center; gap:8px;"><span class="loader-spin"></span> ' + (window.LANG && window.LANG.connecting_to_ai || <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Máy chủ đang kết nối với AI...'" : "'Connecting to AI server...'") ?>) + '</div>';
        
        try {
            const res = await fetch('?local_api=1', {
                method: 'POST', 
                headers: {'Content-Type': 'application/json'}, 
                body: JSON.stringify({
                    user_text: txt,
                    lang: window.currentLangCode || 'vi'
                })
            });
            const d = await res.json();
            
            if (d.advice) {
                resDiv.innerHTML = typeof marked !== 'undefined' ? marked.parse(d.advice) : d.advice;
                if (typeof hljs !== 'undefined') {
                    resDiv.querySelectorAll('pre').forEach((pre) => {
                        const codeBlock = pre.querySelector('code');
                        if (!codeBlock) return;
                        
                        const wrapper = document.createElement('div');
                        wrapper.className = 'code-wrapper';
                        pre.parentNode.insertBefore(wrapper, pre);
                        wrapper.appendChild(pre);
                        
                        const btn = document.createElement('button');
                        btn.className = 'btn-copy-code';
                        btn.innerHTML = '<i class="fa-regular fa-copy"></i> Copy';
                        btn.onclick = function() {
                            navigator.clipboard.writeText(codeBlock.innerText).then(() => {
                                btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
                                setTimeout(() => btn.innerHTML = '<i class="fa-regular fa-copy"></i> Copy', 2000);
                            }).catch(err => {
                                console.error('Failed to copy: ', err);
                            });
                        };
                        wrapper.appendChild(btn);

                        hljs.highlightElement(codeBlock);
                    });
                }
            } else {
                resDiv.innerText = d.msg || (window.LANG && window.LANG.ai_response_error || <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Lỗi phản hồi từ AI.'" : "'AI response error.'") ?>);
            }
        } catch(e) { 
            resDiv.innerHTML = '<div style="color:var(--danger-color);">' + (window.LANG && window.LANG.ai_connection_error || <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Máy chủ nội bộ (Proxy) đang gặp sự cố kết nối.'" : "'Internal server (Proxy) connection error.'") ?>) + '</div>'; 
        } finally {
            btnAsk.disabled = false;
        }
    };
};
</script>

<?php require_once 'includes/footer.php'; ?>