<?php
require_once 'check_captcha.php';
require_once 'captcha_config.php';
session_start();

$captchaConfig = getCaptchaConfig();
$isRecaptcha = isRecaptchaEnabled();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест системы капчи</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .test-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .status-card {
            border: none;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .status-verified {
            border-left: 4px solid #28a745;
            background-color: rgba(40, 167, 69, 0.1);
        }
        
        .status-not-verified {
            border-left: 4px solid #dc3545;
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .btn-custom {
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 600;
            margin: 5px;
        }
        
        .info-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .session-info {
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-container">
            <h1 class="text-center mb-4">🛡️ Тест системы капчи</h1>
            
            <?php
            $captchaStatus = checkCaptchaStatus();
            $isVerified = $captchaStatus['verified'];
            ?>
            
            <!-- Статус капчи -->
            <div class="card status-card <?php echo $isVerified ? 'status-verified' : 'status-not-verified'; ?>">
                <div class="card-body">
                    <h5 class="card-title">
                        <?php echo $isVerified ? '✅ Капча пройдена' : '❌ Капча не пройдена'; ?>
                    </h5>
                    <p class="card-text">
                        <?php 
                        if ($isVerified) {
                            echo "Доступ к защищенным страницам разрешен";
                            if (isset($captchaStatus['expires_at'])) {
                                $expiresAt = date('H:i:s d.m.Y', $captchaStatus['expires_at']);
                                echo "<br><small>Действует до: {$expiresAt}</small>";
                            }
                        } else {
                            echo "Причина: " . ($captchaStatus['reason'] ?? 'Неизвестно');
                        }
                        ?>
                    </p>
                </div>
            </div>
            
            <!-- Действия -->
            <div class="info-box">
                <h6>Доступные действия:</h6>
                <div class="d-flex flex-wrap">
                    <?php if (!$isVerified): ?>
                        <a href="captcha.php?target=miniapp" class="btn btn-success btn-custom">
                            Пройти капчу (miniapp)
                        </a>
                        <a href="captcha.php?target=test" class="btn btn-info btn-custom">
                            Пройти капчу (test)
                        </a>
                    <?php else: ?>
                        <a href="miniapp.php" class="btn btn-primary btn-custom">
                            Открыть miniapp.php
                        </a>
                        <a href="test_miniapp.php" class="btn btn-secondary btn-custom">
                            Открыть test_miniapp.php
                        </a>
                        <button class="btn btn-warning btn-custom" onclick="clearCaptcha()">
                            Очистить капчу
                        </button>
                    <?php endif; ?>
                    
                    <button class="btn btn-outline-primary btn-custom" onclick="location.reload()">
                        Обновить страницу
                    </button>
                </div>
            </div>
            
            <!-- Тест API -->
            <div class="info-box">
                <h6>Тест API запросов:</h6>
                <div class="d-flex flex-wrap">
                    <button class="btn btn-outline-info btn-custom" onclick="testAPI('check_user_api.php')">
                        Тест check_user_api.php
                    </button>
                    <button class="btn btn-outline-info btn-custom" onclick="testAPI('get_affiliates_api.php')">
                        Тест get_affiliates_api.php
                    </button>
                </div>
                <div id="api-result" class="mt-3"></div>
            </div>
            
            <!-- Конфигурация капчи -->
            <div class="info-box">
                <h6>Конфигурация капчи:</h6>
                <div class="session-info">
                    <?php
                    echo "Тип капчи: " . $captchaConfig['type'] . "\n";
                    echo "reCAPTCHA включена: " . ($isRecaptcha ? 'Да' : 'Нет') . "\n";
                    if ($isRecaptcha) {
                        echo "Site Key: " . substr($captchaConfig['recaptcha']['site_key'], 0, 20) . "...\n";
                    }
                    echo "Время действия: " . ($captchaConfig['expires_time'] / 60) . " минут\n";
                    echo "Проверка IP: " . ($captchaConfig['check_ip'] ? 'Да' : 'Нет') . "\n";
                    echo "Логирование: " . ($captchaConfig['logging'] ? 'Да' : 'Нет') . "\n";
                    ?>
                </div>
            </div>

            <!-- Информация о сессии -->
            <div class="info-box">
                <h6>Данные сессии:</h6>
                <div class="session-info">
                    <?php
                    echo "Session ID: " . session_id() . "\n";
                    echo "Время: " . date('H:i:s d.m.Y') . "\n";
                    echo "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n\n";
                    
                    echo "Переменные капчи:\n";
                    $captchaVars = [
                        'captcha_verified', 'captcha_time', 'captcha_target', 
                        'captcha_ip', 'captcha_expires', 'captcha_type'
                    ];
                    
                    foreach ($captchaVars as $var) {
                        $value = $_SESSION[$var] ?? 'не установлено';
                        if ($var === 'captcha_time' || $var === 'captcha_expires') {
                            if (is_numeric($value)) {
                                $value .= ' (' . date('H:i:s d.m.Y', $value) . ')';
                            }
                        }
                        echo "{$var}: {$value}\n";
                    }
                    ?>
                </div>
            </div>
            
            <!-- Логи (если есть права на чтение) -->
            <?php if (file_exists('logs/captcha_verifications.log')): ?>
            <div class="info-box">
                <h6>Последние верификации (5 записей):</h6>
                <div class="session-info">
                    <?php
                    $logs = file('logs/captcha_verifications.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    $logs = array_slice($logs, -5); // Последние 5 записей
                    foreach (array_reverse($logs) as $log) {
                        $data = json_decode($log, true);
                        if ($data) {
                            echo $data['timestamp'] . " - " . $data['ip'] . " -> " . $data['target'] . "\n";
                        }
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        async function testAPI(endpoint) {
            const resultDiv = document.getElementById('api-result');
            resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Тестируем ' + endpoint + '...';
            
            try {
                let response;
                if (endpoint === 'check_user_api.php') {
                    response = await fetch(endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ telegram_id: 123456789 })
                    });
                } else {
                    response = await fetch(endpoint);
                }
                
                const data = await response.json();
                const statusClass = response.ok ? 'alert-success' : 'alert-danger';
                
                resultDiv.innerHTML = `
                    <div class="alert ${statusClass} mt-2">
                        <strong>${endpoint}:</strong> HTTP ${response.status}<br>
                        <small><pre>${JSON.stringify(data, null, 2)}</pre></small>
                    </div>
                `;
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger mt-2">
                        <strong>Ошибка:</strong> ${error.message}
                    </div>
                `;
            }
        }
        
        async function clearCaptcha() {
            if (confirm('Очистить данные капчи из сессии?')) {
                try {
                    const response = await fetch('clear_captcha_session.php', {
                        method: 'POST'
                    });
                    
                    if (response.ok) {
                        alert('Данные капчи очищены');
                        location.reload();
                    } else {
                        alert('Ошибка при очистке');
                    }
                } catch (error) {
                    alert('Ошибка: ' + error.message);
                }
            }
        }
    </script>
</body>
</html> 