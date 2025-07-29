<?php
require_once 'captcha_config.php';
$captchaConfig = getCaptchaConfig();
$isRecaptcha = isRecaptchaEnabled();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проверка безопасности</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php if ($isRecaptcha): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 20px;
        }
        
        .captcha-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .captcha-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .security-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .captcha-title {
            color: #333;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .captcha-subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1rem;
        }
        
        .captcha-box {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            transition: all 0.3s ease;
        }
        
        .captcha-box:hover {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .captcha-checkbox {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            cursor: pointer;
            user-select: none;
        }
        
        .custom-checkbox {
            width: 24px;
            height: 24px;
            border: 2px solid #ccc;
            border-radius: 4px;
            position: relative;
            transition: all 0.3s ease;
            background: white;
        }
        
        .custom-checkbox.checked {
            background: #28a745;
            border-color: #28a745;
        }
        
        .custom-checkbox.checked::after {
            content: '✓';
            position: absolute;
            color: white;
            font-weight: bold;
            font-size: 16px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .checkbox-label {
            font-size: 1.1rem;
            color: #333;
            font-weight: 500;
        }
        
        .continue-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0.5;
            pointer-events: none;
            margin-top: 20px;
            width: 100%;
        }
        
        .continue-btn.enabled {
            opacity: 1;
            pointer-events: auto;
        }
        
        .continue-btn.enabled:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
        }
        
        .loading-spinner {
            display: none;
            margin-right: 10px;
        }
        
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .loading-content {
            text-align: center;
        }
        
        .loading-spinner-big {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Стили для reCAPTCHA */
        .recaptcha-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 78px;
        }
        
        .g-recaptcha {
            transform: scale(0.9);
            transform-origin: center;
        }
        
        @media (max-width: 400px) {
            .g-recaptcha {
                transform: scale(0.8);
            }
        }
        
        .recaptcha-info {
            text-align: center;
            margin-top: 15px;
        }
        
        .recaptcha-error {
            color: #dc3545;
            text-align: center;
            margin-top: 10px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner-big"></div>
            <h4>Проверяем безопасность...</h4>
            <p>Пожалуйста, подождите</p>
        </div>
    </div>

    <div class="captcha-container">
        <div class="security-icon">🛡️</div>
        <h1 class="captcha-title">Проверка безопасности</h1>
        <p class="captcha-subtitle">Подтвердите, что вы не робот</p>
        
        <div class="captcha-box">
            <?php if ($isRecaptcha): ?>
                <!-- Google reCAPTCHA -->
                <div class="recaptcha-container" id="recaptchaContainer">
                    <div class="g-recaptcha" data-sitekey="<?php echo getRecaptchaSiteKey(); ?>" data-callback="onRecaptchaSuccess"></div>
                </div>
            <?php else: ?>
                <!-- Простая капча -->
                <div class="captcha-checkbox" id="captchaCheckbox">
                    <div class="custom-checkbox" id="customCheckbox"></div>
                    <span class="checkbox-label">Я не робот</span>
                </div>
            <?php endif; ?>
        </div>
        
        <button class="continue-btn" id="continueBtn">
            <span class="loading-spinner spinner-border spinner-border-sm" id="loadingSpinner"></span>
            Продолжить
        </button>
        
        <div class="security-badge">
            <span>🔒</span>
            <span>Защищено 
                <?php echo $isRecaptcha ? 'Google reCAPTCHA' : 'системой безопасности'; ?>
            </span>
        </div>
        
        <?php if ($isRecaptcha): ?>
        <div class="recaptcha-info">
            <small class="text-muted">Используется Google reCAPTCHA v2</small>
        </div>
        <?php endif; ?>
    </div>

    <script>
        const tg = window.Telegram?.WebApp;
        if (tg) {
            tg.ready();
            tg.expand();
        }

        const continueBtn = document.getElementById('continueBtn');
        const loadingOverlay = document.getElementById('loadingOverlay');
        
        let isVerified = false;
        let isProcessing = false;
        let recaptchaResponse = null;
        
        // Определяем тип капчи
        const isRecaptcha = <?php echo $isRecaptcha ? 'true' : 'false'; ?>;
        
        // Получаем параметры URL для определения, куда перенаправлять
        const urlParams = new URLSearchParams(window.location.search);
        const targetPage = urlParams.get('target') || 'miniapp';
        
        // Инициализация в зависимости от типа капчи
        if (isRecaptcha) {
            // Функция обратного вызова для reCAPTCHA
            window.onRecaptchaSuccess = function(response) {
                console.log('reCAPTCHA успешно пройдена');
                recaptchaResponse = response;
                isVerified = true;
                continueBtn.classList.add('enabled');
                
                // Убираем сообщение об ошибке если оно есть
                const errorEl = document.querySelector('.recaptcha-error');
                if (errorEl) {
                    errorEl.remove();
                }
            };
            
            // Функция для обработки истечения reCAPTCHA
            window.onRecaptchaExpired = function() {
                console.log('reCAPTCHA истекла');
                recaptchaResponse = null;
                isVerified = false;
                continueBtn.classList.remove('enabled');
                showRecaptchaError('Время действия reCAPTCHA истекло. Пожалуйста, пройдите проверку заново.');
            };
            
            // Функция для обработки ошибок reCAPTCHA
            window.onRecaptchaError = function() {
                console.log('Ошибка reCAPTCHA');
                recaptchaResponse = null;
                isVerified = false;
                continueBtn.classList.remove('enabled');
                showRecaptchaError('Ошибка загрузки reCAPTCHA. Попробуйте обновить страницу.');
            };
            
        } else {
            // Простая капча
            const checkbox = document.getElementById('customCheckbox');
            const captchaCheckbox = document.getElementById('captchaCheckbox');
            
            if (captchaCheckbox) {
                captchaCheckbox.addEventListener('click', function() {
                    if (isProcessing) return;
                    
                    isVerified = !isVerified;
                    
                    if (isVerified) {
                        checkbox.classList.add('checked');
                        
                        // Добавляем небольшую задержку для реалистичности
                        setTimeout(() => {
                            continueBtn.classList.add('enabled');
                        }, 800);
                    } else {
                        checkbox.classList.remove('checked');
                        continueBtn.classList.remove('enabled');
                    }
                });
                
                // Добавляем эффект наведения на чекбокс
                captchaCheckbox.addEventListener('mouseenter', function() {
                    if (!isVerified) {
                        checkbox.style.borderColor = '#667eea';
                    }
                });
                
                captchaCheckbox.addEventListener('mouseleave', function() {
                    if (!isVerified) {
                        checkbox.style.borderColor = '#ccc';
                    }
                });
            }
        }
        
        // Обработчик кнопки "Продолжить"
        continueBtn.addEventListener('click', async function() {
            if (!isVerified || isProcessing) return;
            
            isProcessing = true;
            loadingOverlay.style.display = 'flex';
            
            try {
                let requestData = {
                    target: targetPage,
                    captcha_type: isRecaptcha ? 'recaptcha' : 'simple'
                };
                
                if (isRecaptcha) {
                    if (!recaptchaResponse) {
                        throw new Error('reCAPTCHA не пройдена');
                    }
                    requestData.recaptcha_response = recaptchaResponse;
                } else {
                    requestData.captcha_verified = true;
                }
                
                // Отправляем запрос на сервер для установки сессии
                const response = await fetch('verify_captcha.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Перенаправляем на целевую страницу
                    const targetUrl = targetPage === 'test' ? 'test_miniapp.php' : 'miniapp.php';
                    window.location.href = targetUrl;
                } else {
                    throw new Error(result.message || 'Ошибка верификации');
                }
                
            } catch (error) {
                console.error('Ошибка:', error);
                alert('Ошибка проверки безопасности: ' + error.message);
                
                // Сбрасываем состояние
                resetCaptcha();
                isProcessing = false;
                loadingOverlay.style.display = 'none';
            }
        });
        
        // Функция для сброса капчи
        function resetCaptcha() {
            isVerified = false;
            continueBtn.classList.remove('enabled');
            
            if (isRecaptcha) {
                // Сбрасываем reCAPTCHA
                if (typeof grecaptcha !== 'undefined') {
                    grecaptcha.reset();
                }
                recaptchaResponse = null;
            } else {
                // Сбрасываем простую капчу
                const checkbox = document.getElementById('customCheckbox');
                if (checkbox) {
                    checkbox.classList.remove('checked');
                }
            }
        }
        
        // Функция для показа ошибки reCAPTCHA
        function showRecaptchaError(message) {
            // Удаляем предыдущее сообщение об ошибке
            const existingError = document.querySelector('.recaptcha-error');
            if (existingError) {
                existingError.remove();
            }
            
            // Добавляем новое сообщение об ошибке
            const errorDiv = document.createElement('div');
            errorDiv.className = 'recaptcha-error';
            errorDiv.textContent = message;
            
            const recaptchaContainer = document.getElementById('recaptchaContainer');
            recaptchaContainer.appendChild(errorDiv);
        }
    </script>
</body>
</html> 