// Управление темой
(function() {
    'use strict';
    
    // Получение элементов
    const themeToggle = document.getElementById('theme-toggle');
    const htmlElement = document.documentElement;
    
    // Ключ для localStorage
    const THEME_KEY = 'referral-system-theme';
    
    // Установка темы
    function setTheme(theme) {
        if (theme === 'dark') {
            htmlElement.setAttribute('data-theme', 'dark');
        } else {
            htmlElement.removeAttribute('data-theme');
        }
        
        // Сохранение в localStorage
        localStorage.setItem(THEME_KEY, theme);
    }
    
    // Переключение темы
    function toggleTheme() {
        const currentTheme = htmlElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
    }
    
    // Обработчики событий
    function attachEventListeners() {
        if (themeToggle) {
            themeToggle.addEventListener('click', toggleTheme);
            
            // Добавляем обработчик для клавиатуры (доступность)
            themeToggle.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleTheme();
                }
            });
        }
        
        // Слушаем изменения системной темы
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addEventListener('change', function(e) {
                // Обновляем тему только если пользователь не установил свою вручную
                const savedTheme = localStorage.getItem(THEME_KEY);
                if (!savedTheme) {
                    setTheme(e.matches ? 'dark' : 'light');
                }
            });
        }
    }
    
    // Инициализация после загрузки DOM
    document.addEventListener('DOMContentLoaded', function() {
        attachEventListeners();
    });
    
    // Инициализация сразу (на случай если скрипт загружается после DOM)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            attachEventListeners();
        });
    } else {
        attachEventListeners();
    }
})(); 