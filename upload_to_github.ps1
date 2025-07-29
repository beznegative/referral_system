Write-Host "=== Загрузка изменений на GitHub ===" -ForegroundColor Green

Write-Host "1. Проверяем статус..." -ForegroundColor Yellow
git status

Write-Host "`n2. Добавляем все файлы..." -ForegroundColor Yellow
git add .

Write-Host "`n3. Создаем коммит..." -ForegroundColor Yellow
git commit -m "Обновлен поиск пригласителей: заменен select на автокомплит, смягчена валидация, отключена капча для тестирования"

Write-Host "`n4. Отправляем на GitHub..." -ForegroundColor Yellow
git push origin main

Write-Host "`nГотово!" -ForegroundColor Green
Read-Host "Нажмите Enter для выхода" 