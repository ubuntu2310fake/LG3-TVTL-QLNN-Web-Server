<?php
include 'includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<style>
    .profile-header { text-align: center; margin-bottom: 20px; }
    .qr-box { 
        background: var(--bg-card); 
        padding: 15px; border-radius: 12px; 
        border: 1px solid var(--border-color); 
        display: inline-block; margin-top: 15px; 
    }
    .dev-list { margin-top: 15px; border-top: 1px solid var(--border-color); }
    .dev-item { 
        display: flex; align-items: center; justify-content: space-between; 
        padding: 12px 0; border-bottom: 1px solid var(--border-color); 
    }
    .dev-info { font-size: 14px; color: var(--text-main); font-weight: 500; }
    .dev-sub { font-size: 11px; color: var(--text-muted); font-family: monospace; margin-top: 2px; }
    
    .btn-action { border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; }
    .btn-del-dev { background: #fee2e2; color: #ef4444; }
    .btn-del-dev:hover { background: #fecaca; }
    .btn-test-dev { background: #3b82f6; color: white; margin-right: 5px; }
    .btn-test-dev:hover { background: #2563eb; }
    .win-input { box-sizing: border-box !important; width: 100%; }

    #cropModal {
        display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%;
        background-color: rgba(0,0,0,0.8); align-items: center; justify-content: center;
    }
    .crop-container {
        background: var(--bg-card); color: var(--text-main);
        padding: 20px; border-radius: 10px; width: 90%; max-width: 500px;
        display: flex; flex-direction: column; gap: 15px;
    }
    .img-container { max-height: 60vh; overflow: hidden; background: #333; }
    .img-container img { max-width: 100%; display: block; }
</style>

<div class="win-card" style="max-width:600px; margin:0 auto;">
    <h2 style="color:var(--accent-color); text-align:center; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
        <i class="fas fa-id-card"></i> Hồ Sơ Cá Nhân
    </h2>

    <div class="profile-header">
        <div style="position: relative; display: inline-block;">
            <img src="<?= htmlspecialchars($avatar_url) ?>" 
                 id="displayAvatar" 
                 class="refresh-cache"
                 onerror="this.src='static/default.png'"
                 style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid var(--border-color);">
            
            <div style="position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%); display:flex; gap:5px;">
                <button type="button" onclick="document.getElementById('file-avatar').click()" style="background: var(--accent-color); color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border:none; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                    <i class="fas fa-camera"></i>
                </button>
    
                <?php 
                // Kiểm tra: Nếu có avatar và không phải là avatar mặc định thì hiện nút xóa
                if (!empty($avatar_url) && strpos($avatar_url, 'default.png') === false): 
                ?>
                <button type="button" onclick="deleteAvatar()" style="background: #ef4444; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border:none; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                    <i class="fas fa-trash"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div style="margin-top: 15px; font-weight: bold; font-size: 18px; color:var(--text-main);">
            <?= htmlspecialchars($user['full_name'] ?: $user['username']) ?>
        </div>
        <div style="color: var(--text-muted); font-size: 14px;">
            <?= $user['role'] == 'STUDENT' ? 'Học sinh' : ($user['role'] == 'TEACHER' ? 'Giáo viên' : $user['role']) ?>
        </div>
        <?php if ($student): ?>
        <div class="qr-box">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=Ma_HS_<?= $student['code'] ?>" style="width:120px; height:120px;">
            <div style="font-weight:bold; font-family:monospace; color:var(--text-main); margin-top:5px;"><?= $student['code'] ?></div>
        </div>
        <?php endif; ?>
    </div>

    <div style="background: var(--bg-hover); padding: 15px; border-radius: 8px; border: 1px dashed var(--border-color); margin-bottom: 20px;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h4 style="margin:0; color:var(--text-main);"><i class="fas fa-mobile-alt"></i> Thiết bị đăng nhập</h4>
            <button onclick="registerCurrentDevice()" class="win-btn" style="padding: 6px 10px; font-size: 12px; background: #10b981;">
                <i class="fas fa-plus"></i> Thêm máy này
            </button>
        </div>
        <div class="dev-list">
            <?php if ($devices): foreach ($devices as $d): ?>
            <div class="dev-item" id="dev-<?= $d['session_id'] ?>">
                <div style="flex: 1; padding-right: 10px;">
                    <div class="dev-info">
                        <i class="fas fa-desktop" style="color:var(--text-muted); margin-right:5px;"></i> 
                        <?= htmlspecialchars($d['device_name']) ?>
                        <?php if($d['session_id'] == $currentSessId) echo "<span style='color:#10b981; font-size:11px; margin-left:5px;'>(Máy này)</span>"; ?>
                    </div>
                    <div class="dev-sub">
                        Active: <?= date('d/m H:i', strtotime($d['last_active'])) ?>
                        <?php if($d['push_id']) echo " • <span style='color:#3b82f6'>Đã bật thông báo</span>"; ?>
                    </div>
                </div>
                
                <?php if ($d['session_id'] != $currentSessId): ?>
                <div style="display: flex;">
                    <?php if($d['push_id']): ?>
                    <button onclick="testPush('<?= $d['session_id'] ?>')" class="btn-action btn-test-dev"><i class="fas fa-paper-plane"></i> Test</button>
                    <?php endif; ?>
                    
                    <button onclick="removeDevice('<?= $d['session_id'] ?>')" class="btn-action btn-del-dev"><i class="fas fa-sign-out-alt"></i> Kick</button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; else: ?>
            <div style="text-align:center; padding:15px; color:var(--text-muted); font-size:13px; font-style:italic;">Chưa có phiên đăng nhập nào khác.</div>
            <?php endif; ?>
        </div>
    </div>

    <form id="profileForm" onsubmit="submitProfileForm(event)" enctype="multipart/form-data">
        <input type="hidden" name="delete_image" id="deleteImageInput" value="0">
        <input type="file" name="image" id="file-avatar" accept="image/*" style="display: none;">
        
        <?php if ($student): ?>
            <?php if ($student['has_pending_changes']): ?>
            <div style="background:#fff7ed; color:#c2410c; padding:10px; border-radius:8px; margin-bottom:15px; font-size:13px; border: 1px solid #ffedd5;">
                <i class="fas fa-clock"></i> Đang chờ giáo viên duyệt thay đổi.
            </div>
            <?php endif; ?>

            <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1; min-width: 240px;">
                    <label style="font-weight:bold; font-size:13px; color:var(--text-muted); display:block; margin-bottom:5px;">Họ và Tên:</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($student['has_pending_changes'] ? $student['pending_name'] : $student['name']) ?>" class="win-input" required>
                </div>
                <div style="flex: 1; min-width: 240px;">
                    <label style="font-weight:bold; font-size:13px; color:var(--text-muted); display:block; margin-bottom:5px;">Ngày sinh:</label>
                    <input type="text" name="dob" value="<?= htmlspecialchars($student['has_pending_changes'] ? $student['pending_dob'] : $student['dob']) ?>" class="win-input" placeholder="DD/MM/YYYY">
                </div>
            </div>
        <?php else: ?>
            <div style="margin-bottom: 15px;">
                <label style="font-weight:bold; font-size:13px; color:var(--text-muted); display:block; margin-bottom:5px;">Tên hiển thị:</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" class="win-input">
            </div>
        <?php endif; ?>

        <button type="submit" class="win-btn" style="width:100%; margin-top:10px;">
            <i class="fas fa-save"></i> CẬP NHẬT THÔNG TIN
        </button>
    </form>
</div>

<div id="cropModal"><div class="crop-container"><h3 style="text-align:center; margin:0;">Cắt Ảnh</h3><div class="img-container"><img id="imageToCrop" src=""></div><div style="display:flex; gap:10px; margin-top:15px;"><button type="button" class="win-btn win-btn-secondary" onclick="closeCrop()" style="flex:1;">Hủy</button><button type="button" class="win-btn" onclick="saveCrop()" style="flex:1;">Lưu</button></div></div></div>

<script>
    const vapidKey = "<?= $vapid_key ?>";
    
    // 1. LOGIC THIẾT BỊ
    function urlBase64ToUint8Array(base64String) { const padding = '='.repeat((4 - base64String.length % 4) % 4); const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/'); const rawData = window.atob(base64); const outputArray = new Uint8Array(rawData.length); for (let i = 0; i < rawData.length; ++i) { outputArray[i] = rawData.charCodeAt(i); } return outputArray; }
    
    async function registerCurrentDevice() {
        if(!('serviceWorker' in navigator)) return alert("Trình duyệt không hỗ trợ");
        const reg = await navigator.serviceWorker.ready;
        let sub = await reg.pushManager.getSubscription();
        if(!sub) sub = await reg.pushManager.subscribe({userVisibleOnly:true, applicationServerKey:urlBase64ToUint8Array(vapidKey)});
        
        // Gọi API save_session để map session_id
        const json = sub.toJSON();
        fetch('api/subscribe.php', {method:'POST', body:JSON.stringify(json)}).then(r=>r.json()).then(d=>{
            if(d.status==='success') { Toastify({text:"✅ Đã thêm thiết bị!", style:{background:"#10b981"}}).showToast(); setTimeout(()=>location.reload(),1000); }
        });
    }

    async function removeDevice(sid) {
        if(!confirm("Bạn có chắc chắn muốn đăng xuất thiết bị này không?")) return;
        const fd = new FormData(); fd.append('action', 'delete_device'); fd.append('device_id', sid);
        
        try {
            const res = await fetch('my_profile.php', {method:'POST', body:fd});
            const d = await res.json();
            if(d.status==='success') { 
                document.getElementById('dev-'+sid).remove(); 
                Toastify({text:"✅ "+d.msg, style:{background:"#ef4444"}}).showToast(); 
            } else {
                alert(d.msg);
            }
        } catch(e) {
            alert("Lỗi kết nối server");
        }
    }

    async function testPush(sid) {
        const fd = new FormData(); fd.append('action', 'test_push'); fd.append('device_id', sid);
        fetch('my_profile.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{
            if(d.status==='success') Toastify({text:"🚀 Đã gửi test!", style:{background:"#3b82f6"}}).showToast();
            else alert(d.msg);
        });
    }

    // 2. CROP & FORM
    let cropper; const fileInp = document.getElementById('file-avatar');
    fileInp.onchange = e => { if(e.target.files[0]){ const r=new FileReader(); r.onload=ev=>{ document.getElementById('imageToCrop').src=ev.target.result; document.getElementById('cropModal').style.display='flex'; if(cropper)cropper.destroy(); cropper=new Cropper(document.getElementById('imageToCrop'),{aspectRatio:1,viewMode:1}); }; r.readAsDataURL(e.target.files[0]); }};
    function closeCrop(){ document.getElementById('cropModal').style.display='none'; }
    function saveCrop(){ cropper.getCroppedCanvas({width:300,height:300}).toBlob(blob=>{ const dt=new DataTransfer(); dt.items.add(new File([blob],"a.jpg",{type:"image/jpeg"})); fileInp.files=dt.files; document.getElementById('profileForm').dispatchEvent(new Event('submit')); closeCrop(); }); }
    
    async function submitProfileForm(e) {
        e.preventDefault(); const btn=e.target.querySelector('button[type="submit"]'); const old=btn.innerHTML; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Đang lưu...'; btn.disabled=true;
        try {
            const r = await fetch('my_profile.php', {method:'POST', body:new FormData(e.target)});
            const d = await r.json();
            if(d.status==='success') {
                Toastify({text:"✅ "+d.msg, style:{background:"#10b981"}}).showToast();
                if(d.new_avatar_url) {
                    const newSrc = d.new_avatar_url+'?t='+Date.now();
                    document.getElementById('displayAvatar').src = newSrc;
                    // Cập nhật cả avatar trên header nếu có
                    const headerAvt = document.querySelector('.header-avatar');
                    if(headerAvt) headerAvt.src = newSrc;
                }
            } else alert(d.msg);
        } catch(err) { alert("Lỗi xảy ra, vui lòng thử lại."); }
        btn.disabled=false; btn.innerHTML=old;
    }

    // Hàm này trích xuất từ file my_profile.html
function deleteAvatar() {
    if(confirm("Bạn có chắc muốn xóa ảnh đại diện?")) {
        // 1. Đánh dấu input ẩn thành 1
        document.getElementById('deleteImageInput').value = "1";
        
        // 2. Tự động submit form profileForm
        document.getElementById('profileForm').dispatchEvent(new Event('submit'));
    }
}
</script>
<?php include 'includes/footer.php'; ?>