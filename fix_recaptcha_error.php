<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Исправление ошибки reCAPTCHA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .fix-container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .error-header {
            text-align: center;
            color: #dc3545;
            margin-bottom: 30px;
        }
        
        .error-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        .step-card {
            border: none;
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 20px;
            border-left: 4px solid #007bff;
            background: #f8f9fa;
        }
        
        .current-config {
            background: #fff3cd;
            border-left-color: #ffc107;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        
        .solution {
            background: #d1ecf1;
            border-left-color: #17a2b8;
        }
        
        .success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        
        .domain-info {
            background: #e2e3e5;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
        }
        
        .btn-copy {
            background: #6c757d;
            border: none;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 0.8em;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="fix-container">
            <div class="error-header">
                <div class="error-icon">🚨</div>
                <h1>Исправление ошибки reCAPTCHA</h1>
                <p class="lead">Ошибка "неверный ключ" - давайте исправим!</p>
            </div>
            
            <?php 
            require_once 'captcha_config.php';
            $config = getCaptchaConfig();
            $siteKey = getRecaptchaSiteKey();
            $secretKey = getRecaptchaSecretKey();
            $currentDomain = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $currentUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $currentDomain;
            ?>
            
            <!-- Текущая конфигурация -->
            <div class="step-card current-config">
                <h5>🔍 Текущая конфигурация</h5>
                <p><strong>Домен сайта:</strong></p>
                <div class="domain-info"><?php echo htmlspecialchars($currentDomain); ?></div>
                
                <p><strong>Site Key:</strong></p>
                <div class="domain-info">
                    <?php echo htmlspecialchars($siteKey); ?>
                    <button class="btn-copy" onclick="copyToClipboard('<?php echo htmlspecialchars($siteKey); ?>')">Копировать</button>
                </div>
                
                <p><strong>Secret Key:</strong></p> 
                <div class="domain-info">
                    <?php echo substr($secretKey, 0, 20) . '...'; ?>
                    <button class="btn-copy" onclick="copyToClipboard('<?php echo htmlspecialchars($secretKey); ?>')">Копировать</button>
                </div>
            </div>
            
            <!-- Причины ошибки -->
            <div class="step-card">
                <h5>❌ Возможные причины ошибки "неверный ключ"</h5>
                <ol>
                    <li><strong>Домен не добавлен в Google reCAPTCHA Console</strong></li>
                    <li><strong>Неправильный тип reCAPTCHA</strong> (v3 вместо v2 или наоборот)</li>
                    <li><strong>Ошибка в ключах</strong> (опечатка при копировании)</li>
                    <li><strong>Ключи для другого проекта</strong></li>
                </ol>
            </div>
            
            <!-- Пошаговое решение -->
            <div class="step-card solution">
                <h5>🛠️ Пошаговое решение</h5>
                
                <h6>Шаг 1: Проверьте настройки в Google reCAPTCHA Console</h6>
                <ol>
                    <li>Идите на <a href="https://www.google.com/recaptcha/admin" target="_blank">https://www.google.com/recaptcha/admin</a></li>
                    <li>Найдите ваш сайт в списке или создайте новый</li>
                    <li>Убедитесь, что выбран тип <strong>"reCAPTCHA v2"</strong> → <strong>"Галочка 'Я не робот'"</strong></li>
                    <li>В разделе "Домены" добавьте:</li>
                </ol>
                
                <div class="domain-info">
                    <?php echo htmlspecialchars($currentDomain); ?><br>
                    localhost<br>
                    127.0.0.1
                </div>
                
                <h6 class="mt-3">Шаг 2: Скопируйте правильные ключи</h6>
                <p>После настройки доменов скопируйте ключи из Google Console:</p>
                <ul>
                    <li><strong>Site Key</strong> - для HTML (публичный)</li>
                    <li><strong>Secret Key</strong> - для сервера (приватный)</li>
                </ul>
            </div>
            
            <!-- Быстрое исправление -->
            <div class="step-card success">
                <h5>⚡ Быстрое исправление</h5>
                
                <h6>Вариант 1: Обновите домены в Google Console</h6>
                <p>Добавьте ваш текущий домен <code><?php echo htmlspecialchars($currentDomain); ?></code> в настройки reCAPTCHA</p>
                
                <h6>Вариант 2: Используйте localhost для тестирования</h6>
                <p>Если тестируете локально, откройте сайт через:</p>
                <div class="domain-info">http://localhost/путь_к_капче</div>
                
                <h6>Вариант 3: Создайте новый сайт в reCAPTCHA</h6>
                <ol>
                    <li>Создайте новый сайт в <a href="https://www.google.com/recaptcha/admin/create" target="_blank">Google reCAPTCHA Console</a></li>
                    <li>Выберите <strong>reCAPTCHA v2</strong> → <strong>"Я не робот"</strong></li>
                    <li>Добавьте домен: <code><?php echo htmlspecialchars($currentDomain); ?></code></li>
                    <li>Скопируйте новые ключи и обновите <code>captcha_config.php</code></li>
                </ol>
            </div>
            
            <!-- Тестирование -->
            <div class="step-card">
                <h5>🧪 Тестирование после исправления</h5>
                <p>После внесения изменений протестируйте:</p>
                <ul>
                    <li><a href="test_recaptcha_keys.php" target="_blank">Тест ключей reCAPTCHA</a></li>
                    <li><a href="captcha.php?target=miniapp" target="_blank">Основная капча</a></li>
                    <li><a href="check_captcha_installation.php" target="_blank">Проверка установки</a></li>
                </ul>
            </div>
            
            <!-- Дополнительная диагностика -->
            <div class="step-card">
                <h5>🔧 Дополнительная диагностика</h5>
                
                <h6>Проверьте в консоли браузера:</h6>
                <p>Откройте Developer Tools (F12) и посмотрите вкладку Console на наличие ошибок</p>
                
                <h6>Проверьте Network:</h6>
                <p>Во вкладке Network должны быть успешные запросы к:</p>
                <ul>
                    <li><code>https://www.google.com/recaptcha/api.js</code></li>
                    <li><code>https://www.google.com/recaptcha/api2/...</code></li>
                </ul>
                
                <h6>Если ничего не помогает:</h6>
                <ol>
                    <li>Очистите кеш браузера</li>
                    <li>Попробуйте в приватном режиме</li>
                    <li>Проверьте, не блокируют ли расширения браузера reCAPTCHA</li>
                    <li>Временно переключитесь на простую капчу: <code>define('CAPTCHA_TYPE', 'simple');</code></li>
                </ol>
            </div>
            
            <!-- Контакты для помощи -->
            <div class="step-card" style="border-left-color: #6f42c1; background: #f8f9ff;">
                <h5>💬 Нужна помощь?</h5>
                <p>Если проблема не решается:</p>
                <ul>
                    <li>Проверьте логи в папке <code>logs/</code></li>
                    <li>Убедитесь, что cURL работает на сервере</li>
                    <li>Проверьте, что сервер может подключаться к Google</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Скопировано в буфер обмена!');
            }, function(err) {
                console.error('Ошибка копирования: ', err);
                // Fallback для старых браузеров
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('Скопировано в буфер обмена!');
            });
        }
        
        // Автоматическая диагностика
        window.addEventListener('load', function() {
            console.log('🔍 Автоматическая диагностика reCAPTCHA:');
            console.log('Домен:', window.location.hostname);
            console.log('Site Key:', '<?php echo htmlspecialchars($siteKey); ?>');
            console.log('Протокол:', window.location.protocol);
            
            // Проверка доступности Google reCAPTCHA API
            fetch('https://www.google.com/recaptcha/api.js', {mode: 'no-cors'})
                .then(() => console.log('✅ Google reCAPTCHA API доступен'))
                .catch(() => console.log('❌ Google reCAPTCHA API недоступен'));
        });
    </script>
</body>
</html> 