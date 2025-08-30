<?php
// Подключение к базе данных
require_once 'includes/database.php';
require_once 'referral_calculator.php';

// Заголовок страницы
$pageTitle = 'Настройки системы';

// Обработка формы настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_payments':
                    // Обновление сумм выплат
                    if (isset($_POST['user_id']) && isset($_POST['total_paid_amount']) && isset($_POST['total_paid_for_referrals'])) {
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET total_paid_amount = ?, total_paid_for_referrals = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$_POST['total_paid_amount'], $_POST['total_paid_for_referrals'], $_POST['user_id']]);
                        $success = "Выплаты обновлены успешно!";
                    }
                    break;
                    
                case 'update_referral_counts':
                    // Обновление счетчиков рефералов
                    require_once 'update_referral_counts.php';
                    updateReferralCounts($pdo);
                    $success = "Счетчики рефералов обновлены!";
                    break;
                    
                case 'reset_payments':
                    // Сброс всех выплат
                    $stmt = $pdo->prepare("UPDATE users SET total_paid_amount = 0.00, total_paid_for_referrals = 0.00, monthly_paid_amount = 0.00, monthly_paid_for_referrals = 0.00");
                    $stmt->execute();
                    $success = "Все выплаты сброшены!";
                    break;
                    
                case 'update_referral_settings':
                    // Обновление настроек реферальной системы
                    if (isset($_POST['level_1_percent']) && isset($_POST['level_2_percent']) && isset($_POST['level_3_percent'])) {
                        $level1 = floatval($_POST['level_1_percent']);
                        $level2 = floatval($_POST['level_2_percent']);
                        $level3 = floatval($_POST['level_3_percent']);
                        
                        // Проверка корректности значений
                        if ($level1 >= 0 && $level1 <= 100 && $level2 >= 0 && $level2 <= 100 && $level3 >= 0 && $level3 <= 100) {
                            updateReferralSettings($pdo, $level1, $level2, $level3);
                            $success = "Настройки реферальной системы обновлены!";
                        } else {
                            $error = "Проценты должны быть от 0 до 100!";
                        }
                    }
                    break;
                    
                case 'recalculate_referral_payments':
                    // Пересчет всех выплат в системе
                    recalculateAllReferralPayments($pdo);
                    $success = "Все выплаты пересчитаны!";
                    break;
                    

                    
                case 'archive_month':
                    // Архивировать месячные выплаты
                    if (isset($_POST['archive_month'])) {
                        $month = $_POST['archive_month'];
                        
                        // Получаем всех пользователей с месячными данными
                        $stmt = $pdo->prepare("
                            SELECT id, monthly_paid_amount, monthly_paid_for_referrals 
                            FROM users 
                            WHERE payment_month = ? AND (monthly_paid_amount > 0 OR monthly_paid_for_referrals > 0)
                        ");
                        $stmt->execute([$month]);
                        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $archived_count = 0;
                        foreach ($users as $user) {
                            // Сохраняем в архив
                            $stmt = $pdo->prepare("
                                INSERT INTO monthly_payments (user_id, payment_month, paid_amount, paid_for_referrals) 
                                VALUES (?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE 
                                paid_amount = VALUES(paid_amount),
                                paid_for_referrals = VALUES(paid_for_referrals)
                            ");
                            $stmt->execute([$user['id'], $month, $user['monthly_paid_amount'], $user['monthly_paid_for_referrals']]);
                            
                            // Обнуляем месячные данные
                            $stmt = $pdo->prepare("
                                UPDATE users 
                                SET monthly_paid_amount = 0.00, monthly_paid_for_referrals = 0.00, payment_month = ?
                                WHERE id = ?
                            ");
                            $stmt->execute([date('Y-m'), $user['id']]);
                            
                            $archived_count++;
                        }
                        
                        $success = "Архивированы данные за {$month} для {$archived_count} пользователей. Месячные счетчики обнулены.";
                    }
                    break;
            }
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        // Проверяем, есть ли активная транзакция перед откатом
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Ошибка: " . $e->getMessage();
    }
}

// Получение статистики
try {
    // Общая статистика
    $stats = [];
    
    // Всего пользователей
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_affiliate = 0");
    $stats['total_users'] = $stmt->fetchColumn();
    
    // Всего партнеров
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_affiliate = 1");
    $stats['total_affiliates'] = $stmt->fetchColumn();
    
    // Общая сумма выплат
    $stmt = $pdo->query("SELECT SUM(total_paid_amount) FROM users");
    $stats['total_paid'] = $stmt->fetchColumn() ?: 0;
    
    // Общая сумма выплат за рефералов
    $stmt = $pdo->query("SELECT SUM(total_paid_for_referrals) FROM users");
    $stats['total_paid_referrals'] = $stmt->fetchColumn() ?: 0;
    
    // Топ партнеров по количеству рефералов
    $stmt = $pdo->query("
        SELECT u.full_name, u.total_paid_amount, u.total_paid_for_referrals,
               (SELECT COUNT(*) FROM users WHERE affiliate_id = u.id) as referral_count
        FROM users u 
        WHERE u.is_affiliate = 1 
        ORDER BY referral_count DESC, u.total_paid_for_referrals DESC
        LIMIT 10
    ");
    $top_affiliates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Последние регистрации
    $stmt = $pdo->query("
        SELECT u.full_name, u.telegram_username, u.created_at, u.is_affiliate,
               a.full_name as affiliate_name
        FROM users u
        LEFT JOIN users a ON u.affiliate_id = a.id
        ORDER BY u.created_at DESC
        LIMIT 10
    ");
    $recent_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Получение настроек реферальной системы
    $referralSettings = getReferralSettings($pdo);
    
} catch (Exception $e) {
    $error = "Ошибка получения статистики: " . $e->getMessage();
}

// Подключаем header
require_once 'includes/header.php';
?>

<div class="container">
    <h1>Настройки системы</h1>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="settings-layout">
        <!-- Статистика -->
        <div class="settings-card">
            <h3>Общая статистика</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number"><?= number_format($stats['total_users']) ?></span>
                    <span class="stat-label">Пользователей</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= number_format($stats['total_affiliates']) ?></span>
                    <span class="stat-label">Партнеров</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= number_format($stats['total_paid'], 2, '.', ' ') ?> ₽</span>
                    <span class="stat-label">Всего выплачено</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= number_format($stats['total_paid_referrals'], 2, '.', ' ') ?> ₽</span>
                    <span class="stat-label">Выплачено за рефералов</span>
                </div>
            </div>
        </div>
        
        <!-- Настройки реферальной системы -->
        <div class="settings-card">
            <h3>Настройки реферальной системы</h3>
            <form method="POST" class="referral-settings-form">
                <input type="hidden" name="action" value="update_referral_settings">
                <div class="form-group">
                    <label for="level_1_percent">Процент для 1 уровня (%):</label>
                    <input type="number" step="0.01" min="0" max="100" name="level_1_percent" 
                           value="<?= isset($referralSettings['level_1_percent']) ? $referralSettings['level_1_percent'] : 50.00 ?>" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="level_2_percent">Процент для 2 уровня (%):</label>
                    <input type="number" step="0.01" min="0" max="100" name="level_2_percent" 
                           value="<?= isset($referralSettings['level_2_percent']) ? $referralSettings['level_2_percent'] : 25.00 ?>" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="level_3_percent">Процент для 3 уровня (%):</label>
                    <input type="number" step="0.01" min="0" max="100" name="level_3_percent" 
                           value="<?= isset($referralSettings['level_3_percent']) ? $referralSettings['level_3_percent'] : 10.00 ?>" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Сохранить настройки</button>
            </form>
        </div>
        
        <!-- Системные действия -->
        <div class="settings-card">
            <h3>Системные действия</h3>
            <div class="action-buttons">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="update_referral_counts">
                    <button type="submit" class="btn btn-primary">
                        Обновить счетчики рефералов
                    </button>
                </form>
                
                <form method="POST" style="display: inline;" onsubmit="return confirm('Пересчитать все выплаты партнерам? Это может занять некоторое время.')">
                    <input type="hidden" name="action" value="recalculate_referral_payments">
                    <button type="submit" class="btn btn-warning">
                        Пересчитать выплаты партнерам
                    </button>
                </form>
                
                <form method="POST" style="display: inline;" onsubmit="return confirm('Вы уверены? Это действие нельзя отменить!')">
                    <input type="hidden" name="action" value="reset_payments">
                    <button type="submit" class="btn btn-danger">
                        Сбросить все выплаты
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Топ партнеров -->
        <div class="settings-card">
            <h3>Топ партнеров</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Имя</th>
                            <th>Рефералов</th>
                            <th class="mobile-hide">Выплачено</th>
                            <th>За рефералов</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_affiliates as $affiliate): ?>
                        <tr>
                            <td><?= htmlspecialchars($affiliate['full_name']) ?></td>
                            <td><?= $affiliate['referral_count'] ?></td>
                            <td class="mobile-hide"><?= number_format($affiliate['total_paid_amount'], 2, '.', ' ') ?> ₽</td>
                            <td><?= number_format($affiliate['total_paid_for_referrals'], 2, '.', ' ') ?> ₽</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Последние регистрации -->
        <div class="settings-card">
            <h3>Последние регистрации</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Имя</th>
                            <th class="mobile-hide">Telegram</th>
                            <th>Тип</th>
                            <th class="mobile-hide">Партнер</th>
                            <th>Дата</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_registrations as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                            <td class="mobile-hide"><?= htmlspecialchars($user['telegram_username']) ?></td>
                            <td>
                                <?php if ($user['is_affiliate']): ?>
                                    <span class="badge badge-primary">Партнер</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Пользователь</span>
                                <?php endif; ?>
                            </td>
                            <td class="mobile-hide"><?= $user['affiliate_name'] ? htmlspecialchars($user['affiliate_name']) : '-' ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        

        
        <!-- Месячные отчеты -->
        <div class="settings-card">
            <h3>Месячные отчеты</h3>
            <p>Управление месячными выплатами и генерация отчетов.</p>
            
            <!-- Архивирование месяца -->
            <div class="form-section">
                <h4>Архивирование месяца</h4>
                <form method="POST" style="display: flex; gap: 10px; align-items: end; flex-wrap: wrap;">
                    <input type="hidden" name="action" value="archive_month">
                    <div class="form-group" style="margin: 0;">
                        <label for="archive_month">Месяц для архивирования:</label>
                        <input type="month" id="archive_month" name="archive_month" class="form-control" 
                               value="<?= date('Y-m', strtotime('-1 month')) ?>" required style="width: auto;">
                    </div>
                    <button type="submit" class="btn btn-warning" 
                            onclick="return confirm('Архивировать данные за выбранный месяц? Месячные счетчики будут обнулены.')">
                        Архивировать месяц
                    </button>
                </form>
                <small class="form-text text-muted">Сохраняет месячные выплаты в архив и обнуляет счетчики для нового месяца</small>
            </div>
            
            <!-- Экспорт месячного отчета -->
            <div class="form-section mt-3">
                <h4>Экспорт месячного отчета в PDF</h4>
                <form method="GET" action="monthly_report_pdf.php" style="display: flex; gap: 10px; align-items: end; flex-wrap: wrap;">
                    <div class="form-group" style="margin: 0;">
                        <label for="report_month">Месяц отчета:</label>
                        <input type="month" id="report_month" name="month" class="form-control" 
                               value="<?= date('Y-m') ?>" required style="width: auto;">
                    </div>
                    <button type="submit" class="btn btn-success">
                        Скачать PDF отчет
                    </button>
                </form>
                <small class="form-text text-muted">Генерирует PDF отчет с выплатами за выбранный месяц</small>
            </div>
        </div>
        
        <!-- Экспорт данных -->
        <div class="settings-card">
            <h3>Экспорт данных</h3>
            <p>Экспорт данных в различных форматах для анализа и отчетности.</p>
            <div class="action-buttons">
                <a href="export.php?type=users" class="btn btn-outline-primary">
                    Экспорт пользователей
                </a>
                <a href="export.php?type=affiliates" class="btn btn-outline-primary">
                    Экспорт партнеров
                </a>
                <a href="export.php?type=payments" class="btn btn-outline-primary">
                    Экспорт выплат
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 