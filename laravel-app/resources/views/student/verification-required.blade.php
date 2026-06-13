<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verification Required — {{ $exam->title }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    <style>
        :root{
            --eq-bg:#0d1117;--eq-surface:#161b27;--eq-border:#2a3448;--eq-text:#e2e8f0;--eq-muted:#8b97b0;
        }
        body{background:var(--eq-bg);color:var(--eq-text);min-height:100vh;padding:1rem;display:flex;align-items:center;justify-content:center}
        .proctoring-overlay{position:fixed;inset:0;background:rgba(0,0,0,.88);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;z-index:9999;padding:1rem;overflow-y:auto}
        .proctoring-modal{background:var(--eq-surface);color:var(--eq-text);border:1px solid var(--eq-border);padding:1.5rem;border-radius:14px;width:100%;max-width:520px;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.7)}
        .proctoring-video-preview{width:100%;max-width:320px;margin:.75rem auto;border:3px solid #22c55e;border-radius:10px;display:none;background:#000;max-height:40vh;object-fit:cover}
        .security-notice{background:rgba(234,179,8,.1);border:1px solid rgba(234,179,8,.3);color:#fde68a;border-radius:8px;padding:.875rem 1rem;margin:.875rem 0;text-align:left;font-size:.875rem}
        .permission-status-box{min-height:2.5rem;display:flex;align-items:center;justify-content:center;gap:.5rem;margin:.5rem 0;font-size:.9rem}
        .permission-denied-box{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:.875rem 1rem;margin:.75rem 0;text-align:left;font-size:.85rem;color:#fca5a5}
        .permission-denied-box .browser-hint{margin-top:.5rem;padding:.5rem .75rem;background:rgba(255,255,255,.05);border-radius:6px;font-size:.8rem;color:var(--eq-muted)}
        .permission-granted-box{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);border-radius:8px;padding:.75rem 1rem;margin:.75rem 0;color:#86efac;font-size:.9rem}
        .no-device-guide{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:.875rem 1rem;margin:.75rem 0;text-align:left;font-size:.85rem;color:#fca5a5}
        @keyframes pulse-green{0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,.5)}70%{box-shadow:0 0 0 10px rgba(34,197,94,0)}}
        .btn-pulse{animation:pulse-green 1.5s infinite}
    </style>
    <script>
    window.PROCTORING_CONFIG = {
        logUrl        : '{{ route("proctoring.log") }}',
        captureUrl    : '{{ route("proctoring.capture") }}',
        recordingUrl  : '{{ route("proctoring.recording") }}',
        enableUrl     : '{{ route("proctoring.enable") }}',
        terminatedUrl : '{{ route("student.terminated") }}',
        cheatEventUrl : '{{ route("proctoring.cheat-event") }}',
        csrfToken     : '{{ csrf_token() }}',
        attemptId     : {{ $attempt->id }},
    };
    </script>
    <script src="{{ asset('js/proctoring.js') }}"></script>
    <script>
    const examContinueUrl = @json(route('student.exam'));

    function isProctoringStreamValid() {
        const s = window.proctoringStream;
        if (!s || !s.active) return false;
        const videoTracks = s.getVideoTracks();
        const audioTracks = s.getAudioTracks();
        const videoOk = videoTracks.length > 0 && videoTracks[0].readyState === 'live' && videoTracks[0].enabled;
        const audioOk = audioTracks.length > 0 && audioTracks[0].readyState === 'live' && audioTracks[0].enabled;
        return videoOk && audioOk;
    }

    async function initializeProctoring() {
        const statusBox    = document.getElementById('camera-permission-status');
        const startBtn     = document.getElementById('start-proctoring-btn');
        const retryBtn     = document.getElementById('retry-permission-btn');
        const deniedGuide  = document.getElementById('permission-denied-guide');
        const noDevGuide   = document.getElementById('no-device-guide');
        const grantedBox   = document.getElementById('permission-granted-box');
        const browserHint  = document.getElementById('browser-hint');
        const videoPreview = document.getElementById('proctoring-video-preview');

        if (window.proctoringStreamCheckInterval) {
            clearInterval(window.proctoringStreamCheckInterval);
            window.proctoringStreamCheckInterval = null;
        }

        startBtn.disabled = true;
        startBtn.classList.remove('btn-pulse');
        retryBtn.style.display  = 'none';
        deniedGuide.style.display = 'none';
        noDevGuide.style.display  = 'none';
        grantedBox.style.display  = 'none';
        videoPreview.style.display = 'none';
        statusBox.innerHTML = `
            <div class="spinner-border spinner-border-sm text-secondary" role="status" aria-hidden="true"></div>
            <span class="text-muted">Requesting camera and microphone access…</span>`;

        if (navigator.permissions) {
            try {
                const [camPerm, micPerm] = await Promise.all([
                    navigator.permissions.query({ name: 'camera' }),
                    navigator.permissions.query({ name: 'microphone' }),
                ]);
                if (camPerm.state === 'denied' || micPerm.state === 'denied') {
                    showDeniedState(browserHint, statusBox, deniedGuide, retryBtn, startBtn);
                    return;
                }
            } catch (_) { }
        }

        try {
            if (window.proctoringStream) {
                window.proctoringStream.getTracks().forEach(t => t.stop());
                window.proctoringStream = null;
            }

            const stream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' },
                audio: true,
            });

            window.proctoringStream = stream;
            videoPreview.srcObject = stream;
            videoPreview.style.display = 'block';
            statusBox.innerHTML = '';
            grantedBox.style.display = 'block';
            startBtn.disabled = false;
            startBtn.classList.add('btn-pulse');

            window.proctoringStreamCheckInterval = setInterval(() => {
                if (!isProctoringStreamValid()) {
                    clearInterval(window.proctoringStreamCheckInterval);
                    window.proctoringStreamCheckInterval = null;
                    startBtn.disabled = true;
                    startBtn.classList.remove('btn-pulse');
                    grantedBox.style.display = 'none';
                    videoPreview.style.display = 'none';
                    statusBox.innerHTML = `
                        <span class="text-warning">
                            <i class="bi bi-camera-video-off-fill me-1"></i>
                            Camera or microphone was disabled. Please re-enable and click Try Again.
                        </span>`;
                    retryBtn.style.display = 'inline-block';
                }
            }, 2000);

        } catch (err) {
            const name = err.name || '';
            if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
                statusBox.innerHTML = '';
                noDevGuide.style.display = 'block';
            } else if (
                name === 'NotAllowedError' || name === 'PermissionDeniedError' ||
                name === 'SecurityError'   || name === 'PermissionDismissedError'
            ) {
                showDeniedState(browserHint, statusBox, deniedGuide, retryBtn, startBtn);
                return;
            } else {
                statusBox.innerHTML = `
                    <span class="text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Could not access camera/microphone (${err.message || name}). Please try again.
                    </span>`;
            }
            startBtn.disabled = true;
            retryBtn.style.display = 'inline-block';
        }
    }

    function showDeniedState(browserHint, statusBox, deniedGuide, retryBtn, startBtn) {
        statusBox.innerHTML = '';
        deniedGuide.style.display = 'block';
        startBtn.disabled = true;
        retryBtn.style.display = 'inline-block';
        const ua = navigator.userAgent;
        if (/Edg\//.test(ua)) {
            browserHint.textContent = 'Edge: Click the lock icon in the address bar → Permissions for this site → Camera & Microphone → Allow.';
        } else if (/Chrome\//.test(ua)) {
            browserHint.textContent = 'Chrome: Click the camera icon in the address bar → Camera & Microphone → Allow, then click Try Again.';
        } else if (/Firefox\//.test(ua)) {
            browserHint.textContent = 'Firefox: Click the camera icon in the address bar → remove block → Try Again.';
        } else if (/Safari\//.test(ua)) {
            browserHint.textContent = 'Safari: Safari → Settings for This Website → Camera & Microphone → Allow.';
        } else {
            browserHint.textContent = 'Allow Camera and Microphone for this site in your browser settings, then click Try Again.';
        }
    }

    async function completeVerificationAndContinue() {
        if (!isProctoringStreamValid()) {
            const statusBox = document.getElementById('camera-permission-status');
            const startBtn = document.getElementById('start-proctoring-btn');
            const retryBtn = document.getElementById('retry-permission-btn');
            const grantedBox = document.getElementById('permission-granted-box');
            const videoPreview = document.getElementById('proctoring-video-preview');
            grantedBox.style.display = 'none';
            if (videoPreview) videoPreview.style.display = 'none';
            startBtn.disabled = true;
            startBtn.classList.remove('btn-pulse');
            retryBtn.style.display = 'inline-block';
            statusBox.innerHTML = `
                <span class="text-warning">
                    <i class="bi bi-camera-video-off-fill me-1"></i>
                    Camera or microphone was disabled. Please re-enable and click Try Again.
                </span>`;
            if (window.proctoringStreamCheckInterval) {
                clearInterval(window.proctoringStreamCheckInterval);
                window.proctoringStreamCheckInterval = null;
            }
            return;
        }

        if (window.proctoringStreamCheckInterval) {
            clearInterval(window.proctoringStreamCheckInterval);
            window.proctoringStreamCheckInterval = null;
        }

        const startBtn = document.getElementById('start-proctoring-btn');
        const retryBtn = document.getElementById('retry-permission-btn');
        const statusBox = document.getElementById('camera-permission-status');

        if (startBtn) {
            startBtn.disabled = true;
            startBtn.classList.remove('btn-pulse');
            startBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Verifying…';
        }
        if (retryBtn) retryBtn.style.display = 'none';
        if (statusBox) {
            statusBox.innerHTML = `
                <span class="text-info">
                    <i class="bi bi-camera me-1"></i>Taking verification photo… Please look at the camera.
                </span>`;
        }

        try {
            if (typeof ExamProctoring === 'undefined') {
                throw new Error('Proctoring module failed to load. Refresh the page or try another browser.');
            }
            if (!window.examProctoring || typeof window.examProctoring.capturePreStartPhoto !== 'function') {
                throw new Error('Proctoring engine is not available. Refresh the page.');
            }
            if (window.proctoringStream) {
                window.examProctoring.videoStream = window.proctoringStream;
            }
            await window.examProctoring.capturePreStartPhoto();
        } catch (e) {
            console.warn('[Proctoring] Pre-start photo failed:', e?.message || e);
            if (statusBox) {
                statusBox.innerHTML = `
                    <span class="text-warning">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Verification photo could not be uploaded. Please check your connection and try again.
                    </span>`;
            }
            if (retryBtn) retryBtn.style.display = 'inline-block';
            if (startBtn) {
                startBtn.disabled = false;
                startBtn.classList.add('btn-pulse');
                startBtn.innerHTML = '<i class="bi bi-shield-check me-1"></i>Complete Verification';
            }
            return;
        }

        window.location.assign(examContinueUrl);
    }

    window.addEventListener('load', () => {
        if (typeof ExamProctoring === 'undefined') {
            const box = document.getElementById('camera-permission-status');
            if (box) {
                box.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-octagon me-1"></i>Proctoring scripts failed to load. Check your network, disable blockers, and refresh.</span>';
            }
            document.getElementById('start-proctoring-btn').disabled = true;
            return;
        }
        window.examProctoring = new ExamProctoring();
        document.getElementById('proctoringModal').style.display = 'flex';
        document.getElementById('start-proctoring-btn').addEventListener('click', completeVerificationAndContinue);
        document.getElementById('retry-permission-btn').addEventListener('click', initializeProctoring);
        initializeProctoring();
    });
    </script>
</head>
<body>
<div id="proctoringModal" class="proctoring-overlay" style="display:none">
    <div class="proctoring-modal">
        <div class="mb-3">
            <i class="bi bi-shield-lock-fill text-primary" style="font-size:2.2rem"></i>
        </div>
        <h1 class="h4 fw-bold mb-2">Verification Required</h1>
        <p class="text-muted mb-2" style="font-size:.95rem">
            A proctoring verification photo is required before you can continue this exam.
        </p>
        @if (session('verification_notice'))
            <div class="alert alert-info py-2 small text-start">{{ session('verification_notice') }}</div>
        @endif

        <div class="security-notice">
            <strong><i class="bi bi-info-circle me-1"></i>What happens next</strong>
            <ul class="mb-0 mt-1">
                <li>Allow camera and microphone when your browser asks.</li>
                <li>We capture one verification image linked to your attempt.</li>
                <li>After success, you will continue to the exam questions.</li>
            </ul>
        </div>

        <p class="small text-muted mb-1">{{ $exam->title }}</p>
        <p class="small mb-0"><i class="bi bi-person-badge me-1"></i>{{ $studentName }} · {{ $studentIndex }}</p>

        <video id="proctoring-video-preview" class="proctoring-video-preview" autoplay muted playsinline></video>

        <div id="camera-permission-status" class="permission-status-box">
            <div class="spinner-border spinner-border-sm text-secondary" role="status" aria-hidden="true"></div>
            <span class="text-muted">Requesting camera and microphone access…</span>
        </div>

        <div id="permission-denied-guide" class="permission-denied-box" style="display:none">
            <strong><i class="bi bi-camera-video-off-fill me-1"></i>Access was blocked by your browser.</strong>
            <p class="mb-1 mt-1">Grant Camera and Microphone for this site, then click <strong>Try Again</strong>.</p>
            <div id="browser-hint" class="browser-hint"></div>
        </div>

        <div id="no-device-guide" class="no-device-guide" style="display:none">
            <strong><i class="bi bi-camera-video-off-fill me-1"></i>No camera or microphone detected.</strong>
            <p class="mb-0 mt-1">Connect a working webcam and microphone, then click <strong>Try Again</strong>.</p>
        </div>

        <div id="permission-granted-box" class="permission-granted-box" style="display:none">
            <i class="bi bi-check-circle-fill me-1 text-success"></i>
            <strong>Access granted.</strong> When you are ready, press <strong>Complete Verification</strong>.
        </div>

        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center mt-3">
            <button id="retry-permission-btn" class="btn btn-outline-secondary order-2 order-sm-1" style="display:none" type="button">
                <i class="bi bi-arrow-clockwise me-1"></i>Try Again
            </button>
            <button id="start-proctoring-btn" class="btn btn-success btn-lg px-4 order-1 order-sm-2 flex-grow-1 flex-sm-grow-0" style="min-height:48px" disabled type="button">
                <i class="bi bi-shield-check me-1"></i>Complete Verification
            </button>
        </div>
    </div>
</div>
</body>
</html>
