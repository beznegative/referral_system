# 🔐 Настройка Google reCAPTCHA

## Получение ключей reCAPTCHA

### Шаг 1: Регистрация в Google reCAPTCHA

1. Перейдите на [https://www.google.com/recaptcha/admin/create](https://www.google.com/recaptcha/admin/create)
2. Войдите в свой аккаунт Google
3. Нажмите "Создать" или "+"

### Шаг 2: Настройка сайта

1. **Метка (Label):** Введите название вашего сайта
2. **Тип reCAPTCHA:** Выберите **reCAPTCHA v2** → **Галочка "Я не робот"**
3. **Домены:** Добавьте ваши домены:
   ```
   example.com
   www.example.com
   localhost (для тестирования)
   127.0.0.1 (для тестирования)
   ```
4. **Владельцы:** Добавьте email администраторов
5. **Принять условия использования**
6. Нажмите **Отправить**

### Шаг 3: Получение ключей

После создания вы получите:
- **Site Key (Ключ сайта)** - используется на фронтенде
- **Secret Key (Секретный ключ)** - используется на бэкенде

## Настройка в системе

### 1. Обновите конфигурацию

Откройте файл `captcha_config.php` и обновите следующие строки:

```php
// Замените на ваши реальные ключи
define('RECAPTCHA_SITE_KEY', 'ваш_site_key_здесь');
define('RECAPTCHA_SECRET_KEY', 'ваш_secret_key_здесь');

// Включите reCAPTCHA
define('CAPTCHA_TYPE', 'recaptcha');
```

### 2. Проверьте доступность cURL

reCAPTCHA требует cURL для проверки на сервере. Убедитесь, что он установлен:

```bash
php -m | grep curl
```

Если cURL не установлен:
```bash
# Ubuntu/Debian
sudo apt-get install php-curl

# CentOS/RHEL
sudo yum install php-curl
```

### 3. Тестирование

1. Откройте `test_captcha_system.php`
2. Проверьте раздел "Конфигурация капчи"
3. Убедитесь, что "reCAPTCHA включена: Да"
4. Протестируйте капчу через `captcha.php?target=miniapp`

## Тестовые ключи Google

Для разработки можно использовать тестовые ключи Google:

```php
// Тестовые ключи (всегда проходят проверку)
define('RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI');
define('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe');
```

⚠️ **Внимание:** Тестовые ключи работают только в локальной среде разработки!

## Переключение между типами капчи

В файле `captcha_config.php` измените:

```php
// Для простой капчи
define('CAPTCHA_TYPE', 'simple');

// Для Google reCAPTCHA
define('CAPTCHA_TYPE', 'recaptcha');
```

## Настройка доменов

### Для продакшена:
```
yourdomain.com
www.yourdomain.com
```

### Для разработки:
```
localhost
127.0.0.1
your-dev-domain.com
```

### Для Telegram Mini Apps:
Если используете Telegram Web Apps, добавьте:
```
web.telegram.org
```

## Безопасность

### Защита ключей

1. **Никогда не публикуйте Secret Key в публичном коде**
2. **Храните ключи в отдельном конфигурационном файле:**

```php
// config.local.php (добавьте в .gitignore)
<?php
define('RECAPTCHA_SITE_KEY', 'ваш_реальный_site_key');
define('RECAPTCHA_SECRET_KEY', 'ваш_реальный_secret_key');
?>
```

3. **Используйте переменные окружения:**
```php
define('RECAPTCHA_SITE_KEY', $_ENV['RECAPTCHA_SITE_KEY'] ?? 'fallback_key');
define('RECAPTCHA_SECRET_KEY', $_ENV['RECAPTCHA_SECRET_KEY'] ?? 'fallback_key');
```

### Проверка домена

reCAPTCHA автоматически проверяет домен. Убедитесь, что ваш домен добавлен в настройки reCAPTCHA.

## Устранение неполадок

### Ошибки reCAPTCHA

| Код ошибки | Описание | Решение |
|------------|----------|---------|
| `missing-input-secret` | Отсутствует Secret Key | Проверьте конфигурацию |
| `invalid-input-secret` | Неверный Secret Key | Проверьте ключ в Google Console |
| `missing-input-response` | Пользователь не прошел reCAPTCHA | Убедитесь, что капча отображается |
| `invalid-input-response` | Неверный токен | Токен мог истечь, обновите страницу |
| `bad-request` | Неверный формат запроса | Проверьте код отправки |
| `timeout-or-duplicate` | Тайм-аут или повторное использование | Обновите страницу |

### Не загружается reCAPTCHA

1. **Проверьте доступность Google:**
   ```bash
   ping www.google.com
   ```

2. **Проверьте блокировщики рекламы** - они могут блокировать reCAPTCHA

3. **Проверьте HTTPS** - reCAPTCHA лучше работает с HTTPS

4. **Проверьте CSP заголовки** - убедитесь, что они не блокируют Google

### Логи для отладки

Проверьте логи в папке `logs/`:
- `captcha_verifications.log` - успешные проверки
- `captcha_errors.log` - ошибки проверки

## Мониторинг

### В Google reCAPTCHA Admin Console

1. Перейдите в [https://www.google.com/recaptcha/admin](https://www.google.com/recaptcha/admin)
2. Выберите ваш сайт
3. Просматривайте статистику:
   - Количество запросов
   - Процент успешных проверок
   - Подозрительная активность

### В системе

Используйте `test_captcha_system.php` для мониторинга:
- Конфигурация капчи
- Статус сессии
- Логи проверок

---

## Быстрый чеклист

- [ ] Получил ключи от Google reCAPTCHA
- [ ] Обновил `captcha_config.php`
- [ ] Установил `CAPTCHA_TYPE = 'recaptcha'`
- [ ] Добавил домены в Google Console
- [ ] Проверил работу cURL
- [ ] Протестировал через `test_captcha_system.php`
- [ ] Добавил реальные ключи в .gitignore
- [ ] Протестировал на продакшене

Готово! 🎉 