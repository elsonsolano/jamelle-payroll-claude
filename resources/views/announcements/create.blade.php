<x-app-layout>
    <x-slot name="title">New Announcement</x-slot>

    <div class="max-w-3xl">

        <div class="flex items-center gap-3 mb-5">
            <a href="{{ route('announcements.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-xl font-bold text-gray-900">New Announcement</h1>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <form method="POST" action="{{ route('announcements.store') }}">
                @csrf

                {{-- Subject --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject <span class="text-red-500">*</span></label>
                    <input type="text" name="subject" value="{{ old('subject') }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Announcement subject" required autofocus>
                    @error('subject')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                {{-- Body editor --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Body <span class="text-red-500">*</span></label>

                    <script>
                    function announcementEditor() {
                        return {
                            savedRange: null,
                            selectedImage: null,

                            init() {
                                document.execCommand('defaultParagraphSeparator', false, 'p');
                                const tpl = document.getElementById('editor-initial');
                                const content = tpl ? tpl.innerHTML.trim() : '';
                                this.$refs.editorEl.innerHTML = content || '<p><br></p>';
                                this.$refs.bodyField.value = this.$refs.editorEl.innerHTML;

                                this.$refs.editorEl.addEventListener('click', (e) => {
                                    if (e.target.tagName === 'IMG') {
                                        this.selectImage(e.target);
                                        return;
                                    }

                                    this.clearImageSelection();
                                });

                                this.$refs.editorEl.addEventListener('keydown', (e) => {
                                    if (!this.selectedImage || !['Backspace', 'Delete'].includes(e.key)) {
                                        return;
                                    }

                                    e.preventDefault();
                                    this.removeSelectedImage();
                                });

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
                                const content = this.$refs.editorEl.cloneNode(true);
                                content.querySelectorAll('img').forEach((image) => {
                                    image.classList.remove('ring-2', 'ring-red-400', 'ring-offset-2');
                                });
                                this.$refs.bodyField.value = content.innerHTML;
                            },

                            selectImage(image) {
                                this.clearImageSelection();
                                this.selectedImage = image;
                                image.classList.add('ring-2', 'ring-red-400', 'ring-offset-2');
                            },

                            clearImageSelection() {
                                if (this.selectedImage) {
                                    this.selectedImage.classList.remove('ring-2', 'ring-red-400', 'ring-offset-2');
                                }

                                this.selectedImage = null;
                            },

                            removeSelectedImage() {
                                if (!this.selectedImage) {
                                    return;
                                }

                                const image = this.selectedImage;
                                this.clearImageSelection();
                                image.remove();
                                this.sync();
                            },

                            alignSelectedImage(cmd) {
                                if (!this.selectedImage) {
                                    return false;
                                }

                                const alignments = {
                                    justifyLeft: 'left',
                                    justifyCenter: 'center',
                                    justifyRight: 'right',
                                    justifyFull: 'justify',
                                };

                                if (!alignments[cmd]) {
                                    return false;
                                }

                                const block = this.selectedImage.closest('p, div, h2, h3, li') || this.selectedImage.parentElement;
                                if (block && this.$refs.editorEl.contains(block)) {
                                    block.style.textAlign = alignments[cmd];
                                    this.sync();
                                }

                                return true;
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
                                if (this.alignSelectedImage(cmd)) {
                                    return;
                                }

                                this.clearImageSelection();
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
                                this.clearImageSelection();
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
                                    this.clearImageSelection();
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
                                <button type="button" x-show="selectedImage" @mousedown.prevent @click="removeSelectedImage()"
                                        class="px-2 py-1 text-sm text-red-600 hover:bg-red-50 rounded transition" title="Remove selected image">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-8 0h10"/></svg>
                                </button>
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

                        <template id="editor-initial">{!! old('body', '') !!}</template>
                        <textarea name="body" x-ref="bodyField" class="hidden"></textarea>
                    </div>

                    @error('body')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                {{-- Send option --}}
                <div x-data="{ action: '{{ old('action', 'publish') }}', scheduledAt: '{{ old('scheduled_at') }}' }"
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
                            <p class="text-sm font-medium text-gray-800">Save as draft</p>
                            <p class="text-xs text-gray-400">Won't be visible to staff yet.</p>
                        </div>
                    </label>

                    <input type="hidden" name="action" :value="action">
                </div>

                <div class="flex items-center justify-end gap-3 pt-5 border-t border-gray-100 mt-5">
                    <a href="{{ route('announcements.index') }}"
                       class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">Cancel</a>
                    <button type="submit"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                        Save
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
