<?php
// captcha_challenge.php - LG3 Shield Security Verification
session_start();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? $_SERVER['REQUEST_URI'] ?? '/login.php';
if (!str_starts_with($redirect, '/') || str_contains($redirect, 'captcha_challenge.php')) {
    $redirect = '/login.php';
}

// Nếu là API / AJAX request từ App thì trả về JSON mã 252
$isApi = (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
         (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if ($isApi && $action !== 'verify_captcha') {
    header("HTTP/1.1 252 LG3 Shield Rate Limit");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 252,
        'shield' => 'LG3_SHIELD',
        'msg' => 'Dính mã 252 LG3 Shield Rate Limit. Vui lòng giải Captcha!',
        'captcha_url' => '/captcha_challenge.php?redirect=' . urlencode($redirect)
    ]);
    exit;
}

// Xử lý nộp kết quả Captcha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'verify_captcha') {
    $userAnswer = trim($_POST['captcha_answer'] ?? '');
    $expectedAnswer = $_SESSION['lg3_captcha_answer'] ?? null;
    
    $isValid = false;
    if (!empty($userAnswer) && $expectedAnswer !== null && (int)$userAnswer === (int)$expectedAnswer) {
        $isValid = true;
    }
    
    if ($isValid) {
        // Cấp thẻ bài lg3_shield_pass ký theo Fingerprint (IP + User-Agent) có hạn trong 15 phút
        $client_ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $client_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $salt = 'lg3_secret_salt_2026_secure';
        $now = time();
        $signature = hash_hmac('sha256', "{$client_ip}|{$client_ua}|{$now}", $salt);
        $token = "{$now}.{$signature}";

        setcookie('lg3_shield_pass', $token, [
            'expires' => $now + 900, // 15 phút (900 giây)
            'path' => '/',
            'httponly' => true,      // Chống đánh cắp cookie qua XSS / document.cookie
            'samesite' => 'Lax'
        ]);
        $_SESSION['lg3_shield_pass'] = $token;
        
        if ($isApi) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'success', 'redirect' => $redirect]);
            exit;
        }
        
        header("Location: " . $redirect);
        exit;
    } else {
        $error = "Mã xác nhận không chính xác. Vui lòng thử lại!";
    }
}

// Luôn đặt HTTP status code là 252 khi hiển thị trang Captcha do dính Shield
header("HTTP/1.1 252 LG3 Shield Rate Limit");

// Sinh câu đố toán học ngẫu nhiên
$num1 = rand(1, 9);
$num2 = rand(1, 9);
$_SESSION['lg3_captcha_answer'] = $num1 + $num2;

// Mã hóa dữ liệu vẽ (để bot đọc raw HTML bằng Regex KHÔNG THỂ tìm thấy text thô)
$captcha_raw = "{$num1} + {$num2} = ?";
$xor_key = rand(11, 89);
$encoded_chars = [];
for ($i = 0; $i < strlen($captcha_raw); $i++) {
    $encoded_chars[] = ord($captcha_raw[$i]) ^ $xor_key;
}
$render_token = base64_encode(json_encode([
    'k' => $xor_key,
    'd' => $encoded_chars,
    't' => time()
]));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực bảo vệ [HTTP 252] - LG3 Shield</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, sans-serif; }
        body { background: #0b1120; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; color: #f8fafc; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 20px; width: 100%; max-width: 440px; padding: 32px 24px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5); text-align: center; }
        .badge-252 { display: inline-block; background: rgba(2, 132, 199, 0.2); color: #38bdf8; border: 1px solid #0284c7; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; margin-bottom: 12px; letter-spacing: 1px; }
        .icon-box { width: 64px; height: 64px; border-radius: 16px; background: linear-gradient(135deg, #0284c7, #0369a1); display: flex; align-items: center; justify-content: center; margin: 0 auto 14px auto; font-size: 28px; color: #ffffff; box-shadow: 0 10px 15px -3px rgba(2, 132, 199, 0.4); }
        h1 { font-size: 20px; font-weight: 700; margin-bottom: 8px; color: #ffffff; }
        p { font-size: 13px; color: #94a3b8; line-height: 1.5; margin-bottom: 24px; }
        .puzzle-box { background: #0f172a; border: 1px dashed #38bdf8; border-radius: 14px; padding: 16px; margin-bottom: 20px; }
        .inp-code { width: 100%; background: #0f172a; border: 1.5px solid #334155; border-radius: 12px; padding: 12px; font-size: 18px; color: #ffffff; text-align: center; font-weight: 700; outline: none; margin-bottom: 16px; transition: all 0.2s; }
        .inp-code:focus { border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.2); }
        .btn-verify { width: 100%; background: linear-gradient(135deg, #0284c7, #0369a1); color: #ffffff; border: none; border-radius: 12px; padding: 14px; font-size: 15px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: transform 0.1s, opacity 0.2s; }
        .btn-verify:active { transform: scale(0.98); }
        .btn-verify:hover { opacity: 0.95; }
        .error { color: #f87171; font-size: 12px; margin-bottom: 12px; font-weight: 600; }
        .footer { margin-top: 20px; font-size: 11px; color: #64748b; }
        .btn-refresh { background: #1e293b; border: 1px solid #334155; color: #38bdf8; width: 40px; height: 40px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 15px; transition: all 0.2s; }
        .btn-refresh:hover { background: #334155; transform: rotate(45deg); }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge-252">🛡️ HTTP 252 • LG3 SHIELD RATE LIMIT</div>
        <div class="icon-box">
            <i class="fas fa-shield-halved"></i>
        </div>
        <h1>Xác thực bảo vệ LG3</h1>
        <p>Hệ thống phát hiện lưu lượng truy cập tăng nhanh bất thường. Vui lòng giải phép tính dưới đây để tiếp tục:</p>

        <?php if (!empty($error)): ?>
            <div class="error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="captcha_challenge.php?action=verify_captcha&redirect=<?= urlencode($redirect) ?>">
            <div class="puzzle-box">
                <div style="font-size: 12px; color: #94a3b8; margin-bottom: 10px;">Vui lòng tính kết quả trong hình:</div>
                <div style="display: flex; justify-content: center; align-items: center; gap: 10px;">
                    <canvas id="captchaCanvas" width="220" height="64" style="border-radius: 10px; background: #0b1120; border: 1px solid #1e293b; box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);"></canvas>
                    <button type="button" class="btn-refresh" onclick="location.reload()" title="Đổi phép tính khác">
                        <i class="fas fa-arrows-rotate"></i>
                    </button>
                </div>
            </div>
            
            <input type="number" name="captcha_answer" class="inp-code" placeholder="Nhập kết quả..." required autofocus autocomplete="off">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
            
            <button type="submit" class="btn-verify">
                <i class="fas fa-check-circle"></i> Xác nhận & Tiếp tục tải trang
            </button>
        </form>

        <div class="footer">
            <i class="fas fa-lock"></i> LG3 Shield Anti-DDoS Protection • THPT Lạng Giang Số 3
        </div>
    </div>

    <script>
    (function() {
        const canvas = document.getElementById('captchaCanvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        
        // Giải mã chuỗi vẽ phía Client (Text không hề tồn tại trong HTML DOM)
        const tokenStr = "<?= $render_token ?>";
        let text = "";
        try {
            const payload = JSON.parse(atob(tokenStr));
            const k = payload.k;
            text = payload.d.map(c => String.fromCharCode(c ^ k)).join('');
        } catch(e) {
            text = "?";
        }

        // 1. Vẽ nền gradient
        const bgGrad = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
        bgGrad.addColorStop(0, '#090d16');
        bgGrad.addColorStop(1, '#0f172a');
        ctx.fillStyle = bgGrad;
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // 2. Vẽ các hạt nhiễu (noise dots)
        for (let i = 0; i < 40; i++) {
            ctx.fillStyle = `rgba(${Math.floor(Math.random()*150 + 50)}, ${Math.floor(Math.random()*150 + 100)}, 255, ${Math.random() * 0.35 + 0.15})`;
            ctx.beginPath();
            ctx.arc(Math.random() * canvas.width, Math.random() * canvas.height, Math.random() * 1.8 + 0.5, 0, Math.PI * 2);
            ctx.fill();
        }

        // 3. Vẽ các đường gạch nhiễu (noise lines) cắt ngang
        for (let i = 0; i < 4; i++) {
            ctx.strokeStyle = `rgba(${Math.floor(Math.random()*100 + 50)}, ${Math.floor(Math.random()*150 + 100)}, 255, ${Math.random() * 0.35 + 0.2})`;
            ctx.lineWidth = Math.random() * 1.5 + 0.8;
            ctx.beginPath();
            ctx.moveTo(Math.random() * canvas.width * 0.2, Math.random() * canvas.height);
            ctx.bezierCurveTo(
                Math.random() * canvas.width, Math.random() * canvas.height,
                Math.random() * canvas.width, Math.random() * canvas.height,
                canvas.width - Math.random() * 20, Math.random() * canvas.height
            );
            ctx.stroke();
        }

        // 4. Vẽ từng ký tự với góc xoay ngẫu nhiên (rotation) và hiệu ứng phát sáng (glow)
        ctx.textBaseline = 'middle';
        const charList = text.split('');
        const startX = 25;
        const stepX = (canvas.width - 50) / charList.length;

        charList.forEach((ch, idx) => {
            ctx.save();
            const x = startX + idx * stepX + (Math.random() * 4 - 2);
            const y = canvas.height / 2 + (Math.random() * 6 - 3);
            const angle = (Math.random() * 24 - 12) * Math.PI / 180; // xoay nhẹ -12 đến +12 độ

            ctx.translate(x, y);
            ctx.rotate(angle);

            ctx.font = `bold ${Math.floor(Math.random() * 4 + 24)}px 'Be Vietnam Pro', sans-serif`;
            ctx.shadowColor = 'rgba(56, 189, 248, 0.6)';
            ctx.shadowBlur = 8;
            ctx.fillStyle = idx % 2 === 0 ? '#38bdf8' : '#7dd3fc';
            ctx.fillText(ch, 0, 0);

            ctx.restore();
        });
    })();
    </script>
</body>
</html>
