<?php
// captcha_challenge.php - LG3 Shield Security Verification
session_start();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '/login.php';
// Đảm bảo redirect an toàn, chỉ trong nội bộ site
if (!str_starts_with($redirect, '/')) {
    $redirect = '/login.php';
}

// Xử lý xác thực Captcha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'verify_captcha') {
    $userAnswer = trim($_POST['captcha_answer'] ?? '');
    $expectedAnswer = $_SESSION['lg3_captcha_answer'] ?? null;
    
    // Hỗ trợ cả câu hỏi bảo mật lẫn checkbox xác thực
    $isValid = false;
    if (!empty($userAnswer) && $expectedAnswer !== null && (int)$userAnswer === (int)$expectedAnswer) {
        $isValid = true;
    } elseif (isset($_POST['is_human_verified']) && $_POST['is_human_verified'] === '1') {
        $isValid = true;
    }
    
    if ($isValid) {
        // Cấp thẻ bài lg3_shield_pass trong 30 phút
        $token = hash('sha256', session_id() . 'lg3_secret_salt_2026_' . time());
        setcookie('lg3_shield_pass', $token, [
            'expires' => time() + 1800,
            'path' => '/',
            'httponly' => false,
            'samesite' => 'Lax'
        ]);
        $_SESSION['lg3_shield_pass'] = $token;
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['status' => 'success', 'redirect' => $redirect]);
            exit;
        }
        
        header("Location: " . $redirect);
        exit;
    } else {
        $error = "Mã xác nhận không chính xác. Vui lòng thử lại!";
    }
}

// Sinh câu đố toán học đơn giản
$num1 = rand(1, 9);
$num2 = rand(1, 9);
$_SESSION['lg3_captcha_answer'] = $num1 + $num2;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực bảo vệ - LG3 Shield</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, sans-serif; }
        body { background: #0b1120; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; color: #f8fafc; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 20px; width: 100%; max-width: 440px; padding: 32px 24px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5); text-align: center; }
        .icon-box { width: 64px; height: 64px; border-radius: 16px; background: linear-gradient(135deg, #0284c7, #0369a1); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; font-size: 28px; color: #ffffff; box-shadow: 0 10px 15px -3px rgba(2, 132, 199, 0.4); }
        h1 { font-size: 20px; font-weight: 700; margin-bottom: 8px; color: #ffffff; }
        p { font-size: 13px; color: #94a3b8; line-height: 1.5; margin-bottom: 24px; }
        .puzzle-box { background: #0f172a; border: 1px dashed #38bdf8; border-radius: 14px; padding: 18px; margin-bottom: 20px; }
        .puzzle-text { font-size: 18px; font-weight: 800; color: #38bdf8; letter-spacing: 2px; }
        .inp-code { width: 100%; background: #0f172a; border: 1.5px solid #334155; border-radius: 12px; padding: 12px; font-size: 18px; color: #ffffff; text-align: center; font-weight: 700; outline: none; margin-bottom: 16px; transition: all 0.2s; }
        .inp-code:focus { border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.2); }
        .btn-verify { width: 100%; background: linear-gradient(135deg, #0284c7, #0369a1); color: #ffffff; border: none; border-radius: 12px; padding: 14px; font-size: 15px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: transform 0.1s, opacity 0.2s; }
        .btn-verify:active { transform: scale(0.98); }
        .btn-verify:hover { opacity: 0.95; }
        .error { color: #f87171; font-size: 12px; margin-bottom: 12px; font-weight: 600; }
        .footer { margin-top: 20px; font-size: 11px; color: #64748b; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-box">
            <i class="fas fa-shield-halved"></i>
        </div>
        <h1>Xác thực bảo vệ LG3</h1>
        <p>Hệ thống phát hiện lưu lượng truy cập tăng đột biến. Vui lòng giải phép tính đơn giản để tiếp tục:</p>

        <?php if (!empty($error)): ?>
            <div class="error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="captcha_challenge.php?action=verify_captcha&redirect=<?= urlencode($redirect) ?>">
            <div class="puzzle-box">
                <div style="font-size: 12px; color: #94a3b8; margin-bottom: 6px;">Vui lòng tính kết quả:</div>
                <div class="puzzle-text"><?= $num1 ?> + <?= $num2 ?> = ?</div>
            </div>
            
            <input type="number" name="captcha_answer" class="inp-code" placeholder="Nhập kết quả..." required autofocus autocomplete="off">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
            
            <button type="submit" class="btn-verify">
                <i class="fas fa-check-circle"></i> Xác nhận & Tiếp tục
            </button>
        </form>

        <div class="footer">
            <i class="fas fa-lock"></i> LG3 Shield Anti-DDoS Protection • THPT Lạng Giang Số 3
        </div>
    </div>
</body>
</html>
