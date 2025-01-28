<thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
    <tr>
        <th scope="col" class="px-6 py-3">Facebook ID</th>
        <th scope="col" class="px-6 py-3">Email</th>
        <th scope="col" class="px-6 py-3">Device</th>
        <th scope="col" class="px-6 py-3">App</th>
        <th scope="col" class="px-6 py-3">Response Mode</th>
        <th scope="col" class="px-6 py-3">Last Active</th>
        <th scope="col" class="px-6 py-3">Trial Status</th>
        <th scope="col" class="px-6 py-3">Actions</th>
    </tr>
</thead>
<tbody>
    @forelse($customers as $customer)
        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
            <td class="px-6 py-4">{{ $customer->facebook_id }}</td>
            <td class="px-6 py-4">{{ $customer->email ?? 'N/A' }}</td>
            <td class="px-6 py-4">{{ $customer->device ?? 'N/A' }}</td>
            <td class="px-6 py-4">{{ $customer->app ?? 'N/A' }}</td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs rounded-full {{ $customer->response_mode === 'ai' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                    {{ ucfirst($customer->response_mode) }}
                </span>
            </td>
            <td class="px-6 py-4">{{ $customer->last_message_at ? \Carbon\Carbon::parse($customer->last_message_at)->diffForHumans() : 'Never' }}</td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs rounded-full {{ $customer->trial_status === 'Sent' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ $customer->trial_status }}
                </span>
            </td>
            <td class="px-6 py-4 space-x-2">
                <a href="{{ route('chat.show', $customer->conversation_id) }}" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">View Chat</a>
                <button 
                    onclick="openEmailModal('{{ $customer->facebook_id }}')"
                    class="font-medium {{ $customer->email ? 'text-green-600 dark:text-green-500 hover:underline' : 'text-gray-400 cursor-not-allowed' }}"
                    {{ !$customer->email ? 'disabled' : '' }}
                    title="{{ !$customer->email ? 'Email required to send trial' : 'Send trial email' }}"
                >
                    Send Email
                </button>
            </td>
        </tr>
        <x-email-modal :customer="$customer" />
    @empty
        <tr>
            <td colspan="8" class="px-6 py-4 text-center">No customers found</td>
        </tr>
    @endforelse
</tbody>
