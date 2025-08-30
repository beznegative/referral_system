Write-Host "=== Загрузка изменений на GitHub ===" -ForegroundColor Green

Write-Host "1. Проверяем статус..." -ForegroundColor Yellow
git status

Write-Host "`n2. Добавляем все файлы..." -ForegroundColor Yellow
git add .

Write-Host "`n3. Создаем коммит..." -ForegroundColor Yellow
git commit -m "Реализована полная система отчетов с поддержкой всех типов:

НОВАЯ ФУНКЦИОНАЛЬНОСТЬ:
- Добавлено поле 'год выплат' в user_form.php
- Система месячных выплат с сохранением истории
- Динамическая загрузка данных через AJAX (get_monthly_data.php)
- Исправлен расчет месячных выплат за рефералов (updateMonthlyAffiliatePayments)

ОТЧЕТЫ:
- Полностью переработан monthly_report_pdf.php
- Поддержка 3 типов отчетов: месячный, за все время, топ партнёры
- Современный HTML дизайн с CSS Grid и градиентами
- Экспорт в PDF и Excel форматах
- Разбивка по месяцам для отчетов за все время

ИНТЕРФЕЙС:
- Обновлен раздел 'Отчеты и аналитика' в settings.php
- Удалено архивирование, добавлены гибкие настройки отчетов
- Быстрые отчеты с одним кликом
- Адаптивный дизайн для всех устройств

БАЗА ДАННЫХ:
- Корректная работа с таблицей monthly_payments
- Исправлены SQL запросы для всех типов отчетов
- Оптимизированные вычисления сумм"

Write-Host "`n4. Отправляем на GitHub..." -ForegroundColor Yellow
git push origin main

Write-Host "`nГотово!" -ForegroundColor Green
Read-Host "Нажмите Enter для выхода"