<x-app-layout>

    <div class="space-y-4 max-w-2xl">

        {{-- Update Profile Info --}}
        <div class="bg-panel border border-border rounded-lg p-6">
            @include('profile.partials.update-profile-information-form')
        </div>

        {{-- Update Password --}}
        <div class="bg-panel border border-border rounded-lg p-6">
            @include('profile.partials.update-password-form')
        </div>

        {{-- Delete Account --}}
        <div class="bg-panel border border-border rounded-lg p-6">
            @include('profile.partials.delete-user-form')
        </div>

    </div>

</x-app-layout>