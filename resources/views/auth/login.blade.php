<x-guest-layout>

            <!-- Logo -->
            <div class="flex items-center gap-2 mb-6">
                <div class="w-6 h-6 rounded bg-[#3b82f6] flex items-center justify-center">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                        <rect x="2" y="2" width="5" height="5" rx="1" fill="white"/>
                        <rect x="9" y="2" width="5" height="5" rx="1" fill="white" opacity=".6"/>
                        <rect x="2" y="9" width="5" height="5" rx="1" fill="white" opacity=".6"/>
                        <rect x="9" y="9" width="5" height="5" rx="1" fill="white" opacity=".3"/>
                    </svg>
                </div>

                <div class="text-sm font-medium text-[#e5e7eb]">
                    system<span class="text-gray-500">-</span>monitoring
                </div>
            </div>

            <!-- Title -->
            <h2 class="text-lg font-semibold text-white mb-1">
                Welcome back
            </h2>
            <p class="text-sm text-gray-500 mb-6">
                Sign in to your workspace
            </p>

            <!-- Session status -->
            <x-auth-session-status class="mb-4 text-sm text-green-500" :status="session('status')" />

            <!-- Form -->
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <!-- Email -->
                <div>
                    <label class="text-xs text-gray-400">Email</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="input w-full rounded-md px-3 py-2 text-sm text-gray-300 bg-[#111111] border-[#2a2a2a]"
                    />
                    <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs text-red-500" />
                </div>

                <!-- Password -->
                <div>
                    <label class="text-xs text-gray-400">Password</label>
                    <input
                        type="password"
                        name="password"
                        required
                        class="input w-full rounded-md px-3 py-2 text-sm text-gray-300 bg-[#111111] border-[#2a2a2a]"
                    />
                    <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs text-red-500" />
                </div>

                <!-- Remember -->
                <label class="flex items-center gap-2 text-xs text-gray-400">
                    <input type="checkbox" name="remember" class="bg-[#111111] border-[#2a2a2a]">
                    Remember me
                </label>

                <!-- Button -->
                <button
                    type="submit"
                    class="btn-primary w-full text-white text-sm font-medium py-2 rounded-md transition duration-200 hover:bg-[#3b82f6]/90 bg-[#3b82f6]"
                >
                    Sign in
                </button>

            </form>
</x-guest-layout>