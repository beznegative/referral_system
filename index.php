<?php
// Подключение к базе данных
require_once 'includes/database.php';

// Заголовок страницы
$pageTitle = 'Список пользователей';

// Определяем активную вкладку
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'users';

// Подключаем header
require_once 'includes/header.php';
?>

<div class="container">
    <h1>Список пользователей</h1>
    
    <?php
    // Отображение сообщений об успехе или ошибке
    session_start();
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-error" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']);
    }
    ?>
    
    <!-- Вкладки -->
    <div class="tabs">
        <a href="?tab=users" class="tab <?= $activeTab === 'users' ? 'active' : '' ?>">Пользователи</a>
        <a href="?tab=affiliates" class="tab <?= $activeTab === 'affiliates' ? 'active' : '' ?>">Партнёры</a>
    </div>

    <!-- Добавляем форму поиска -->
    <div class="search-container">
        <form class="search-form" onsubmit="return false;">
            <input type="text" 
                   id="user-search" 
                   class="search-input" 
                   placeholder="Поиск по ФИО..." 
                   autocomplete="off">
        </form>
    </div>

    <!-- Сообщение при отсутствии результатов -->
    <div id="no-results" class="no-results" style="display: none;">
        Ничего не найдено
    </div>

    <?php
    try {
        // Добавляем условие WHERE для фильтрации по типу пользователя
        $sql = "SELECT u.*, 
                       (SELECT COUNT(*) FROM users WHERE affiliate_id = u.id) as referral_count
                FROM users u 
                WHERE u.is_affiliate = :is_affiliate 
                ORDER BY u.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['is_affiliate' => ($activeTab === 'affiliates' ? 1 : 0)]);
        $users = $stmt->fetchAll();

        if (count($users) > 0): ?>
            <ul class="users-list">
                <?php foreach ($users as $user): ?>
                    <li>
                        <a href="user.php?id=<?= htmlspecialchars($user['id']) ?>">
                            <div class="user-card-header">
                                <div class="user-avatar">
                                    <?php if ($user['is_affiliate']): ?>
                                        <!-- Иконка партнёра (звезда) -->
                                        <svg class="icon icon-affiliate" viewBox="0 0 24 24" width="24" height="24">
                                            <path d="M12 2l2.4 7.4h7.6l-6.2 4.5 2.4 7.4-6.2-4.5-6.2 4.5 2.4-7.4-6.2-4.5h7.6z"/>
                                        </svg>
                                    <?php else: ?>
                                        <!-- Иконка пользователя -->
                                        <svg class="icon icon-user" viewBox="0 0 24 24" width="24" height="24">
                                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="user-card-info">
                                    <h3 class="user-name"><?= htmlspecialchars($user['full_name']) ?></h3>
                                    <div class="user-stats">
                                        <span class="stat-item">Выплачено: <?= number_format($user['total_paid_amount'], 2, '.', ' ') ?> ₽</span>
                                        <?php if ($user['is_affiliate']): ?>
                                            <span class="stat-item">Рефералов: <?= $user['referral_count'] ?></span>
                                            <span class="stat-item">За рефералов: <?= number_format($user['total_paid_for_referrals'], 2, '.', ' ') ?> ₽</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>
                <?= $activeTab === 'affiliates' ? 'Партнёры не найдены' : 'Пользователи не найдены' ?>
            </p>
        <?php endif;
    } catch (PDOException $e) {
        echo '<div class="alert alert-error">Ошибка при получении списка пользователей</div>';
    }
    ?>
</div>

<!-- Подключаем JavaScript -->
<script src="js/search.js"></script>

<?php require_once 'includes/footer.php'; ?> 