# 🚀 Быстрый старт с системой капчи

## 1️⃣ Простая капча (готова к работе)

Система уже настроена и готова к использованию с простой капчей:

```bash
# Откройте в браузере:
http://yoursite.com/captcha.php?target=miniapp
```

## 2️⃣ Google reCAPTCHA (рекомендуется)

### Шаг 1: Получите ключи
1. Идите на https://www.google.com/recaptcha/admin/create
2. Выберите **reCAPTCHA v2** → **"Я не робот"**
3. Добавьте ваш домен
4. Скопируйте Site Key и Secret Key

### Шаг 2: Настройте систему
```php
// Откройте captcha_config.php и замените:
define('CAPTCHA_TYPE', 'recaptcha');
define('RECAPTCHA_SITE_KEY', 'ваш_site_key_здесь');
define('RECAPTCHA_SECRET_KEY', 'ваш_secret_key_здесь');
```

### Шаг 3: Проверьте
```bash
# Тест ваших ключей reCAPTCHA:
http://yoursite.com/test_recaptcha_keys.php

# Проверка установки системы:
http://yoursite.com/check_captcha_installation.php
```

## 3️⃣ Тестирование

```bash
# Полная диагностическая панель:
http://yoursite.com/test_captcha_system.php
```

## 🎯 URL для использования

| Цель | URL |
|------|-----|
| Обычное приложение | `captcha.php?target=miniapp` |
| Тестовое приложение | `captcha.php?target=test` |
| Тест ключей reCAPTCHA | `test_recaptcha_keys.php` |
| **Исправить ошибку "неверный ключ"** | `fix_recaptcha_error.php` ⚠️ |
| Диагностика | `test_captcha_system.php` |
| Проверка установки | `check_captcha_installation.php` |

## ⚡ Переключение типов

```php
// Простая капча
define('CAPTCHA_TYPE', 'simple');

// Google reCAPTCHA
define('CAPTCHA_TYPE', 'recaptcha');
```

## 🔧 Если что-то не работает

1. **Ошибка "неверный ключ":** `fix_recaptcha_error.php` ⚠️
2. **Проверьте установку:** `check_captcha_installation.php`
3. **Посмотрите диагностику:** `test_captcha_system.php`
4. **Изучите логи:** папка `logs/`
5. **Читайте документацию:** `RECAPTCHA_SETUP.md`

---

**✅ Система готова к работе!** Пользователи будут проходить капчу перед доступом к вашим приложениям. 