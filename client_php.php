<?php
require 'vendor/autoload.php';

use Ratchet\ConnectionInterface;

class NotificationManager {
    private static $instance = null;
    private $clients;

    private function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function addClient(ConnectionInterface $client) {
        $this->clients->attach($client);
    }

    public function removeClient(ConnectionInterface $client) {
        $this->clients->detach($client);
    }

    public function sendNotification($message) {
        foreach ($this->clients as $client) {
            $client->send($message);
        }
    }
}

// Tạo một WebSocket client để kết nối với server và gửi thông báo rùi ngắt kết nối luôn
$token = 'valid_token_1'; // Thay đổi token cho phù hợp

$client = new Client("ws://127.0.0.1:8080/?token=" . $token);

$client->send('thông báo bằng php file nè');
$client->close();

echo "Notification sent!\n";
?>
