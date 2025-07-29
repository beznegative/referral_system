<?php
// Подключение к базе данных
require_once 'includes/database.php';

// Ключ шифрования (должен быть таким же, как в save_user.php)
define('ENCRYPTION_KEY', 'your-secret-key-123');

// Функция для расшифровки данных
function decryptData($encryptedData) {
    if (empty($encryptedData)) {
        return '';
    }
    
    $method = "AES-256-CBC";
    $key = substr(hash('sha256', ENCRYPTION_KEY, true), 0, 32);
    $data = base64_decode($encryptedData);
    
    if ($data === false) {
        return '';
    }
    
    if (strlen($data) < 16) {
        return '';
    }
    
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    
    $decrypted = openssl_decrypt($encrypted, $method, $key, OPENSSL_RAW_DATA, $iv);
    return $decrypted !== false ? $decrypted : '';
}

// Инициализация переменных
$user = [
    'id' => '',
    'full_name' => '',
    'bank_card' => '',
    'telegram_username' => '',
    'telegram_id' => '',
    'phone_number' => '',
    'birth_date' => '',
    'is_affiliate' => 0
];

$pageTitle = 'Добавление пользователя';
$isEdit = false;
$selectedBookmakers = [];

// Если передан ID, загружаем данные пользователя
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $userId = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $userData = $stmt->fetch();
        
        if ($userData) {
            $user = $userData;
            $isEdit = true;
            $pageTitle = 'Редактирование пользователя';

            // Загружаем выбранные букмекерские конторы
            $stmt = $pdo->prepare('
                SELECT bookmaker_id 
                FROM user_bookmakers 
                WHERE user_id = ?
            ');
            $stmt->execute([$userId]);
            $selectedBookmakers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            die('Ошибка: Пользователь не найден');
        }
    } catch (PDOException $e) {
        die('Ошибка при получении данных пользователя: ' . htmlspecialchars($e->getMessage()));
    }
}

// Загружаем список букмекерских контор
try {
    $stmt = $pdo->query('SELECT * FROM bookmakers ORDER BY name');
    $bookmakers = $stmt->fetchAll();
} catch (PDOException $e) {
    die('Ошибка при получении списка букмекерских контор: ' . htmlspecialchars($e->getMessage()));
}

// Получаем список всех пользователей для выбора пригласителя (исключая текущего пользователя)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT id, full_name, telegram_username FROM users WHERE id != ? ORDER BY telegram_username");
    $stmt->execute([$_GET['id']]);
} else {
    $stmt = $pdo->prepare("SELECT id, full_name, telegram_username FROM users ORDER BY telegram_username");
    $stmt->execute();
}
$inviters = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Подключаем header
require_once 'includes/header.php';
?>

<div class="container">
    <div class="form-container">
        <h1><?= isset($_GET['id']) ? 'Редактирование пользователя' : 'Добавление пользователя' ?></h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="save_user.php" method="POST">
            <?php if (isset($_GET['id'])): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($_GET['id']) ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="full_name">
                    ФИО
                    <span class="required">*</span>
                </label>
                <input type="text" 
                       id="full_name" 
                       name="full_name" 
                       required 
                       value="<?= isset($user) ? htmlspecialchars($user['full_name']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="bank_card">
                    Номер банковской карты
                </label>
                <input type="text" 
                       id="bank_card" 
                       name="bank_card" 
                       maxlength="19"
                       placeholder="1234 5678 9012 3456"
                       value="<?= isset($user['bank_card']) && $user['bank_card'] ? htmlspecialchars(decryptData($user['bank_card'])) : '' ?>">
            </div>

            <div class="form-group">
                <label for="telegram_username">
                    Имя пользователя Telegram
                    <span class="required">*</span>
                </label>
                <input type="text" 
                       id="telegram_username" 
                       name="telegram_username" 
                       required
                       placeholder="@name"
                       value="<?= isset($user) ? htmlspecialchars($user['telegram_username']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="telegram_id">
                    ID Telegram
                </label>
                <input type="number" 
                       id="telegram_id" 
                       name="telegram_id" 
                       value="<?= isset($user) ? htmlspecialchars($user['telegram_id']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="phone_number">
                    Номер телефона
                    <span class="required">*</span>
                </label>
                <input type="tel" 
                       id="phone_number" 
                       name="phone_number" 
                       required
                       pattern="8\d{10}"
                       placeholder="88005553535"
                       value="<?= isset($user) ? htmlspecialchars($user['phone_number']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="birth_date">
                    Дата рождения <span class="required">*</span> (не раньше 2007 года)
                </label>
                <input type="date" 
                       id="birth_date" 
                       name="birth_date" 
                       required
                       max="<?= date('Y-m-d', strtotime('-18 years')) ?>"
                       min="<?= date('Y-m-d', strtotime('-100 years')) ?>"
                       value="<?= isset($user) ? htmlspecialchars($user['birth_date']) : '' ?>">
            </div>



            <div class="form-group checkbox-group">
                <input type="checkbox" 
                       id="is_affiliate" 
                       name="is_affiliate" 
                       value="1"
                       <?= isset($user) && $user['is_affiliate'] ? 'checked' : '' ?>>
                <label for="is_affiliate">Является партнёром</label>
            </div>

            <div class="form-group">
                <label for="affiliate_id" class="form-label">Пригласил</label>
                <select class="form-select" id="affiliate_id" name="affiliate_id">
                    <option value="">Не выбран</option>
                    <?php foreach ($inviters as $inviter): ?>
                        <option value="<?= $inviter['id'] ?>" 
                                <?= (isset($user['affiliate_id']) && $user['affiliate_id'] == $inviter['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($inviter['telegram_username']) ?> - <?= htmlspecialchars($inviter['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group related-field">
                <label for="total_paid_amount">
                    Всего выплачено в рублях
                    <small style="color: var(--text-secondary); font-weight: normal;">(обновляется автоматически)</small>
                </label>
                <input type="number" 
                       id="total_paid_amount" 
                       name="total_paid_amount" 
                       step="0.01" 
                       min="0"
                       placeholder="0.00"
                       value="<?= isset($user['total_paid_amount']) ? htmlspecialchars($user['total_paid_amount']) : '0.00' ?>">
                <small class="form-text text-muted">Рассчитывается автоматически на основе архивных данных + текущий месяц</small>
            </div>

            <div class="form-group related-field">
                <label for="total_paid_for_referrals">
                    Всего выплачено за рефералов
                    <small style="color: var(--text-secondary); font-weight: normal;">(обновляется автоматически)</small>
                </label>
                <input type="number" 
                       id="total_paid_for_referrals" 
                       name="total_paid_for_referrals" 
                       step="0.01" 
                       min="0"
                       placeholder="0.00"
                       value="<?= isset($user['total_paid_for_referrals']) ? htmlspecialchars($user['total_paid_for_referrals']) : '0.00' ?>">
                <small class="form-text text-muted">Рассчитывается автоматически на основе архивных данных + текущий месяц</small>
            </div>

            <div class="form-group">
                <label for="payment_month">
                    Месяц учета выплат
                </label>
                <input type="month" 
                       id="payment_month" 
                       name="payment_month" 
                       value="<?= isset($user['payment_month']) ? htmlspecialchars($user['payment_month']) : date('Y-m') ?>">
                <small class="form-text text-muted">Выберите месяц для учета текущих выплат</small>
            </div>

            <div class="form-group">
                <label for="monthly_paid_amount">
                    Выплачено в рублях за месяц
                    <small style="color: var(--success-color); font-weight: normal;">(влияет на общую сумму)</small>
                </label>
                <input type="number" 
                       id="monthly_paid_amount" 
                       name="monthly_paid_amount" 
                       step="0.01" 
                       min="0"
                       placeholder="0.00"
                       value="<?= isset($user['monthly_paid_amount']) ? htmlspecialchars($user['monthly_paid_amount']) : '0.00' ?>">
                <small class="form-text text-muted">При изменении автоматически обновится общая сумма выплат</small>
            </div>

            <div class="form-group">
                <label for="monthly_paid_for_referrals">
                    Выплачено за рефералов за месяц
                    <small style="color: var(--success-color); font-weight: normal;">(влияет на общую сумму)</small>
                </label>
                <input type="number" 
                       id="monthly_paid_for_referrals" 
                       name="monthly_paid_for_referrals" 
                       step="0.01" 
                       min="0"
                       placeholder="0.00"
                       value="<?= isset($user['monthly_paid_for_referrals']) ? htmlspecialchars($user['monthly_paid_for_referrals']) : '0.00' ?>">
                <small class="form-text text-muted">При изменении автоматически обновится общая сумма за рефералов</small>
            </div>

            <?php if (isset($user['id'])): ?>
                <div class="form-group">
                    <label for="referral_count">
                        Количество рефералов
                    </label>
                    <input type="number" 
                           id="referral_count" 
                           name="referral_count" 
                           readonly 
                           value="<?php 
                               // Подсчитываем количество рефералов
                               $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE affiliate_id = ?');
                               $stmt->execute([$user['id']]);
                               echo $stmt->fetchColumn(); 
                           ?>">
                    <small class="form-text text-muted">Это поле заполняется автоматически</small>
                </div>
            <?php endif; ?>

            <!-- Букмекерские конторы -->
            <div class="form-group bookmakers-group">
                <label class="group-label">Букмекерские конторы</label>
                <div class="bookmakers-grid">
                    <?php foreach ($bookmakers as $bookmaker): ?>
                        <div class="bookmaker-item">
                            <input type="checkbox" 
                                   id="bookmaker_<?= $bookmaker['id'] ?>" 
                                   name="bookmakers[]" 
                                   value="<?= $bookmaker['id'] ?>"
                                   <?= in_array($bookmaker['id'], $selectedBookmakers) ? 'checked' : '' ?>>
                            <label for="bookmaker_<?= $bookmaker['id'] ?>"><?= htmlspecialchars($bookmaker['name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="submit-button">
                <?= isset($_GET['id']) ? 'Сохранить изменения' : 'Добавить пользователя' ?>
            </button>
        </form>
    </div>
</div>

<script>
    // Простая валидация формы на стороне клиента
    document.querySelector('form').addEventListener('submit', function(e) {
        const telegramUsername = document.getElementById('telegram_username').value;
        if (!telegramUsername.startsWith('@')) {
            alert('Имя пользователя Telegram должно начинаться с символа @');
            e.preventDefault();
            return;
        }

        const phoneNumber = document.getElementById('phone_number').value;
        if (!/^8\d{10}$/.test(phoneNumber)) {
            alert('Пожалуйста, введите корректный номер телефона в формате: 89051068938');
            e.preventDefault();
            return;
        }

        const birthDate = new Date(document.getElementById('birth_date').value);
        const today = new Date();
        const minDate = new Date();
        minDate.setFullYear(today.getFullYear() - 100);
        const maxDate = new Date();
        maxDate.setFullYear(today.getFullYear() - 18);
        
        if (birthDate > maxDate || birthDate < minDate) {
            alert('Пожалуйста, введите корректную дату рождения. Возраст должен быть от 18 до 100 лет.');
            e.preventDefault();
            return;
        }
    });

    // Форматирование номера карты
    document.getElementById('bank_card').addEventListener('input', function() {
        let value = this.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = '';
        
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) {
                formattedValue += ' ';
            }
            formattedValue += value[i];
        }
        
        this.value = formattedValue;
    });

    // Автоматическое обновление общих сумм при изменении месячных данных
    function updateTotalAmounts() {
        const monthlyAmount = parseFloat(document.getElementById('monthly_paid_amount').value) || 0;
        const monthlyReferrals = parseFloat(document.getElementById('monthly_paid_for_referrals').value) || 0;
        const totalAmountField = document.getElementById('total_paid_amount');
        const totalReferralsField = document.getElementById('total_paid_for_referrals');
        
        // Получаем текущие значения
        const currentTotalAmount = parseFloat(totalAmountField.value) || 0;
        const currentTotalReferrals = parseFloat(totalReferralsField.value) || 0;
        
        // Обновляем только если месячное значение больше 0 или общая сумма равна 0
        if (monthlyAmount > 0 || currentTotalAmount === 0) {
            // Если общая сумма меньше месячной или равна 0, устанавливаем минимум = месячной
            const newTotalAmount = Math.max(currentTotalAmount, monthlyAmount);
            if (newTotalAmount !== currentTotalAmount) {
                totalAmountField.value = newTotalAmount.toFixed(2);
                totalAmountField.style.backgroundColor = '#e8f5e8'; // Легкий зеленый фон для индикации обновления
                
                // Показываем уведомление
                showUpdateNotification(totalAmountField, `Обновлено: ${currentTotalAmount.toFixed(2)} → ${newTotalAmount.toFixed(2)}`);
            }
        }
        
        if (monthlyReferrals > 0 || currentTotalReferrals === 0) {
            const newTotalReferrals = Math.max(currentTotalReferrals, monthlyReferrals);
            if (newTotalReferrals !== currentTotalReferrals) {
                totalReferralsField.value = newTotalReferrals.toFixed(2);
                totalReferralsField.style.backgroundColor = '#e8f5e8';
                
                showUpdateNotification(totalReferralsField, `Обновлено: ${currentTotalReferrals.toFixed(2)} → ${newTotalReferrals.toFixed(2)}`);
            }
        }
        
        // Убираем цветовую индикацию через 3 секунды
        setTimeout(() => {
            totalAmountField.style.backgroundColor = '';
            totalReferralsField.style.backgroundColor = '';
        }, 3000);
    }
    
    // Функция для показа уведомления об обновлении
    function showUpdateNotification(element, message) {
        const notification = document.createElement('div');
        notification.className = 'update-notification';
        notification.textContent = message;
        notification.style.cssText = `
            position: absolute;
            background: var(--success-color);
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            font-size: 0.75rem;
            z-index: 1000;
            top: -40px;
            right: 0;
            white-space: nowrap;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            animation: notification-slide-in 0.3s ease;
        `;
        
        element.parentNode.style.position = 'relative';
        element.parentNode.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 4000);
    }

    // Слушатели событий для месячных полей
    document.getElementById('monthly_paid_amount').addEventListener('input', updateTotalAmounts);
    document.getElementById('monthly_paid_for_referrals').addEventListener('input', updateTotalAmounts);
    
    // Функция для показа подсказки
    function showTooltip(element, message) {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip-auto-update';
        tooltip.textContent = message;
        tooltip.style.cssText = `
            position: absolute;
            background: var(--primary-color);
            color: white;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            z-index: 1000;
            top: -30px;
            left: 0;
            white-space: nowrap;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        `;
        
        element.style.position = 'relative';
        element.appendChild(tooltip);
        
        setTimeout(() => {
            if (tooltip.parentNode) {
                tooltip.remove();
            }
        }, 3000);
    }
    
    // Добавляем подсказки при первом фокусе на месячные поля
    let tooltipShown = false;
    document.getElementById('monthly_paid_amount').addEventListener('focus', function() {
        if (!tooltipShown) {
            showTooltip(this.parentNode, 'Общие суммы обновятся автоматически');
            tooltipShown = true;
        }
    });
</script>

<?php
// Подключаем footer
require_once 'includes/footer.php';
?> 