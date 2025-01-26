<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-2xl font-semibold mb-4">Customer Conversations</h2>
                    
                    <div class="space-y-4" id="conversationsContainer">
                        @forelse ($conversations as $conversation)
                            <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors" id="conversation-{{ $conversation->id }}">
                                <a href="{{ route('chat.show', $conversation) }}" class="block">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <h3 class="text-lg font-medium">
                                                Customer #{{ $conversation->facebook_user_id }}
                                            </h3>
                                            <p class="text-sm text-gray-600">
                                                {{ $conversation->latestMessage?->content ?? 'No messages yet' }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $conversation->response_mode === 'ai' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                                {{ ucfirst($conversation->response_mode) }} Mode
                                            </span>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ $conversation->last_message_at?->diffForHumans() ?? 'Never' }}
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-500">
                                No conversations yet
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-4">
                        {{ $conversations->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const conversationsContainer = document.getElementById('conversationsContainer');
            
            let lastUpdate = new Date().toISOString();
            let pollInterval;
            let displayedConversations = new Set(); // Track displayed conversations

            function startPolling() {
                if (pollInterval) {
                    clearInterval(pollInterval);
                }
                pollInterval = setInterval(fetchUpdates, 500);
            }

            async function fetchUpdates() {
                try {
                    const response = await fetch(`/conversations/updates?since=${encodeURIComponent(lastUpdate)}`, {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();
                    
                    if (data.conversations && data.conversations.length > 0) {
                        // Update or add each conversation
                        data.conversations.forEach(conversation => {
                            const conversationId = `conversation-${conversation.id}`;
                            const existingConversation = document.getElementById(conversationId);
                            
                            // Create the HTML for the conversation
                            const conversationHtml = `
                                <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors" id="${conversationId}">
                                    <a href="/conversations/${conversation.id}" class="block">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <h3 class="text-lg font-medium">
                                                    Customer #${conversation.facebook_user_id}
                                                </h3>
                                                <p class="text-sm text-gray-600">
                                                    ${conversation.latest_message || 'No messages yet'}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${conversation.response_mode === 'ai' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'}">
                                                    ${conversation.response_mode.charAt(0).toUpperCase() + conversation.response_mode.slice(1)} Mode
                                                </span>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    ${conversation.last_message_at_human}
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            `;

                            if (existingConversation) {
                                // Only update if content has changed
                                if (existingConversation.innerHTML !== conversationHtml) {
                                    existingConversation.outerHTML = conversationHtml;
                                }
                            } else if (!displayedConversations.has(conversation.id)) {
                                // Add new conversation only if not already displayed
                                displayedConversations.add(conversation.id);
                                conversationsContainer.insertAdjacentHTML('afterbegin', conversationHtml);
                            }
                        });

                        lastUpdate = data.current_time;
                    }
                } catch (error) {
                    console.error('Error fetching updates:', error);
                }
            }

            // Start polling when page loads
            startPolling();

            // Clean up on page unload
            window.addEventListener('beforeunload', function() {
                if (pollInterval) {
                    clearInterval(pollInterval);
                }
            });
        });
    </script>
</x-app-layout>
