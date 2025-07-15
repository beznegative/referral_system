<?php
// Получаем текущий путь для определения активной страницы
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Реферальная система'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime('css/style.css'); ?>">
    
    <!-- Инлайн-скрипт для предотвращения моргания темы -->
    <script>
        (function() {
            'use strict';
            
            const THEME_KEY = 'referral-system-theme';
            
            // Получение сохраненной темы или системной темы
            function getInitialTheme() {
                const savedTheme = localStorage.getItem(THEME_KEY);
                if (savedTheme) {
                    return savedTheme;
                }
                
                // Проверка системной темы
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    return 'dark';
                }
                
                return 'light';
            }
            
            // Применение темы немедленно
            const theme = getInitialTheme();
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
</head>
<body>
    <header>
        <nav class="main-nav">
            <div class="nav-container">
                <div class="logo">
                    <a href="index.php">Реферальная система</a>
                </div>
                <ul class="nav-links">
                    <li>
                        <a href="index.php" <?php echo $currentPage == 'index.php' ? 'class="active"' : ''; ?>>
                            Главная
                        </a>
                    </li>
                    <li>
                        <a href="user_form.php" <?php echo $currentPage == 'user_form.php' ? 'class="active"' : ''; ?>>
                            Добавить пользователя
                        </a>
                    </li>
                    <li>
                        <a href="settings.php" <?php echo $currentPage == 'settings.php' ? 'class="active"' : ''; ?>>
                            Настройки
                        </a>
                    </li>
                </ul>
                <div class="theme-switcher">
                    <button id="theme-toggle" class="theme-toggle" aria-label="Переключить тему">
                        <svg class="icon-sun" viewBox="0 0 24 24" width="20" height="20">
                            <path d="M12 17.5A5.5 5.5 0 1 0 12 6.5a5.5 5.5 0 0 0 0 11zM12 1.5a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0V2a.5.5 0 0 1 .5-.5zM21.5 12a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM4.5 12a.5.5 0 0 1-.5.5H2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM18.364 18.364a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM7.757 7.757a.5.5 0 0 1-.707 0L5.636 6.343a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM18.364 5.636a.5.5 0 0 1 0 .707l-1.414 1.414a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zM7.757 16.243a.5.5 0 0 1 0 .707L6.343 18.364a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zM12 22.5a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 1 0v2a.5.5 0 0 1-.5.5z"/>
                        </svg>
                        <svg class="icon-moon" viewBox="0 0 24 24" width="20" height="20">
                            <path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1-8.313-12.454z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </nav>
    </header>
    <main class="container"><?php
if (isset($_GET['error'])) {
    echo '<div class="alert alert-error">' . htmlspecialchars($_GET['error']) . '</div>';
}
if (isset($_GET['success'])) {
    echo '<div class="alert alert-success">Операция выполнена успешно!</div>';
}
?> 