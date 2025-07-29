<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест ключей reCAPTCHA</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .test-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .status-card {
            border: none;
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 20px;
        }
        
        .status-ok { background-color: rgba(40, 167, 69, 0.1); border-left: 4px solid #28a745; }
        .status-error { background-color: rgba(220, 53, 69, 0.1); border-left: 4px solid #dc3545; }
        .status-warning { background-color: rgba(255, 193, 7, 0.1); border-left: 4px solid #ffc107; }
        
        .recaptcha-container {
            display: flex;
            justify-content: center;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .btn-test {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            width: 100%;
            margin-top: 15px;
        }
        
        .btn-test:disabled {
            background: #6c757d;
            opacity: 0.6;
        }
        
        .key-info {
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            word-break: break-all;
        }
        
        .result-container {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-container">
            <h1 class="text-center mb-4">🔐 Тест ключей Google reCAPTCHA</h1>
            
            <?php 
            require_once 'captcha_config.php';
            $config = getCaptchaConfig();
            $siteKey = getRecaptchaSiteKey();
            $isRecaptchaEnabled = isRecaptchaEnabled();
            ?>
            
            <!-- Статус конфигурации -->
            <div class="status-card <?php echo $isRecaptchaEnabled ? 'status-ok' : 'status-error'; ?>">
                <h5><?php echo $isRecaptchaEnabled ? '✅ reCAPTCHA настроена' : '❌ reCAPTCHA не настроена'; ?></h5>
                <p>
                    <?php if ($isRecaptchaEnabled): ?>
                        Ваши ключи загружены и готовы к тестированию
                    <?php else: ?>
                        Проверьте настройки в captcha_config.php
                    <?php endif; ?>
                </p>
            </div>
            
            <!-- Информация о ключах -->
            <div class="status-card status-warning">
                <h6>🔑 Информация о ключах</h6>
                <p><strong>Site Key:</strong></p>
                <div class="key-info"><?php echo htmlspecialchars($siteKey); ?></div>
                <p class="mt-2"><strong>Secret Key:</strong></p>
                <div class="key-info"><?php echo substr(getRecaptchaSecretKey(), 0, 20) . '...'; ?></div>
                <small class="text-muted">Secret Key показан частично в целях безопасности</small>
            </div>
            
            <?php if ($isRecaptchaEnabled): ?>
            <!-- Тест reCAPTCHA -->
            <div class="status-card">
                <h6>🧪 Тест функциональности</h6>
                <p>Пройдите reCAPTCHA ниже, чтобы проверить работу ваших ключей:</p>
                
                <div class="recaptcha-container">
                    <div class="g-recaptcha" 
                         data-sitekey="<?php echo htmlspecialchars($siteKey); ?>" 
                         data-callback="onRecaptchaSuccess">
                    </div>
                </div>
                
                <button class="btn btn-test" id="testBtn" onclick="testRecaptcha()" disabled>
                    Тестировать reCAPTCHA
                </button>
                
                <div id="resultContainer" class="result-container">
                    <div id="resultContent"></div>
                </div>
            </div>
            
            <!-- Инструкции -->
            <div class="status-card">
                <h6>📋 Что делать дальше</h6>
                <ul>
                    <li><strong>Если тест прошел успешно:</strong> Ваша reCAPTCHA готова к работе!</li>
                    <li><strong>Откройте:</strong> <a href="captcha.php?target=miniapp">captcha.php?target=miniapp</a></li>
                    <li><strong>Диагностика:</strong> <a href="test_captcha_system.php">test_captcha_system.php</a></li>
                    <li><strong>Проверка установки:</strong> <a href="check_captcha_installation.php">check_captcha_installation.php</a></li>
                </ul>
            </div>
            
            <?php else: ?>
            <!-- Инструкции по настройке -->
            <div class="status-card status-error">
                <h6>🛠️ Настройка требуется</h6>
                <p>Для работы reCAPTCHA необходимо:</p>
                <ol>
                    <li>Убедиться, что ключи правильно указаны в captcha_config.php</li>
                    <li>Установить CAPTCHA_TYPE = 'recaptcha'</li>
                    <li>Проверить работу cURL на сервере</li>
                </ol>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let recaptchaResponse = null;
        
        // Callback для успешного прохождения reCAPTCHA
        window.onRecaptchaSuccess = function(response) {
            console.log('reCAPTCHA пройдена успешно');
            recaptchaResponse = response;
            document.getElementById('testBtn').disabled = false;
            document.getElementById('testBtn').textContent = 'Тестировать reCAPTCHA ✅';
        };
        
        // Тестирование reCAPTCHA
        async function testRecaptcha() {
            if (!recaptchaResponse) {
                alert('Сначала пройдите reCAPTCHA');
                return;
            }
            
            const btn = document.getElementById('testBtn');
            const resultContainer = document.getElementById('resultContainer');
            const resultContent = document.getElementById('resultContent');
            
            btn.disabled = true;
            btn.textContent = 'Тестируем...';
            
            try {
                const response = await fetch('verify_captcha.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        captcha_type: 'recaptcha',
                        recaptcha_response: recaptchaResponse,
                        target: 'test'
                    })
                });
                
                const result = await response.json();
                
                resultContainer.style.display = 'block';
                
                if (result.success) {
                    resultContainer.className = 'result-container status-ok';
                    resultContent.innerHTML = `
                        <h6>✅ Тест успешен!</h6>
                        <p><strong>Сообщение:</strong> ${result.message}</p>
                        <p><strong>Тип капчи:</strong> ${result.captcha_type}</p>
                        <p><strong>Время действия:</strong> ${Math.round(result.expires_in / 60)} минут</p>
                        ${result.recaptcha_info ? `
                            <p><strong>Домен reCAPTCHA:</strong> ${result.recaptcha_info.hostname || 'не указан'}</p>
                        ` : ''}
                        <div class="alert alert-success mt-3">
                            🎉 <strong>Поздравляем!</strong> Ваши ключи reCAPTCHA работают корректно.
                            <br>Теперь можете использовать систему капчи.
                        </div>
                    `;
                } else {
                    resultContainer.className = 'result-container status-error';
                    resultContent.innerHTML = `
                        <h6>❌ Тест не пройден</h6>
                        <p><strong>Ошибка:</strong> ${result.message}</p>
                        <div class="alert alert-danger mt-3">
                            Проверьте настройки ключей в captcha_config.php
                        </div>
                    `;
                }
                
            } catch (error) {
                console.error('Ошибка тестирования:', error);
                
                resultContainer.style.display = 'block';
                resultContainer.className = 'result-container status-error';
                resultContent.innerHTML = `
                    <h6>❌ Ошибка тестирования</h6>
                    <p><strong>Ошибка:</strong> ${error.message}</p>
                    <div class="alert alert-danger mt-3">
                        Проверьте консоль браузера для подробностей
                    </div>
                `;
            }
            
            btn.disabled = false;
            btn.textContent = 'Повторить тест';
        }
        
        // Обработка ошибок reCAPTCHA
        window.onRecaptchaError = function() {
            console.error('Ошибка загрузки reCAPTCHA');
            alert('Ошибка загрузки reCAPTCHA. Проверьте интернет-соединение.');
        };
        
        window.onRecaptchaExpired = function() {
            console.log('reCAPTCHA истекла');
            recaptchaResponse = null;
            document.getElementById('testBtn').disabled = true;
            document.getElementById('testBtn').textContent = 'Тестировать reCAPTCHA';
            alert('Время действия reCAPTCHA истекло. Пройдите проверку заново.');
        };
    </script>
</body>
</html> 