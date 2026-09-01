<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *"); 
require_once '../includes/config.php';

$current_version = isset($_GET['version']) ? $_GET['version'] : '1.0.0';
$client_abi = isset($_GET['abi']) ? $_GET['abi'] : 'universal';

$github_base_url = "https://github.com/ubuntu2310fake/LG3-TVTL-QLNN-Mobile/releases/download/";

// ==========================================
// 1. DANH SÁCH CHẶN (BẮT BUỘC CẬP NHẬT)
// Những phiên bản trong mảng này sẽ bị khóa app, không cho ấn "Để sau"
// ==========================================
$blocked_versions = ['1.0.0', '1.0.1', '1.0.1_r1', '1.0.2', '1.0.3_r1', '1.0.2_r1', '1.0.4_r1', '1.0.5', '1.0.5_r1', '2.0.0','2.0.1','2.0.2','2.0.3','2.0.4','2.0.5','2.0.6','2.0.6_hf1','2.0.6_hf2','2.0.6_hf3','2.0.6_hf4']; // Ví dụ: '1.0.0', '1.0.1', ... (có thể thêm sau này)

// Mảng cấu hình các phiên bản mới
$updates = [
    '1.0.1_r1' => [
        'tag' => '1.0.1_r1', // Tên tag trên GitHub
        'note' => __('update_note_1_0_1', "Bản cập nhật chia ABI tối ưu dung lượng.\n- Tăng tốc độ mở app\n- Giảm 40% dung lượng cài đặt"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
            'universal'   => 'LG3_TVTL_QLNN_Android.apk'
        ]
    ],
    '1.0.2_r1' => [
        'tag' => '1.0.2_r1', // Tên tag trên GitHub
        'note' => __('update_note_1_0_2', "Bản cập nhật tập trung sửa lỗi và cải thiện hiệu suất.\n- Thêm tính năng chat với bạn bè\n- Cải thiện một số giao diện người dùng"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
            'universal'   => 'LG3_TVTL_QLNN_Android.apk'
        ]
    ],
    '1.0.3_r1' => [
        'tag' => '1.0.3_r1', // Tên tag trên GitHub
        'note' => __('update_note_1_0_3', "Bản cập nhật tập trung sửa lỗi và cải thiện hiệu suất.\n- Cải thiện giao diện người dùng\n- Tối ưu hóa hiệu suất và sửa lỗi nhỏ"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
            'universal'   => 'LG3_TVTL_QLNN_Android.apk'
        ]
    ],
    '1.0.4_r1' => [
        'tag' => '1.0.4_r1', // Tên tag trên GitHub
        'note' => __('update_note_1_0_4', "Bản cập nhật tập trung sửa lỗi và cải thiện hiệu suất.\n- Fix lỗi phân quyền cho HS và GV\n-Yêu cầu các bạn xóa app đi và cài lại ở trang chủ vì sự thay đổi về mặt kỹ thuật"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
            'universal'   => 'LG3_TVTL_QLNN_Android.apk'
        ]
    ],
    '1.0.5_r1' => [
        'tag' => '1.0.5_r1', // Tên tag trên GitHub
        'note' => __('update_note_1_0_5', "Thêm phần Tin tức, Tra cứu điểm thi, Grammar Check AI\nCải thiện hiệu suất thông báo cho GV và HS"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
        ]
    ],
    '2.0.0' => [
        'tag' => '2.0.0', // Tên tag trên GitHub
        'note' => __('update_note_2_0_0', "Cải thiện toàn bộ app\nCải thiện hiệu suất thông báo cho GV và HS"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
        ]
    ],
    '2.0.1' => [
        'tag' => '2.0.1', // Tên tag trên GitHub
        'note' => __('update_note_2_0_1', "Cải thiện toàn bộ app\nCải thiện hiệu suất thông báo cho GV và HS\nSửa sai cho codebase lỗi tại 2.0.0"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
        ]
    ],
    '2.0.2' => [
        'tag' => '2.0.2', // Tên tag trên GitHub
        'note' => __('update_note_2_0_2', "Cải thiện toàn bộ app\nCải thiện hiệu suất thông báo cho GV và HS"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
        ]
    ],
    '2.0.3' => [
        'tag' => '2.0.3', // Tên tag trên GitHub
        'note' => __('update_note_2_0_3', "Cải thiện toàn bộ app\nCải thiện hiệu suất thông báo cho GV và HS"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
        ]
    ],
    '2.0.4' => [
        'tag' => '2.0.4', // Tên tag trên GitHub
        'note' => __('update_note_2_0_4', "Cải thiện toàn bộ app\nCải thiện hiệu suất thông báo cho GV và HS"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
        ]
    ],
    '2.0.5_hf1' => [
        'tag' => '2.0.5_hf1', // Tên tag trên GitHub
        'note' => __('update_note_2_0_5_hf1', "Bản vá Hotfix 2.0.5_hf1:\n- Tự động gợi ý mở liên kết mặc định trong App\n- Tối ưu hóa hiệu năng và kết nối"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
            'patch'       => 'patch_2.0.5_hf1.patch'
        ]
    ],
    '2.0.5' => [
        'tag' => '2.0.5', // Tên tag trên GitHub
        'note' => __('update_note_2_0_5', "Cải thiện toàn bộ app\nCải thiện hiệu suất thông báo cho GV và HS"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
        ]
    ],
    '2.0.6' => [
        'tag' => '2.0.6', // Tên tag trên GitHub
        'note' => __('update_note_2_0_6', "Bản cập nhật v2.0.6:\n- Tự động gợi ý mở liên kết mặc định trong App\n- Tối ưu bộ nhớ ngoài & quyền lưu trữ\n- Loại bỏ thư viện thừa (FFmpeg/News), tối ưu dung lượng app"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
        ]
    ],
    '2.0.6_hf1' => [
        'tag' => '2.0.6_hf1', // Tên tag trên GitHub
        'note' => __('update_note_2_0_6_hf1', "Bản vá Hotfix 2.0.6_hf1:\n- Tự động gợi ý mở liên kết mặc định trong App\n- Tối ưu hóa hiệu năng và kết nối"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
            'patch'       => 'patch_2.0.6_hf1.patch'
        ]
    ],
    '2.0.6_hf2' => [
        'tag' => '2.0.6_hf2', // Tên tag trên GitHub
        'note' => __('update_note_2_0_6_hf2', "Bản vá Hotfix 2.0.6_hf2:\n- Tự động gợi ý mở liên kết mặc định trong App\n- Tối ưu hóa hiệu năng và kết nối"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
            'patch'       => 'patch_2.0.6_hf2.patch'
        ]
    ],
    '2.0.6_hf3' => [
        'tag' => '2.0.6_hf3', // Tên tag trên GitHub
        'note' => __('update_note_2_0_6_hf3', "Bản cập nhật Hotfix 3:
- Bổ sung biểu đồ trực quan cho phần Tư vấn nghề nghiệp
- Cải thiện tải APK nội bộ"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
            'universal'   => 'LG3_TVTL_QLNN_Android.apk'
        ]
    ],
    '2.0.6_hf4' => [
        'tag' => '2.0.6_hf4', // Tên tag trên GitHub
        'note' => __('update_note_2_0_6_hf4', "Bản cập nhật Hotfix 3:
- Bổ sung biểu đồ trực quan cho phần Tư vấn nghề nghiệp
- Cải thiện tải APK nội bộ"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
            'universal'   => 'LG3_TVTL_QLNN_Android.apk'
        ]
    ],
    '2.0.6_hf5' => [
        'tag' => '2.0.6_hf5', // Tên tag trên GitHub
        'note' => __('update_note_2_0_6_hf5', "Bản cập nhật Hotfix 3:
- Bổ sung biểu đồ trực quan cho phần Tư vấn nghề nghiệp
- Cải thiện tải APK nội bộ"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
            'universal'   => 'LG3_TVTL_QLNN_Android.apk'
        ]
    ],
    '2.0.6_hf6' => [
        'tag' => '2.0.6_hf6', // Tên tag trên GitHub
        'note' => __('update_note_2_0_6_hf6', "Bản cập nhật Hotfix 6:
- Tích hợp định dạng khối mã/sao chép mã trong AI tư vấn
- Xác nhận xóa lịch sử khi chuyển chế độ ẩn danh"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
            'universal'   => 'LG3_TVTL_QLNN_Android.apk'
        ]
    ],
    '2.0.6_hf7' => [
        'tag' => '2.0.6_hf7', // Tên tag trên GitHub
        'note' => __('update_note_2_0_6_hf7', "Bản cập nhật Hotfix 7:
- Tự động nhận diện chế độ ẩn danh và đồng bộ thông tin đối tác chat
- Tối ưu giao diện phòng chat tư vấn"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
            'universal'   => 'LG3_TVTL_QLNN_Android.apk'
        ]
    ],
    '2.0.6_hf8' => [
        'tag' => '2.0.6_hf8', // Tên tag trên GitHub
        'note' => __('update_note_2_0_6_hf8', "Bản cập nhật Hotfix 8:
- Tối ưu hóa kết nối HTTP Keep-Alive toàn diện qua AppConfig.client
- Tăng tốc độ tải dữ liệu và giảm thiểu độ trễ mạng"),
        'files' => [
            'arm64-v8a'   => 'LG3_TVTL_QLNN_Android_arm64-v8a.apk',
            'armeabi-v7a' => 'LG3_TVTL_QLNN_Android_armeabi-v7a.apk',
            'universal'   => 'LG3_TVTL_QLNN_Android.apk'
        ]
    ]
    // Sếp có thể thêm bản mới vào đây
];

// Định nghĩa phiên bản cao nhất hiện tại
$latest_version = '2.0.6_hf8';
$current_patch = isset($_GET['patch']) ? (int)$_GET['patch'] : 0;

$hotfixes = [];

if ($current_version === $latest_version) {
    echo json_encode(['update_available' => false, 'hotfix_available' => false]);
    exit;
}

// Kiểm tra xem bản hiện tại có nằm trong danh sách bị cấm không
$is_force_update = in_array($current_version, $blocked_versions);

$target_update = $updates[$latest_version];
$tag = $target_update['tag'];

if (isset($target_update['files'][$client_abi])) {
    $filename = $target_update['files'][$client_abi];
} else {
    $filename = isset($target_update['files']['universal']) ? $target_update['files']['universal'] : null;
}

if ($filename) {
    echo json_encode([
        'update_available' => true,
        'is_force_update' => $is_force_update, // Trả cờ ép buộc về cho Flutter
        'version' => $latest_version,
        'download_url' => $github_base_url . $tag . '/' . $filename,
        'note' => $target_update['note']
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['update_available' => false, 'error' => __('no_update_file_configured', 'Chưa cấu hình file tải trên server')]);
}
?>