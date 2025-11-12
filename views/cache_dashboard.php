<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHPWeave Cache Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            font-size: 2em;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .header h1::before {
            content: "⚡";
            margin-right: 15px;
            font-size: 1.2em;
        }

        .header p {
            color: #666;
            font-size: 1.1em;
        }

        .controls {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .auto-refresh {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #667eea;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .btn.danger {
            background: #e74c3c;
        }

        .btn.danger:hover {
            background: #c0392b;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .stat-value {
            color: #333;
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-description {
            color: #999;
            font-size: 0.85em;
        }

        .stat-card.hits .stat-value {
            color: #27ae60;
        }

        .stat-card.misses .stat-value {
            color: #e74c3c;
        }

        .stat-card.writes .stat-value {
            color: #3498db;
        }

        .stat-card.deletes .stat-value {
            color: #f39c12;
        }

        .chart-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .chart-title {
            color: #333;
            font-size: 1.5em;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .progress-bar {
            width: 100%;
            height: 40px;
            background: #ecf0f1;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            margin-bottom: 10px;
        }

        .progress-fill {
            height: 100%;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .progress-fill.hits {
            background: linear-gradient(90deg, #27ae60 0%, #2ecc71 100%);
        }

        .progress-fill.misses {
            background: linear-gradient(90deg, #e74c3c 0%, #ec7063 100%);
        }

        .driver-info {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .driver-badge {
            display: inline-block;
            padding: 8px 16px;
            background: #667eea;
            color: white;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
            margin-top: 10px;
        }

        .info-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
        }

        .info-table td:first-child {
            font-weight: 600;
            color: #666;
            width: 40%;
        }

        .info-table td:last-child {
            color: #333;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .last-updated {
            color: #999;
            font-size: 0.85em;
            text-align: right;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .auto-refresh {
                justify-content: space-between;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <h1>Cache Dashboard</h1>
            <p>Real-time cache performance monitoring and statistics</p>
        </div>

        <div class="controls">
            <div class="auto-refresh">
                <label for="autoRefresh">Auto-refresh</label>
                <label class="switch">
                    <input type="checkbox" id="autoRefresh" checked>
                    <span class="slider"></span>
                </label>
                <select id="refreshInterval" style="padding: 8px; border-radius: 6px; border: 1px solid #ddd;">
                    <option value="2000">2 seconds</option>
                    <option value="5000" selected>5 seconds</option>
                    <option value="10000">10 seconds</option>
                    <option value="30000">30 seconds</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn" onclick="refreshStats()">Refresh Now</button>
                <button class="btn danger" onclick="resetStats()">Reset Stats</button>
            </div>
        </div>

        <div id="statsContainer">
            <div class="loading">Loading cache statistics...</div>
        </div>
    </div>

    <script>
        let autoRefreshInterval = null;

        // Fetch and display statistics
        async function refreshStats() {
            try {
                const response = await fetch('/cache/stats');
                const data = await response.json();

                if (data.error) {
                    displayError(data.error);
                    return;
                }

                displayStats(data);
            } catch (error) {
                displayError('Failed to fetch cache statistics: ' + error.message);
            }
        }

        // Display statistics in the UI
        function displayStats(stats) {
            const container = document.getElementById('statsContainer');

            const html = `
                <div class="stats-grid">
                    <div class="stat-card hits">
                        <div class="stat-label">Cache Hits</div>
                        <div class="stat-value">${stats.hits.toLocaleString()}</div>
                        <div class="stat-description">${stats.hit_rate}% hit rate</div>
                    </div>

                    <div class="stat-card misses">
                        <div class="stat-label">Cache Misses</div>
                        <div class="stat-value">${stats.misses.toLocaleString()}</div>
                        <div class="stat-description">${stats.miss_rate}% miss rate</div>
                    </div>

                    <div class="stat-card writes">
                        <div class="stat-label">Cache Writes</div>
                        <div class="stat-value">${stats.writes.toLocaleString()}</div>
                        <div class="stat-description">Items stored</div>
                    </div>

                    <div class="stat-card deletes">
                        <div class="stat-label">Cache Deletes</div>
                        <div class="stat-value">${stats.deletes.toLocaleString()}</div>
                        <div class="stat-description">Items removed</div>
                    </div>
                </div>

                <div class="chart-container">
                    <div class="chart-title">Hit/Miss Ratio</div>
                    <div class="progress-bar">
                        <div class="progress-fill hits" style="width: ${stats.hit_rate}%">
                            ${stats.hit_rate}% Hits
                        </div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill misses" style="width: ${stats.miss_rate}%">
                            ${stats.miss_rate}% Misses
                        </div>
                    </div>
                    <div class="last-updated">
                        Total Requests: ${stats.total_requests.toLocaleString()}
                    </div>
                </div>

                <div class="driver-info">
                    <div class="chart-title">Cache Driver Information</div>
                    <span class="driver-badge">${getDriverName(stats.driver)}</span>
                    <table class="info-table">
                        <tr>
                            <td>Driver Class</td>
                            <td>${stats.driver}</td>
                        </tr>
                        <tr>
                            <td>Total Requests</td>
                            <td>${stats.total_requests.toLocaleString()}</td>
                        </tr>
                        <tr>
                            <td>Hit Rate</td>
                            <td>${stats.hit_rate}%</td>
                        </tr>
                        <tr>
                            <td>Miss Rate</td>
                            <td>${stats.miss_rate}%</td>
                        </tr>
                        <tr>
                            <td>Efficiency Score</td>
                            <td>${getEfficiencyScore(stats.hit_rate)}</td>
                        </tr>
                    </table>
                    <div class="last-updated">Last updated: ${new Date().toLocaleString()}</div>
                </div>
            `;

            container.innerHTML = html;
        }

        // Display error message
        function displayError(message) {
            const container = document.getElementById('statsContainer');
            container.innerHTML = `<div class="error">${message}</div>`;
        }

        // Get friendly driver name
        function getDriverName(driverClass) {
            const drivers = {
                'MemoryCacheDriver': 'Memory Cache',
                'APCuCacheDriver': 'APCu Cache',
                'FileCacheDriver': 'File Cache',
                'RedisCacheDriver': 'Redis Cache',
                'MemcachedCacheDriver': 'Memcached Cache'
            };
            return drivers[driverClass] || driverClass;
        }

        // Calculate efficiency score
        function getEfficiencyScore(hitRate) {
            if (hitRate >= 90) return '⭐⭐⭐⭐⭐ Excellent';
            if (hitRate >= 75) return '⭐⭐⭐⭐ Good';
            if (hitRate >= 60) return '⭐⭐⭐ Fair';
            if (hitRate >= 40) return '⭐⭐ Poor';
            return '⭐ Very Poor';
        }

        // Reset statistics
        async function resetStats() {
            if (!confirm('Are you sure you want to reset cache statistics?')) {
                return;
            }

            try {
                const response = await fetch('/cache/reset', { method: 'POST' });
                const data = await response.json();

                if (data.success) {
                    refreshStats();
                } else {
                    alert('Failed to reset statistics: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Failed to reset statistics: ' + error.message);
            }
        }

        // Auto-refresh controls
        document.getElementById('autoRefresh').addEventListener('change', function() {
            if (this.checked) {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        });

        document.getElementById('refreshInterval').addEventListener('change', function() {
            if (document.getElementById('autoRefresh').checked) {
                stopAutoRefresh();
                startAutoRefresh();
            }
        });

        function startAutoRefresh() {
            const interval = parseInt(document.getElementById('refreshInterval').value);
            autoRefreshInterval = setInterval(refreshStats, interval);
        }

        function stopAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
            }
        }

        // Initial load
        refreshStats();
        startAutoRefresh();
    </script>
</body>
</html>
