э<?php
require_once 'includes/database.php';
require_once 'test_encryption.php';
require_once 'referral_calculator.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

try {
    // Получаем данные пользователя
    $stmt = $pdo->prepare("
        SELECT u.*, 
               a.full_name as affiliate_name,
               a.telegram_username as affiliate_telegram,
               (SELECT COUNT(*) FROM users WHERE affiliate_id = u.id) as referral_count
        FROM users u 
        LEFT JOIN users a ON u.affiliate_id = a.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Пользователь не найден');
    }

    // Получаем букмекерские конторы пользователя
    $stmt = $pdo->prepare("
        SELECT b.* 
        FROM bookmakers b
        JOIN user_bookmakers ub ON b.id = ub.bookmaker_id
        WHERE ub.user_id = ?
        ORDER BY b.name
    ");
    $stmt->execute([$_GET['id']]);
    $bookmakers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получаем настройки реферальной системы
    $referralSettings = getReferralSettings($pdo);
    
    // Рассчитываем выплаты по уровням для партнеров
    $referralEarnings = null;
    if ($user['is_affiliate'] == 1) {
        $referralEarnings = calculateReferralEarnings($pdo, $user['id']);
    }
    
    // Получаем детальную информацию о рефералах для всех пользователей
    $referralDetails = getReferralDetails($pdo, $user['id']);

} catch (Exception $e) {
    die('Ошибка: ' . htmlspecialchars($e->getMessage()));
}

require_once 'includes/header.php';
?>

    <div class="container">
        <div class="user-info">
            <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
        
        <?php if ($user['affiliate_name']): ?>
            <div class="affiliate-info">
                <p><strong>Пригласил: </strong> <?php echo htmlspecialchars($user['affiliate_name']); ?></p>
                <p><strong>Telegram пригласителя: </strong> <?php echo htmlspecialchars($user['affiliate_telegram']); ?></p>
            </div>
        <?php endif; ?>

        <div class="user-details">
            <p><strong>Telegram:</strong> <?php echo htmlspecialchars($user['telegram_username']); ?></p>
            <p><strong>ID Telegram:</strong> <?php echo htmlspecialchars($user['telegram_id']); ?></p>
            <p><strong>Телефон:</strong> <?php echo htmlspecialchars($user['phone_number']); ?></p>
            <p><strong>Дата рождения:</strong> <?php echo date('d.m.Y', strtotime($user['birth_date'])); ?></p>
            <p><strong>Банковская карта:</strong> <?php echo htmlspecialchars(decryptData($user['bank_card'])); ?></p>
            
            <!-- Новые поля для реферальной системы -->
            <div class="referral-info mt-3">
                <h4>Реферальная информация</h4>
                <p><strong>Выплаченно в рублях:</strong> <?php echo number_format($user['paid_amount'], 2, '.', ' '); ?> ₽</p>
                <p><strong>Количество рефералов:</strong> <?php echo $user['referral_count']; ?></p>
                <p><strong>Выплаченно за рефералов:</strong> <?php echo number_format($user['paid_for_referrals'], 2, '.', ' '); ?> ₽</p>
            </div>
            
            <?php if ($user['is_affiliate'] == 1 && $referralEarnings): ?>
            <!-- Таблица выплат по уровням реферальной системы -->
            <div class="referral-levels-info mt-3">
                <h4>Выплаты по уровням реферальной системы</h4>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Уровень</th>
                                <th>Процент</th>
                                <th>Сумма к выплате</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1 уровень</td>
                                <td><?php echo isset($referralSettings['level_1_percent']) ? $referralSettings['level_1_percent'] : 50.00; ?>%</td>
                                <td><?php echo number_format($referralEarnings['level_1'], 2, '.', ' '); ?> ₽</td>
                            </tr>
                            <tr>
                                <td>2 уровень</td>
                                <td><?php echo isset($referralSettings['level_2_percent']) ? $referralSettings['level_2_percent'] : 25.00; ?>%</td>
                                <td><?php echo number_format($referralEarnings['level_2'], 2, '.', ' '); ?> ₽</td>
                            </tr>
                            <tr>
                                <td>3 уровень</td>
                                <td><?php echo isset($referralSettings['level_3_percent']) ? $referralSettings['level_3_percent'] : 10.00; ?>%</td>
                                <td><?php echo number_format($referralEarnings['level_3'], 2, '.', ' '); ?> ₽</td>
                            </tr>
                            <tr class="table-info">
                                <td><strong>Итого</strong></td>
                                <td>-</td>
                                <td><strong><?php echo number_format($referralEarnings['total'], 2, '.', ' '); ?> ₽</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($referralDetails)): ?>
            <!-- Детальная информация о рефералах -->
            <div class="referral-details mt-3">
                <h4>Детальная информация о рефералах</h4>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Имя</th>
                                <th>Уровень</th>
                                <th>Выплачено</th>
                                <?php if ($user['is_affiliate'] == 1): ?>
                                <th>Процент</th>
                                <th>К получению</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($referralDetails as $detail): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($detail['name']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $detail['level'] == 1 ? 'primary' : ($detail['level'] == 2 ? 'secondary' : 'info'); ?>">
                                        <?php echo $detail['level']; ?> уровень
                                    </span>
                                </td>
                                <td><?php echo number_format($detail['paid_amount'], 2, '.', ' '); ?> ₽</td>
                                <?php if ($user['is_affiliate'] == 1): ?>
                                <td><?php echo $detail['percent']; ?>%</td>
                                <td><?php echo number_format($detail['earning'], 2, '.', ' '); ?> ₽</td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($bookmakers)): ?>
            <div class="bookmakers-info mt-3">
                <h4>Букмекерские конторы</h4>
                <div class="bookmakers-list">
                    <?php foreach ($bookmakers as $bookmaker): ?>
                        <span class="badge"><?php echo htmlspecialchars($bookmaker['name']); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="user_form.php?id=<?php echo $user['id']; ?>" class="btn btn-primary">Редактировать</a>
            <?php 
            // Формируем ссылку для Telegram (убираем @ если есть)
            $telegram_link = 'https://t.me/' . ltrim($user['telegram_username'], '@');
            ?>
            <a href="<?php echo $telegram_link; ?>" target="_blank" class="btn btn-success">Написать в Telegram</a>
            <a href="index.php" class="btn btn-secondary">Назад</a>
        </div>
    </div>


</div>

<?php require_once 'includes/footer.php'; ?> 