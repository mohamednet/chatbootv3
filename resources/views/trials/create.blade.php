<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Trial') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('trials.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="username" :value="__('Username')" />
                            <x-text-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username')" required autofocus />
                            <x-input-error :messages="$errors->get('username')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="password" :value="__('Password')" />
                            <x-text-input id="password" class="block mt-1 w-full" type="text" name="password" :value="old('password')" required />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="url" :value="__('URL')" />
                            <x-text-input id="url" class="block mt-1 w-full" type="text" name="url" :value="old('url')" required />
                            <x-input-error :messages="$errors->get('url')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="m3u_link" :value="__('M3U Link')" />
                            <x-text-input id="m3u_link" class="block mt-1 w-full" type="text" name="m3u_link" :value="old('m3u_link')" required />
                            <x-input-error :messages="$errors->get('m3u_link')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('trials.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</a>
                            <x-primary-button>
                                {{ __('Create Trial') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
