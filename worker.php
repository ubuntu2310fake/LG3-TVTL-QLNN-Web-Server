<?php
// worker.php — HẠ TẦNG BACKGROUND WORKER PUSH NOTIFICATION NÂNG CẤP
// Xử lý tất cả các loại sự kiện hệ thống (Nền nếp, Tâm lý, Chat, Điểm học tập, Thông báo...)
// Đóng gói JSON Payload chuẩn hóa cho Web PWA và Flutter Mobile App

set_time_limit(0);
ini_set('memory_limit', '512M');

echo "=================================================\n";
echo "   LG3 PUSH NOTIFICATION WORKER - SYSTEM READY\n";
echo "=================================================\n\n";

$possible_paths = [__DIR__ . '/includes/openssl.cnf', 'C:/xampp/apache/bin/openssl.cnf', 'C:/laragon/etc/ssl/openssl.cnf'];
foreach ($possible_paths as $path) {
    if (file_exists($path)) { putenv("OPENSSL_CONF=" . str_replace('/', DIRECTORY_SEPARATOR, $path)); break; }
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/push_helper.php';

echo "   -> ✅ Worker đang chạy... (Nhấn Ctrl+C để dừng)\n";

while (true) {
    try {
        try { $pdo->query("SELECT 1"); } 
        catch (PDOException $e) { require __DIR__ . '/includes/config.php'; }

        $pdo->beginTransaction();
        
        $stmt = $pdo->query("SELECT * FROM notification_queue WHERE status = 'pending' ORDER BY id ASC LIMIT 1 FOR UPDATE");
        $job = $stmt->fetch();

        if ($job) {
            $startTime = microtime(true);
            $jobId = $job['id'];
            $type = strtoupper($job['type'] ?? 'VIOLATION');
            echo "\n[JOB #$jobId] Loại: $type... ";

            $pdo->prepare("UPDATE notification_queue SET status = 'processing' WHERE id = ?")->execute([$jobId]);
            $pdo->commit(); 

            $data = json_decode($job['payload'], true) ?: [];
            $hasError = false;

            // =========================================================
            // CASE 1: CẢNH BÁO TÂM LÝ (PSYCHOLOGY) — CHỈ BÁO VỀ CHO GVCN CỦA HS
            // =========================================================
            if ($type === 'PSYCHOLOGY') {
                echo "-> Xử lý Cảnh báo Tâm lý (Gửi GVCN)... ";
                $riskLevel   = $data['risk_level'] ?? 'WARNING';
                $riskEmoji   = ($riskLevel === 'DANGER') ? '🆘' : '⚠️';
                $studentName = $data['student_name'] ?? 'Học sinh';
                $studentCode = $data['student_code'] ?? '';
                $message     = $data['message'] ?? '';
                
                $pushTitle = "$riskEmoji " . __('psych_warning', 'CẢNH BÁO TÂM LÝ:') . " $studentName";
                $body = __('detect_sign', "Phát hiện dấu hiệu '") . "$riskLevel'.\n" . __('content_label', 'Nội dung:') . " \"$message\"";

                // Tìm class_id của học sinh
                $classId = 0;
                if (!empty($studentCode)) {
                    $stmtC = $pdo->prepare("SELECT class_id FROM student WHERE code = ? LIMIT 1");
                    $stmtC->execute([$studentCode]);
                    $classId = (int)$stmtC->fetchColumn();
                }

                // Tìm giáo viên chủ nhiệm (GVCN) của lớp này
                $gvcnIds = [];
                if ($classId > 0) {
                    $stmtGvcn = $pdo->prepare("SELECT id FROM users WHERE role = 'TEACHER' AND homeroom_class_id = ?");
                    $stmtGvcn->execute([$classId]);
                    $gvcnIds = $stmtGvcn->fetchAll(PDO::FETCH_COLUMN);
                }

                // Nếu không tìm thấy GVCN theo lớp, gửi cho ADMIN
                if (empty($gvcnIds)) {
                    $stmtAdmin = $pdo->query("SELECT id FROM users WHERE role = 'ADMIN'");
                    $gvcnIds = $stmtAdmin->fetchAll(PDO::FETCH_COLUMN);
                }

                $pushJobs = [];
                foreach ($gvcnIds as $uid) {
                    $pushJobs[] = [
                        'user_id'     => (int)$uid,
                        'title'       => $pushTitle,
                        'body'        => $body,
                        'url'         => '/teacher_dashboard.php',
                        'type'        => 'PSYCHOLOGY',
                        'target_id'   => (string)($data['consulting_id'] ?? ''),
                        'action'      => 'open_my_class',
                        'channel_key' => 'danger_channel'
                    ];
                }

                // Bắn thêm SSE để GVCN đang online trên Web/App nhận realtime
                if (function_exists('sse_push')) {
                    sse_push($pdo, 'psychology_alert', [
                        'student_name' => $studentName,
                        'student_code' => $studentCode,
                        'class_id'     => $classId,
                        'risk_level'   => $riskLevel,
                        'message'      => $message,
                        'time'         => date('H:i d/m/Y')
                    ]);
                }

                PushHelper::sendBulk($pdo, $pushJobs);
            }
            
            // =========================================================
            // CASE 2 & 3: CHAT & FRIEND REQUEST
            // =========================================================
            elseif ($type === 'CHAT' || $type === 'FRIEND_REQUEST') {
                echo "-> Xử lý " . ($type === 'CHAT' ? 'Tin nhắn' : 'Kết bạn') . "... ";
                $receiverId = $data['receiver_id'] ?? null;
                $url = $data['url'] ?? ($type === 'CHAT' ? '/consulting_dashboard.php' : '/my_profile.php?tab=friends');

                if (empty($receiverId) && !empty($data['receiver_username'])) {
                    $stmtU = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $stmtU->execute([$data['receiver_username']]);
                    if ($u = $stmtU->fetch()) $receiverId = $u['id'];
                }

                if ($receiverId) {
                    if ($type === 'CHAT') {
                        $content = $data['content'] ?? __('new_message_alert', 'Bạn có tin nhắn mới');
                        $senderName = $data['sender_name'] ?? __('system', 'Hệ thống');
                        $pushTitle = "💬 " . __('msg_from', 'Tin nhắn từ ') . $senderName;
                        $body = (mb_strlen($content) > 80) ? mb_substr($content, 0, 77) . '...' : $content;
                        $channelKey = 'chat_channel';
                        $action = 'open_chat';
                    } else {
                        $senderName = $data['sender_name'] ?? __('a_friend', 'Một người bạn');
                        $pushTitle = "👥 " . __('new_friend_request', 'Lời mời kết bạn mới');
                        $body = "$senderName " . __('wants_to_be_friend', 'muốn kết bạn với bạn.');
                        $channelKey = 'social_channel';
                        $action = 'open_chat';
                    }

                    PushHelper::sendToUser(
                        $pdo, 
                        (int)$receiverId, 
                        $pushTitle, 
                        $body, 
                        $url, 
                        $type, 
                        (string)($data['sender_id'] ?? ''), 
                        $action, 
                        $channelKey
                    );
                }
            }

            // CASE 4: CHẤM SỔ ĐẦU BÀI / THI ĐUA LỚP (CLASS_CHECK)
            // =========================================================
            elseif ($type === 'CLASS_CHECK') {
                echo "-> Xử lý Thi đua lớp... ";
                $className = $data['class_name'] ?? 'Lớp';
                $week = $data['week'] ?? '';
                $pushTitle = "📋 Báo cáo lớp | Class Report: $className";
                $body = "🇻🇳 Đã cập nhật bảng nền nếp tuần $week cho lớp $className.\n🇬🇧 Updated discipline report for week $week.";

                $stmtReceivers = $pdo->prepare("
                    SELECT u.id FROM users u 
                    LEFT JOIN student s ON u.username = s.code 
                    WHERE s.class_id = ? OR u.role IN ('TEACHER', 'ADMIN')
                ");
                $stmtReceivers->execute([$data['class_id'] ?? 0]);
                $receivers = $stmtReceivers->fetchAll(PDO::FETCH_COLUMN);

                $pushJobs = [];
                foreach ($receivers as $uid) {
                    $pushJobs[] = [
                        'user_id'     => (int)$uid,
                        'title'       => $pushTitle,
                        'body'        => $body,
                        'url'         => '/teacher_dashboard.php',
                        'type'        => 'CLASS_CHECK',
                        'target_id'   => (string)($data['class_id'] ?? ''),
                        'action'      => 'open_class_check',
                        'channel_key' => 'violation_channel'
                    ];
                }
                PushHelper::sendBulk($pdo, $pushJobs);
            }

            // =========================================================
            // CASE 5: NHẬP ĐIỂM HỌC TẬP (ACADEMIC_SCORE)
            // =========================================================
            elseif ($type === 'ACADEMIC_SCORE') {
                echo "-> Xử lý Điểm học tập... ";
                $className = $data['class_name'] ?? 'Lớp';
                $week = $data['week'] ?? '';
                $pushTitle = "📊 Điểm học tập | Academic Score: Tuần/W $week";
                $body = "🇻🇳 Đã cập nhật điểm học tập tuần $week cho $className.\n🇬🇧 Academic score for week $week updated for $className.";

                $stmtReceivers = $pdo->prepare("
                    SELECT u.id FROM users u 
                    LEFT JOIN student s ON u.username = s.code 
                    WHERE s.class_id = ? OR u.role IN ('TEACHER', 'ADMIN')
                ");
                $stmtReceivers->execute([$data['class_id'] ?? 0]);
                $receivers = $stmtReceivers->fetchAll(PDO::FETCH_COLUMN);

                $pushJobs = [];
                foreach ($receivers as $uid) {
                    $pushJobs[] = [
                        'user_id'     => (int)$uid,
                        'title'       => $pushTitle,
                        'body'        => $body,
                        'url'         => '/teacher_dashboard.php',
                        'type'        => 'ACADEMIC_SCORE',
                        'target_id'   => (string)($data['class_id'] ?? ''),
                        'action'      => 'open_academic_scores',
                        'channel_key' => 'academic_channel'
                    ];
                }
                PushHelper::sendBulk($pdo, $pushJobs);
            }

            // =========================================================
            // CASE 6: THÔNG BÁO / TIN TỨC NHÀ TRƯỜNG (SCHOOL_NEWS)
            // =========================================================
            elseif ($type === 'SCHOOL_NEWS') {
                echo "-> Xử lý Thông báo nhà trường... ";
                $newsTitle = $data['title'] ?? 'Thông báo nhà trường | School News';
                $pushTitle = "📰 " . $newsTitle;
                $body = (mb_strlen($data['summary'] ?? '') > 90) ? mb_substr($data['summary'], 0, 87) . '...' : ($data['summary'] ?? '');

                $stmtAll = $pdo->query("SELECT id FROM users");
                $allUsers = $stmtAll->fetchAll(PDO::FETCH_COLUMN);

                $pushJobs = [];
                foreach ($allUsers as $uid) {
                    $pushJobs[] = [
                        'user_id'     => (int)$uid,
                        'title'       => $pushTitle,
                        'body'        => $body,
                        'url'         => '/news.php?id=' . ($data['news_id'] ?? ''),
                        'type'        => 'SCHOOL_NEWS',
                        'target_id'   => (string)($data['news_id'] ?? ''),
                        'action'      => 'open_news',
                        'channel_key' => 'news_channel'
                    ];
                }
                PushHelper::sendBulk($pdo, $pushJobs);
            }

            // =========================================================
            // CASE 7: CÔNG BỐ ĐIỂM THI (EXAM_SCORES)
            // =========================================================
            elseif ($type === 'EXAM_SCORES') {
                echo "-> Xử lý Đánh giá Điểm thi... ";
                $examTitle = $data['exam_name'] ?? 'Kỳ thi | Exam';
                $pushTitle = "🎓 Điểm thi | Exam Scores: " . $examTitle;
                $body = "🇻🇳 Đã có điểm thi mới. Bấm vào đây để tra cứu!\n🇬🇧 New exam scores published. Tap to check!";

                $stmtAll = $pdo->query("SELECT id FROM users WHERE role = 'STUDENT'");
                $students = $stmtAll->fetchAll(PDO::FETCH_COLUMN);

                $pushJobs = [];
                foreach ($students as $uid) {
                    $pushJobs[] = [
                        'user_id'     => (int)$uid,
                        'title'       => $pushTitle,
                        'body'        => $body,
                        'url'         => '/tracuudiemthi.php',
                        'type'        => 'EXAM_SCORES',
                        'target_id'   => (string)($data['exam_id'] ?? ''),
                        'action'      => 'open_exam_scores',
                        'channel_key' => 'academic_channel'
                    ];
                }
                PushHelper::sendBulk($pdo, $pushJobs);
            }
            // =========================================================
            // CASE 8: VI PHẠM NỀN NẾP (VIOLATION — 4 NHÓM ĐỐI TƯỢNG)
            // =========================================================
            // =========================================================
            else { 
                echo "-> Xử lý Vi phạm nền nếp (4 nhóm đối tượng)... ";
                $reporterName = "Người chấm";
                if (!empty($data['created_by'])) {
                    $stmtUser = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                    $stmtUser->execute([$data['created_by']]);
                    if ($user = $stmtUser->fetch()) $reporterName = $user['full_name'];
                }

                $studentName = $data['student_name'] ?? 'Học sinh';
                $studentCode = $data['student_code'] ?? '';
                $studentId   = (int)($data['student_id'] ?? 0);
                $className   = $data['class_name'] ?? '';
                $classId     = (int)($data['class_id'] ?? 0);
                $totalPoints = $data['total_points'] ?? 0;
                $errorsStr   = $data['errors_str'] ?? '';
                $violationId = (string)($data['violation_id'] ?? '');

                // 0. Xác định chính xác class_id nếu chưa có
                if ($classId <= 0 && !empty($studentCode)) {
                    $stmtC = $pdo->prepare("SELECT class_id FROM student WHERE code = ? LIMIT 1");
                    $stmtC->execute([$studentCode]);
                    $classId = (int)$stmtC->fetchColumn();
                }
                if ($classId <= 0 && !empty($className)) {
                    $stmtC = $pdo->prepare("SELECT id FROM classroom WHERE name = ? LIMIT 1");
                    $stmtC->execute([$className]);
                    $classId = (int)$stmtC->fetchColumn();
                }

                // 1. Nhóm 4: Tìm CHÍNH HỌC SINH ĐÓ VI PHẠM
                $violatorUserId = 0;
                if (!empty($studentCode)) {
                    $stmtU = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                    $stmtU->execute([$studentCode]);
                    $violatorUserId = (int)$stmtU->fetchColumn();
                } elseif ($studentId > 0) {
                    $stmtU = $pdo->prepare("SELECT u.id FROM users u JOIN student s ON u.username = s.code WHERE s.id = ? LIMIT 1");
                    $stmtU->execute([$studentId]);
                    $violatorUserId = (int)$stmtU->fetchColumn();
                }

                // 2. Nhóm 2: Tìm GIÁO VIÊN CHỦ NHIỆM (GVCN) của lớp đó
                $gvcnUserIds = [];
                if ($classId > 0) {
                    $stmtGvcn = $pdo->prepare("SELECT id FROM users WHERE role = 'TEACHER' AND homeroom_class_id = ?");
                    $stmtGvcn->execute([$classId]);
                    $gvcnUserIds = $stmtGvcn->fetchAll(PDO::FETCH_COLUMN);
                }

                // 3. Nhóm 1: Tìm HỌC SINH KHÁC TRONG CÙNG LỚP ĐÓ (bao gồm cả RED_FLAG của lớp)
                $classStudentUserIds = [];
                if ($classId > 0) {
                    $stmtClassStudents = $pdo->prepare("
                        SELECT u.id FROM users u 
                        JOIN student s ON u.username = s.code 
                        WHERE s.class_id = ?
                    ");
                    $stmtClassStudents->execute([$classId]);
                    $classStudentUserIds = $stmtClassStudents->fetchAll(PDO::FETCH_COLUMN);
                }

                // 4. Nhóm 3: Tìm GIÁO VIÊN KHÔNG CHỦ NHIỆM LỚP NÀY & ADMIN
                $otherTeacherAndAdminIds = [];
                if ($classId > 0) {
                    $stmtOthers = $pdo->prepare("
                        SELECT id FROM users 
                        WHERE role = 'ADMIN' 
                           OR (role = 'TEACHER' AND (homeroom_class_id IS NULL OR homeroom_class_id != ?))
                    ");
                    $stmtOthers->execute([$classId]);
                    $otherTeacherAndAdminIds = $stmtOthers->fetchAll(PDO::FETCH_COLUMN);
                } else {
                    $stmtOthers = $pdo->query("SELECT id FROM users WHERE role IN ('ADMIN', 'TEACHER')");
                    $otherTeacherAndAdminIds = $stmtOthers->fetchAll(PDO::FETCH_COLUMN);
                }

                $pushJobs = [];
                $processedUserIds = [];

                // -------------------------------------------------------------
                // NHÓM 4: CHÍNH HỌC SINH ĐÓ VI PHẠM -> VÀO "VI PHẠM CỦA TÔI"
                // -------------------------------------------------------------
                if ($violatorUserId > 0) {
                    $pushJobs[] = [
                        'user_id'     => $violatorUserId,
                        'title'       => "⚠️ " . __('violation_rule', "Vi phạm nền nếp"),
                        'body'        => __('you_are_deducted', "Bạn bị trừ ") . "{$totalPoints}" . __('points', "đ.") . " " . __('error_label', "Lỗi:") . " {$errorsStr}.",
                        'url'         => '/student_violations.php',
                        'type'        => 'VIOLATION',
                        'target_id'   => $violationId,
                        'action'      => 'open_student_violations',
                        'channel_key' => 'violation_channel'
                    ];
                    $processedUserIds[$violatorUserId] = true;
                }

                // -------------------------------------------------------------
                // NHÓM 2: GVCN LỚP ĐÓ -> VÀO "LỚP CỦA TÔI"
                // -------------------------------------------------------------
                foreach ($gvcnUserIds as $uid) {
                    $uid = (int)$uid;
                    if (isset($processedUserIds[$uid])) continue;
                    $pushJobs[] = [
                        'user_id'     => $uid,
                        'title'       => "🔔 " . __('class_report', "Báo cáo lớp ") . $className,
                        'body'        => "HS {$studentName} bị trừ {$totalPoints}" . __('points', "đ.") . "\n" . __('error_label', "Lỗi:") . " {$errorsStr}\n✍️ $reporterName",
                        'url'         => '/teacher_dashboard.php',
                        'type'        => 'VIOLATION',
                        'target_id'   => $violationId,
                        'action'      => 'open_my_class',
                        'channel_key' => 'violation_channel'
                    ];
                    $processedUserIds[$uid] = true;
                }

                // -------------------------------------------------------------
                // NHÓM 1: HỌC SINH CÙNG LỚP / RED_FLAG ĐỨNG LỚP -> VÀO "LỚP CỦA TÔI"
                // -------------------------------------------------------------
                foreach ($classStudentUserIds as $uid) {
                    $uid = (int)$uid;
                    if (isset($processedUserIds[$uid])) continue;
                    $pushJobs[] = [
                        'user_id'     => $uid,
                        'title'       => "🔔 " . __('class_report', "Báo cáo lớp ") . $className,
                        'body'        => "HS {$studentName} bị trừ {$totalPoints}" . __('points', "đ.") . "\n" . __('error_label', "Lỗi:") . " {$errorsStr}\n✍️ $reporterName",
                        'url'         => '/teacher_dashboard.php',
                        'type'        => 'VIOLATION',
                        'target_id'   => $violationId,
                        'action'      => 'open_my_class',
                        'channel_key' => 'violation_channel'
                    ];
                    $processedUserIds[$uid] = true;
                }

                // -------------------------------------------------------------
                // NHÓM 3: GIÁO VIÊN KHÔNG CHỦ NHIỆM & ADMIN -> VÀO "LỊCH SỬ VI PHẠM"
                // -------------------------------------------------------------
                foreach ($otherTeacherAndAdminIds as $uid) {
                    $uid = (int)$uid;
                    if (isset($processedUserIds[$uid])) continue;
                    $pushJobs[] = [
                        'user_id'     => $uid,
                        'title'       => "🔔 " . __('violation_report_for', "Báo cáo vi phạm: ") . $className,
                        'body'        => "HS {$studentName} ({$className}) bị trừ {$totalPoints}" . __('points', "đ.") . "\n" . __('error_label', "Lỗi:") . " {$errorsStr}\n✍️ $reporterName",
                        'url'         => '/violation_history.php',
                        'type'        => 'VIOLATION',
                        'target_id'   => $violationId,
                        'action'      => 'open_violation_history',
                        'channel_key' => 'violation_channel'
                    ];
                    $processedUserIds[$uid] = true;
                }

                // Đẩy đồng thời vào SSE event để các client Web/App đang online nhận ngay lập tức
                if (function_exists('sse_push')) {
                    sse_push($pdo, 'violation_new', [
                        'student_name' => $studentName,
                        'student_code' => $studentCode,
                        'class_name'   => $className,
                        'class_id'     => $classId,
                        'total_points' => $totalPoints,
                        'errors_str'   => $errorsStr,
                        'reporter'     => $reporterName,
                        'violation_id' => $violationId,
                        'time'         => date('H:i d/m')
                    ]);
                }

                PushHelper::sendBulk($pdo, $pushJobs);
            }

            $execTime = round(microtime(true) - $startTime, 2);
            $finalStatus = $hasError ? 'completed_with_errors' : 'completed';
            $pdo->prepare("UPDATE notification_queue SET status = ? WHERE id = ?")->execute([$finalStatus, $jobId]);
            echo "Xong trong {$execTime}s.\n";

        } else {
            $pdo->commit();
            sleep(1); 
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "\n[ERROR] " . $e->getMessage() . "\n";
        sleep(5);
    }
}
?>