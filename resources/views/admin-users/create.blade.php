<x-app-layout>
    <x-slot name="title">Add Admin User</x-slot>

    <div class="max-w-lg">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <form method="POST" action="{{ route('admin-users.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Full name" required autofocus>
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="admin@example.com" required>
                    @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Minimum 8 characters" required>
                    @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password_confirmation"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Re-enter password" required>
                </div>

                {{-- Access level --}}
                <div x-data="{ superAdmin: '1' }" class="border-t border-gray-100 pt-5 space-y-3">
                    <p class="text-sm font-medium text-gray-700">Access Level</p>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" name="is_super_admin" value="1" x-model="superAdmin"
                               class="mt-0.5 text-indigo-600 border-gray-300" checked>
                        <div>
                            <p class="text-sm font-medium text-gray-800">Super Admin</p>
                            <p class="text-xs text-gray-400">Full access to all sections.</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" name="is_super_admin" value="0" x-model="superAdmin"
                               class="mt-0.5 text-indigo-600 border-gray-300">
                        <div>
                            <p class="text-sm font-medium text-gray-800">Limited Admin</p>
                            <p class="text-xs text-gray-400">Restricted to selected sections only.</p>
                        </div>
                    </label>

                    <div x-show="superAdmin === '0'" class="ml-6 space-y-2 pt-1">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="permissions[]" value="schedules"
                                   {{ in_array('schedules', old('permissions', [])) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600">
                            <span>Schedules — upload schedules, manage employee schedules</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="permissions[]" value="announcements"
                                   {{ in_array('announcements', old('permissions', [])) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600">
                            <span>Announcements — create, edit, and delete company announcements</span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('admin-users.index') }}"
                       class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">Cancel</a>
                    <button type="submit"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                        Create Admin User
                    </button>
                </div>
            </form>
        </div>
    </div>

</x-app-layout>
