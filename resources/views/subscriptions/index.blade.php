<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Subscriptions') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 flex justify-between items-center">
                        <h3 class="text-lg font-semibold">Active Subscriptions</h3>
                        <div class="text-sm text-gray-600">
                            Total Subscribers: {{ count($subscribers) }}
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID/Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subscription</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Devices</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($subscribers as $subscriber)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $subscriber['email'] ?? 'No email' }}</div>
                                            <div class="text-sm text-gray-500">ID: {{ $subscriber['facebook_id'] }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $subscriber['plan'] ?? 'N/A' }}</div>
                                            <div class="text-sm text-gray-500">{{ $subscriber['subscription_type'] ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">Count: {{ $subscriber['number_of_devices'] ?? '0' }}</div>
                                            <div class="text-xs text-gray-500 truncate max-w-xs" title="{{ $subscriber['subscription_id'] }}">
                                                {{ $subscriber['subscription_id'] ?? 'No devices' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">${{ $subscriber['amount'] ?? '0' }}</div>
                                            <div class="text-sm text-gray-500">{{ $subscriber['payment_method'] ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                Ends: {{ $subscriber['subscription_end_date'] ? date('Y-m-d', strtotime($subscriber['subscription_end_date'])) : 'N/A' }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                Last Payment: {{ $subscriber['last_payment_date'] ? date('Y-m-d', strtotime($subscriber['last_payment_date'])) : 'N/A' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($subscriber['conversation_id'])
                                                <a href="{{ route('chat.show', $subscriber['conversation_id']) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900">
                                                    View Conversation
                                                </a>
                                            @else
                                                <span class="text-gray-500">No conversation</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            No subscribers found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
