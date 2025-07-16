<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реферальная система</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: var(--tg-theme-bg-color, #ffffff);
            color: var(--tg-theme-text-color, #000000);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 20px;
            margin: 0;
        }
        
        .container {
            max-width: 400px;
            margin: 0 auto;
        }
        
        .card {
            background-color: var(--tg-theme-secondary-bg-color, #f8f9fa);
            border: none;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid var(--tg-theme-hint-color, #999999);
            padding: 12px;
            margin-bottom: 15px;
            background-color: var(--tg-theme-bg-color, #ffffff);
            color: var(--tg-theme-text-color, #000000);
        }
        
        .btn-primary {
            background-color: var(--tg-theme-button-color, #007bff);
            border-color: var(--tg-theme-button-color, #007bff);
            color: var(--tg-theme-button-text-color, #ffffff);
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            width: 100%;
        }
        
        .btn-primary:hover {
            background-color: var(--tg-theme-button-color, #0056b3);
            border-color: var(--tg-theme-button-color, #0056b3);
        }
        
        .loading {
            text-align: center;
            padding: 40px;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
            color: var(--tg-theme-button-color, #007bff);
        }
        
        .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--tg-theme-text-color, #155724);
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        
        .alert-info {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--tg-theme-text-color, #0c5460);
            border: 1px solid rgba(23, 162, 184, 0.2);
        }
        
        .welcome-message {
            text-align: center;
            padding: 30px;
        }
        
        .welcome-message h2 {
            color: var(--tg-theme-text-color, #000000);
            margin-bottom: 20px;
        }
        
        .user-info {
            background-color: var(--tg-theme-secondary-bg-color, #f8f9fa);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .hidden {
            display: none;
        }
        
        .error {
            color: #dc3545;
            text-align: center;
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
            color: var(--tg-theme-text-color, #000000);
        }
        
        .required {
            color: #dc3545;
        }
        
        .inviter-info {
            background-color: rgba(23, 162, 184, 0.1);
            padding: 10px;
            border-radius: 6px;
            margin-top: 5px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Экран загрузки -->
        <div id="loading" class="loading">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <p class="mt-3">Проверяем данные...</p>
        </div>

        <!-- Сообщение об ошибке -->
        <div id="error" class="error hidden">
            <h3>Ошибка</h3>
            <p id="error-message"></p>
        </div>

        <!-- Экран для зарегистрированных пользователей -->
        <div id="registered" class="hidden">
            <div class="welcome-message">
                <h2>Добро пожаловать!</h2>
                <div class="alert alert-info">
                    <strong>Вы уже зарегистрированы в системе</strong>
                </div>
                <div class="user-info">
                    <p><strong>Имя:</strong> <span id="user-name"></span></p>
                    <p><strong>Telegram:</strong> <span id="user-telegram"></span></p>
                    <p><strong>Статус:</strong> <span id="user-status"></span></p>
                </div>
            </div>
        </div>

        <!-- Форма регистрации -->
        <div id="registration" class="hidden">
            <div class="card">
                <h3 class="text-center mb-4">Регистрация</h3>
                
                <form id="registrationForm">
                    <div class="form-group">
                        <label class="form-label" for="full_name">ФИО <span class="required">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="bank_card">Номер банковской карты <span class="required">*</span></label>
                        <input type="text" class="form-control" id="bank_card" name="bank_card" 
                               placeholder="1234 5678 9012 3456" maxlength="19" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="telegram_username">Имя пользователя Telegram <span class="required">*</span></label>
                        <input type="text" class="form-control" id="telegram_username" name="telegram_username" 
                               placeholder="@username" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone_number">Номер телефона <span class="required">*</span></label>
                        <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                               placeholder="89051234567" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="birth_date">Дата рождения <span class="required">*</span></label>
                        <input type="date" class="form-control" id="birth_date" name="birth_date" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="affiliate_select">Пригласил</label>
                        <select class="form-control" id="affiliate_select" name="affiliate_id">
                            <option value="">Выберите пригласителя</option>
                        </select>
                        <div id="affiliate-info" class="inviter-info hidden"></div>
                    </div>

                    <input type="hidden" id="telegram_id" name="telegram_id">

                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        Зарегистрироваться
                    </button>
                </form>
            </div>
        </div>

        <!-- Сообщение об успешной регистрации -->
        <div id="success" class="hidden">
            <div class="welcome-message">
                <h2>Регистрация успешна!</h2>
                <div class="alert alert-success">
                    <strong>Вы успешно зарегистрировались в системе</strong>
                </div>
                <p>Добро пожаловать в нашу реферальную программу!</p>
            </div>
        </div>
    </div>

    <script>
        // Инициализация Telegram WebApp
        const tg = window.Telegram.WebApp;
        
        // Настройка темы
        tg.ready();
        tg.expand();
        
        // Данные пользователя из Telegram
        let telegramUser = null;
        let affiliates = [];
        
        // Загрузка данных при запуске
        document.addEventListener('DOMContentLoaded', function() {
            initializeApp();
        });

        async function initializeApp() {
            try {
                // Получаем данные пользователя из Telegram
                telegramUser = tg.initDataUnsafe.user;
                
                if (!telegramUser) {
                    throw new Error('Не удалось получить данные пользователя из Telegram');
                }
                
                // Проверяем статус пользователя
                const userStatus = await checkUserStatus(telegramUser.id);
                
                // Скрываем загрузку
                document.getElementById('loading').classList.add('hidden');
                
                // Показываем соответствующий экран
                if (userStatus.exists) {
                    showRegisteredUser(userStatus.user);
                } else {
                    // Загружаем список партнеров для формы
                    await loadAffiliates();
                    showRegistrationForm();
                }
                
            } catch (error) {
                console.error('Ошибка инициализации:', error);
                showError('Ошибка подключения к системе: ' + error.message);
            }
        }

        async function checkUserStatus(telegramId) {
            const response = await fetch('check_user_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ telegram_id: telegramId })
            });
            
            if (!response.ok) {
                throw new Error('Ошибка проверки пользователя');
            }
            
            return await response.json();
        }

        async function loadAffiliates() {
            const response = await fetch('get_affiliates_api.php');
            
            if (!response.ok) {
                throw new Error('Ошибка загрузки партнеров');
            }
            
            const data = await response.json();
            affiliates = data.affiliates;
            
            // Заполняем select
            const select = document.getElementById('affiliate_select');
            affiliates.forEach(affiliate => {
                const option = document.createElement('option');
                option.value = affiliate.id;
                option.textContent = `${affiliate.full_name} (${affiliate.telegram_username})`;
                select.appendChild(option);
            });
        }

        function showRegisteredUser(user) {
            document.getElementById('user-name').textContent = user.full_name;
            document.getElementById('user-telegram').textContent = user.telegram_username;
            document.getElementById('user-status').textContent = user.is_affiliate ? 'Партнер' : 'Пользователь';
            document.getElementById('registered').classList.remove('hidden');
        }

        function showRegistrationForm() {
            // Заполняем данные из Telegram
            document.getElementById('telegram_id').value = telegramUser.id;
            document.getElementById('telegram_username').value = '@' + (telegramUser.username || '');
            
            // Если есть имя и фамилия из Telegram, заполняем ФИО
            if (telegramUser.first_name || telegramUser.last_name) {
                const fullName = [telegramUser.first_name, telegramUser.last_name].filter(Boolean).join(' ');
                document.getElementById('full_name').value = fullName;
            }
            
            document.getElementById('registration').classList.remove('hidden');
        }

        function showError(message) {
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('error-message').textContent = message;
            document.getElementById('error').classList.remove('hidden');
        }

        // Обработка формы регистрации
        document.getElementById('registrationForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Регистрация...';
            
            try {
                const formData = new FormData(this);
                const response = await fetch('register_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('registration').classList.add('hidden');
                    document.getElementById('success').classList.remove('hidden');
                } else {
                    throw new Error(result.message || 'Ошибка регистрации');
                }
                
            } catch (error) {
                console.error('Ошибка регистрации:', error);
                alert('Ошибка регистрации: ' + error.message);
                
                submitBtn.disabled = false;
                submitBtn.textContent = 'Зарегистрироваться';
            }
        });

        // Форматирование номера карты
        document.getElementById('bank_card').addEventListener('input', function() {
            let value = this.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = '';
            
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            
            this.value = formattedValue;
        });

        // Форматирование телефона
        document.getElementById('phone_number').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Показ информации о пригласителе
        document.getElementById('affiliate_select').addEventListener('change', function() {
            const selectedId = this.value;
            const infoDiv = document.getElementById('affiliate-info');
            
            if (selectedId) {
                const affiliate = affiliates.find(a => a.id == selectedId);
                if (affiliate) {
                    infoDiv.innerHTML = `
                        <strong>Информация о пригласителе:</strong><br>
                        Имя: ${affiliate.full_name}<br>
                        Telegram: ${affiliate.telegram_username}<br>
                        Рефералов: ${affiliate.referral_count}
                    `;
                    infoDiv.classList.remove('hidden');
                }
            } else {
                infoDiv.classList.add('hidden');
            }
        });
    </script>
</body>
</html> 