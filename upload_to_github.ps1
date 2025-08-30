Write-Host "=== Загрузка изменений на GitHub ===" -ForegroundColor Green

Write-Host "1. Проверяем статус..." -ForegroundColor Yellow
git status

Write-Host "`n2. Добавляем все файлы..." -ForegroundColor Yellow
git add .

Write-Host "`n3. Создаем коммит..." -ForegroundColor Yellow
git commit -m "Крупное обновление: удаление полей, исправление капчи и API:

- Удалены поля phone_number, birth_date, bank_card из всех файлов
- Восстановлен user_form.php с полной функциональностью редактирования
- Исправлен функционал theme-toggle для мобильных устройств  
- Восстановлен verify_captcha.php для корректной работы капчи
- Исправлены все API файлы (register_api.php, check_user_api.php)
- Убрана функциональность Telegram бота из settings.php
- Исправлены проблемы с регистрацией и JSON ответами
- Улучшена совместимость капчи между разными страницами"

Write-Host "`n4. Отправляем на GitHub..." -ForegroundColor Yellow
git push origin main

Write-Host "`nГотово!" -ForegroundColor Green
Read-Host "Нажмите Enter для выхода" 