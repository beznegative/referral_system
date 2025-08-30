<?php
require_once 'includes/database.php';
require_once 'test_encryption.php';

// Функции шифрования уже подключены из test_encryption.php

$isEdit = isset($_GET['id']);
$user = null;
$user_bookmakers = [];

if ($isEdit) {
    // Режим редактирования
    $pageTitle = 'Редактирование пользователя';
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception('Пользователь не найден');
        }
        
        // Получаем букмекерские конторы пользователя
        $stmt = $pdo->prepare("
            SELECT bookmaker_id 
            FROM user_bookmakers 
            WHERE user_id = ?
        ");
        $stmt->execute([$_GET['id']]);
        $user_bookmakers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
    } catch (Exception $e) {
        die('Ошибка: ' . htmlspecialchars($e->getMessage()));
    }
} else {
    // Режим добавления
    $pageTitle = 'Добавление пользователя';
}

// Получаем список букмекерских контор
$stmt = $pdo->query("SELECT * FROM bookmakers ORDER BY name");
$bookmakers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем список всех пользователей для выбора как пригласивших
// Исключаем текущего пользователя, чтобы избежать циклических ссылок
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id != ? ORDER BY full_name");
    $stmt->execute([$_GET['id']]);
} else {
    $stmt = $pdo->query("SELECT id, full_name FROM users ORDER BY full_name");
}
$affiliates = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="container">
    <h1><?= $pageTitle ?></h1>
    
    <div class="form-container">
        <form method="POST" action="save_user.php" class="user-form" enctype="multipart/form-data">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= $user['id'] ?>">
            <?php endif; ?>
            
            <!-- Основная информация -->
            <div class="form-section">
                <h3>Основная информация</h3>
                
                <div class="form-group">
                    <label for="full_name">ФИО <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           value="<?= $isEdit ? htmlspecialchars($user['full_name']) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="telegram_username">Имя пользователя Telegram <span class="required">*</span></label>
                    <input type="text" id="telegram_username" name="telegram_username" class="form-control" 
                           placeholder="@username" 
                           value="<?= $isEdit ? htmlspecialchars($user['telegram_username']) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="telegram_id">ID Telegram</label>
                    <input type="text" id="telegram_id" name="telegram_id" class="form-control" 
                           placeholder="123456789"
                           value="<?= $isEdit ? htmlspecialchars($user['telegram_id']) : '' ?>">
                </div>
                

            </div>
            
            <!-- Реферальная система -->
            <div class="form-section">
                <h3>Реферальная система</h3>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="is_affiliate" name="is_affiliate" 
                           <?= $isEdit && $user['is_affiliate'] ? 'checked' : '' ?>>
                    <label for="is_affiliate">Является партнёром</label>
                </div>
                
                <div class="form-group">
                    <label for="affiliate_id">Пригласивший пользователь</label>
                    <select id="affiliate_id" name="affiliate_id" class="form-control">
                        <option value="">Выберите пользователя</option>
                        <?php foreach ($affiliates as $affiliate): ?>
                            <option value="<?= $affiliate['id'] ?>" 
                                    <?= $isEdit && $user['affiliate_id'] == $affiliate['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($affiliate['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Финансовая информация -->
            <div class="form-section">
                <h3>Финансовая информация</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="payment_year">Год выплат</label>
                        <select id="payment_year" name="payment_year" class="form-control">
                            <?php 
                            $currentYear = date('Y');
                            $selectedYear = $isEdit && $user['payment_month'] ? date('Y', strtotime($user['payment_month'] . '-01')) : $currentYear;
                            for ($year = $currentYear - 2; $year <= $currentYear + 1; $year++): ?>
                                <option value="<?= $year ?>" <?= $year == $selectedYear ? 'selected' : '' ?>>
                                    <?= $year ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_month">Месяц выплат</label>
                        <select id="payment_month" name="payment_month" class="form-control">
                            <?php 
                            $months = [
                                '01' => 'Январь',
                                '02' => 'Февраль', 
                                '03' => 'Март',
                                '04' => 'Апрель',
                                '05' => 'Май',
                                '06' => 'Июнь',
                                '07' => 'Июль',
                                '08' => 'Август',
                                '09' => 'Сентябрь',
                                '10' => 'Октябрь',
                                '11' => 'Ноябрь',
                                '12' => 'Декабрь'
                            ];
                            $selectedMonth = $isEdit && $user['payment_month'] ? date('m', strtotime($user['payment_month'] . '-01')) : date('m');
                            foreach ($months as $value => $name): ?>
                                <option value="<?= $value ?>" <?= $value == $selectedMonth ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="monthly_paid_amount">Выплачено за месяц (₽)</label>
                        <input type="number" step="0.01" min="0" id="monthly_paid_amount" 
                               name="monthly_paid_amount" class="form-control" 
                               value="<?= $isEdit ? $user['monthly_paid_amount'] : '0.00' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="monthly_paid_for_referrals">Выплачено за рефералов за месяц (₽)</label>
                        <input type="number" step="0.01" min="0" id="monthly_paid_for_referrals" 
                               name="monthly_paid_for_referrals" class="form-control" 
                               value="<?= $isEdit ? $user['monthly_paid_for_referrals'] : '0.00' ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="total_paid_amount">Всего выплачено (₽)</label>
                        <input type="text" id="total_paid_amount" class="form-control readonly-field" 
                               value="<?= $isEdit ? number_format($user['total_paid_amount'], 2, '.', ' ') : '0.00' ?>"
                               readonly>
                        <small class="form-text text-muted">Рассчитывается автоматически на основе всех выплат</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="total_paid_for_referrals">Всего выплачено за рефералов (₽)</label>
                        <input type="text" id="total_paid_for_referrals" class="form-control readonly-field" 
                               value="<?= $isEdit ? number_format($user['total_paid_for_referrals'], 2, '.', ' ') : '0.00' ?>"
                               readonly>
                        <small class="form-text text-muted">Рассчитывается автоматически на основе всех выплат за рефералов</small>
                    </div>
                </div>
            </div>
            
            <!-- Букмекерские конторы -->
            <?php if (!empty($bookmakers)): ?>
            <div class="form-section">
                <h3>Букмекерские конторы</h3>
                <div class="bookmakers-grid">
                    <?php foreach ($bookmakers as $bookmaker): ?>
                        <div class="bookmaker-item">
                            <input type="checkbox" id="bookmaker_<?= $bookmaker['id'] ?>" 
                                   name="bookmakers[]" value="<?= $bookmaker['id'] ?>"
                                   <?= in_array($bookmaker['id'], $user_bookmakers) ? 'checked' : '' ?>>
                            <label for="bookmaker_<?= $bookmaker['id'] ?>">
                                <?= htmlspecialchars($bookmaker['name']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Кнопки -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? 'Обновить' : 'Сохранить' ?>
                </button>
                <a href="<?= $isEdit ? 'user.php?id=' . $user['id'] : 'index.php' ?>" class="btn btn-secondary">
                    Отмена
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.form-container {
    max-width: 800px;
    margin: 0 auto;
}

.form-section {
    background: var(--card-background);
    border-radius: var(--radius);
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
}

.form-section h3 {
    color: var(--text-primary);
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--border-color);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    font-size: 1rem;
    color: var(--text-primary);
    background-color: var(--card-background);
    transition: all 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-primary);
}

.required {
    color: var(--error-color);
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.checkbox-group input[type="checkbox"] {
    width: 1.25rem;
    height: 1.25rem;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-color);
    cursor: pointer;
}

.bookmakers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.bookmaker-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    background-color: var(--background-color);
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-color);
    transition: all 0.2s ease;
}

.bookmaker-item:hover {
    border-color: var(--primary-color);
    background-color: var(--card-background);
}

.bookmaker-item input[type="checkbox"] {
    width: 1.25rem;
    height: 1.25rem;
    border-radius: var(--radius-sm);
    border: 2px solid var(--border-color);
    transition: all 0.2s ease;
    cursor: pointer;
}

.bookmaker-item input[type="checkbox"]:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.bookmaker-item label {
    cursor: pointer;
    font-size: 0.9rem;
    color: var(--text-primary);
    margin: 0;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-start;
    margin-top: 2rem;
}

.form-text {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.text-muted {
    color: var(--text-secondary) !important;
}

.readonly-field {
    background-color: var(--background-color) !important;
    color: var(--text-secondary) !important;
    cursor: not-allowed !important;
    font-weight: 500;
}

.readonly-field:focus {
    box-shadow: none !important;
    border-color: var(--border-color) !important;
}

/* Адаптивность */
@media (max-width: 768px) {
    .form-container {
        padding: 0;
    }
    
    .form-section {
        padding: 1.5rem;
        margin-bottom: 1rem;
        border-radius: 0;
        border-left: none;
        border-right: none;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .bookmakers-grid {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .form-actions {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .btn {
        width: 100%;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .form-section {
        padding: 1rem;
        margin: 0 -0.5rem 1rem -0.5rem;
    }
    
    .form-section h3 {
        font-size: 1.125rem;
        margin-bottom: 1rem;
    }
    
    .form-control {
        padding: 0.625rem 0.75rem;
        font-size: 16px; /* Предотвращает зум на iOS */
    }
}
</style>

<script>
// Функция для загрузки данных месяца/года
function loadMonthlyData() {
    const userId = <?= $isEdit ? $user['id'] : 'null' ?>;
    if (!userId) return;
    
    const year = document.getElementById('payment_year').value;
    const month = document.getElementById('payment_month').value;
    const paymentMonth = year + '-' + month;
    
    // Показываем индикатор загрузки
    const monthlyAmountField = document.getElementById('monthly_paid_amount');
    const monthlyReferralsField = document.getElementById('monthly_paid_for_referrals');
    const totalAmountField = document.getElementById('total_paid_amount');
    const totalReferralsField = document.getElementById('total_paid_for_referrals');
    
    monthlyAmountField.disabled = true;
    monthlyReferralsField.disabled = true;
    
    // AJAX запрос для получения данных
    fetch('get_monthly_data.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'user_id=' + userId + '&payment_month=' + encodeURIComponent(paymentMonth)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            monthlyAmountField.value = data.monthly_paid_amount || '0.00';
            monthlyReferralsField.value = data.monthly_paid_for_referrals || '0.00';
            totalAmountField.value = data.total_paid_amount || '0.00';
            totalReferralsField.value = data.total_paid_for_referrals || '0.00';
        } else {
            console.error('Ошибка загрузки данных:', data.error);
        }
    })
    .catch(error => {
        console.error('Ошибка запроса:', error);
    })
    .finally(() => {
        monthlyAmountField.disabled = false;
        monthlyReferralsField.disabled = false;
    });
}

// Валидация формы перед отправкой
document.querySelector('.user-form').addEventListener('submit', function(e) {
    const fullName = document.getElementById('full_name').value.trim();
    const telegramUsername = document.getElementById('telegram_username').value.trim();
    
    if (!fullName) {
        alert('Пожалуйста, заполните поле "ФИО"');
        e.preventDefault();
        return false;
    }
    
    if (!telegramUsername) {
        alert('Пожалуйста, заполните поле "Имя пользователя Telegram"');
        e.preventDefault();
        return false;
    }
    
    // Объединяем год и месяц в payment_month поле перед отправкой
    const year = document.getElementById('payment_year').value;
    const month = document.getElementById('payment_month').value;
    const hiddenPaymentMonth = document.createElement('input');
    hiddenPaymentMonth.type = 'hidden';
    hiddenPaymentMonth.name = 'payment_month_combined';
    hiddenPaymentMonth.value = year + '-' + month;
    this.appendChild(hiddenPaymentMonth);
    
    // Отладочная информация
    console.log('Отправка формы:', {
        fullName: fullName,
        telegramUsername: telegramUsername,
        paymentMonth: year + '-' + month,
        method: this.method,
        action: this.action
    });
    
    return true;
});

// Обработчики событий для изменения года и месяца
document.getElementById('payment_year').addEventListener('change', loadMonthlyData);
document.getElementById('payment_month').addEventListener('change', loadMonthlyData);

// Обработка поля Telegram username
document.getElementById('telegram_username').addEventListener('input', function(e) {
    let value = e.target.value;
    if (value && !value.startsWith('@')) {
        e.target.value = '@' + value;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
