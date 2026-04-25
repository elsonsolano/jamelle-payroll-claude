<x-app-layout>
    <x-slot name="title">Edit Admin User</x-slot>

    <div class="max-w-lg">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <form method="POST" action="{{ route('admin-users.update', $user) }}" class="space-y-5">
                @csrf @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                           required autofocus>
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                           required>
                    @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="border-t border-gray-100 pt-5">
                    <p class="text-xs text-gray-500 mb-4">Leave password fields blank to keep the current password.</p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <input type="password" name="password"
                                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                   placeholder="Minimum 8 characters">
                            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                            <input type="password" name="password_confirmation"
                                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                   placeholder="Re-enter new password">
                        </div>
                    </div>
                </div>

                {{-- Access level (cannot change your own) --}}
                @if($user->id !== Auth::id())
                <div x-data="{ superAdmin: '{{ $user->isSuperAdmin() ? '1' : '0' }}' }" class="border-t border-gray-100 pt-5 space-y-3">
                    <p class="text-sm font-medium text-gray-700">Access Level</p>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" name="is_super_admin" value="1" x-model="superAdmin"
                               class="mt-0.5 text-indigo-600 border-gray-300"
                               {{ $user->isSuperAdmin() ? 'checked' : '' }}>
                        <div>
                            <p class="text-sm font-medium text-gray-800">Super Admin</p>
                            <p class="text-xs text-gray-400">Full access to all sections.</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" name="is_super_admin" value="0" x-model="superAdmin"
                               class="mt-0.5 text-indigo-600 border-gray-300"
                               {{ !$user->isSuperAdmin() ? 'checked' : '' }}>
                        <div>
                            <p class="text-sm font-medium text-gray-800">Limited Admin</p>
                            <p class="text-xs text-gray-400">Restricted to selected sections only.</p>
                        </div>
                    </label>

                    <div x-show="superAdmin === '0'" class="ml-6 space-y-2 pt-1">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="permissions[]" value="schedules"
                                   {{ in_array('schedules', old('permissions', $user->permissions ?? [])) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600">
                            <span>Schedules — upload schedules, manage employee schedules</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="permissions[]" value="announcements"
                                   {{ in_array('announcements', old('permissions', $user->permissions ?? [])) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600">
                            <span>Announcements — create, edit, and delete company announcements</span>
                        </label>
                    </div>
                </div>
                @else
                <div class="border-t border-gray-100 pt-5">
                    <p class="text-xs text-gray-400">Access level cannot be changed for your own account.</p>
                </div>
                @endif

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('admin-users.index') }}"
                       class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">Cancel</a>
                    <button type="submit"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

</x-app-layout>
