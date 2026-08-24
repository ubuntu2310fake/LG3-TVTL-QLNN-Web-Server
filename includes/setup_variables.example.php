<?php
// includes/setup_variables.php
// BẢNG ĐIỀU KHIỂN BIẾN MÔI TRƯỜNG DÀNH CHO ADMIN TRƯỜNG

// =================================================================
// 1. CẤU HÌNH APP PYTHON (TƯ VẤN TÂM LÝ)
// =================================================================
define('SSO_SECRET_KEY', 'your_sso_secret_key_here');

// =================================================================
// 2. CẤU HÌNH PUSH NOTIFICATION (VAPID KEYS)
// =================================================================
define('VAPID_PUBLIC_KEY', 'YOUR_VAPID_PUBLIC_KEY');
define('VAPID_PRIVATE_KEY', 'YOUR_VAPID_PRIVATE_KEY');
define('VAPID_SUBJECT', 'mailto:admin@example.com');

// =================================================================
// 3. CẤU HÌNH GỬI EMAIL RESEND API & BREVO SMTP (DUAL DỊCH VỤ)
// =================================================================
define('RESEND_API_KEY', 'YOUR_RESEND_API_KEY');
define('RESEND_FROM_EMAIL', 'Support <support@example.com>');

define('BREVO_SMTP_HOST', 'smtp-relay.brevo.com');
define('BREVO_SMTP_PORT', 587);
define('BREVO_SMTP_USER', 'your_smtp_user');
define('BREVO_SMTP_PASS', 'your_smtp_password');

// =================================================================
// 4. CẤU HÌNH AI (GEMINI & GROQ LLM API KEYS)
// =================================================================
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY');
define('GROQ_API_KEY', 'YOUR_GROQ_API_KEY');
