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
    <script>
        $(document).ready(function() {
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
                                console.log('Customer list updated at:', new Date().toLocaleTimeString());
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error updating customer list:', error);
                    },
                    complete: function() {
                        isUpdating = false;
                    }
                });
            }

            // Update every 2 seconds
            setInterval(updateCustomerList, 2000);

            // Initial update
            updateCustomerList();
        });
    </script>
    @endpush
</x-app-layout>
