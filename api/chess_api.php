<?php
require_once '../includes/config.php';
require_once '../includes/sse_push.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Unauthorized']);
    exit;
}

$currentUser = $_SESSION['user'];
$currentUserId = $currentUser['id'] ?? 0;
$currentUsername = $currentUser['username'] ?? '';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'challenge':
            $targetId = (int)($_POST['target_id'] ?? 0);
            if ($targetId <= 0 || $targetId == $currentUserId) {
                echo json_encode(['status' => 'error', 'msg' => 'Invalid target']);
                exit;
            }
            
            // Get target username
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$targetId]);
            $targetUsername = $stmt->fetchColumn();
            if (!$targetUsername) {
                echo json_encode(['status' => 'error', 'msg' => 'User not found']);
                exit;
            }

            // Create match
            $stmt = $pdo->prepare("INSERT INTO chess_matches (player1_id, player2_id, status) VALUES (?, ?, 'PENDING')");
            $stmt->execute([$currentUserId, $targetId]);
            $matchId = $pdo->lastInsertId();

            $challengerName = $currentUser['full_name'] ?? $currentUsername;

            // Push event to target
            $payload = [
                'match_id' => $matchId,
                'challenger_id' => $currentUserId,
                'challenger_name' => $challengerName,
                'challenger_username' => $currentUsername
            ];
            sse_push($pdo, 'CHESS_CHALLENGE', $payload, 'user:' . $targetUsername);

            // Gửi Push Notification (FCM App / Web Push) cho đối thủ
            require_once __DIR__ . '/../includes/push_helper.php';
            PushHelper::sendToUser(
                $pdo,
                $targetId,
                '♟️ ' . (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thách đấu Cờ Vua' : 'Chess Challenge'),
                $challengerName . ' ' . (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'muốn thách đấu cờ vua với bạn!' : 'wants to challenge you to a chess match!'),
                '/chess.php?match_id=' . $matchId,
                'CHESS_CHALLENGE',
                (string)$matchId,
                'open_chess'
            );

            echo json_encode(['status' => 'success', 'match_id' => $matchId]);
            break;

        case 'accept':
            $matchId = (int)($_POST['match_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM chess_matches WHERE id = ? AND player2_id = ? AND status = 'PENDING'");
            $stmt->execute([$matchId, $currentUserId]);
            $match = $stmt->fetch();
            
            if (!$match) {
                echo json_encode(['status' => 'error', 'msg' => 'Match not found or already processed']);
                exit;
            }

            // Update status
            $stmt = $pdo->prepare("UPDATE chess_matches SET status = 'PLAYING', turn_user_id = ? WHERE id = ?");
            // White plays first. Let's make player1 white.
            $stmt->execute([$match['player1_id'], $matchId]);

            // Get player1 username
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$match['player1_id']]);
            $p1Username = $stmt->fetchColumn();

            // Notify player1
            $payload = ['match_id' => $matchId];
            sse_push($pdo, 'CHESS_ACCEPTED', $payload, 'user:' . $p1Username);

            echo json_encode(['status' => 'success']);
            break;

        case 'decline':
            $matchId = (int)($_POST['match_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM chess_matches WHERE id = ? AND player2_id = ? AND status = 'PENDING'");
            $stmt->execute([$matchId, $currentUserId]);
            $match = $stmt->fetch();
            
            if (!$match) {
                echo json_encode(['status' => 'error', 'msg' => 'Match not found or already processed']);
                exit;
            }

            // Update status
            $stmt = $pdo->prepare("UPDATE chess_matches SET status = 'DECLINED' WHERE id = ?");
            $stmt->execute([$matchId]);

            // Get player1 username
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$match['player1_id']]);
            $p1Username = $stmt->fetchColumn();

            // Notify player1
            $payload = ['match_id' => $matchId];
            sse_push($pdo, 'CHESS_DECLINED', $payload, 'user:' . $p1Username);

            echo json_encode(['status' => 'success']);
            break;

        case 'cancel_challenge':
            $matchId = (int)($_POST['match_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM chess_matches WHERE id = ? AND player1_id = ? AND status = 'PENDING'");
            $stmt->execute([$matchId, $currentUserId]);
            $match = $stmt->fetch();
            if ($match) {
                $stmt = $pdo->prepare("UPDATE chess_matches SET status = 'CANCELLED' WHERE id = ?");
                $stmt->execute([$matchId]);
                $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$match['player2_id']]);
                $p2Username = $stmt->fetchColumn();
                if ($p2Username) {
                    sse_push($pdo, 'CHESS_CANCELLED', ['match_id' => $matchId], 'user:' . $p2Username);
                }
            }
            echo json_encode(['status' => 'success']);
            break;

        case 'move':
            $matchId = (int)($_POST['match_id'] ?? 0);
            $fen = $_POST['fen'] ?? '';
            $status = $_POST['game_status'] ?? 'PLAYING'; // PLAYING or FINISHED
            $winnerId = $_POST['winner_id'] ?? null;
            
            if (!$fen) {
                echo json_encode(['status' => 'error', 'msg' => 'Missing FEN']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM chess_matches WHERE id = ? AND status = 'PLAYING'");
            $stmt->execute([$matchId]);
            $match = $stmt->fetch();

            if (!$match || ($match['player1_id'] != $currentUserId && $match['player2_id'] != $currentUserId)) {
                echo json_encode(['status' => 'error', 'msg' => 'Invalid match']);
                exit;
            }
            
            // Check turn
            if ($match['turn_user_id'] != $currentUserId) {
                echo json_encode(['status' => 'error', 'msg' => 'Not your turn']);
                exit;
            }

            $opponentId = ($match['player1_id'] == $currentUserId) ? $match['player2_id'] : $match['player1_id'];
            
            $updateStmt = $pdo->prepare("UPDATE chess_matches SET fen = ?, turn_user_id = ?, status = ?, winner_id = ? WHERE id = ?");
            $updateStmt->execute([$fen, $opponentId, $status, $winnerId ?: null, $matchId]);

            // Get opponent username
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$opponentId]);
            $oppUsername = $stmt->fetchColumn();

            $payload = [
                'match_id' => $matchId,
                'fen' => $fen,
                'game_status' => $status,
                'winner_id' => $winnerId
            ];
            sse_push($pdo, 'CHESS_MOVE', $payload, 'user:' . $oppUsername);

            echo json_encode(['status' => 'success']);
            break;
            
        case 'resign':
            $matchId = (int)($_POST['match_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM chess_matches WHERE id = ? AND status = 'PLAYING'");
            $stmt->execute([$matchId]);
            $match = $stmt->fetch();

            if (!$match || ($match['player1_id'] != $currentUserId && $match['player2_id'] != $currentUserId)) {
                echo json_encode(['status' => 'error', 'msg' => 'Invalid match']);
                exit;
            }
            
            $opponentId = ($match['player1_id'] == $currentUserId) ? $match['player2_id'] : $match['player1_id'];
            
            $updateStmt = $pdo->prepare("UPDATE chess_matches SET status = 'FINISHED', winner_id = ? WHERE id = ?");
            $updateStmt->execute([$opponentId, $matchId]);

            // Get opponent username
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$opponentId]);
            $oppUsername = $stmt->fetchColumn();

            $payload = [
                'match_id' => $matchId,
                'winner_id' => $opponentId,
                'resigned_id' => $currentUserId
            ];
            sse_push($pdo, 'CHESS_RESIGN', $payload, 'user:' . $oppUsername);

            echo json_encode(['status' => 'success']);
            break;
            
        case 'match_info':
        case 'get_state':
            $matchId = (int)($_GET['match_id'] ?? $_POST['match_id'] ?? 0);
            $stmt = $pdo->prepare("
                SELECT m.*, 
                       u1.full_name as p1_name, u1.username as p1_username, u1.avatar as p1_avatar,
                       u2.full_name as p2_name, u2.username as p2_username, u2.avatar as p2_avatar 
                FROM chess_matches m 
                LEFT JOIN users u1 ON m.player1_id = u1.id 
                LEFT JOIN users u2 ON m.player2_id = u2.id 
                WHERE m.id = ?
            ");
            $stmt->execute([$matchId]);
            $match = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($match) {
                $p1 = [
                    'id' => (int)$match['player1_id'],
                    'name' => $match['p1_name'] ?: $match['p1_username'],
                    'avatar' => $match['p1_avatar']
                ];
                $p2 = [
                    'id' => (int)$match['player2_id'],
                    'name' => $match['p2_name'] ?: $match['p2_username'],
                    'avatar' => $match['p2_avatar']
                ];
                
                $yourColor = ($currentUserId == $match['player1_id']) ? 'w' : 'b';
                
                $match['your_color'] = $yourColor;
                $match['is_your_turn'] = ($match['turn_user_id'] == $currentUserId);
                $match['white_player'] = $p1;
                $match['black_player'] = $p2;
                $match['p1'] = $p1;
                $match['p2'] = $p2;
                echo json_encode(['status' => 'success', 'match' => $match]);
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'Match not found']);
            }
            break;

        case 'get_players':
            $players = [];
            // Lấy danh sách bạn bè
            $stmt = $pdo->prepare("
                SELECT u.id, u.username, u.full_name, u.avatar, u.role,
                       (SELECT MAX(last_active) FROM user_sessions WHERE user_id = u.id) as last_active
                FROM friendships f 
                JOIN users u ON (f.user_id_1 = u.id OR f.user_id_2 = u.id) 
                WHERE (f.user_id_1 = ? OR f.user_id_2 = ?) AND f.status = 'accepted' AND u.id != ?
            ");
            $stmt->execute([$currentUserId, $currentUserId, $currentUserId]);
            $friends = $stmt->fetchAll();
            foreach ($friends as $f) {
                $f['is_online'] = ($f['last_active'] && strtotime($f['last_active']) >= time() - 180);
                $players[$f['id']] = $f;
            }
            
            // Nếu là giáo viên, cho phép thách đấu tất cả học sinh
            if ($currentUser['role'] === 'TEACHER') {
                $stmt = $pdo->prepare("
                    SELECT id, username, full_name, avatar, role, 
                           (SELECT MAX(last_active) FROM user_sessions WHERE user_id = users.id) as last_active 
                    FROM users WHERE role = 'STUDENT' AND id != ?
                ");
                $stmt->execute([$currentUserId]);
                $students = $stmt->fetchAll();
                foreach ($students as $s) {
                    $s['is_online'] = ($s['last_active'] && strtotime($s['last_active']) >= time() - 180);
                    $players[$s['id']] = $s;
                }
            }
            
            echo json_encode(['status' => 'success', 'players' => array_values($players)]);
            break;

        default:
            echo json_encode(['status' => 'error', 'msg' => 'Unknown action']);
            break;
    }
} catch (\Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
