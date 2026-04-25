<x-app-layout>
    <x-slot name="title">Edit Announcement</x-slot>

    <div class="max-w-3xl">

        <div class="flex items-center gap-3 mb-5">
            <a href="{{ route('announcements.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-xl font-bold text-gray-900">Edit Announcement</h1>
            @if($announcement->status === 'published')
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Published</span>
            @elseif($announcement->status === 'scheduled')
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Scheduled</span>
            @else
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Draft</span>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <form method="POST" action="{{ route('announcements.update', $announcement) }}">
                @csrf @method('PUT')

                {{-- Subject --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject <span class="text-red-500">*</span></label>
                    <input type="text" name="subject" value="{{ old('subject', $announcement->subject) }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                           required autofocus>
                    @error('subject')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                {{-- Body editor --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Body <span class="text-red-500">*</span></label>

                    <script>
                    function announcementEditor() {
                        return {
                            savedRange: null,

                            init() {
                                document.execCommand('defaultParagraphSeparator', false, 'p');
                                const tpl = document.getElementById('editor-initial');
                                const content = tpl ? tpl.innerHTML.trim() : '';
                                this.$refs.editorEl.innerHTML = content || '<p><br></p>';
                                this.$refs.bodyField.value = this.$refs.editorEl.innerHTML;

                                // Continuously track selection so we can restore it after a
                                // toolbar button click moves focus away from the editor.
                                document.addEventListener('selectionchange', () => {
                                    const sel = window.getSelection();
                                    if (sel && sel.rangeCount > 0) {
                                        const range = sel.getRangeAt(0);
                                        if (this.$refs.editorEl.contains(range.commonAncestorContainer)) {
                                            this.savedRange = range.cloneRange();
                                        }
                                    }
                                });
                            },

                            sync() {
                                this.$refs.bodyField.value = this.$refs.editorEl.innerHTML;
                            },

                            restoreSelection() {
                                this.$refs.editorEl.focus();
                                if (!this.savedRange) {
                                    return;
                                }

                                const sel = window.getSelection();
                                sel.removeAllRanges();
                                sel.addRange(this.savedRange.cloneRange());
                            },

                            exec(cmd, value = null) {
                                this.restoreSelection();
                                if (cmd === 'formatBlock' && value && !/^</.test(value)) {
                                    value = '<' + value + '>';
                                }
                                document.execCommand(cmd, false, value);
                                this.sync();
                            },

                            insertLink() {
                                const url = prompt('Enter URL (include https://):');
                                if (!url) return;
                                this.restoreSelection();
                                document.execCommand('createLink', false, url);
                                this.sync();
                            },

                            async uploadImage(e) {
                                const file = e.target.files[0];
                                if (!file) return;
                                const fd = new FormData();
                                fd.append('image', file);
                                fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
                                try {
                                    const r = await fetch('{{ route('announcements.upload-image') }}', { method: 'POST', body: fd });
                                    if (!r.ok) throw new Error();
                                    const d = await r.json();
                                    this.restoreSelection();
                                    document.execCommand('insertImage', false, d.url);
                                    this.sync();
                                } catch { alert('Image upload failed. Please try again.'); }
                                e.target.value = '';
                            },
                        }
                    }
                    </script>

                    {{-- x-data wraps editor + textarea so $refs.bodyField is in scope --}}
                    <div x-data="announcementEditor()">
                        <div class="border border-gray-300 rounded-lg overflow-hidden focus-within:ring-1 focus-within:ring-indigo-500 focus-within:border-indigo-500">
                            {{-- Toolbar: @mousedown.prevent keeps focus + selection in the editor; command runs immediately --}}
                            <div class="flex flex-wrap items-center gap-0.5 px-2 py-1.5 bg-gray-50 border-b border-gray-200">
                                <button type="button" @mousedown.prevent="exec('bold')"
                                        class="px-2 py-1 text-sm font-bold text-gray-700 hover:bg-gray-200 rounded transition" title="Bold">B</button>
                                <button type="button" @mousedown.prevent="exec('italic')"
                                        class="px-2 py-1 text-sm italic text-gray-700 hover:bg-gray-200 rounded transition" title="Italic">I</button>
                                <button type="button" @mousedown.prevent="exec('underline')"
                                        class="px-2 py-1 text-sm underline text-gray-700 hover:bg-gray-200 rounded transition" title="Underline">U</button>
                                <div class="w-px h-5 bg-gray-300 mx-1"></div>
                                <button type="button" @mousedown.prevent="exec('formatBlock', 'h2')"
                                        class="px-2 py-1 text-xs font-bold text-gray-700 hover:bg-gray-200 rounded transition" title="Heading">H2</button>
                                <button type="button" @mousedown.prevent="exec('formatBlock', 'h3')"
                                        class="px-2 py-1 text-xs font-bold text-gray-700 hover:bg-gray-200 rounded transition" title="Subheading">H3</button>
                                <div class="w-px h-5 bg-gray-300 mx-1"></div>
                                <button type="button" @mousedown.prevent="exec('insertUnorderedList')"
                                        class="px-2 py-1 text-sm text-gray-700 hover:bg-gray-200 rounded transition" title="Bullet list">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                </button>
                                <button type="button" @mousedown.prevent="exec('insertOrderedList')"
                                        class="px-2 py-1 text-sm text-gray-700 hover:bg-gray-200 rounded transition" title="Numbered list">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h11M9 12h11M9 19h11M4 5v.01M4 12v.01M4 19v.01"/></svg>
                                </button>
                                <div class="w-px h-5 bg-gray-300 mx-1"></div>
                                <button type="button" @mousedown.prevent="exec('justifyLeft')"
                                        class="px-2 py-1 text-sm text-gray-700 hover:bg-gray-200 rounded transition" title="Align left">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M3 10h12M3 14h18M3 18h12"/></svg>
                                </button>
                                <button type="button" @mousedown.prevent="exec('justifyCenter')"
                                        class="px-2 py-1 text-sm text-gray-700 hover:bg-gray-200 rounded transition" title="Center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M6 10h12M3 14h18M6 18h12"/></svg>
                                </button>
                                <button type="button" @mousedown.prevent="exec('justifyRight')"
                                        class="px-2 py-1 text-sm text-gray-700 hover:bg-gray-200 rounded transition" title="Align right">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M9 10h12M3 14h18M9 18h12"/></svg>
                                </button>
                                <button type="button" @mousedown.prevent="exec('justifyFull')"
                                        class="px-2 py-1 text-sm text-gray-700 hover:bg-gray-200 rounded transition" title="Justify">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M3 10h18M3 14h18M3 18h18"/></svg>
                                </button>
                                <div class="w-px h-5 bg-gray-300 mx-1"></div>
                                <button type="button" @mousedown.prevent @click="insertLink()"
                                        class="px-2 py-1 text-sm text-gray-700 hover:bg-gray-200 rounded transition" title="Insert link">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                </button>
                                <label class="px-2 py-1 text-sm text-gray-700 hover:bg-gray-200 rounded transition cursor-pointer" title="Insert image">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <input type="file" @change="uploadImage($event)" accept="image/*" class="hidden">
                                </label>
                            </div>

                            {{-- Editable area --}}
                            <div x-ref="editorEl"
                                 contenteditable="true"
                                 @input="sync()"
                                 data-gramm="false"
                                 data-gramm_editor="false"
                                 data-enable-grammarly="false"
                                 class="min-h-48 p-4 text-sm text-gray-800 focus:outline-none announcement-body"></div>
                        </div>

                        <template id="editor-initial">{!! old('body', $announcement->body) !!}</template>
                        <textarea name="body" x-ref="bodyField" class="hidden"></textarea>
                    </div>

                    @error('body')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                {{-- Send option (only for non-published) --}}
                @if($announcement->status !== 'published')
                <div x-data="{ action: '{{ old('action', $announcement->status === 'scheduled' ? 'schedule' : 'draft') }}', scheduledAt: '{{ old('scheduled_at', $announcement->scheduled_at?->format('Y-m-d\TH:i')) }}' }"
                     class="border-t border-gray-100 pt-5 space-y-3">
                    <p class="text-sm font-medium text-gray-700">Send Option</p>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" x-model="action" value="publish" class="mt-0.5 text-indigo-600 border-gray-300">
                        <div>
                            <p class="text-sm font-medium text-gray-800">Publish now</p>
                            <p class="text-xs text-gray-400">Sends push notifications to all staff immediately.</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" x-model="action" value="schedule" class="mt-0.5 text-indigo-600 border-gray-300">
                        <div>
                            <p class="text-sm font-medium text-gray-800">Schedule</p>
                            <p class="text-xs text-gray-400">Publish at a specific date and time.</p>
                        </div>
                    </label>

                    <div x-show="action === 'schedule'" class="ml-7 pt-1">
                        <input type="datetime-local" name="scheduled_at" x-model="scheduledAt"
                               class="rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('scheduled_at')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" x-model="action" value="draft" class="mt-0.5 text-indigo-600 border-gray-300">
                        <div>
                            <p class="text-sm font-medium text-gray-800">Keep as draft</p>
                            <p class="text-xs text-gray-400">Won't be visible to staff yet.</p>
                        </div>
                    </label>

                    <input type="hidden" name="action" :value="action">
                </div>
                @endif

                <div class="flex items-center justify-end gap-3 pt-5 border-t border-gray-100 mt-5">
                    <a href="{{ route('announcements.index') }}"
                       class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">Cancel</a>
                    <button type="submit"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                        Save Changes
                    </button>
                </div>

            </form>
        </div>

    </div>

    @push('styles')
    <style>
        .announcement-body h2 { font-size: 1.125rem; font-weight: 700; margin: .75rem 0 .25rem; }
        .announcement-body h3 { font-size: 1rem; font-weight: 600; margin: .5rem 0 .25rem; }
        .announcement-body ul { list-style: disc !important; padding-left: 1.5rem !important; margin: .25rem 0; }
        .announcement-body ol { list-style: decimal !important; padding-left: 1.5rem !important; margin: .25rem 0; }
        .announcement-body li { display: list-item !important; }
        .announcement-body a  { color: #4f46e5; text-decoration: underline; }
        .announcement-body img { max-width: 100%; border-radius: .375rem; margin: .5rem 0; }
        .announcement-body p  { margin: .25rem 0; }
    </style>
    @endpush

</x-app-layout>
