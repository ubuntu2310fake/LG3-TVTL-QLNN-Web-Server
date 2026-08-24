<?php
include 'includes/header.php';
?>

<div class="win-card" style="border-top: 4px solid var(--primary-color);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <h2 style="color: var(--primary-color); margin: 0;">
            <i aria-hidden="true" class="fas fa-chart-area"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Giám sát lưu lượng truy cập' : 'Traffic Monitor') ?>
        </h2>
        
        <div style="display: flex; gap: 8px;">
            <a href="?range=1h" class="win-btn <?= $current_range != '1h' ? 'win-btn-secondary' : '' ?>" style="font-size: 12px; padding: 6px 12px;">
                <i aria-hidden="true" class="fas fa-clock"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '1 Giờ' : '1 Hour') ?>
            </a>
            <a href="?range=24h" class="win-btn <?= $current_range != '24h' ? 'win-btn-secondary' : '' ?>" style="font-size: 12px; padding: 6px 12px;">
                <i aria-hidden="true" class="fas fa-calendar-day"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '24 Giờ' : '24 Hours') ?>
            </a>
            <a href="?range=7d" class="win-btn <?= $current_range != '7d' ? 'win-btn-secondary' : '' ?>" style="font-size: 12px; padding: 6px 12px;">
                <i aria-hidden="true" class="fas fa-calendar-week"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '7 Ngày' : '7 Days') ?>
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
        <div style="background: var(--bg-hover); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
            <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 5px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tổng truy cập' : 'Total Visits') ?></div>
            <div style="font-size: 24px; font-weight: 800; color: var(--primary-color);">
                <?= number_format($total_requests) ?>
            </div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 5px;">
                <i aria-hidden="true" class="fas fa-arrow-up"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Yêu cầu /' : 'Requests /') ?> <?= htmlspecialchars($current_range) ?>
            </div>
        </div>

        <div style="background: var(--bg-hover); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
            <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 5px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Người dùng (IP)' : 'Users (IP)') ?></div>
            <div style="font-size: 24px; font-weight: 800; color: #10b981;">
                <?= number_format($unique_visitors) ?>
            </div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 5px;">
                <i aria-hidden="true" class="fas fa-user-shield"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Duy nhất' : 'Unique') ?>
            </div>
        </div>

        <div style="background: var(--bg-hover); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
            <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 5px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Độ trễ TB' : 'Avg Latency') ?></div>
            <div style="font-size: 24px; font-weight: 800; color: #f59e0b;">
                <?= $avg_latency ?>ms
            </div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 5px;">
                <i aria-hidden="true" class="fas fa-tachometer-alt"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tốc độ phản hồi' : 'Response Speed') ?>
            </div>
        </div>

        <div style="background: var(--bg-hover); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
            <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 5px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tỉ lệ lỗi' : 'Error Rate') ?></div>
            <div style="font-size: 24px; font-weight: 800; color: <?= $error_rate > 5 ? '#ef4444' : '#10b981' ?>;">
                <?= $error_rate ?>%
            </div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 5px;">
                <i aria-hidden="true" class="fas fa-exclamation-triangle"></i> Status >= 400
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <h4 style="margin: 0 0 20px 0; color: var(--text-main); font-size: 16px;">
                <i aria-hidden="true" class="fas fa-wave-square" style="color: var(--primary-color);"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Biểu đồ lưu lượng' : 'Traffic Chart') ?>
            </h4>
            <div style="height: 300px; width: 100%;">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px;">
                <h4 style="margin: 0 0 15px 0; font-size: 15px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Độ trễ (ms)' : 'Latency (ms)') ?></h4>
                <div style="height: 200px;">
                    <canvas id="latencyChart"></canvas>
                </div>
            </div>
            <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px;">
                <h4 style="margin: 0 0 15px 0; font-size: 15px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'IP Truy cập' : 'Access IPs') ?></h4>
                <div style="height: 200px;">
                    <canvas id="ipChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top: 30px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
        <h4 style="margin: 0 0 15px 0; color: var(--text-main); font-size: 16px; display: flex; align-items: center; gap: 8px;">
            <i aria-hidden="true" class="fas fa-list-alt" style="color: var(--primary-color);"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhật ký truy cập thời gian thực (150 dòng mới nhất)' : 'Real-time Access Log (150 latest)') ?>
        </h4>
        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table class="rank-table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="position: sticky; top: 0; background: var(--bg-card); z-index: 10;">
                        <th style="padding: 10px; text-align: left; border-bottom: 2px solid var(--border-color);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thời gian' : 'Time') ?></th>
                        <th style="padding: 10px; text-align: left; border-bottom: 2px solid var(--border-color);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Địa chỉ IP' : 'IP Address') ?></th>
                        <th style="padding: 10px; text-align: left; border-bottom: 2px solid var(--border-color);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trạng thái' : 'Status') ?></th>
                        <th style="padding: 10px; text-align: left; border-bottom: 2px solid var(--border-color);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Độ trễ' : 'Latency') ?></th>
                        <th style="padding: 10px; text-align: left; border-bottom: 2px solid var(--border-color);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đường dẫn (Path)' : 'Path') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_logs)): ?>
                        <tr>
                            <td colspan="5" style="padding: 20px; text-align: center; color: var(--text-muted);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Không có dữ liệu truy cập nào gần đây.' : 'No recent access data.') ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_logs as $log): ?>
                            <?php 
                                $statusClass = '';
                                if ($log['status'] >= 400) {
                                    $statusClass = 'color: #ef4444; font-weight: bold;';
                                } elseif ($log['status'] >= 300) {
                                    $statusClass = 'color: #f59e0b;';
                                } else {
                                    $statusClass = 'color: #10b981;';
                                }
                            ?>
                            <tr style="border-bottom: 1px solid var(--border-color); hover: background-color: var(--bg-hover);">
                                <td style="padding: 8px 10px; color: var(--text-muted); white-space: nowrap;"><?= htmlspecialchars($log['time']) ?></td>
                                <td style="padding: 8px 10px; font-family: monospace;"><?= htmlspecialchars($log['ip']) ?></td>
                                <td style="padding: 8px 10px; <?= $statusClass ?>"><?= htmlspecialchars($log['status']) ?></td>
                                <td style="padding: 8px 10px;"><?= number_format($log['duration'], 1) ?> ms</td>
                                <td style="padding: 8px 10px; word-break: break-all;" title="<?= htmlspecialchars($log['path']) ?>"><?= htmlspecialchars($log['path']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    window.pageDestroy = function() {
        if(window.tm_chart1) window.tm_chart1.destroy();
        if(window.tm_chart2) window.tm_chart2.destroy();
        if(window.tm_chart3) window.tm_chart3.destroy();
        if(window.tm_reloadTimer) clearTimeout(window.tm_reloadTimer);
        if(window.tm_initTimeout) clearTimeout(window.tm_initTimeout);
    };

    window.pageInit = function() {
        if (window.tm_initTimeout) clearTimeout(window.tm_initTimeout);
        
        window.tm_initTimeout = setTimeout(() => {
            if(window.tm_chart1) { try { window.tm_chart1.destroy(); } catch(e){} }
            if(window.tm_chart2) { try { window.tm_chart2.destroy(); } catch(e){} }
            if(window.tm_chart3) { try { window.tm_chart3.destroy(); } catch(e){} }

            const elTraffic = document.getElementById('trafficChart');
            if(!elTraffic) return;
            
            const ctx = elTraffic.getContext('2d');
            const ctxLat = document.getElementById('latencyChart').getContext('2d');
            const ctxIp = document.getElementById('ipChart').getContext('2d');
            
            const stats = <?= json_encode($stats) ?>;
            const labels = stats.map(item => item.time);
            const requestData = stats.map(item => item.requests);
            const uniqueIpData = stats.map(item => item.unique_ips);
            const latencyData = stats.map(item => item.avg_response_time);

            const isDark = document.body.classList.contains('dark-mode') || document.documentElement.getAttribute('data-theme') === 'dark';
            const gridColor = isDark ? '#334155' : '#e2e8f0';
            const textColor = isDark ? '#94a3b8' : '#64748b';

            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.5)'); gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

            window.tm_chart1 = new Chart(ctx, { type: 'line', data: { labels: labels, datasets: [{ label: 'Requests', data: requestData, borderColor: '#3b82f6', backgroundColor: gradient, borderWidth: 2, fill: true, tension: 0.4, pointRadius: 0, pointHoverRadius: 6 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } }, scales: { y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } }, x: { grid: { display: false }, ticks: { color: textColor, maxTicksLimit: 12 } } }, interaction: { mode: 'nearest', axis: 'x', intersect: false } } });
            window.tm_chart2 = new Chart(ctxLat, { type: 'bar', data: { labels: labels, datasets: [{ label: 'Avg Latency (ms)', data: latencyData, backgroundColor: '#f59e0b', borderRadius: 4 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { display: false }, ticks: { display: false } }, x: { display: false } } } });
            window.tm_chart3 = new Chart(ctxIp, { type: 'line', data: { labels: labels, datasets: [{ label: 'Unique IPs', data: uniqueIpData, borderColor: '#10b981', borderWidth: 2, tension: 0.3, pointRadius: 0 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { display: false }, ticks: { display: false } }, x: { display: false } } } });
        }, 300);

        if(window.tm_reloadTimer) clearTimeout(window.tm_reloadTimer);
        <?php if ($current_range == '1h'): ?>
        window.tm_reloadTimer = setTimeout(() => {
            const card = document.querySelector('.win-card'); if(card) card.style.opacity = '0.5';
            if(window.loadPage) window.loadPage(window.location.href, false, {force: true}); else location.reload();
        }, 60000);
        <?php endif; ?>
    };

    // Fix load js by ajax
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.pageInit);
    } else {
        window.pageInit();
    }
</script>
<?php include 'includes/footer.php'; ?>

<style>
[data-theme="dark"] [style*="#fff7ed"], [data-theme="dark"] [style*="#fef9c3"], [data-theme="dark"] [style*="#e2e8f0"], [data-theme="dark"] [style*="#f1f5f9"], [data-theme="dark"] [style*="#f8fafc"], [data-theme="dark"] [style*="#fef3c7"], [data-theme="dark"] [style*="#eff6ff"], [data-theme="dark"] [style*="#f0fdf4"], [data-theme="dark"] .note-box, [data-theme="dark"] .info-box { background: #111111 !important; color: var(--text-main) !important; border-color: var(--border-color) !important; }
</style>
