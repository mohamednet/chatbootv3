<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Customer List</h2>
                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300" id="customer-count">
                            Total: {{ $customers->count() }}
                        </span>
                    </div>

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400" id="customers-table">
                            @include('customers.table')
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    
    <script>
        // Configure Toastr
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "3000"
        };

        let currentHash = '';
        let isUpdating = false;

        // Set up AJAX CSRF token
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        function updateCustomerList() {
            if (isUpdating) return;
            isUpdating = true;

            $.ajax({
                url: "{{ route('customers.updates') }}",
                method: 'GET',
                headers: {
                    'X-Data-Hash': currentHash
                },
                success: function(response) {
                    if (response.success) {
                        if (response.html) {
                            $('#customers-table').html(response.html);
                            $('#customer-count').text('Total: ' + response.count);
                            currentHash = response.hash;
                        }
                    }
                },
                complete: function() {
                    isUpdating = false;
                }
            });
        }

        $(document).ready(function() {
            // Update list every 5 seconds
            setInterval(updateCustomerList, 5000);
        });

        function openEmailModal(customerId) {
            const modal = document.getElementById(`email-modal-${customerId}`);
            if (modal) {
                modal.classList.remove('hidden');
                // Add animation classes
                modal.classList.add('animate-fadeIn');
                modal.querySelector('.modal-content')?.classList.add('animate-slideIn');
            }
        }

        function closeEmailModal(customerId) {
            const modal = document.getElementById(`email-modal-${customerId}`);
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        function openConfirmModal(customerId) {
            const modal = document.getElementById(`confirm-modal-${customerId}`);
            if (modal) {
                modal.classList.remove('hidden');
                // Add animation classes
                modal.classList.add('animate-fadeIn');
                modal.querySelector('.modal-content')?.classList.add('animate-slideIn');
            }
        }

        function closeConfirmModal(customerId) {
            const modal = document.getElementById(`confirm-modal-${customerId}`);
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        function confirmAndSendEmail(customerId) {
            closeConfirmModal(customerId);
            sendTrialEmail(customerId);
        }

        function sendTrialEmail(customerId) {
            const template = document.getElementById(`template-${customerId}`).value;
            const button = document.querySelector(`#email-modal-${customerId} button[onclick*="openConfirmModal"]`);
            const originalContent = button.innerHTML;
            
            // Disable button and show loading state
            button.disabled = true;
            button.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Sending...
            `;

            $.ajax({
                url: "{{ route('customers.send-trial', ['customer' => ':customerId']) }}".replace(':customerId', customerId),
                method: 'POST',
                data: {
                    template: template
                },
                success: function(response) {
                    toastr.success('Trial email sent successfully!', null, {
                        "positionClass": "toast-top-center",
                        "closeButton": true,
                        "progressBar": true,
                        "timeOut": "3000",
                        "extendedTimeOut": "1000",
                        "showEasing": "swing",
                        "hideEasing": "linear",
                        "showMethod": "fadeIn",
                        "hideMethod": "fadeOut"
                    });
                    closeEmailModal(customerId);
                    updateCustomerList();
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.error || 'Failed to send trial email. Please try again.', 'Error', {
                        "positionClass": "toast-top-center",
                        "closeButton": true,
                        "progressBar": true,
                        "timeOut": "5000",
                        "extendedTimeOut": "1000",
                        "showEasing": "swing",
                        "hideEasing": "linear",
                        "showMethod": "fadeIn",
                        "hideMethod": "fadeOut"
                    });
                },
                complete: function() {
                    // Restore button state
                    button.disabled = false;
                    button.innerHTML = originalContent;
                }
            });
        }
    </script>
    @endpush
</x-app-layout>
