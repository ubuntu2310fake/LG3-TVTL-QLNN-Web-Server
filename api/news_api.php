<?php
// Đường dẫn: api/news_api.php
require_once '../includes/config.php';
header('Content-Type: application/json; charset=utf-8');
http_response_code(403);
echo json_encode(['status' => 'error', 'msg' => 'Tính năng Tin tức đã bị vô hiệu hóa cho toàn bộ người dùng.']);
exit;

// Đường dẫn: api/news_api.php
require_once '../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
if (!isset($_SESSION['user']) && $action !== 'list') { 
    echo json_encode(['status' => 'error', 'msg' => __('not_logged_in', 'Chưa đăng nhập')]); 
    exit; 
}

// ==========================================
// 🛡️ LƯỚI LỌC 3 LỚP CHỐNG ZALO (BACKEND)
// ==========================================
function validateZaloCompression($img_data, $mime_type = 'image/jpeg', $filename = '') {
    // LỚP 1: Dấu vân tay tên file Zalo
    if (!empty($filename) && preg_match('/^z\d{10,}_/i', $filename)) {
        throw new Exception(__('zalo_direct_file_error_1', "HỆ THỐNG TỪ CHỐI: Phát hiện file tải trực tiếp từ Zalo (") . $filename . __('zalo_direct_file_error_2', "). Vui lòng lấy file gốc HD!"));
    }

    $im = @imagecreatefromstring($img_data);
    if (!$im) return; 

    $original_size = strlen($img_data);
    // Nếu ảnh > 2MB thì auto qua ải (Chắc chắn là ảnh xịn)
    if ($original_size > 2 * 1024 * 1024) { imagedestroy($im); return; }

    $width = imagesx($im);
    $height = imagesy($im);
    $bpp = $original_size / ($width * $height);

    // LỚP 2: Bắt chặt JPG BPP (Đặc trị ảnh chụp màn hình điện thoại nén qua Zalo)
    $is_jpg = (strpos(strtolower($mime_type), 'jpeg') !== false || strpos(strtolower($mime_type), 'jpg') !== false);
    if ($is_jpg && $bpp < 0.18 && $width >= 400) {
        imagedestroy($im);
        throw new Exception(__('zalo_blur_error_1', "HỆ THỐNG TỪ CHỐI: Ảnh nén Zalo mờ căm (Mật độ: ") . round($bpp, 2) . __('zalo_blur_error_2', " BPP). Vui lòng xin lại ảnh gốc!"));
    }

    // LỚP 3: Thuật toán Nén ngược (Đặc trị ảnh Camera/Phong cảnh bị Zalo bóp)
    ob_start();
    imagejpeg($im, null, 95);
    $recompressed_data = ob_get_clean();
    $recompressed_size = strlen($recompressed_data);
    $ratio = $recompressed_size / $original_size;
    imagedestroy($im);

    if ($ratio > 2.2) {
        throw new Exception(__('zalo_pixelated_1', "HỆ THỐNG TỪ CHỐI: Ảnh bị vỡ hạt (Tỷ lệ phình to: ") . round($ratio, 1) . __('zalo_pixelated_2', "x). Dữ liệu ảnh đã bị Zalo làm rỗng!"));
    }
}

// ==========================================
// HÀM XỬ LÝ ẢNH TRONG BÀI VIẾT (RESIZE & BỌC LINK)
// ==========================================
function processContentImages($html_content) {
    $upload_dir = '../static/uploads/news_content_images/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    return preg_replace_callback('/<img\s+([^>]*?)src="data:image\/(.*?);base64,(.*?)"([^>]*?)>/i', function($matches) use ($upload_dir) {
        $img_type = strtolower($matches[2]);
        $base64_data = $matches[3];
        $img_data = base64_decode($base64_data);

        validateZaloCompression($img_data, 'image/' . $img_type, 'base64_image');

        $im = @imagecreatefromstring($img_data);
        if (!$im) return $matches[0]; 

        $width = imagesx($im); $height = imagesy($im);
        if ($width < 400) {
            imagedestroy($im);
            throw new Exception(__('img_too_small', "HỆ THỐNG TỪ CHỐI: Kích thước ảnh quá nhỏ (Ngang < 400px). Hãy gửi lại ảnh chất lượng cao!"));
        }

        $filename = time() . '_' . rand(1000, 9999);
        $original_path = $upload_dir . $filename . '_original.' . $img_type;
        $resized_path = $upload_dir . $filename . '_resized.' . $img_type;

        file_put_contents($original_path, $img_data);

        $new_width = min($width, 800);
        $new_height = floor($height * ($new_width / $width));
        $resized_im = imagecreatetruecolor($new_width, $new_height);

        if ($img_type === 'png' || $img_type === 'webp' || $img_type === 'gif') {
            imagealphablending($resized_im, false); imagesavealpha($resized_im, true);
            $transparent = imagecolorallocatealpha($resized_im, 255, 255, 255, 127);
            imagefilledrectangle($resized_im, 0, 0, $new_width, $new_height, $transparent);
        }

        imagecopyresampled($resized_im, $im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        if ($img_type == 'png') imagepng($resized_im, $resized_path, 8);
        elseif ($img_type == 'webp') imagewebp($resized_im, $resized_path, 80);
        else imagejpeg($resized_im, $resized_path, 85);

        imagedestroy($im); imagedestroy($resized_im);

        $url_original = 'static/uploads/news_content_images/' . basename($original_path);
        $url_resized = 'static/uploads/news_content_images/' . basename($resized_path);

        return '<a href="'.$url_original.'" target="_blank" title="'.__('click_to_view_hd', 'Bấm để xem ảnh gốc HD').'"><img src="'.$url_resized.'" class="content-img-resized" style="max-width:100%; border-radius:8px;"></a>';
    }, $html_content);
}

// HÀM XÓA ĐỆ QUY THƯ MỤC HLS
function deleteDir($dirPath) {
    if (!is_dir($dirPath)) return;
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) { is_dir($file) ? deleteDir($file) : unlink($file); }
    rmdir($dirPath);
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `news` ( `id` int(11) NOT NULL AUTO_INCREMENT, `title` varchar(255) NOT NULL, `category` varchar(50) DEFAULT 'tin_tuc', `content` LONGTEXT NOT NULL, `attachment_url` TEXT DEFAULT NULL, `thumbnail_url` varchar(500) DEFAULT NULL, `views` int(11) DEFAULT 0, `created_by` int(11) NOT NULL, `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `news_comments` ( `id` int(11) NOT NULL AUTO_INCREMENT, `news_id` int(11) NOT NULL, `user_id` int(11) NOT NULL, `content` text NOT NULL, `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), FOREIGN KEY (`news_id`) REFERENCES `news`(`id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    try { $pdo->exec("ALTER TABLE `news` MODIFY `attachment_url` TEXT;"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE `news` ADD COLUMN `thumbnail_url` varchar(500) DEFAULT NULL AFTER `content`;"); } catch (Exception $e) {}



    if ($action === 'list') {
        $category = $_GET['category'] ?? 'all'; 
        if ($category === 'all') $stmt = $pdo->query("SELECT n.*, u.full_name FROM news n JOIN users u ON n.created_by = u.id ORDER BY n.created_at DESC LIMIT 30");
        else { $stmt = $pdo->prepare("SELECT n.*, u.full_name FROM news n JOIN users u ON n.created_by = u.id WHERE n.category = ? ORDER BY n.created_at DESC LIMIT 30"); $stmt->execute([$category]); }
        
        $newsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($newsList as &$news) {
            $cStmt = $pdo->prepare("SELECT c.*, u.full_name, c.user_id FROM news_comments c JOIN users u ON c.user_id = u.id WHERE c.news_id = ? ORDER BY c.created_at ASC");
            $cStmt->execute([$news['id']]);
            $news['comments'] = $cStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        echo json_encode(['status' => 'success', 'data' => $newsList]);
    } 
    
    elseif ($action === 'add' && in_array($_SESSION['user']['role'], ['ADMIN', 'TEACHER'])) {
        $title = $_POST['title'] ?? ''; $raw_content = $_POST['content'] ?? ''; $category = $_POST['category'] ?? 'tin_tuc';
        
        try {
            $content = processContentImages($raw_content);
        } catch (Exception $imgEx) {
            echo json_encode(['status' => 'error', 'msg' => $imgEx->getMessage()]); exit;
        }

        $attachments = []; $thumbnail_url = null;

        // ĐA TÀI LIỆU
        if (isset($_FILES['attachments'])) {
            $upload_dir = '../static/uploads/news/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $file_count = count($_FILES['attachments']['name']);
            for ($i = 0; $i < $file_count; $i++) {
                if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                    if (strpos($_FILES['attachments']['type'][$i], 'image/') === 0) {
                        try { validateZaloCompression(file_get_contents($_FILES['attachments']['tmp_name'][$i]), $_FILES['attachments']['type'][$i], $_FILES['attachments']['name'][$i]); } 
                        catch (Exception $e) { echo json_encode(['status' => 'error', 'msg' => __('attachment_prefix', "File đính kèm: ") . $e->getMessage()]); exit; }
                    }
                    $file_name = time() . '_' . rand(100,999) . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['attachments']['name'][$i]));
                    if (move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $upload_dir . $file_name)) $attachments[] = 'static/uploads/news/' . $file_name;
                }
            }
        }
        $attachment_json = count($attachments) > 0 ? json_encode($attachments) : null;

        // THUMBNAIL
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            try { validateZaloCompression(file_get_contents($_FILES['thumbnail']['tmp_name']), $_FILES['thumbnail']['type'], $_FILES['thumbnail']['name']); } 
            catch (Exception $e) { echo json_encode(['status' => 'error', 'msg' => __('thumbnail_prefix', "Ảnh Thumbnail: ") . $e->getMessage()]); exit; }

            $thumb_dir = '../static/uploads/news_thumbs/';
            if (!is_dir($thumb_dir)) mkdir($thumb_dir, 0777, true);
            $thumb_ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION) ?: 'png';
            $thumb_name = time() . '_thumb_' . rand(100, 999) . '.' . $thumb_ext;
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumb_dir . $thumb_name)) {
                $thumbnail_url = 'static/uploads/news_thumbs/' . $thumb_name;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO news (title, content, category, attachment_url, thumbnail_url, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $content, $category, $attachment_json, $thumbnail_url, $_SESSION['user']['id']]);
        
        $newNewsId = $pdo->lastInsertId();
        require_once '../includes/push_helper.php';
        enqueueNotification($pdo, 'SCHOOL_NEWS', [
            'news_id' => (string)$newNewsId,
            'title'   => $title,
            'summary' => strip_tags(mb_substr($content, 0, 100))
        ]);

        echo json_encode(['status' => 'success', 'msg' => __('news_added_success', 'Thêm bài viết thành công!')]); 
    }

    // API XÓA FILE HLS KHI HỦY BÀI VIẾT
    elseif ($action === 'delete_hls') {
        $task_id = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['task_id'] ?? '');
        if ($task_id) {
            $dir = '../static/uploads/hls_videos/' . $task_id;
            deleteDir($dir);
            echo json_encode(['status' => 'success']);
        }
    }

    elseif ($action === 'delete') {
        $news_id = $_POST['news_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?"); $stmt->execute([$news_id]); $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($post) {
            if ($_SESSION['user']['role'] === 'ADMIN' || $_SESSION['user']['id'] == $post['created_by']) {
                // 1. Càn quét thư mục HLS Video
                preg_match_all('/src="[^"]*?(vid_[a-zA-Z0-9_]+)\/index\.m3u8"/', $post['content'], $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $task_id) { deleteDir('../static/uploads/hls_videos/' . $task_id); }
                }
                // 2. Xóa Thumbnail
                if ($post['thumbnail_url'] && file_exists('../' . $post['thumbnail_url'])) unlink('../' . $post['thumbnail_url']);
                // 3. Xóa Attachment Files
                if ($post['attachment_url']) {
                    $files = json_decode($post['attachment_url'], true);
                    if (is_array($files)) { foreach ($files as $f) { if (file_exists('../' . $f)) unlink('../' . $f); } }
                }
                // 4. Xóa Ảnh trong nội dung
                preg_match_all('/src="static\/uploads\/news_content_images\/([^"]+)"/', $post['content'], $img_matches);
                if (!empty($img_matches[1])) {
                    foreach ($img_matches[1] as $img_name) { if (file_exists('../static/uploads/news_content_images/' . $img_name)) unlink('../static/uploads/news_content_images/' . $img_name); }
                }
                
                $pdo->prepare("DELETE FROM news WHERE id = ?")->execute([$news_id]); 
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'msg' => __('no_delete_permission', 'Không có quyền xóa!')]);
            }
        }
    }

    elseif ($action === 'comment') {
        $news_id = $_POST['news_id'] ?? 0; $content = $_POST['content'] ?? '';
        if ($news_id && $content) { 
            $pdo->prepare("INSERT INTO news_comments (news_id, user_id, content) VALUES (?, ?, ?)")->execute([$news_id, $_SESSION['user']['id'], $content]); 
            echo json_encode(['status' => 'success']); 
        } else {
            echo json_encode(['status' => 'error', 'msg' => __('empty_content', 'Nội dung trống')]);
        }
    }

    elseif ($action === 'delete_comment') {
        $comment_id = $_POST['comment_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT user_id FROM news_comments WHERE id = ?"); $stmt->execute([$comment_id]); $cmt = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cmt) {
            if ($_SESSION['user']['role'] === 'ADMIN' || $_SESSION['user']['id'] == $cmt['user_id']) { 
                $pdo->prepare("DELETE FROM news_comments WHERE id = ?")->execute([$comment_id]); 
                echo json_encode(['status' => 'success']); 
            } else {
                echo json_encode(['status' => 'error', 'msg' => __('you_no_delete_permission', 'Bạn không có quyền xóa!')]);
            }
        } else {
            echo json_encode(['status' => 'error', 'msg' => __('comment_not_exist', 'Bình luận không tồn tại.')]);
        }
    } 
    
    else {
        echo json_encode(['status' => 'error', 'msg' => __('invalid_action', 'Hành động không hợp lệ!')]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>