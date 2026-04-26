<x-staff-layout>
    <x-slot name="title">My Profile</x-slot>

    <div class="space-y-4">

        {{-- Identity card --}}
        <script>
        function profilePhotoCard() {
            return {
                isOpen: false,
                previewUrl: null,

                open() {
                    this.isOpen = true;
                },

                close() {
                    this.isOpen = false;
                    this.previewUrl = null;
                    if (this.$refs.photoInput) {
                        this.$refs.photoInput.value = '';
                    }
                },

                choosePhoto() {
                    this.$refs.photoInput.click();
                },

                preview(event) {
                    const file = event.target.files[0];
                    if (!file) {
                        this.previewUrl = null;
                        return;
                    }

                    if (this.previewUrl) {
                        URL.revokeObjectURL(this.previewUrl);
                    }

                    this.previewUrl = URL.createObjectURL(file);
                },
            };
        }
        </script>
        <div class="bg-green-500 rounded-2xl p-5 text-white" x-data="profilePhotoCard()">
            <div class="flex items-center gap-4">
                <button type="button"
                        @click="open"
                        class="relative w-16 h-16 rounded-full bg-white/20 flex items-center justify-center shrink-0 ring-2 ring-white/40 overflow-hidden group"
                        aria-label="Update profile photo">
                    @if(Auth::user()->profile_photo_url)
                        <img src="{{ Auth::user()->profile_photo_url }}" alt="{{ $employee->full_name }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-2xl font-bold text-white">{{ strtoupper(substr($employee->first_name, 0, 1)) }}</span>
                    @endif
                    <span class="absolute right-0 bottom-0 w-6 h-6 rounded-full bg-white text-green-700 flex items-center justify-center shadow-sm border border-green-100">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 9a2 2 0 012-2h2.5l1-1.5A2 2 0 0110.2 4h3.6a2 2 0 011.7.9L16.5 7H19a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <circle cx="12" cy="13" r="3" stroke-width="2"/>
                        </svg>
                    </span>
                </button>
                <div class="min-w-0">
                    <p class="text-lg font-bold leading-tight truncate">{{ $employee->full_name }}</p>
                    <p class="text-sm text-green-100 truncate">{{ $employee->position ?? '—' }}</p>
                    <p class="text-xs text-green-200 mt-0.5">{{ $employee->branch->name }}</p>
                    <button type="button" @click="open" class="mt-2 text-xs font-semibold text-white/95 underline decoration-white/40 underline-offset-4">
                        {{ Auth::user()->profile_photo_url ? 'Change photo' : 'Add photo' }}
                    </button>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-white/20 flex gap-6 text-sm">
                <div>
                    <p class="text-green-200 text-xs">Employee #</p>
                    <p class="font-semibold">{{ $employee->employee_code ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-green-200 text-xs">Date Hired</p>
                    <p class="font-semibold">{{ $employee->hired_date ? $employee->hired_date->format('M d, Y') : '—' }}</p>
                </div>
                <div>
                    <p class="text-green-200 text-xs">Birthday</p>
                    <p class="font-semibold">{{ $employee->birthday ? $employee->birthday->format('M d, Y') : '—' }}</p>
                </div>
            </div>

            @error('photo')
                <div class="mt-4 rounded-xl bg-white/15 border border-white/20 px-3 py-2 text-xs font-medium">
                    {{ $message }}
                </div>
            @enderror

            {{-- Photo bottom sheet --}}
            <div x-show="isOpen" x-cloak
                 class="fixed inset-0 z-50 flex flex-col justify-end text-gray-900"
                 @keydown.escape.window="close">

                <div class="absolute inset-0 bg-black/40" @click="close"></div>

                <div class="relative bg-white rounded-t-2xl p-5 space-y-4 shadow-xl">
                    <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto"></div>

                    <div class="flex items-center justify-between">
                        <p class="font-semibold text-gray-800">Profile Photo</p>
                        <button type="button" @click="close" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('staff.profile.photo') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div class="flex items-center gap-4">
                            <button type="button"
                                    @click="choosePhoto"
                                    class="w-20 h-20 rounded-full bg-green-50 border border-green-100 flex items-center justify-center overflow-hidden shrink-0">
                                <template x-if="previewUrl">
                                    <img :src="previewUrl" alt="" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!previewUrl">
                                    @if(Auth::user()->profile_photo_url)
                                        <img src="{{ Auth::user()->profile_photo_url }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-2xl font-bold text-green-700">{{ strtoupper(substr($employee->first_name, 0, 1)) }}</span>
                                    @endif
                                </template>
                            </button>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800">Choose a clear, friendly photo.</p>
                                <p class="text-xs text-gray-500 mt-1">JPG, PNG, or WebP up to 2 MB.</p>
                            </div>
                        </div>

                        <input x-ref="photoInput"
                               type="file"
                               name="photo"
                               accept="image/jpeg,image/png,image/webp"
                               class="hidden"
                               @change="preview">

                        <div class="grid grid-cols-2 gap-3">
                            <button type="button"
                                    @click="choosePhoto"
                                    class="py-3 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition">
                                Choose Photo
                            </button>
                            <button type="submit"
                                    class="py-3 rounded-xl bg-green-600 hover:bg-green-700 text-sm font-semibold text-white transition">
                                Save Photo
                            </button>
                        </div>
                    </form>

                    @if(Auth::user()->profile_photo_url)
                        <form method="POST" action="{{ route('staff.profile.photo.destroy') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full py-3 rounded-xl border border-red-100 text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 transition">
                                Remove Photo
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Contact --}}
        <div class="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-100">
            <div class="px-5 py-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Contact</p>
            </div>
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">Email</span>
                <span class="font-medium text-gray-800">{{ $employee->email ?? '—' }}</span>
            </div>
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">Mobile</span>
                <span class="font-medium text-gray-800">{{ $employee->contact_number ?? '—' }}</span>
            </div>
        </div>

        {{-- Government IDs --}}
        <div class="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-100">
            <div class="px-5 py-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Government IDs</p>
            </div>
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">SSS</span>
                <span class="font-medium text-gray-800 font-mono">{{ $employee->sss_no ?? '—' }}</span>
            </div>
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">PhilHealth</span>
                <span class="font-medium text-gray-800 font-mono">{{ $employee->phic_no ?? '—' }}</span>
            </div>
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">Pag-IBIG</span>
                <span class="font-medium text-gray-800 font-mono">{{ $employee->pagibig_no ?? '—' }}</span>
            </div>
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">TIN</span>
                <span class="font-medium text-gray-800 font-mono">{{ $employee->tin_no ?? '—' }}</span>
            </div>
        </div>

        {{-- Emergency Contact --}}
        @if($employee->emergency_contact_name)
        <div class="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-100">
            <div class="px-5 py-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Emergency Contact</p>
            </div>
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">Name</span>
                <span class="font-medium text-gray-800">{{ $employee->emergency_contact_name }}</span>
            </div>
            @if($employee->emergency_contact_relationship)
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">Relationship</span>
                <span class="font-medium text-gray-800">{{ $employee->emergency_contact_relationship }}</span>
            </div>
            @endif
            @if($employee->emergency_contact_number)
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">Number</span>
                <span class="font-medium text-gray-800">{{ $employee->emergency_contact_number }}</span>
            </div>
            @endif
        </div>
        @endif

        {{-- Signature --}}
        <script>
        function signatureCard() {
            return {
                isOpen: false,
                ctx: null,
                drawing: false,
                lastX: 0,
                lastY: 0,

                open() {
                    this.isOpen = true;
                    this.$nextTick(() => this.initCanvas());
                },

                close() {
                    this.isOpen = false;
                },

                initCanvas() {
                    const canvas = document.getElementById('update-sig-canvas');
                    if (!canvas) return;
                    const ctx = canvas.getContext('2d');
                    this.ctx = ctx;

                    const rect = canvas.getBoundingClientRect();
                    const dpr = window.devicePixelRatio || 1;
                    canvas.width = rect.width * dpr;
                    canvas.height = rect.height * dpr;
                    ctx.scale(dpr, dpr);
                    ctx.strokeStyle = '#1a1a2e';
                    ctx.lineWidth = 2.5;
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';

                    const getPos = (e) => {
                        const r = canvas.getBoundingClientRect();
                        if (e.touches) return { x: e.touches[0].clientX - r.left, y: e.touches[0].clientY - r.top };
                        return { x: e.clientX - r.left, y: e.clientY - r.top };
                    };

                    canvas.addEventListener('mousedown',  (e) => { e.preventDefault(); this.drawing = true; const p = getPos(e); this.lastX = p.x; this.lastY = p.y; });
                    canvas.addEventListener('mousemove',  (e) => { if (!this.drawing) return; e.preventDefault(); const p = getPos(e); ctx.beginPath(); ctx.moveTo(this.lastX, this.lastY); ctx.lineTo(p.x, p.y); ctx.stroke(); this.lastX = p.x; this.lastY = p.y; });
                    canvas.addEventListener('mouseup',    () => { this.drawing = false; });
                    canvas.addEventListener('mouseleave', () => { this.drawing = false; });
                    canvas.addEventListener('touchstart', (e) => { e.preventDefault(); this.drawing = true; const p = getPos(e); this.lastX = p.x; this.lastY = p.y; }, { passive: false });
                    canvas.addEventListener('touchmove',  (e) => { if (!this.drawing) return; e.preventDefault(); const p = getPos(e); ctx.beginPath(); ctx.moveTo(this.lastX, this.lastY); ctx.lineTo(p.x, p.y); ctx.stroke(); this.lastX = p.x; this.lastY = p.y; }, { passive: false });
                    canvas.addEventListener('touchend',   () => { this.drawing = false; });

                    document.getElementById('update-clear-btn').onclick = () => {
                        const r = canvas.getBoundingClientRect();
                        ctx.clearRect(0, 0, r.width, r.height);
                    };

                    document.getElementById('update-sig-form').onsubmit = (e) => {
                        const data = canvas.toDataURL('image/png');
                        const blank = document.createElement('canvas');
                        blank.width = canvas.width;
                        blank.height = canvas.height;
                        if (data === blank.toDataURL('image/png')) {
                            e.preventDefault();
                            alert('Please draw your signature before saving.');
                            return;
                        }
                        document.getElementById('update-signature-data').value = data;
                    };
                },
            };
        }
        </script>
        <div class="bg-white rounded-2xl border border-gray-100 p-5" x-data="signatureCard()">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">My Signature</p>
                <button type="button" @click="open"
                        class="text-xs font-semibold text-green-600 hover:text-green-700">
                    Update
                </button>
            </div>
            @if(Auth::user()->signature)
                <div class="flex justify-center bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                    <img src="{{ Auth::user()->signature }}" alt="Your signature" class="h-14 w-auto object-contain">
                </div>
            @else
                <p class="text-sm text-gray-400 text-center py-2">No signature on file.</p>
            @endif

            {{-- Bottom sheet --}}
            <div x-show="isOpen" x-cloak
                 class="fixed inset-0 z-50 flex flex-col justify-end"
                 @keydown.escape.window="close">

                {{-- Backdrop --}}
                <div class="absolute inset-0 bg-black/40" @click="close"></div>

                {{-- Sheet --}}
                <div class="relative bg-white rounded-t-2xl p-5 space-y-4 shadow-xl">
                    <div class="flex items-center justify-between">
                        <p class="font-semibold text-gray-800">Update Signature</p>
                        <button type="button" @click="close" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <p class="text-xs text-gray-500">Draw your new signature below. This will replace your current one.</p>

                    <form method="POST" action="{{ route('staff.profile.signature') }}" id="update-sig-form">
                        @csrf
                        <input type="hidden" name="signature" id="update-signature-data">

                        <div class="border-2 border-dashed border-gray-300 rounded-xl bg-gray-50 relative" style="touch-action: none;">
                            <canvas id="update-sig-canvas" class="w-full rounded-xl" style="height: 200px; display: block;"></canvas>
                            <button type="button" id="update-clear-btn"
                                    class="absolute top-2 right-2 text-xs bg-white border border-gray-300 text-gray-600 px-2 py-1 rounded hover:bg-gray-100">
                                Clear
                            </button>
                        </div>

                        <button type="submit"
                                class="mt-4 w-full py-3 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl transition">
                            Save Signature
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- My Payslips --}}
        <a href="{{ route('staff.payslips.index') }}"
           class="flex items-center justify-between w-full bg-white rounded-2xl border border-gray-100 px-5 py-4 hover:border-green-200 hover:shadow-sm transition">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-green-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <span class="text-sm font-semibold text-gray-800">My Payslips</span>
            </div>
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        {{-- Logout --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full py-3 rounded-2xl border border-red-200 text-red-600 text-sm font-semibold bg-white hover:bg-red-50 transition">
                Log Out
            </button>
        </form>

    </div>
</x-staff-layout>
