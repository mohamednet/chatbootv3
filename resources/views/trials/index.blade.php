<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Trials List</h2>
                        <div class="flex items-center gap-4">
                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">
                                Total: {{ $trials->count() }}
                            </span>
                            <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition duration-150 ease-in-out flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Trial
                            </button>
                        </div>
                    </div>

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Username</th>
                                    <th scope="col" class="px-6 py-3">Password</th>
                                    <th scope="col" class="px-6 py-3">URL</th>
                                    <th scope="col" class="px-6 py-3">M3U Link</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($trials as $trial)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <td class="px-6 py-4">{{ $trial->username }}</td>
                                        <td class="px-6 py-4">{{ $trial->password }}</td>
                                        <td class="px-6 py-4">
                                            <a href="{{ $trial->url }}" target="_blank" class="text-blue-600 dark:text-blue-500 hover:underline" title="{{ $trial->url }}">
                                                {{ \Illuminate\Support\Str::limit($trial->url, 15) }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="{{ $trial->m3u_link }}" target="_blank" class="text-blue-600 dark:text-blue-500 hover:underline" title="{{ $trial->m3u_link }}">
                                                {{ \Illuminate\Support\Str::limit($trial->m3u_link, 15) }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($trial->assigned_user)
                                                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                                    {{ $trial->assigned_user }}
                                                </span>
                                            @else
                                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                    Available
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 space-x-2">
                                            <button onclick="openEditModal({{ $trial->id }})" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
                                                Edit
                                            </button>
                                            <form action="{{ route('trials.destroy', $trial) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-medium text-red-600 dark:text-red-500 hover:underline" onclick="return confirm('Are you sure you want to delete this trial?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center">No trials found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $trials->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 transition-opacity hidden backdrop-blur-sm" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-xl bg-white dark:bg-gray-900 w-full max-w-md shadow-2xl transition-all">
                    <!-- Modal Header -->
                    <div class="relative border-b dark:border-gray-700">
                        <div class="flex items-center justify-between p-4">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Create New Trial</h3>
                            <button type="button" onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                <span class="sr-only">Close</span>
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6">
                        <form id="createForm" method="POST" action="{{ route('trials.store') }}" class="space-y-6">
                            @csrf
                            <div class="space-y-5">
                                <div>
                                    <label for="username" class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Username</label>
                                    <input type="text" name="username" id="username" required
                                        class="block w-full px-4 py-2.5 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent">
                                </div>

                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Password</label>
                                    <input type="text" name="password" id="password" required
                                        class="block w-full px-4 py-2.5 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent">
                                </div>

                                <div>
                                    <label for="url" class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">URL</label>
                                    <input type="text" name="url" id="url" required
                                        class="block w-full px-4 py-2.5 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent">
                                </div>

                                <div>
                                    <label for="m3u_link" class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">M3U Link</label>
                                    <input type="text" name="m3u_link" id="m3u_link" required
                                        class="block w-full px-4 py-2.5 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent">
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 mt-8">
                                <button type="button" onclick="closeCreateModal()"
                                    class="w-full sm:w-auto px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-200 dark:focus:ring-gray-600 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="w-full sm:w-auto px-4 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors inline-flex items-center justify-center">
                                    Create Trial
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 transition-opacity hidden backdrop-blur-sm" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-xl bg-white dark:bg-gray-900 w-full max-w-md shadow-2xl transition-all">
                    <!-- Modal Header -->
                    <div class="relative border-b dark:border-gray-700">
                        <div class="flex items-center justify-between p-4">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Edit Trial</h3>
                            <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                <span class="sr-only">Close</span>
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6">
                        <form id="editForm" method="POST" class="space-y-6">
                            @csrf
                            @method('PUT')
                            <div class="space-y-5">
                                <div>
                                    <label for="edit_username" class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Username</label>
                                    <input type="text" name="username" id="edit_username" required
                                        class="block w-full px-4 py-2.5 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent">
                                </div>

                                <div>
                                    <label for="edit_password" class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Password</label>
                                    <input type="text" name="password" id="edit_password" required
                                        class="block w-full px-4 py-2.5 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent">
                                </div>

                                <div>
                                    <label for="edit_url" class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">URL</label>
                                    <input type="text" name="url" id="edit_url" required
                                        class="block w-full px-4 py-2.5 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent">
                                </div>

                                <div>
                                    <label for="edit_m3u_link" class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">M3U Link</label>
                                    <input type="text" name="m3u_link" id="edit_m3u_link" required
                                        class="block w-full px-4 py-2.5 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent">
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 mt-8">
                                <button type="button" onclick="closeEditModal()"
                                    class="w-full sm:w-auto px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-200 dark:focus:ring-gray-600 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="w-full sm:w-auto px-4 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors inline-flex items-center justify-center">
                                    Update Trial
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div id="successMessage" class="fixed top-4 right-4 flex items-center p-4 space-x-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg border-l-4 border-green-500 transform transition-all duration-500 ease-out translate-x-0 opacity-100 z-50">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1 text-sm font-medium text-gray-900 dark:text-white">
                {{ session('success') }}
            </div>
            <button onclick="closeSuccessMessage()" class="flex-shrink-0 ml-4 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500 rounded-full p-1">
                <span class="sr-only">Close</span>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <script>
            function closeSuccessMessage() {
                const message = document.getElementById('successMessage');
                message.style.opacity = '0';
                message.style.transform = 'translateX(100%)';
                setTimeout(() => message.remove(), 500);
            }

            // Auto-hide success message
            setTimeout(() => {
                const message = document.getElementById('successMessage');
                if (message) {
                    message.style.opacity = '0';
                    message.style.transform = 'translateX(100%)';
                    setTimeout(() => message.remove(), 500);
                }
            }, 3000);
        </script>
    @endif

    @push('scripts')
    <script>
        function openCreateModal() {
            document.getElementById('createModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function openEditModal(id) {
            fetch(`/trials/${id}/edit`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_username').value = data.username;
                    document.getElementById('edit_password').value = data.password;
                    document.getElementById('edit_url').value = data.url;
                    document.getElementById('edit_m3u_link').value = data.m3u_link;
                    document.getElementById('editForm').action = `/trials/${id}`;
                    document.getElementById('editModal').classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                });
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            let createModal = document.getElementById('createModal');
            let editModal = document.getElementById('editModal');
            if (event.target.classList.contains('fixed')) {
                if (event.target === createModal) {
                    closeCreateModal();
                }
                if (event.target === editModal) {
                    closeEditModal();
                }
            }
        }

        // Close modals on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeCreateModal();
                closeEditModal();
            }
        });

        // Auto-hide success message
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.querySelector('[role="alert"]');
            if (alert) {
                setTimeout(() => {
                    alert.style.transition = 'opacity 1s ease-out';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 1000);
                }, 3000);
            }
        });
    </script>
    @endpush
</x-app-layout>
