<?php 
// File: /grammar_check.php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'includes/config.php';

// 1. TẠO TOKEN JWT BỌC THÉP CHO SSO
function generate_sso_token($user) {
    $secret = defined('SSO_SECRET_KEY') ? SSO_SECRET_KEY : "khoa_bi_mat_ket_noi_hai_app_123456_secure"; 
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'sbd' => $user['username'] ?? 'unknown', 
        'name' => $user['full_name'] ?? __('guest', 'Khách'), 
        'role' => $user['role'] ?? 'STUDENT', 
        'exp' => time() + 900
    ]);
    $b64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $b64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    $signature = hash_hmac('sha256', $b64Header . "." . $b64Payload, $secret, true);
    return $b64Header . "." . $b64Payload . "." . str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
}
$jwt_token = generate_sso_token($_SESSION['user'] ?? []);

// 2. CỔNG GIAO TIẾP PROXY GỌI AI TRỰC TIẾP QUA PHP
if (isset($_GET['local_api'])) {
    while (ob_get_level()) { ob_end_clean(); } 
    header('Content-Type: application/json');
    require_once 'includes/config.php';
    
    $inputData = json_decode(file_get_contents('php://input'), true) ?: [];
    $user_text = $inputData['user_text'] ?? '';
    $language = $inputData['language'] ?? 'en';
    $app_lang = $inputData['app_lang'] ?? ($_SESSION['lang'] ?? 'vi');
    $explanation_lang = ($app_lang === 'en') ? 'English' : 'Vietnamese';
    
    if (empty($user_text)) {
        echo json_encode(['status' => 'error', 'msg' => __('empty_text', 'Văn bản trống')]);
        exit;
    }
    
    $system_msg = "You are a professional grammar checker. Analyze the user's text (in English or Vietnamese, depending on the language option) for spelling, grammar, punctuation, and style issues.
Return the result strictly as a valid JSON object matching the following structure. Do not output any markdown code blocks, just raw JSON.
JSON Structure:
{
  \"status\": \"success\",
  \"issues\": [
    {
      \"wrong\": \"the exact wrong word or phrase from the user input\",
      \"correct\": \"the corrected word or phrase\",
      \"explanation\": \"Brief explanation of the error and correction in " . $explanation_lang . "\"
    }
  ]
}
If there are no errors, return:
{
  \"status\": \"success\",
  \"issues\": []
}
Important: The explanation MUST be written in " . $explanation_lang . ".
Make sure all keys and string values are properly escaped for valid JSON.";

    $gemini_key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
    $gemini_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent?key=" . $gemini_key;
    $gemini_payload = [
        'contents' => [['parts' => [['text' => "Language context: " . $language . "\nText to check:\n" . $user_text]]]],
        'systemInstruction' => ['parts' => [['text' => $system_msg]]],
        'generationConfig' => [
            'temperature' => 0.2,
            'maxOutputTokens' => 2000,
            'responseMimeType' => 'application/json'
        ]
    ];
    
    $ch = curl_init($gemini_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($gemini_payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $ai_response = null;
    if ($http_code === 200 && $res) {
        $res_data = json_decode($res, true);
        if (isset($res_data['candidates'][0]['content']['parts'][0]['text'])) {
            $ai_response = $res_data['candidates'][0]['content']['parts'][0]['text'];
        }
    }
    
    // Fallback Groq
    if (!$ai_response) {
        $groq_key = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
        $groq_url = "https://api.groq.com/openai/v1/chat/completions";
        $groq_payload = [
            'messages' => [
                ['role' => 'system', 'content' => $system_msg],
                ['role' => 'user', 'content' => "Language context: " . $language . "\nText to check:\n" . $user_text]
            ],
            'model' => 'llama-3.3-70b-versatile',
            'max_tokens' => 2000,
            'temperature' => 0.2,
            'response_format' => ['type' => 'json_object']
        ];
        
        $ch = curl_init($groq_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($groq_payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $groq_key]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200 && $res) {
            $res_data = json_decode($res, true);
            if (isset($res_data['choices'][0]['message']['content'])) {
                $ai_response = $res_data['choices'][0]['message']['content'];
            }
        }
    }
    
    if ($ai_response) {
        echo $ai_response;
    } else {
        echo json_encode([
            'status' => 'error',
            'msg' => __('ai_connect_error', 'Không thể kết nối đến trí tuệ nhân tạo (Mã lỗi: 502)')
        ]);
    }
    exit;
}

// 3. RENDER GIAO DIỆN CHÍNH
require_once 'includes/header.php'; 

// Gọi View hiển thị
require_once 'views/grammar_check_view.php'; 

require_once 'includes/footer.php'; 
?>