<?php
// Начинаем сессию и проверяем капчу перед любым выводом
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'check_captcha.php';
requireCaptcha('test');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тестовая страница Mini App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a1a1a;
            color: #ffffff;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 20px;
            margin: 0;
        }
        
        .container {
            max-width: 400px;
            margin: 0 auto;
        }
        
        .card {
            background-color: #2d2d2d;
            border: none;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        
        .card h3 {
            color: #ffffff !important;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid #555555;
            padding: 12px;
            margin-bottom: 15px;
            background-color: #3a3a3a;
            color: #ffffff;
        }
        
        .form-control:focus {
            background-color: #3a3a3a;
            border-color: #007bff;
            color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .form-control::placeholder {
            color: #888888;
            opacity: 1;
        }
        
        .form-control::-webkit-input-placeholder {
            color: #888888;
        }
        
        .form-control::-moz-placeholder {
            color: #888888;
            opacity: 1;
        }
        
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            color: #ffffff;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            width: 100%;
        }
        
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
            color: #007bff;
        }
        
        .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            color: #75dd88;
            border: 1px solid rgba(40, 167, 69, 0.4);
        }
        
        .alert-info {
            background-color: rgba(23, 162, 184, 0.2);
            color: #17a2b8;
            border: 1px solid rgba(23, 162, 184, 0.4);
        }
        
        .welcome-message {
            text-align: center;
            padding: 30px;
        }
        
        .welcome-message h2 {
            color: #ffffff;
            margin-bottom: 20px;
        }
        
        .user-info {
            background-color: #3a3a3a;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .hidden {
            display: none;
        }
        
        .error {
            color: #ff6b6b;
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
            color: #ffffff;
        }
        
        .required {
            color: #dc3545;
        }
        
        .inviter-info {
            background-color: rgba(23, 162, 184, 0.2);
            padding: 10px;
            border-radius: 6px;
            margin-top: 5px;
            font-size: 0.9em;
            color: #ffffff;
        }
        
        .test-controls {
            background-color: #3a3a3a;
            border: 1px solid #555555;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .test-controls h4 {
            margin-bottom: 15px;
            color: #ffc107;
        }
        
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000;
            margin-right: 10px;
            margin-bottom: 5px;
        }
        
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: #fff;
            margin-right: 10px;
            margin-bottom: 5px;
        }
        
        select.form-control {
            background-color: #3a3a3a;
            color: #ffffff;
            border: 1px solid #555555;
        }
        
        select.form-control option {
            background-color: #3a3a3a;
            color: #ffffff;
        }
        
        .search-results {
            background-color: #3a3a3a;
            border: 1px solid #555555;
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            margin-top: 5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        
        .search-result-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #555555;
        }
        
        .search-result-item:last-child {
            border-bottom: none;
        }
        
        .search-result-item:hover {
            background-color: #4a4a4a;
        }
        
        .search-result-item .name {
            font-weight: 600;
            color: #ffffff;
        }
        
        .search-result-item .username {
            color: #17a2b8;
            font-size: 0.9em;
        }
        
        .search-result-item .badge {
            font-size: 0.8em;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 5px;
        }
        
        .search-result-item .badge.partner {
            background-color: #28a745;
            color: #ffffff;
        }
        
        .search-result-item .badge.user {
            background-color: #6c757d;
            color: #ffffff;
        }
        
        .selected-inviter {
            background-color: rgba(23, 162, 184, 0.2);
            padding: 10px;
            border-radius: 6px;
            margin-top: 5px;
            font-size: 0.9em;
            color: #ffffff;
            border: 1px solid rgba(23, 162, 184, 0.4);
        }
        
        .selected-inviter .remove-btn {
            float: right;
            background: none;
            border: none;
            color: #ff6b6b;
            cursor: pointer;
            font-size: 1.2em;
            padding: 0;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-controls">
            <h4>🔧 Тестовые режимы</h4>
            <button class="btn btn-warning" onclick="testNewUser()">Новый пользователь</button>
            <button class="btn btn-info" onclick="testExistingUser()">Существующий партнер</button>
            <button class="btn btn-warning" onclick="testExistingAffiliate()">Существующий пользователь</button>
        </div>
        
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
                 
                 <!-- Кнопка для партнеров -->
                 <div id="affiliate-actions" class="hidden">
                     <div class="alert alert-success">
                         <strong>🎉 Добро пожаловать в панель партнера!</strong>
                     </div>
                     <button class="btn btn-primary" onclick="goToWorkPanel()">
                         Перейти к работе
                     </button>
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
                        <label class="form-label" for="telegram_username">Имя пользователя Telegram <span class="required">*</span></label>
                        <input type="text" class="form-control" id="telegram_username" name="telegram_username" 
                               placeholder="@username" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inviter_search">Пригласил</label>
                        <input type="text" class="form-control" id="inviter_search" 
                               placeholder="Введите @username или имя..." autocomplete="off">
                        <div id="search-results" class="search-results hidden"></div>
                        <div id="selected-inviter" class="selected-inviter hidden"></div>
                        <input type="hidden" id="affiliate_id" name="affiliate_id">
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
                <p>Добро пожаловать!</p>
            </div>
        </div>
    </div>

    <script>
        // Эмуляция Telegram WebApp для тестирования
        const mockTelegramUsers = {
            new: {
                id: 999999999,
                first_name: 'Тестовый',
                last_name: 'Пользователь',
                username: 'testuser'
            },
            existing: {
                id: 430892673,
                first_name: 'Сергей',
                last_name: 'Партнеров',
                username: 'sergey_partner'
            },
            affiliate: {
                id: 222222222,
                first_name: 'Андрей',
                last_name: 'Иванов',
                username: 'andrey_ivanov'
            }
        };
        
        // Текущий пользователь для теста
        let currentTestUser = mockTelegramUsers.new;
        
        // Данные пользователя из Telegram
        let telegramUser = null;
        
        // Функции для тестирования
        function testNewUser() {
            currentTestUser = mockTelegramUsers.new;
            resetAndStart();
        }
        
        function testExistingUser() {
            currentTestUser = mockTelegramUsers.existing;
            resetAndStart();
        }
        
        function testExistingAffiliate() {
            currentTestUser = mockTelegramUsers.affiliate;
            resetAndStart();
        }
        
        function resetAndStart() {
            // Скрываем все экраны
            document.querySelectorAll('.container > div:not(.test-controls)').forEach(div => {
                div.classList.add('hidden');
            });
            
            // Очищаем выбор пригласителя
            clearSelection();
            
            // Показываем загрузку
            document.getElementById('loading').classList.remove('hidden');
            
            // Запускаем инициализацию
            setTimeout(initializeApp, 1000);
        }
        
        // Загрузка данных при запуске
        document.addEventListener('DOMContentLoaded', function() {
            initializeApp();
        });

        async function initializeApp() {
            try {
                // Используем тестовые данные
                telegramUser = currentTestUser;
                
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

        let searchTimeout;
        let selectedUser = null;

        async function searchUsers(query) {
            try {
                if (!query || query.trim().length < 2) {
                    hideSearchResults();
                    return;
                }
                
                const response = await fetch(`search_users_api.php?search=${encodeURIComponent(query)}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Ошибка поиска пользователей');
                }
                
                showSearchResults(data.users || []);
                
            } catch (error) {
                console.error('Ошибка поиска пользователей:', error);
                hideSearchResults();
            }
        }

        function showSearchResults(users) {
            const resultsDiv = document.getElementById('search-results');
            
            if (users.length === 0) {
                resultsDiv.innerHTML = '<div class="search-result-item">Пользователи не найдены</div>';
                resultsDiv.classList.remove('hidden');
                return;
            }
            
            let html = '';
            users.forEach(user => {
                const badge = user.is_affiliate ? 
                    '<span class="badge partner">Партнер</span>' : 
                    '<span class="badge user">Пользователь</span>';
                
                const displayUsername = user.telegram_username.startsWith('@') ? user.telegram_username : `@${user.telegram_username}`;
                html += `
                    <div class="search-result-item" onclick="selectUser(${user.id}, '${user.full_name}', '${user.telegram_username}', ${user.is_affiliate}, ${user.referral_count})">
                        <div class="name">${user.full_name} ${badge}</div>
                        <div class="username">${displayUsername}</div>
                        ${user.referral_count > 0 ? `<div style="font-size: 0.8em; color: #888;">Рефералов: ${user.referral_count}</div>` : ''}
                    </div>
                `;
            });
            
            resultsDiv.innerHTML = html;
            resultsDiv.classList.remove('hidden');
        }

        function hideSearchResults() {
            document.getElementById('search-results').classList.add('hidden');
        }

        function selectUser(id, name, username, isAffiliate, referralCount) {
            selectedUser = { id, name, username, isAffiliate, referralCount };
            
            // Проверяем, начинается ли username с @
            const displayUsername = username.startsWith('@') ? username : `@${username}`;
            document.getElementById('inviter_search').value = displayUsername;
            document.getElementById('affiliate_id').value = id;
            
            const selectedDiv = document.getElementById('selected-inviter');
            
            const displayUsernameForInfo = username.startsWith('@') ? username : `@${username}`;
            selectedDiv.innerHTML = `
                <strong>${name}</strong><br>
                Telegram: ${displayUsernameForInfo}
                <button type="button" class="remove-btn" onclick="clearSelection()">&times;</button>
            `;
            selectedDiv.classList.remove('hidden');
            
            hideSearchResults();
        }

        function clearSelection() {
            selectedUser = null;
            document.getElementById('inviter_search').value = '';
            document.getElementById('affiliate_id').value = '';
            document.getElementById('selected-inviter').classList.add('hidden');
        }

                 function showRegisteredUser(user) {
             document.getElementById('user-name').textContent = user.full_name;
             document.getElementById('user-telegram').textContent = user.telegram_username;
             document.getElementById('user-status').textContent = user.is_affiliate ? 'Партнер' : 'Пользователь';
             
             // Показываем кнопку для партнеров
             if (user.is_affiliate) {
                 document.getElementById('affiliate-actions').classList.remove('hidden');
             }
             
             document.getElementById('registered').classList.remove('hidden');
         }

         function goToWorkPanel() {
             // Перенаправляем на панель управления
             window.location.href = 'index.php';
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
            
            // Убеждаемся, что selected-inviter скрыт при инициализации
            document.getElementById('selected-inviter').classList.add('hidden');
            document.getElementById('search-results').classList.add('hidden');
            
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
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                let result;
                try {
                    result = await response.json();
                } catch (jsonError) {
                    throw new Error('Ошибка обработки ответа сервера');
                }
                
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



        // Обработчик поиска пригласителя
        document.getElementById('inviter_search').addEventListener('input', function() {
            const query = this.value;
            
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchUsers(query);
            }, 300);
        });

        // Скрыть результаты при клике вне поля
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#inviter_search') && !e.target.closest('#search-results')) {
                hideSearchResults();
            }
        });
    </script>
</body>
</html> 