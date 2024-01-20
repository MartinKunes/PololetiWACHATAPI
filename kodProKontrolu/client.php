<?php
session_start();

require_once 'errorlogy.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebSocket Chat</title>
</head>
<body>

<?php
if (!isset($_SESSION["email"])) {
    logInfo("Unauthorized access attempted to " . $_SERVER['REQUEST_URI']);
    header("Location: Login.php");
    return;
} else {
    echo '<h1>' . $_SESSION['email'] . '</h1>';
}
?>

<label for="room">Choose or create a room:</label>
<select id="room" onchange="joinOrCreateRoom()">
    <option value="">Select or create a room</option>
</select>
<button type="button" onclick="createRoom()">Create Room</button>
<div id="usersTable"></div>

<form id="form">
    <input type="text" id="message" placeholder="Enter your message">
    <button type="button" onclick="sendMessageWithUserEmail()">Send</button>
</form>

<div id="chat"></div>

<script>
    const socket = new WebSocket('ws://localhost:8080');
    let currentRoom = '';

    socket.addEventListener('open', (event) => {
        console.log('Connected to the server');
        getRoomsList();

        socket.send(JSON.stringify({
            action: 'getUserId',
        }));
    });

    const userEmail = '<?php echo isset($_SESSION["email"]) ? $_SESSION["email"] : ""; ?>';

    function restrictAccess(userId) {

        socket.send(JSON.stringify({
            action: 'restrictAccess',
            user: userId,
            room: currentRoom,
        }));
    }


    socket.addEventListener('message', (event) => {
        const data = JSON.parse(event.data);

        switch (data.action) {
            case 'message':
                displayMessage(data.username, data.message, userEmail);
                break;
            case 'roomsList':
                updateRoomsList(data.rooms);
                break;
            case 'restrictAccess':
                console.log('Access restricted for user: ' + data.user + ' in room: ' + data.room);
                break;
            case 'usersTable':
                document.getElementById('usersTable').innerHTML = data.table;
                break;
            case 'userId':
                const userId = data.userId;
                checkAndJoinRoomAccess(userId);
                break;
            default:
                break;
        }
    });

    function joinOrCreateRoom() {
        const roomSelect = document.getElementById('room');
        const selectedRoom = roomSelect.value;

        if (selectedRoom !== currentRoom) {
            if (currentRoom) {
                socket.send(JSON.stringify({
                    action: 'leave',
                    room: currentRoom,
                }));
            }

            if (selectedRoom) {
                socket.send(JSON.stringify({
                    action: 'join',
                    room: selectedRoom,
                }));
            }

            currentRoom = selectedRoom;
            document.getElementById('chat').innerHTML = '';

            checkAndJoinRoomAccess(userEmail);
        }
    }

    function createRoom() {
        const roomInput = prompt('Enter a new room name:');
        if (roomInput) {
            socket.send(JSON.stringify({
                action: 'create',
                room: roomInput,
            }));
            currentRoom = roomInput;
            document.getElementById('chat').innerHTML = '';
            updateRoomsList();
        }
    }

    function sendMessageWithUserEmail() {
        const messageInput = document.getElementById('message');
        const message = messageInput.value;

        if (message.trim() !== '') {
            socket.send(JSON.stringify({
                action: 'message',
                room: currentRoom,
                message: message,
                userEmail: userEmail,
            }));

            messageInput.value = '';
        }
    }

    function displayMessage(username, message, userEmail) {
        const chatDiv = document.getElementById('chat');
        chatDiv.innerHTML += `<p><strong>${userEmail} (${username}):</strong> ${message}</p>`;
    }

    function getRoomsList() {
        socket.send(JSON.stringify({
            action: 'getRooms',
        }));
    }

    function updateRoomsList(rooms) {
        const roomSelect = document.getElementById('room');
        roomSelect.innerHTML = '<option value="">Select or create a room</option>';

        rooms.forEach(room => {
            const option = document.createElement('option');
            option.value = room;
            option.textContent = room;
            roomSelect.appendChild(option);
        });
    }

    function checkAndJoinRoomAccess(userId) {
        const roomSelect = document.getElementById('room');
        const selectedRoom = roomSelect.value;

        if (selectedRoom) {
            socket.send(JSON.stringify({
                action: 'checkAndJoinRoomAccess',
                user: userId,
                room: selectedRoom,
            }));
        }
    }
</script>
</body>
</html>