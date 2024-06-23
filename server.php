<?php
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class NotificationManager {
    private static $instance = null;
    public $clients;

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

class Chat implements MessageComponentInterface {
    public function onOpen(ConnectionInterface $conn) {
        // Kiểm tra đúng không mới cho kết nối vô socket server ở chỗ này nè
        // Kiểm tra thông tin chứng thực từ URL query
        $query = $conn->httpRequest->getUri()->getQuery();
        parse_str($query, $params);

        if (!isset($params['token']) || !$this->isValidToken($params['token'])) {
            $conn->send("Invalid token");
            $conn->close();
            return;
        }

        //Thêm kết nối vào SV
        NotificationManager::getInstance()->addClient($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        foreach (NotificationManager::getInstance()->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        NotificationManager::getInstance()->removeClient($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    private function isValidToken($token) {
        // Thay đổi logic kiểm tra token cho phù hợp với ứng dụng của bạn
        $validTokens = ['valid_token_1', 'valid_token_2']; // Ví dụ danh sách token hợp lệ
        return in_array($token, $validTokens);
    }
}

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8080
);

echo "Server running on port 8080...\n";
$server->run();
?>
