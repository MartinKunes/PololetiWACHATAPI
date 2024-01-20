<?php
require __DIR__ . '/vendor/autoload.php';
require_once 'errorlogy.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $rooms;
    protected $db;
    protected $disconnectedUsers;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
        $this->disconnectedUsers = [];
        $this->db = new mysqli("localhost", "root", "", "register");

        if ($this->db->connect_error) {
            die('Connect Error (' . $this->db->connect_errno . ') ' . $this->db->connect_error);

        }
    }

    public function onOpen(ConnectionInterface $conn)
    {
        try {
            $this->clients->attach($conn);
            echo "New connection! ({$conn->resourceId})\n";

            $this->sendRoomsList($conn);
            $this->tryRejoin($conn);
        } catch (\Exception $e) {
            // Log the error
            logError("Error in onOpen: " . $e->getMessage());
        }
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);

        switch ($data['action']) {
            case 'join':
                $this->joinRoom($from, $data['room']);
                break;
            case 'create':
                $this->createRoom($from, $data['room']);
                break;
            case 'message':
                $this->sendMessage($from, $data['room'], $data['message']);
                $this->saveMessage($from, $data['room'], $data['message'], $data['userEmail']); // Pass the email to the saveMessage method
                break;
            case 'getRooms':
                $this->sendRoomsList($from);
                break;
            case 'rejoin':
                $this->rejoinRooms($from);
                break;
            case 'restrictAccess':
                $this->restrictAccess($from, $data['user'], $data['room']);
                break;
            default:
                break;
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
        $this->leaveRoom($conn);
        $this->disconnectUser($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        try {
            // Log the error
            logError("An error has occurred: {$e->getMessage()}");
            $conn->close();
        } catch (\Exception $ex) {
            // Log the secondary error, if any
            logError("Error in onError: " . $ex->getMessage());
        }
    }

    protected function joinRoom(ConnectionInterface $conn, $room)
    {
        $this->leaveRoom($conn);

        if (!isset($this->rooms[$room])) {
            echo "Room '{$room}' does not exist\n";
            return;
        }
        $this->rooms[$room]->attach($conn);
        echo "User {$conn->resourceId} joined room '{$room}'\n";
    }

    protected function createRoom(ConnectionInterface $conn, $room, $restrictedUsers = [])
    {
        if (isset($this->rooms[$room])) {
            echo "Room '{$room}' already exists\n";
            return;
        }
        $this->rooms[$room] = new \SplObjectStorage;
        $this->rooms[$room]->attach($conn);
        echo "User {$conn->resourceId} created and joined room '{$room}'\n";
        $query = "INSERT INTO rooms (room_name) VALUES ('{$room}')";
        $this->db->query($query);

        foreach ($restrictedUsers as $userId) {
            $query = "INSERT INTO pristupy (register_id, rooms_id) VALUES ('{$userId}', '{$room}')";
            $this->db->query($query);
        }

        $this->broadcastRoomsList();
        $this->broadcastUsersTable();
    }

    protected function broadcastUsersTable()
    {
        $query = "SELECT * FROM register";
        $result = $this->db->query($query);

        if (!$result) {
            die('Query Error (' . $this->db->errno . ') ' . $this->db->error);
        }

        // Build HTML table for users
        $usersTable = '<table border="1">
                         <tr>
                             <th>Email</th>
                             <th>Action</th>
                         </tr>';

        while ($row = $result->fetch_assoc()) {
            $userId = $row['id'];
            $usersTable .= '<tr>
                       <td>' . $row['email'] . '</td>
                       <td>
                           <button onclick="restrictAccess(\'' . $userId . '\')">Restrict Access</button>
                       </td>
                   </tr>';
        }

        $usersTable .= '</table>';
        foreach ($this->clients as $client) {
            $client->send(json_encode([
                'action' => 'usersTable',
                'table' => $usersTable,
            ]));
        }
    }

    protected function restrictAccess(ConnectionInterface $from, $userId, $room)
    {
        $roomQuery = "SELECT id FROM rooms WHERE room_name = '{$room}'";
        $roomResult = $this->db->query($roomQuery);

        if ($roomResult && $roomResult->num_rows > 0) {
            $row = $roomResult->fetch_assoc();
            $roomId = $row['id'];

            $query = "INSERT INTO pristupy (register_id, rooms_id) VALUES ('{$userId}', '{$roomId}')";
            $result = $this->db->query($query);

            if ($result) {
                $from->send(json_encode([
                    'action' => 'restrictAccess',
                    'user' => $userId,
                    'room' => $room,
                ]));
            } else {
                $from->send(json_encode([
                    'action' => 'error',
                    'message' => 'Failed to restrict access for user: ' . $userId . ' in room: ' . $room,
                ]));
            }
        }
    }

    protected function checkAndJoinRoomAccess(ConnectionInterface $from, $userId, $room)
    {
        $email = $_SESSION['email'];

        $roomQuery = "SELECT id FROM rooms WHERE room_name = '{$room}'";
        $roomResult = $this->db->query($roomQuery);

        if ($roomResult && $roomResult->num_rows > 0) {
            $row = $roomResult->fetch_assoc();
            $roomId = $row['id'];

            $accessQuery = "SELECT * FROM pristupy WHERE register_id = (SELECT id FROM register WHERE email = '{$email}') AND rooms_id = '{$roomId}'";
            $accessResult = $this->db->query($accessQuery);

            if ($accessResult && $accessResult->num_rows > 0) {
                $from->send(json_encode([
                    'action' => 'error',
                    'message' => 'Access to room ' . $room . ' is restricted for user with email ' . $email,
                ]));
            } else {
                $this->joinRoom($from, $room);
            }
        }
    }

    protected function leaveRoom(ConnectionInterface $conn)
    {
        foreach ($this->rooms as $room => $clients) {
            if ($clients->contains($conn)) {
                $clients->detach($conn);
                echo "User {$conn->resourceId} left room '{$room}'\n";


                break;
            }
        }
    }

    protected function sendMessage(ConnectionInterface $from, $room, $message)
    {
        if (isset($this->rooms[$room])) {
            foreach ($this->rooms[$room] as $client) {
                // Odeslat zprávu pouze klientům ve stejné místnosti
                $client->send(json_encode([
                    'action' => 'message',
                    'username' => $from->resourceId,
                    'message' => $message,
                ]));
            }
        }
    }

    protected function sendRoomsList(ConnectionInterface $conn)
    {
        $query = 'SELECT room_name FROM rooms';
        $result = $this->db->query($query);

        if ($result) {
            $roomsList = $result->fetch_all(MYSQLI_ASSOC);
            $roomsList = array_column($roomsList, 'room_name');
        } else {
            $roomsList = [];
        }

        $conn->send(json_encode([
            'action' => 'roomsList',
            'rooms' => $roomsList,
        ]));
    }

    protected function broadcastRoomsList()
    {
        foreach ($this->clients as $client) {
            $this->sendRoomsList($client);
        }
    }

    protected function disconnectUser(ConnectionInterface $conn)
    {
        $this->disconnectedUsers[$conn->resourceId] = [
            'rooms' => [],
        ];

        foreach ($this->rooms as $room => $clients) {
            if ($clients->contains($conn)) {
                $this->disconnectedUsers[$conn->resourceId]['rooms'][] = $room;
            }
        }
    }

    protected function tryRejoin(ConnectionInterface $conn)
    {
        $userId = $conn->resourceId;

        if (isset($this->disconnectedUsers[$userId])) {
            $disconnectedRooms = $this->disconnectedUsers[$userId]['rooms'];

            foreach ($disconnectedRooms as $room) {
                $this->rejoinRoom($conn, $room);
            }

            unset($this->disconnectedUsers[$userId]);
        }
    }

    protected function rejoinRoom(ConnectionInterface $conn, $room)
    {
        if (isset($this->rooms[$room])) {
            $this->rooms[$room]->attach($conn);
            echo "User {$conn->resourceId} reconnected to room '{$room}'\n";
        }
    }

    protected function rejoinRooms(ConnectionInterface $conn)
    {
        $this->tryRejoin($conn);
    }

    protected function saveMessage(ConnectionInterface $from, $room, $message, $userEmail)
    {
        $sql = "INSERT INTO messagess (user_id, room, message) VALUES (?, ?, ?)";

        try {
            $stmt = $this->db->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Failed to prepare SQL statement');
            }

            $stmt->bind_param('sss', $userEmail, $room, $message);

            $result = $stmt->execute();
            if ($result === false) {
                throw new Exception('Failed to execute SQL statement');
            }

            $stmt->close();
        } catch (Exception $e) {
            error_log($e->getMessage());

            $from->send(json_encode([
                'action' => 'error',
                'message' => 'Failed to save message',
            ]));
        }
    }

}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8080
);

$server->run();