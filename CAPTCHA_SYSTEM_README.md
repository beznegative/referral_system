# 🛡️ Система капчи для реферальной системы (v2.0)

## 🆕 Что нового

- ✅ **Поддержка Google reCAPTCHA v2** - профессиональная защита
- ✅ **Гибкая конфигурация** - легкое переключение между типами капчи
- ✅ **Улучшенная безопасность** - проверка через Google API
- ✅ **Расширенное логирование** - подробная аналитика
- ✅ **Тестовая панель** - удобная диагностика

## 🚀 Быстрый старт

### 1. Для простой капчи (по умолчанию)
```bash
# Откройте браузер
http://yoursite.com/test_captcha_system.php
```

### 2. Для Google reCAPTCHA
```bash
# 1. Получите ключи на https://www.google.com/recaptcha/admin/create
# 2. Обновите captcha_config.php
# 3. Измените CAPTCHA_TYPE на 'recaptcha'
```

## 📁 Структура системы

```
🛡️ Система капчи:
├── 📄 captcha.php              # Универсальная страница капчи
├── ⚙️ captcha_config.php       # Конфигурация системы
├── 🔐 verify_captcha.php       # Обработка верификации
├── 🔍 check_captcha.php        # Функции проверки статуса
├── 🧪 test_captcha_system.php  # Тестовая панель
├── 🗑️ clear_captcha_session.php # Очистка сессии
└── 📋 logs/                    # Логи системы

🔒 Защищенные файлы:
├── 📱 miniapp.php
├── 🧪 test_miniapp.php  
├── 🔌 check_user_api.php
├── 🔌 get_affiliates_api.php
└── 🔌 register_api.php

📚 Документация:
├── 📖 CAPTCHA_SYSTEM_README.md    # Эта документация
├── 🛠️ RECAPTCHA_SETUP.md         # Настройка reCAPTCHA
├── 📋 CAPTCHA_USAGE.md           # Инструкции по использованию
└── 📝 README_CAPTCHA.md          # Техническая документация
```

## ⚙️ Конфигурация

### Основные настройки (`captcha_config.php`)

```php
// Тип капчи
define('CAPTCHA_TYPE', 'simple');    // 'simple' или 'recaptcha'

// Google reCAPTCHA (если используется)
define('RECAPTCHA_SITE_KEY', 'ваш_site_key');
define('RECAPTCHA_SECRET_KEY', 'ваш_secret_key');

// Время действия (секунды)
define('CAPTCHA_EXPIRES_TIME', 30 * 60);  // 30 минут

// Безопасность
define('CAPTCHA_CHECK_IP', true);          // Проверка IP
define('CAPTCHA_MAX_ATTEMPTS', 5);         // Макс. попыток
```

### Переключение типа капчи

**Простая капча:**
```php
define('CAPTCHA_TYPE', 'simple');
```

**Google reCAPTCHA:**
```php
define('CAPTCHA_TYPE', 'recaptcha');
// + настройте ключи reCAPTCHA
```

## 🎯 Использование

### Для пользователей

| Действие | URL | Результат |
|----------|-----|-----------|
| Обычное приложение | `captcha.php?target=miniapp` | → `miniapp.php` |
| Тестовое приложение | `captcha.php?target=test` | → `test_miniapp.php` |
| Диагностика | `test_captcha_system.php` | Панель управления |

### Для разработчиков

```php
// Проверка статуса капчи в коде
require_once 'check_captcha.php';

$status = checkCaptchaStatus();
if ($status['verified']) {
    echo "Капча пройдена: " . $status['captcha_type'];
} else {
    echo "Требуется капча: " . $status['reason'];
}

// Принудительная проверка с перенаправлением
requireCaptcha('miniapp'); // Перенаправит на капчу если не пройдена
```

## 🔧 Тестирование

### Тестовая панель
```
http://yoursite.com/test_captcha_system.php
```

**Возможности:**
- ✅ Просмотр текущего статуса капчи
- 🔧 Кнопки быстрого тестирования
- 📊 Информация о конфигурации
- 🧪 Тест API запросов
- 📋 Просмотр логов
- 🗑️ Очистка сессии

### Сценарии тестирования

1. **Новый пользователь:** `miniapp.php` → автоперенаправление на капчу
2. **Действующая капча:** `miniapp.php` → прямой доступ
3. **Просроченная капча:** `miniapp.php` → новая капча
4. **API без капчи:** HTTP 403 с перенаправлением

## 🔒 Безопасность

### Уровни защиты

**Простая капча:**
- ✅ Защита от простых ботов
- ✅ Привязка к IP и сессии
- ✅ Временные ограничения

**Google reCAPTCHA:**
- ✅ Профессиональная защита Google
- ✅ Машинное обучение
- ✅ Анализ поведения пользователя
- ✅ Защита от сложных атак

### Настройки безопасности

```php
// Проверка IP адреса
define('CAPTCHA_CHECK_IP', true);

// Время действия
define('CAPTCHA_EXPIRES_TIME', 30 * 60);  // 30 минут

// Максимум попыток
define('CAPTCHA_MAX_ATTEMPTS', 5);
```

## 📊 Логирование

### Типы логов

| Файл | Описание |
|------|----------|
| `captcha_verifications.log` | Успешные проверки |
| `captcha_errors.log` | Ошибки верификации |
| `captcha_actions.log` | Действия (очистка, сброс) |

### Пример лога

```json
{
  "timestamp": "2024-01-15 14:30:25",
  "ip": "192.168.1.100",
  "captcha_type": "recaptcha",
  "target": "miniapp",
  "recaptcha_score": null,
  "session_id": "abc123..."
}
```

## 🌐 Интеграция с API

### Защита API

Все API автоматически проверяют капчу:

```php
// В начале каждого API файла
require_once 'check_captcha.php';

$captchaStatus = checkCaptchaStatus();
if (!$captchaStatus['verified']) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Требуется прохождение капчи',
        'redirect_url' => 'captcha.php?target=miniapp'
    ]);
    exit;
}
```

### Обработка в JavaScript

```javascript
try {
    const response = await fetch('api_endpoint.php');
    const data = await response.json();
    
    if (!response.ok && data.redirect_url) {
        // Перенаправляем на капчу
        window.location.href = data.redirect_url;
    }
} catch (error) {
    console.error('API Error:', error);
}
```

## 🎨 Кастомизация

### Стили капчи

Редактируйте CSS в `captcha.php`:

```css
/* Изменение цветовой схемы */
.captcha-container {
    background: your-color;
}

/* Кастомные стили для reCAPTCHA */
.g-recaptcha {
    transform: scale(0.9);
}
```

### Тексты и переводы

```php
// В captcha.php
<h1 class="captcha-title">Ваш заголовок</h1>
<p class="captcha-subtitle">Ваш подзаголовок</p>
```

## 🔧 Устранение неполадок

### Простая капча не работает

1. **Проверьте сессии PHP:**
   ```bash
   # Права на папку сессий
   chmod 755 /var/lib/php/sessions
   ```

2. **Проверьте логи:**
   ```bash
   tail -f logs/captcha_errors.log
   ```

### reCAPTCHA не работает

1. **Проверьте ключи:**
   ```php
   // В test_captcha_system.php смотрите раздел конфигурации
   ```

2. **Проверьте cURL:**
   ```bash
   php -m | grep curl
   ```

3. **Проверьте домены в Google Console**

4. **Проверьте сетевые ограничения:**
   ```bash
   curl -I https://www.google.com/recaptcha/api/siteverify
   ```

### Общие проблемы

| Проблема | Причина | Решение |
|----------|---------|---------|
| Постоянные перенаправления | Проблемы с сессиями | Очистите cookies, проверьте PHP сессии |
| API возвращает 403 | Капча не пройдена | Сначала пройдите капчу в браузере |
| reCAPTCHA не загружается | Блокировка сети/рекламы | Проверьте блокировщики, файрволы |
| Неверные ключи | Ошибка в конфигурации | Проверьте ключи в Google Console |

## 📈 Мониторинг

### Встроенная аналитика

```
http://yoursite.com/test_captcha_system.php
```

- 📊 Статистика прохождений
- 🕒 Время действия сессий
- 📋 Последние события
- ⚙️ Текущая конфигурация

### Google reCAPTCHA Analytics

```
https://www.google.com/recaptcha/admin
```

- 📈 Количество запросов
- ✅ Процент успешных проверок
- 🤖 Обнаружение ботов
- 🌍 География запросов

## 🚀 Производительность

### Рекомендации

1. **Включите кеширование:**
   ```php
   // Кеш для статических ресурсов
   header('Cache-Control: public, max-age=3600');
   ```

2. **Оптимизируйте reCAPTCHA:**
   ```javascript
   // Ленивая загрузка
   <script src="https://www.google.com/recaptcha/api.js" async defer></script>
   ```

3. **Настройте CDN** для статических файлов

## 🔄 Обновления

### v2.0 (Текущая)
- ✅ Поддержка Google reCAPTCHA
- ✅ Гибкая конфигурация
- ✅ Улучшенная безопасность
- ✅ Расширенное логирование

### v1.0 (Предыдущая)
- ✅ Простая капча с галочкой
- ✅ Базовая защита
- ✅ Интеграция с miniapp

---

## 📞 Поддержка

Если возникли вопросы или проблемы:

1. 📋 Проверьте `test_captcha_system.php`
2. 📝 Изучите логи в папке `logs/`
3. 📖 Читайте документацию в `RECAPTCHA_SETUP.md`
4. 🔍 Используйте отладочную информацию

**Система готова к работе! 🎉** 