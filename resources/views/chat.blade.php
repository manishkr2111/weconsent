<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>One-to-One Chat Demo</title>
    <style>
        body { font-family: Arial; max-width: 600px; margin: 50px auto; }
        #chat-box { border:1px solid #ccc; padding:10px; height:400px; overflow-y:auto; margin-bottom:10px; }
        .message { padding:5px; border-bottom:1px solid #eee; }
        .sender { font-weight:bold; color:blue; }
        .receiver { font-weight:bold; color:green; }
        #message-input { width:80%; padding:5px; }
        #send-btn { padding:5px 10px; }
    </style>
</head>
<body>

<h2>One-to-One Chat</h2>

<div id="chat-box"></div>

<input type="text" id="message-input" placeholder="Type a message...">
<button id="send-btn">Send</button>

<script src="https://js.pusher.com/8.2/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
    // -------------------------
    // CONFIGURATION
    // -------------------------
    const PUSHER_KEY = "e0219b37b23fee7f77b8";      // replace with your Pusher key
    const PUSHER_CLUSTER = "mt1"; // replace with your Pusher cluster

    const API_BASE = "https://dev.weconsent.app"; 
    const AUTH_TOKEN = "114|HoMvrA5IrYcJCAvptcjCCz3j7AES4LqDEhx4KwCF1c0eb80f";

    // -------------------------
    // USER INFO
    // -------------------------
    const loggedInUserId = 2;      // ID of the currently logged-in user
    const chatWithUserId = 33;     // ID of the user you want to chat with

    // -------------------------
    // INITIALIZE PUSHER
    // -------------------------
    const pusher = new Pusher(PUSHER_KEY, {
        cluster: PUSHER_CLUSTER,
        authEndpoint: `${API_BASE}/broadcasting/auth`,
        auth: {
            headers: {
                'Authorization': `Bearer ${AUTH_TOKEN}`
            }
        }
    });

    const channel = pusher.subscribe(`private-chat.${loggedInUserId}`);

    channel.bind('App\\Events\\MessageSent', function(data) {
        console.log('New message received:', data.message);
        addMessageToChat(data.message);
    });

    // -------------------------
    // FETCH EXISTING MESSAGES
    // -------------------------
    async function loadMessages() {
        try {
            const res = await axios.get(`${API_BASE}/api/chat/${chatWithUserId}`, {
                headers: { 'Authorization': `Bearer ${AUTH_TOKEN}` }
            });

            res.data.forEach(msg => addMessageToChat(msg));
        } catch(e) {
            console.error('Error fetching messages:', e);
        }
    }

    loadMessages();

    // -------------------------
    // DISPLAY MESSAGE
    // -------------------------
    function addMessageToChat(msg) {
        const chatBox = document.getElementById('chat-box');
        const div = document.createElement('div');
        div.classList.add('message');

        const isSender = msg.sender_id == loggedInUserId;
        div.innerHTML = `<span class="${isSender ? 'sender' : 'receiver'}">
            ${isSender ? 'You' : 'Sender'}:</span> ${msg.message}`;
        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    // -------------------------
    // SEND MESSAGE
    // -------------------------
    document.getElementById('send-btn').addEventListener('click', async () => {
        const input = document.getElementById('message-input');
        const message = input.value.trim();
        if (!message) return;

        try {
            const res = await axios.post(`${API_BASE}/api/chat-send`, {
                receiver_id: chatWithUserId,
                message: message
            }, {
                headers: { 'Authorization': `Bearer ${AUTH_TOKEN}` }
            });

            input.value = '';
            addMessageToChat(res.data.data); 
        } catch(e) {
            console.error('Error sending message:', e);
        }
    });
</script>

</body>
</html>
