@echo off
echo === Загрузка изменений на GitHub ===
echo.

echo 1. Проверяем статус...
git status
echo.

echo 2. Добавляем все файлы...
git add .
echo.

echo 3. Создаем коммит...
git commit -m "Исправлены проблемы с сессиями и перенаправлением на капчу: добавлена корректная инициализация сессии, исправлена функция redirectToCaptcha(), устранены ошибки headers already sent"
echo.

echo 4. Отправляем на GitHub...
git push origin main
echo.

echo Готово!
pause
