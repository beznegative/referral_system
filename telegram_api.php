<?php

/**
 * Класс для работы с Telegram API
 */
class TelegramAPI {
    private $token;
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->token = $this->getTokenFromDatabase();
    }
    
    /**
     * Получение токена из базы данных
     */
    private function getTokenFromDatabase() {
        try {
            $stmt = $this->pdo->prepare("SELECT token_value FROM api_tokens WHERE token_name = 'telegram_bot_token' AND is_active = TRUE");
            $stmt->execute();
            $token = $stmt->fetchColumn();
            return $token ?: null;
        } catch (Exception $e) {
            error_log('Ошибка получения токена: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Получение информации о боте
     */
    public function getMe() {
        if (!$this->token) {
            return ['ok' => false, 'error' => 'Токен не найден'];
        }
        
        $url = "https://api.telegram.org/bot{$this->token}/getMe";
        
        $response = $this->makeRequest($url);
        return $response;
    }
    
    /**
     * Отправка текстового сообщения
     */
    public function sendMessage($chat_id, $text, $parse_mode = 'HTML') {
        if (!$this->token) {
            return ['ok' => false, 'error' => 'Токен не найден'];
        }
        
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";
        
        $data = [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => $parse_mode
        ];
        
        return $this->makeRequest($url, $data);
    }
    
    /**
     * Отправка фото с подписью
     */
    public function sendPhoto($chat_id, $photo, $caption = '', $parse_mode = 'HTML') {
        if (!$this->token) {
            return ['ok' => false, 'error' => 'Токен не найден'];
        }
        
        $url = "https://api.telegram.org/bot{$this->token}/sendPhoto";
        
        $data = [
            'chat_id' => $chat_id,
            'caption' => $caption,
            'parse_mode' => $parse_mode
        ];
        
        // Если фото - это файл
        if (is_file($photo) && file_exists($photo)) {
            $data['photo'] = new CURLFile($photo);
        } else {
            $data['photo'] = $photo;
        }
        
        return $this->makeRequest($url, $data, true);
    }
    
    /**
     * Отправка сообщения всем партнерам
     */
    public function sendMessageToAll($text, $parse_mode = 'HTML') {
        if (!$this->token) {
            return ['ok' => false, 'error' => 'Токен не найден'];
        }
        
        try {
            // Получаем всех партнеров с telegram_id
            $stmt = $this->pdo->query("SELECT telegram_id, full_name FROM users WHERE telegram_id IS NOT NULL AND telegram_id != '' AND is_affiliate = 1");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $results = [];
            $success_count = 0;
            $error_count = 0;
            
            foreach ($users as $user) {
                $response = $this->sendMessage($user['telegram_id'], $text, $parse_mode);
                $results[] = [
                    'user' => $user['full_name'],
                    'telegram_id' => $user['telegram_id'],
                    'success' => $response['ok'] ?? false,
                    'error' => $response['error'] ?? null,
                    'error_code' => $response['error_code'] ?? null,
                    'debug' => $response['debug'] ?? null
                ];
                
                if ($response['ok'] ?? false) {
                    $success_count++;
                } else {
                    $error_count++;
                }
                
                // Небольшая задержка между отправками
                usleep(100000); // 0.1 секунда
            }
            
            return [
                'ok' => true,
                'total_users' => count($users),
                'success_count' => $success_count,
                'error_count' => $error_count,
                'results' => $results
            ];
            
        } catch (Exception $e) {
            return ['ok' => false, 'error' => 'Ошибка отправки: ' . $e->getMessage()];
        }
    }
    
    /**
     * Отправка фото всем партнерам
     */
    public function sendPhotoToAll($photo, $caption = '', $parse_mode = 'HTML') {
        if (!$this->token) {
            return ['ok' => false, 'error' => 'Токен не найден'];
        }
        
        try {
            // Получаем всех партнеров с telegram_id
            $stmt = $this->pdo->query("SELECT telegram_id, full_name FROM users WHERE telegram_id IS NOT NULL AND telegram_id != '' AND is_affiliate = 1");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $results = [];
            $success_count = 0;
            $error_count = 0;
            
            foreach ($users as $user) {
                $response = $this->sendPhoto($user['telegram_id'], $photo, $caption, $parse_mode);
                $results[] = [
                    'user' => $user['full_name'],
                    'telegram_id' => $user['telegram_id'],
                    'success' => $response['ok'] ?? false,
                    'error' => $response['error'] ?? null,
                    'error_code' => $response['error_code'] ?? null,
                    'debug' => $response['debug'] ?? null
                ];
                
                if ($response['ok'] ?? false) {
                    $success_count++;
                } else {
                    $error_count++;
                }
                
                // Небольшая задержка между отправками
                usleep(100000); // 0.1 секунда
            }
            
            return [
                'ok' => true,
                'total_users' => count($users),
                'success_count' => $success_count,
                'error_count' => $error_count,
                'results' => $results
            ];
            
        } catch (Exception $e) {
            return ['ok' => false, 'error' => 'Ошибка отправки: ' . $e->getMessage()];
        }
    }
    
    /**
     * Выполнение HTTP запроса
     */
    private function makeRequest($url, $data = null, $multipart = false) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Отключаем проверку SSL для локальной разработки
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($multipart) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['ok' => false, 'error' => 'cURL Error: ' . $error, 'debug' => ['url' => $url, 'data' => $data]];
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return ['ok' => false, 'error' => 'HTTP Error: ' . $httpCode, 'debug' => ['url' => $url, 'response' => $response]];
        }
        
        $result = json_decode($response, true);
        
        if (!$result) {
            return ['ok' => false, 'error' => 'Invalid JSON response', 'debug' => ['response' => $response]];
        }
        
        // Если Telegram API вернул ошибку
        if (!$result['ok']) {
            return [
                'ok' => false, 
                'error' => $result['description'] ?? 'Unknown Telegram API error',
                'error_code' => $result['error_code'] ?? 0,
                'debug' => ['url' => $url, 'data' => $data]
            ];
        }
        
        return $result;
    }
    
    /**
     * Отправка сообщения всем пользователям (не только партнерам)
     */
    public function sendMessageToAllUsers($text, $parse_mode = 'HTML') {
        if (!$this->token) {
            return ['ok' => false, 'error' => 'Токен не найден'];
        }
        
        try {
            // Получаем всех пользователей с telegram_id
            $stmt = $this->pdo->query("SELECT telegram_id, full_name FROM users WHERE telegram_id IS NOT NULL AND telegram_id != ''");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $results = [];
            $success_count = 0;
            $error_count = 0;
            
            foreach ($users as $user) {
                $response = $this->sendMessage($user['telegram_id'], $text, $parse_mode);
                $results[] = [
                    'user' => $user['full_name'],
                    'telegram_id' => $user['telegram_id'],
                    'success' => $response['ok'] ?? false,
                    'error' => $response['error'] ?? null,
                    'error_code' => $response['error_code'] ?? null,
                    'debug' => $response['debug'] ?? null
                ];
                
                if ($response['ok'] ?? false) {
                    $success_count++;
                } else {
                    $error_count++;
                }
                
                // Небольшая задержка между отправками
                usleep(100000); // 0.1 секунда
            }
            
            return [
                'ok' => true,
                'total_users' => count($users),
                'success_count' => $success_count,
                'error_count' => $error_count,
                'results' => $results
            ];
            
        } catch (Exception $e) {
            return ['ok' => false, 'error' => 'Ошибка отправки: ' . $e->getMessage()];
        }
    }
    
    /**
     * Отправка фото всем пользователям (не только партнерам)
     */
    public function sendPhotoToAllUsers($photo, $caption = '', $parse_mode = 'HTML') {
        if (!$this->token) {
            return ['ok' => false, 'error' => 'Токен не найден'];
        }
        
        try {
            // Получаем всех пользователей с telegram_id
            $stmt = $this->pdo->query("SELECT telegram_id, full_name FROM users WHERE telegram_id IS NOT NULL AND telegram_id != ''");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $results = [];
            $success_count = 0;
            $error_count = 0;
            
            foreach ($users as $user) {
                $response = $this->sendPhoto($user['telegram_id'], $photo, $caption, $parse_mode);
                $results[] = [
                    'user' => $user['full_name'],
                    'telegram_id' => $user['telegram_id'],
                    'success' => $response['ok'] ?? false,
                    'error' => $response['error'] ?? null,
                    'error_code' => $response['error_code'] ?? null,
                    'debug' => $response['debug'] ?? null
                ];
                
                if ($response['ok'] ?? false) {
                    $success_count++;
                } else {
                    $error_count++;
                }
                
                // Небольшая задержка между отправками
                usleep(100000); // 0.1 секунда
            }
            
            return [
                'ok' => true,
                'total_users' => count($users),
                'success_count' => $success_count,
                'error_count' => $error_count,
                'results' => $results
            ];
            
        } catch (Exception $e) {
            return ['ok' => false, 'error' => 'Ошибка отправки: ' . $e->getMessage()];
        }
    }
}
?> 