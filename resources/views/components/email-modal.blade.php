@props(['customer'])

<div id="email-modal-{{ $customer->facebook_id }}" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full bg-gray-900 bg-opacity-50">
    <div class="relative w-full max-w-4xl max-h-full mx-auto mt-10">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow-2xl dark:bg-gray-700 transform transition-all">
            <!-- Modal header -->
            <div class="flex items-start justify-between p-5 border-b rounded-t dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-6 h-6 mr-2 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.2 8.4c.5.4.8 1 .8 1.6v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V10c0-.6.3-1.2.8-1.6L12 2l9.2 6.4Z"/>
                    </svg>
                    Send Trial Email
                </h3>
                <button type="button" onclick="closeEmailModal('{{ $customer->facebook_id }}')" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white transition-colors">
                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <!-- Modal body -->
            <div class="p-6 space-y-6">
                <div class="mb-6">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="email-{{ $customer->facebook_id }}">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.9 5.3a3 3 0 0 0 3.4 0L22 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z"/>
                            </svg>
                            Recipient Email:
                        </span>
                    </label>
                    <div class="relative">
                        <input type="email" id="email-{{ $customer->facebook_id }}" value="{{ $customer->email }}" 
                            class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" 
                            readonly>
                        <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-gray-500">
                            <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 17H3m-2-2 2 2 2-2m13-5H3m-2-2 2 2 2-2M16 5H3m-2-2 2 2 2-2"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="template-{{ $customer->facebook_id }}">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 0 0-2 2v4m5-6h8M8 7V5c0-1.1.9-2 2-2h4a2 2 0 0 1 2 2v2m0 0h3a2 2 0 0 1 2 2v4m0 0v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6m18 0H3"/>
                            </svg>
                            Email Template:
                        </span>
                    </label>
                    <div class="relative">
                        <textarea id="template-{{ $customer->facebook_id }}" rows="12" 
                            class="block p-4 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">Hi there!

Thank you for choosing our service. Here are your setup instructions for {{ $customer->device ?? 'your device' }}:

1. Download and install the app
2. Open the app and sign in with your credentials:
   Username: [Your Username]
   Password: [Your Password]
3. Go to Settings > Add Source
4. Enter the following URL: [IPTV Source URL]
5. Click Save and enjoy your content!

If you need any assistance, please don't hesitate to contact us.

Best regards,
Your IPTV Support Team</textarea>
                    </div>
                </div>
            </div>
            <!-- Modal footer -->
            <div class="flex items-center justify-end p-6 space-x-2 border-t border-gray-200 rounded-b dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                <button type="button" onclick="closeEmailModal('{{ $customer->facebook_id }}')" 
                    class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600 transition-colors">
                    Cancel
                </button>
                <button type="button" onclick="openConfirmModal('{{ $customer->facebook_id }}')"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 transition-colors inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.5 11.5 8.9 9M11.5 11.5l2.6 2.5M11.5 11.5l2.6-2.5M11.5 11.5 8.9 14m7.6-8.5L12.9 3h-1.8L7.5 5.5m11 3L12.9 21h-1.8L5.5 8.5m13 0h-13"/>
                    </svg>
                    Send Email
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirm-modal-{{ $customer->facebook_id }}" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-[60] hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full bg-gray-900 bg-opacity-50">
    <div class="relative w-full max-w-md max-h-full mx-auto mt-10">
        <div class="relative bg-white rounded-lg shadow-2xl dark:bg-gray-700 transform transition-all">
            <div class="p-6 text-center">
                <div class="flex items-center justify-center mb-4">
                    <div class="relative">
                        <div class="animate-ping absolute inline-flex h-12 w-12 rounded-full bg-blue-400 opacity-25"></div>
                        <svg class="relative text-blue-600 w-12 h-12 dark:text-blue-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.2 8.4c.5.4.8 1 .8 1.6v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V10c0-.6.3-1.2.8-1.6L12 2l9.2 6.4Z"/>
                        </svg>
                    </div>
                </div>
                <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
                    Are you sure you want to send this trial email to<br>
                    <span class="font-semibold text-gray-800 dark:text-white mt-2 block">{{ $customer->email }}</span>
                </h3>
                <div class="flex justify-center gap-4">
                    <button type="button" onclick="confirmAndSendEmail('{{ $customer->facebook_id }}')"
                        class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center mr-2 dark:bg-blue-500 dark:hover:bg-blue-600 dark:focus:ring-blue-800 transition-all duration-200 hover:scale-105">
                        <svg class="w-4 h-4 mr-2 -ml-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4.7 4.5 9.3-9"/>
                        </svg>
                        Yes, send it
                    </button>
                    <button type="button" onclick="closeConfirmModal('{{ $customer->facebook_id }}')"
                        class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600 transition-all duration-200 hover:scale-105">
                        <svg class="w-4 h-4 mr-2 -ml-1 inline" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6m0 12L6 6"/>
                        </svg>
                        No, cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
