<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Header -->
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-xl font-semibold mb-4">
                                Conversation #{{ substr($conversation->facebook_user_id, -4) }}
                                <span class="text-sm text-gray-500 ml-2">{{ $conversation->response_mode === 'ai' ? '(AI Mode)' : '(Manual Mode)' }}</span>
                            </h2>
                            <p class="text-sm text-gray-500">
                                Started {{ $conversation->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <button 
                                onclick="toggleResponseMode()"
                                class="px-4 py-2 bg-white border rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                id="modeToggleButton"
                            >
                                Current Mode: <span class="font-semibold" id="currentMode">{{ ucfirst($conversation->response_mode) }}</span>
                            </button>
                            <a 
                                href="{{ route('chat.index') }}"
                                class="px-4 py-2 bg-gray-100 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-200"
                            >
                                Back to List
                            </a>
                        </div>
                    </div>

                    <!-- Messages Container -->
                    <div class="border rounded-lg bg-gray-50 h-[600px] mb-4 p-4 overflow-y-auto" id="messagesContainer">
                        <div class="space-y-4">
                            @foreach($messages->reverse() as $message)
                                <div class="flex {{ $message->type === 'incoming' ? 'justify-start' : 'justify-end' }}">
                                    <div class="max-w-[70%] {{ $message->type === 'incoming' ? 'bg-white' : ($message->sender_type === 'ai' ? 'bg-green-100' : 'bg-blue-100') }} rounded-lg px-4 py-2 shadow">
                                        <div class="text-sm {{ $message->type === 'incoming' ? 'text-gray-800' : 'text-gray-900' }}">
                                            {{ $message->content }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ $message->created_at->format('M j, g:i a') }}
                                            @if($message->type === 'outgoing')
                                                · {{ $message->sender_type === 'ai' ? 'AI' : 'Manual' }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Message Input -->
                    <div class="border rounded-lg p-4">
                        <form onsubmit="sendMessage(event)" class="flex space-x-4">
                            <input 
                                type="text" 
                                id="messageInput"
                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                placeholder="Type your message..."
                            >
                            <button 
                                type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Send
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const conversationId = {{ $conversation->id }};
        let displayedMessageIds = new Set();

        // Initialize displayed message IDs
        @foreach($messages as $message)
            displayedMessageIds.add({{ $message->id }});
        @endforeach

        // Function to add a message to the chat
        function addMessageToChat(message) {
            if (displayedMessageIds.has(message.id)) {
                return; // Skip if already displayed
            }

            const container = document.querySelector('#messagesContainer .space-y-4');
            const messageElement = document.createElement('div');
            messageElement.className = `flex ${message.type === 'incoming' ? 'justify-start' : 'justify-end'}`;
            
            messageElement.innerHTML = `
                <div class="max-w-[70%] ${message.type === 'incoming' ? 'bg-white' : (message.sender_type === 'ai' ? 'bg-green-100' : 'bg-blue-100')} rounded-lg px-4 py-2 shadow">
                    <div class="text-sm ${message.type === 'incoming' ? 'text-gray-800' : 'text-gray-900'}">
                        ${message.content}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        ${message.created_at}
                        ${message.type === 'outgoing' ? ` · ${message.sender_type === 'ai' ? 'AI' : 'Manual'}` : ''}
                    </div>
                </div>
            `;

            container.appendChild(messageElement);
            displayedMessageIds.add(message.id);
            
            // Scroll to bottom
            const messagesContainer = document.getElementById('messagesContainer');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Function to fetch new messages
        async function fetchNewMessages() {
            try {
                const response = await fetch(`/conversations/${conversationId}/messages`);
                if (!response.ok) {
                    throw new Error('Failed to fetch messages');
                }
                const messages = await response.json();
                
                messages.forEach(message => {
                    if (!displayedMessageIds.has(message.id)) {
                        addMessageToChat(message);
                    }
                });
            } catch (error) {
                console.error('Error fetching messages:', error);
            }
        }

        // Poll for new messages every 2 seconds
        setInterval(fetchNewMessages, 2000);

        // Send message function
        window.sendMessage = async function(event) {
            event.preventDefault();
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            
            if (!message) {
                return;
            }

            const submitButton = event.target.querySelector('button[type="submit"]');
            submitButton.disabled = true;

            try {
                const response = await fetch(`/conversations/${conversationId}/messages`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ message })
                });

                if (!response.ok) {
                    throw new Error('Failed to send message');
                }

                const newMessage = await response.json();
                if (newMessage.error) {
                    throw new Error(newMessage.error);
                }

                messageInput.value = '';
                addMessageToChat(newMessage);
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to send message. Please try again.');
            } finally {
                submitButton.disabled = false;
            }
        };

        // Toggle response mode function
        window.toggleResponseMode = async function() {
            try {
                const response = await fetch(`/conversations/${conversationId}/toggle-mode`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    document.getElementById('currentMode').textContent = data.response_mode.charAt(0).toUpperCase() + data.response_mode.slice(1);
                    
                    // Update the mode in the header
                    const modeSpan = document.querySelector('h2 .text-gray-500');
                    modeSpan.textContent = `(${data.response_mode === 'ai' ? 'AI Mode' : 'Manual Mode'})`;
                }
            } catch (error) {
                console.error('Error toggling mode:', error);
            }
        };

        // Scroll to bottom on load
        document.addEventListener('DOMContentLoaded', () => {
            const messagesContainer = document.getElementById('messagesContainer');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        });
    </script>
</x-app-layout>
