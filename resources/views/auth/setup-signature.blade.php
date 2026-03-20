<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup Signature — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow p-6">

        <div class="text-center mb-6">
            <h2 class="text-xl font-bold text-gray-800">Draw Your Signature</h2>
            <p class="text-sm text-gray-500 mt-1">Use your finger or stylus. This will be used on your payslips.</p>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('signature.setup.store') }}" id="sig-form">
            @csrf
            <input type="hidden" name="signature" id="signature-data">

            <div class="border-2 border-dashed border-gray-300 rounded-xl bg-gray-50 mb-4 relative" style="touch-action: none;">
                <canvas id="sig-canvas" class="w-full rounded-xl" style="height: 220px; display: block;"></canvas>
                <button type="button" id="clear-btn"
                    class="absolute top-2 right-2 text-xs bg-white border border-gray-300 text-gray-600 px-2 py-1 rounded hover:bg-gray-100">
                    Clear
                </button>
            </div>

            <button type="submit" id="save-btn"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition">
                Save Signature & Continue
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
            @csrf
            <button type="submit" class="text-sm text-gray-400 hover:text-gray-600">Log out</button>
        </form>
    </div>

<script>
(function () {
    const canvas = document.getElementById('sig-canvas');
    const ctx = canvas.getContext('2d');
    let drawing = false;
    let lastX = 0, lastY = 0;

    function resize() {
        const rect = canvas.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        ctx.scale(dpr, dpr);
        ctx.strokeStyle = '#1a1a2e';
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
    }
    resize();

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        if (e.touches) {
            return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
        }
        return { x: e.clientX - rect.left, y: e.clientY - rect.top };
    }

    function start(e) {
        e.preventDefault();
        drawing = true;
        const pos = getPos(e);
        lastX = pos.x; lastY = pos.y;
    }

    function draw(e) {
        if (!drawing) return;
        e.preventDefault();
        const pos = getPos(e);
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        lastX = pos.x; lastY = pos.y;
    }

    function stop(e) {
        drawing = false;
    }

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stop);
    canvas.addEventListener('mouseleave', stop);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    canvas.addEventListener('touchend', stop);

    document.getElementById('clear-btn').addEventListener('click', function () {
        const rect = canvas.getBoundingClientRect();
        ctx.clearRect(0, 0, rect.width, rect.height);
    });

    document.getElementById('sig-form').addEventListener('submit', function (e) {
        const data = canvas.toDataURL('image/png');
        // Check if canvas is blank
        const blank = document.createElement('canvas');
        blank.width = canvas.width;
        blank.height = canvas.height;
        if (data === blank.toDataURL('image/png')) {
            e.preventDefault();
            alert('Please draw your signature before continuing.');
            return;
        }
        document.getElementById('signature-data').value = data;
    });
})();
</script>
</body>
</html>
