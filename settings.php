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
        

        
        <!-- Отчеты и аналитика -->
        <div class="settings-card">
            <h3>📊 Отчеты и аналитика</h3>
            <p>Генерация подробных отчетов о выплатах, партнёрах и рефералах.</p>
            
            <!-- Месячный отчет -->
            <div class="form-section">
                <h4>📅 Месячный отчет</h4>
                <p class="form-description">Подробный отчет с выплатами всех пользователей и партнёров за выбранный месяц.</p>
                <form method="GET" action="monthly_report_pdf.php" class="report-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="monthly_report_month">Выберите месяц:</label>
                            <input type="month" id="monthly_report_month" name="month" class="form-control" 
                                   value="<?= date('Y-m') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="monthly_report_format">Формат отчета:</label>
                            <select id="monthly_report_format" name="format" class="form-control">
                                <option value="pdf">PDF документ</option>
                                <option value="excel">Excel таблица</option>
                            </select>
                        </div>
                    </div>
                    <div class="report-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="include_users" value="1" checked>
                            Включить всех пользователей
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="include_affiliates" value="1" checked>
                            Включить партнёров
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="include_referrals" value="1" checked>
                            Включить выплаты за рефералов
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-report">
                        <span>📄</span> Скачать месячный отчет
                    </button>
                </form>
            </div>
            
            <!-- Отчет за все время -->
            <div class="form-section">
                <h4>🕐 Отчет за все время</h4>
                <p class="form-description">Полный отчет с общей статистикой, выплатами по всем периодам и структурой рефералов.</p>
                <form method="GET" action="monthly_report_pdf.php" class="report-form">
                    <input type="hidden" name="type" value="all_time">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="alltime_format">Формат отчета:</label>
                            <select id="alltime_format" name="format" class="form-control">
                                <option value="pdf">PDF документ</option>
                                <option value="excel">Excel таблица</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="sort_by">Сортировать по:</label>
                            <select id="sort_by" name="sort_by" class="form-control">
                                <option value="total_paid">Общей сумме выплат</option>
                                <option value="referrals_earnings">Выплатам за рефералов</option>
                                <option value="referrals_count">Количеству рефералов</option>
                                <option value="name">Имени</option>
                            </select>
                        </div>
                    </div>
                    <div class="report-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="include_monthly_breakdown" value="1" checked>
                            Разбивка по месяцам
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="include_referral_tree" value="1">
                            Дерево рефералов
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="include_statistics" value="1" checked>
                            Общая статистика
                        </label>
                    </div>
                    <button type="submit" class="btn btn-success btn-report">
                        <span>📈</span> Скачать полный отчет
                    </button>
                </form>
            </div>
            
            <!-- Быстрые отчеты -->
            <div class="form-section">
                <h4>⚡ Быстрые отчеты</h4>
                <div class="quick-reports">
                    <a href="monthly_report_pdf.php?month=<?= date('Y-m') ?>&format=pdf&quick=current" class="btn btn-outline-primary">
                        📅 Текущий месяц
                    </a>
                    <a href="monthly_report_pdf.php?month=<?= date('Y-m', strtotime('-1 month')) ?>&format=pdf&quick=previous" class="btn btn-outline-primary">
                        📅 Прошлый месяц
                    </a>
                    <a href="monthly_report_pdf.php?type=all_time&format=pdf&quick=summary" class="btn btn-outline-success">
                        📊 Краткая сводка
                    </a>
                    <a href="monthly_report_pdf.php?type=top_performers&format=pdf" class="btn btn-outline-warning">
                        🏆 Топ партнёры
                    </a>
                </div>
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

<style>
/* Стили для улучшенного интерфейса отчетов */
.form-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--background-color);
    border-radius: var(--radius);
    border: 1px solid var(--border-color);
}

.form-section h4 {
    color: var(--text-primary);
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.form-description {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.report-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.report-options {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    padding: 1rem;
    background: var(--card-background);
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-color);
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: var(--text-primary);
    cursor: pointer;
    margin: 0;
}

.checkbox-label input[type="checkbox"] {
    width: 1.2rem;
    height: 1.2rem;
    border-radius: var(--radius-sm);
    border: 2px solid var(--border-color);
    cursor: pointer;
}

.checkbox-label input[type="checkbox"]:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-report {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
}

.btn-report span {
    font-size: 1.1rem;
}

.quick-reports {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
}

.quick-reports .btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem;
    text-align: center;
    font-size: 0.9rem;
}

/* Адаптивность для отчетов */
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .report-options {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .quick-reports {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .form-section {
        padding: 1rem;
        margin-bottom: 1rem;
    }
}

@media (max-width: 480px) {
    .checkbox-label {
        font-size: 0.85rem;
    }
    
    .btn-report {
        padding: 0.625rem 1rem;
        font-size: 0.9rem;
    }
    
    .quick-reports .btn {
        padding: 0.625rem;
        font-size: 0.85rem;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 