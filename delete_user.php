<?php
require_once 'includes/database.php';

// Проверяем, что запрос пришёл методом POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Проверяем наличие ID пользователя
if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    die('Ошибка: неверный ID пользователя');
}

$user_id = (int)$_POST['user_id'];

try {
    // Начинаем транзакцию для безопасного удаления
    $pdo->beginTransaction();
    
    // Сначала получаем информацию о пользователе для логирования
    $stmt = $pdo->prepare("SELECT full_name, telegram_username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('Пользователь не найден');
    }
    
    // Подсчитываем количество рефералов у этого пользователя
    $stmt = $pdo->prepare("SELECT COUNT(*) as referral_count FROM users WHERE affiliate_id = ?");
    $stmt->execute([$user_id]);
    $referral_count = $stmt->fetch(PDO::FETCH_ASSOC)['referral_count'];
    
    // Удаляем пользователя
    // Благодаря внешним ключам CASCADE и SET NULL связанные записи обработаются автоматически:
    // - referral_earnings удалятся (CASCADE)
    // - user_bookmakers удалятся (CASCADE)
    // - affiliate_id у рефералов установится в NULL (SET NULL)
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    
    // Проверяем, что пользователь был удалён
    if ($stmt->rowCount() === 0) {
        throw new Exception('Не удалось удалить пользователя');
    }
    
    // Фиксируем транзакцию
    $pdo->commit();
    
    // Записываем в лог (можно добавить логирование в файл)
    $log_message = date('Y-m-d H:i:s') . " - Удален пользователь: {$user['full_name']} ({$user['telegram_username']}) ID: {$user_id}";
    if ($referral_count > 0) {
        $log_message .= ", у пользователя было {$referral_count} рефералов";
    }
    
    // Перенаправляем на главную страницу с сообщением об успехе
    session_start();
    $_SESSION['success_message'] = "Пользователь {$user['full_name']} успешно удален";
    header('Location: index.php');
    exit;
    
} catch (Exception $e) {
    // Откатываем транзакцию в случае ошибки
    $pdo->rollBack();
    
    // Записываем ошибку в лог
    $error_log = date('Y-m-d H:i:s') . " - Ошибка при удалении пользователя ID {$user_id}: " . $e->getMessage() . PHP_EOL;
    
    // Показываем ошибку пользователю
    die('Ошибка при удалении пользователя: ' . htmlspecialchars($e->getMessage()));
}
?> 