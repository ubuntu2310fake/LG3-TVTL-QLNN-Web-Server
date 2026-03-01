<?php
include 'includes/header.php';
?>

<div class="win-card" style="border-top: 4px solid var(--primary-color);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <h2 style="color: var(--primary-color); margin: 0;">
            <i class="fas fa-chart-area"></i> Giám sát lưu lượng truy cập
        </h2>
        
        <div style="display: flex; gap: 8px;">
            <a href="?range=1h" class="win-btn <?= $current_range != '1h' ? 'win-btn-secondary' : '' ?>" style="font-size: 12px; padding: 6px 12px;">
                <i class="fas fa-clock"></i> 1 Giờ
            </a>
            <a href="?range=24h" class="win-btn <?= $current_range != '24h' ? 'win-btn-secondary' : '' ?>" style="font-size: 12px; padding: 6px 12px;">
                <i class="fas fa-calendar-day"></i> 24 Giờ
            </a>
            <a href="?range=7d" class="win-btn <?= $current_range != '7d' ? 'win-btn-secondary' : '' ?>" style="font-size: 12px; padding: 6px 12px;">
                <i class="fas fa-calendar-week"></i> 7 Ngày
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
        <div style="background: var(--bg-hover); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
            <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 5px;">Tổng truy cập</div>
            <div style="font-size: 24px; font-weight: 800; color: var(--primary-color);">
                <?= number_format($total_requests) ?>
            </div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 5px;">
                <i class="fas fa-arrow-up"></i> Yêu cầu / <?= htmlspecialchars($current_range) ?>
            </div>
        </div>

        <div style="background: var(--bg-hover); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
            <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 5px;">Người dùng (IP)</div>
            <div style="font-size: 24px; font-weight: 800; color: #10b981;">
                <?= number_format($unique_visitors) ?>
            </div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 5px;">
                <i class="fas fa-user-shield"></i> Duy nhất
            </div>
        </div>

        <div style="background: var(--bg-hover); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
            <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 5px;">Độ trễ TB</div>
            <div style="font-size: 24px; font-weight: 800; color: #f59e0b;">
                <?= $avg_latency ?>ms
            </div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 5px;">
                <i class="fas fa-tachometer-alt"></i> Tốc độ phản hồi
            </div>
        </div>

        <div style="background: var(--bg-hover); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
            <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 5px;">Tỉ lệ lỗi</div>
            <div style="font-size: 24px; font-weight: 800; color: <?= $error_rate > 5 ? '#ef4444' : '#10b981' ?>;">
                <?= $error_rate ?>%
            </div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 5px;">
                <i class="fas fa-exclamation-triangle"></i> Status >= 400
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <h4 style="margin: 0 0 20px 0; color: var(--text-main); font-size: 16px;">
                <i class="fas fa-wave-square" style="color: var(--primary-color);"></i> Biểu đồ lưu lượng
            </h4>
            <div style="height: 300px; width: 100%;">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px;">
                <h4 style="margin: 0 0 15px 0; font-size: 15px;">Độ trễ (ms)</h4>
                <div style="height: 200px;">
                    <canvas id="latencyChart"></canvas>
                </div>
            </div>
            <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px;">
                <h4 style="margin: 0 0 15px 0; font-size: 15px;">IP Truy cập</h4>
                <div style="height: 200px;">
                    <canvas id="ipChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('trafficChart').getContext('2d');
        const ctxLat = document.getElementById('latencyChart').getContext('2d');
        const ctxIp = document.getElementById('ipChart').getContext('2d');
        
        // PHP: Pass data to JS
        const stats = <?= json_encode($stats) ?>;

        const labels = stats.map(item => item.time);
        const requestData = stats.map(item => item.requests);
        const uniqueIpData = stats.map(item => item.unique_ips);
        const latencyData = stats.map(item => item.avg_response_time);

        const isDark = document.body.classList.contains('dark-mode');
        const gridColor = isDark ? '#334155' : '#e2e8f0';
        const textColor = isDark ? '#94a3b8' : '#64748b';

        // 1. Traffic Chart (Gradient Area)
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.5)');
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Requests',
                    data: requestData,
                    borderColor: '#3b82f6',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: gridColor },
                        ticks: { color: textColor }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { color: textColor, maxTicksLimit: 12 }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });

        // 2. Latency Chart (Bar)
        new Chart(ctxLat, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Avg Latency (ms)',
                    data: latencyData,
                    backgroundColor: '#f59e0b',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: false }, ticks: { display: false } },
                    x: { display: false }
                }
            }
        });

        // 3. IP Chart (Line)
        new Chart(ctxIp, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Unique IPs',
                    data: uniqueIpData,
                    borderColor: '#10b981',
                    borderWidth: 2,
                    tension: 0.3,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: false }, ticks: { display: false } },
                    x: { display: false }
                }
            }
        });

        // Auto reload nếu đang xem 1h (Giống logic cũ)
        <?php if ($current_range == '1h'): ?>
        setTimeout(() => {
            document.querySelector('.win-card').style.opacity = '0.5';
            location.reload();
        }, 60000);
        <?php endif; ?>
    });
</script>
<?php include 'includes/footer.php'; ?>