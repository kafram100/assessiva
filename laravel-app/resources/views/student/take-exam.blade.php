<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $exam->title }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    <style>
        /* ── Dark palette ── */
        :root{
            --eq-bg:       #0d1117;
            --eq-surface:  #161b27;
            --eq-surface2: #1e2636;
            --eq-border:   #2a3448;
            --eq-text:     #e2e8f0;
            --eq-muted:    #8b97b0;
            --eq-indigo:   #818cf8;
            --eq-indigo-bg:#1e2a4a;
        }
        body{background:var(--eq-bg);color:var(--eq-text);min-height:100vh;padding-bottom:env(safe-area-inset-bottom,0);-webkit-tap-highlight-color:transparent}

        /* ── Exam header ── */
        .exam-header{position:sticky;top:0;z-index:1000;background:var(--eq-surface);border-bottom:1px solid var(--eq-border);padding:.5rem .75rem .5rem max(.75rem,env(safe-area-inset-left));padding-right:max(.75rem,env(safe-area-inset-right));display:flex;align-items:center;gap:.5rem;box-shadow:0 2px 8px rgba(0,0,0,.4);flex-wrap:wrap}
        .exam-header-title{font-weight:700;font-size:.9375rem;color:var(--eq-text);flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .exam-header-student{font-size:.8125rem;color:var(--eq-muted);white-space:nowrap;max-width:120px;overflow:hidden;text-overflow:ellipsis}

        /* ── Timer ── */
        .timer-display{font-size:1.0625rem;font-weight:700;font-variant-numeric:tabular-nums;letter-spacing:.03em;color:var(--eq-text);white-space:nowrap;background:var(--eq-surface2);border-radius:6px;padding:.25rem .625rem;border:1px solid var(--eq-border)}
        .timer-display.warning{background:#2d1f00;color:#fbbf24;border-color:#78350f}
        .timer-display.danger{background:#2d0000;color:#f87171;border-color:#7f1d1d}

        /* ── Autosave ── */
        .autosave-status{display:inline-flex;align-items:center;gap:.4rem;font-size:.8125rem;color:var(--eq-muted);white-space:nowrap}
        .autosave-dot{width:7px;height:7px;border-radius:50%;background:#4b5563;display:inline-block;flex-shrink:0}
        .autosave-status.is-saving .autosave-dot{background:#3b82f6}
        .autosave-status.is-offline .autosave-dot{background:#ef4444}
        .autosave-status.is-saved .autosave-dot{background:#22c55e}

        /* ── Content ── */
        .content{margin-top:1.5rem;padding-bottom:max(3rem,calc(2rem + env(safe-area-inset-bottom)))}

        /* ── Question navigator ── */
        .q-nav{display:flex;flex-direction:column;gap:.5rem;margin-bottom:1.25rem;padding:.875rem 1rem;background:var(--eq-surface);border:1px solid var(--eq-border);border-radius:12px}
        .q-nav-label{font-size:.8125rem;font-weight:600;color:var(--eq-muted);display:flex;align-items:center;gap:.375rem}
        .q-nav-btns{display:flex;flex-wrap:wrap;gap:.375rem;max-height:120px;overflow-y:auto;-webkit-overflow-scrolling:touch}
        .q-nav-btn{width:40px;height:40px;min-width:40px;min-height:40px;border-radius:50%;border:2px solid var(--eq-border);background:var(--eq-surface2);font-size:.8125rem;font-weight:600;cursor:pointer;color:var(--eq-muted);transition:all .15s;display:flex;align-items:center;justify-content:center;text-decoration:none;flex-shrink:0;touch-action:manipulation}
        .q-nav-btn:hover,.q-nav-btn:focus{border-color:var(--eq-indigo);color:var(--eq-indigo)}
        .q-nav-btn.answered{background:#166534;border-color:#22c55e;color:#bbf7d0}

        /* ── Question cards ── */
        .question-card{border:1px solid var(--eq-border);border-radius:12px;margin-bottom:1.25rem;overflow:hidden;background:var(--eq-surface)}
        .question-card-header{background:var(--eq-surface2);border-bottom:1px solid var(--eq-border);padding:.625rem 1rem;display:flex;align-items:center;gap:.75rem}
        .q-number-badge{width:26px;height:26px;background:var(--eq-indigo);color:#fff;border-radius:50%;font-size:.6875rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .q-type-tag{font-size:.6875rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--eq-indigo);background:var(--eq-indigo-bg);padding:.125rem .5rem;border-radius:4px}
        .q-marks-tag{margin-left:auto;font-size:.75rem;color:var(--eq-muted);white-space:nowrap}
        .question-card-body{padding:1rem 1.125rem;background:var(--eq-surface)}
        .question-text{font-size:.9375rem;font-weight:500;color:var(--eq-text);margin-bottom:1rem;line-height:1.6}

        /* ── MCQ options ── */
        .mcq-option{display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;min-height:48px;border:2px solid var(--eq-border);border-radius:10px;margin-bottom:.5rem;cursor:pointer;transition:all .12s;background:var(--eq-surface2);touch-action:manipulation;-webkit-tap-highlight-color:transparent}
        .mcq-option:hover,.mcq-option:focus-within{border-color:var(--eq-indigo);background:var(--eq-indigo-bg)}
        .mcq-option.is-selected{border-color:var(--eq-indigo);background:var(--eq-indigo-bg)}
        .mcq-option input[type="radio"]{accent-color:var(--eq-indigo);width:20px;height:20px;min-width:20px;min-height:20px;flex-shrink:0;cursor:pointer}
        .option-letter{font-weight:700;color:var(--eq-indigo);font-size:.875rem;min-width:22px}
        .option-text{font-size:.9375rem;color:var(--eq-text);line-height:1.4}

        /* ── Form controls in dark ── */
        .form-control,.form-select{background:var(--eq-surface2)!important;color:var(--eq-text)!important;border-color:var(--eq-border)!important}
        .form-control:focus,.form-select:focus{border-color:var(--eq-indigo)!important;box-shadow:0 0 0 .2rem rgba(129,140,248,.2)!important}
        .form-control::placeholder{color:var(--eq-muted)!important}
        .bg-light{background:var(--eq-surface2)!important;border-color:var(--eq-border)!important;color:var(--eq-text)!important}

        /* ── Submit area ── */
        .submit-area{background:var(--eq-surface);border:1px solid var(--eq-border);border-radius:12px;padding:1rem 1rem;padding-bottom:max(1rem,env(safe-area-inset-bottom));display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-top:.5rem}
        .submit-summary{font-size:.875rem;color:var(--eq-muted)}
        .btn-submit-exam{background:var(--eq-indigo);color:#fff;border:none;padding:.75rem 1.5rem;min-height:48px;border-radius:10px;font-weight:600;font-size:1rem;cursor:pointer;transition:background .15s;touch-action:manipulation;flex:1;min-width:140px}
        .btn-submit-exam:hover,.btn-submit-exam:focus{background:#6366f1;color:#fff}

        /* ── Alert overrides ── */
        .alert-light{background:var(--eq-surface2)!important;border-color:var(--eq-border)!important;color:var(--eq-text)!important}
        .alert-info{background:rgba(59,130,246,.12)!important;border-color:rgba(59,130,246,.3)!important;color:#93c5fd!important}
        .alert-success{background:rgba(34,197,94,.1)!important;border-color:rgba(34,197,94,.3)!important;color:#86efac!important}

        /* ── Security ── */
        *{-webkit-user-select:none!important;-moz-user-select:none!important;user-select:none!important}
        input,textarea{-webkit-user-select:text!important;-moz-user-select:text!important;user-select:text!important}
        img{pointer-events:none!important}
        @media print{body *{visibility:hidden!important}body::after{visibility:visible!important;content:'Printing this exam is not permitted.';position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);font-size:2rem;font-weight:700;color:#ef4444}}

        /* ── Mobile-first responsive ── */
        html{scroll-behavior:smooth}
        .content-safe{padding-left:max(1rem,env(safe-area-inset-left))!important;padding-right:max(1rem,env(safe-area-inset-right))!important}
        @media(min-width:768px){.content-safe{padding-left:max(1.5rem,env(safe-area-inset-left))!important;padding-right:max(1.5rem,env(safe-area-inset-right))!important}}
        input.form-control,textarea.form-control{font-size:16px!important}
        @media(max-width:767px){
            .exam-header{flex-direction:row;flex-wrap:wrap;padding:.5rem .75rem}
            .exam-header-title{font-size:.875rem;order:1;flex:1 1 100%}
            .exam-header-student{font-size:.75rem;order:2;max-width:100px}
            .autosave-status{order:3;font-size:.75rem}
            .autosave-status #autosave-text{max-width:60px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
            .timer-display{order:4;font-size:1rem;padding:.2rem .5rem}
            .face-dot{order:5}
            .q-nav{padding:.75rem;margin-bottom:1rem}
            .q-nav-btns{max-height:100px}
            .q-nav-btn{width:44px;height:44px;min-width:44px;min-height:44px;font-size:.875rem}
            .question-card{margin-bottom:1rem;border-radius:10px}
            .question-card-header{padding:.5rem .75rem;flex-wrap:wrap;gap:.5rem}
            .question-card-body{padding:.875rem 1rem}
            .question-text{font-size:1rem;line-height:1.55}
            .mcq-option{padding:.875rem 1rem;min-height:52px;margin-bottom:.625rem}
            .mcq-option input[type="radio"]{width:22px;height:22px;min-width:22px;min-height:22px}
            .option-text{font-size:1rem}
            .submit-area{flex-direction:column;align-items:stretch;padding:1rem .75rem;padding-bottom:max(1rem,calc(1rem + env(safe-area-inset-bottom)))}
            .btn-submit-exam{width:100%;min-height:52px;font-size:1.0625rem;padding:.875rem}
            input[type="file"].form-control{min-height:44px;padding:.5rem}
            .alert.alert-info .btn{display:block;width:100%;margin-top:.5rem;margin-left:0!important}
        }
        @media(max-width:400px){
            .exam-header-title{font-size:.8125rem}
            .q-nav-btn{width:40px;height:40px;min-width:40px;min-height:40px}
        }

        /* ── Proctoring modal ── */
        .proctoring-overlay{position:fixed;inset:0;background:rgba(0,0,0,.88);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;z-index:9999;padding:1rem;padding-left:max(1rem,env(safe-area-inset-left));padding-right:max(1rem,env(safe-area-inset-right));padding-bottom:max(1rem,env(safe-area-inset-bottom));overflow-y:auto;-webkit-overflow-scrolling:touch}
        .proctoring-modal{background:var(--eq-surface);color:var(--eq-text);border:1px solid var(--eq-border);padding:1.5rem;padding-left:max(1.5rem,env(safe-area-inset-left));padding-right:max(1.5rem,env(safe-area-inset-right));border-radius:14px;width:100%;max-width:520px;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.7);margin:auto}
        .proctoring-video-preview{width:100%;max-width:320px;margin:.75rem auto;border:3px solid #22c55e;border-radius:10px;display:none;background:#000;max-height:40vh;object-fit:cover}
        .security-notice{background:rgba(234,179,8,.1);border:1px solid rgba(234,179,8,.3);color:#fde68a;border-radius:8px;padding:.875rem 1rem;margin:.875rem 0;text-align:left;font-size:.875rem}
        .permission-status-box{min-height:2.5rem;display:flex;align-items:center;justify-content:center;gap:.5rem;margin:.5rem 0;font-size:.9rem}
        .permission-denied-box{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:.875rem 1rem;margin:.75rem 0;text-align:left;font-size:.85rem;color:#fca5a5}
        .permission-denied-box .browser-hint{margin-top:.5rem;padding:.5rem .75rem;background:rgba(255,255,255,.05);border-radius:6px;font-size:.8rem;color:var(--eq-muted);line-height:1.5}
        .permission-granted-box{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);border-radius:8px;padding:.75rem 1rem;margin:.75rem 0;color:#86efac;font-size:.9rem}
        .no-device-guide{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:.875rem 1rem;margin:.75rem 0;text-align:left;font-size:.85rem;color:#fca5a5}
        @keyframes pulse-green{0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,.5)}70%{box-shadow:0 0 0 10px rgba(34,197,94,0)}}
        .btn-pulse{animation:pulse-green 1.5s infinite}
        /* ── Face detection status dot ── */
        .face-dot{width:9px;height:9px;border-radius:50%;background:#4b5563;display:inline-block;flex-shrink:0;cursor:default;transition:background .3s}
        .face-dot--loading{background:#f59e0b;animation:pulse-amber .8s infinite}
        .face-dot--ready,.face-dot--active{background:#22c55e}
        .face-dot--error{background:#6b7280}
        @keyframes pulse-amber{0%,100%{opacity:1}50%{opacity:.4}}
        @media(max-width:767px){
            .proctoring-modal{padding:1.25rem 1rem}
            .proctoring-modal h4{font-size:1.125rem}
            .security-notice{font-size:.8125rem;padding:.75rem}
        }
    </style>
    @if($requiresProctoring)
    {{-- Inject URL config BEFORE loading proctoring scripts --}}
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
    <script src="{{ asset('js/face-detection.js') }}"></script>
    @endif
    <script>
    const isProctoringRequired = @json($requiresProctoring);
    const isSubmissionPasswordRequired = @json((bool) $exam->submission_password_enabled);

    let timeLeft  = {{ $remainingSec }};
    let isUnlimited = {{ $isUnlimited ? 'true' : 'false' }};
    let examTimeExpired = false;
    let examStarted = !isProctoringRequired;

    function lockExamInputsAfterTimeout() {
        const allowedIds = new Set(['submission_password', 'submit-exam-btn']);
        document.querySelectorAll('#examForm input, #examForm textarea, #examForm select, #examForm button').forEach((el) => {
            const id = el.id || '';
            const name = el.name || '';
            const type = (el.type || '').toLowerCase();

            if (allowedIds.has(id)) return;
            if (name === '_token' || name === 'exam_attempt_id') return;
            if (type === 'hidden') return;

            el.disabled = true;
        });
    }

    function updateTimer() {
        if (isUnlimited) return;
        if (!examStarted) return;
        let m = Math.floor(timeLeft/60), s = timeLeft%60;
        document.getElementById('timer').innerText = String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');
        const td = document.getElementById('timer-display');
        if (td) {
            td.classList.remove('warning','danger');
            if (timeLeft <= 60) td.classList.add('danger');
            else if (timeLeft <= 300) td.classList.add('warning');
        }
        if (timeLeft <= 0) {
            timeLeft = 0;
            if (!examTimeExpired) {
                examTimeExpired = true;
                lockExamInputsAfterTimeout();
                const help = document.getElementById('submit-help-text');
                const examForm = document.getElementById('examForm');
                const submitBtn = document.getElementById('submit-exam-btn');
                if (isSubmissionPasswordRequired) {
                    if (help) {
                        help.style.display = 'block';
                        help.textContent =
                            'Time is up. Enter the submission password, then press Submit exam.';
                    }
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                } else if (examForm) {
                    if (help) {
                        help.style.display = 'block';
                        help.textContent = 'Time is up. Submitting your exam…';
                    }
                    let autoSubmitInput = examForm.querySelector('input[name="auto_submit"]');
                    if (!autoSubmitInput) {
                        autoSubmitInput = document.createElement('input');
                        autoSubmitInput.type = 'hidden';
                        autoSubmitInput.name = 'auto_submit';
                        examForm.appendChild(autoSubmitInput);
                    }
                    autoSubmitInput.value = '1';
                    examForm.submit();
                }
            }
            return;
        }
        timeLeft--;
        setTimeout(updateTimer, 1000);
    }

    window.addEventListener('load', () => {
        if (isProctoringRequired) {
            if (typeof ExamProctoring === 'undefined') {
                document.getElementById('proctoringModal').style.display = 'flex';
                const statusBox = document.getElementById('camera-permission-status');
                if (statusBox) {
                    statusBox.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-octagon me-1"></i>Proctoring module failed to load. Refresh the page or try another browser.</span>';
                }
                const startBtn = document.getElementById('start-proctoring-btn');
                if (startBtn) {
                    startBtn.disabled = true;
                    startBtn.classList.remove('btn-pulse');
                }
                return;
            }
            window.examProctoring = new ExamProctoring();
            document.getElementById('proctoringModal').style.display = 'flex';
            document.getElementById('start-proctoring-btn').addEventListener('click', resumeProctoringAfterVerified);
            document.getElementById('retry-permission-btn').addEventListener('click', initializeProctoring);
            initializeProctoring();
        } else {
            document.getElementById('exam-content-area').style.display = 'block';
            updateTimer();
            initAutosave();
        }
    });

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

        /* ── Clear any existing stream monitor (user may have disabled mid-modal) ── */
        if (window.proctoringStreamCheckInterval) {
            clearInterval(window.proctoringStreamCheckInterval);
            window.proctoringStreamCheckInterval = null;
        }

        /* ── Reset all state ── */
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

        /* ── Pre-check via Permissions API (Chrome/Edge/Firefox) ── */
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
            } catch (_) { /* Permissions API not fully supported - proceed to getUserMedia */ }
        }

        /* ── Request media access ── */
        try {
            /* Stop any previous stream tracks first */
            if (window.proctoringStream) {
                window.proctoringStream.getTracks().forEach(t => t.stop());
                window.proctoringStream = null;
            }

            const stream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' },
                audio: true,
            });

            window.proctoringStream = stream;

            /* Show live preview */
            videoPreview.srcObject = stream;
            videoPreview.style.display = 'block';

            /* Success state */
            statusBox.innerHTML = '';
            grantedBox.style.display = 'block';
            startBtn.disabled = false;
            startBtn.classList.add('btn-pulse');
            startBtn.innerHTML = '<i class="bi bi-play-circle me-1"></i>Continue to exam';

            /* ── Monitor stream while modal is visible: if user disables camera/mic, disable Continue ── */
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
                /* No hardware */
                statusBox.innerHTML = '';
                noDevGuide.style.display = 'block';
            } else if (
                name === 'NotAllowedError' || name === 'PermissionDeniedError' ||
                name === 'SecurityError'   || name === 'PermissionDismissedError'
            ) {
                /* User denied or dismissed the prompt */
                showDeniedState(browserHint, statusBox, deniedGuide, retryBtn, startBtn);
                return; /* retryBtn already shown inside helper */
            } else {
                /* Other error (e.g. hardware in use, constraint error) */
                statusBox.innerHTML = `
                    <span class="text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Could not access camera/microphone (${err.message || name}). Please try again.
                    </span>`;
            }

            /* ── NEVER enable the start button on any error ── */
            startBtn.disabled = true;
            retryBtn.style.display = 'inline-block';
        }
    }

    function showDeniedState(browserHint, statusBox, deniedGuide, retryBtn, startBtn) {
        statusBox.innerHTML = '';
        deniedGuide.style.display = 'block';
        startBtn.disabled = true;
        retryBtn.style.display = 'inline-block';

        /* Browser-specific instructions */
        const ua = navigator.userAgent;
        if (/Edg\//.test(ua)) {
            browserHint.textContent = 'Edge: Click the lock icon in the address bar → Permissions for this site → Camera & Microphone → Allow.';
        } else if (/Chrome\//.test(ua)) {
            browserHint.textContent = 'Chrome: Click the camera icon (🔒) in the address bar → Camera & Microphone → Allow, then click Try Again.';
        } else if (/Firefox\//.test(ua)) {
            browserHint.textContent = 'Firefox: Click the camera icon in the address bar → "Blocked" → Remove block → then click Try Again.';
        } else if (/Safari\//.test(ua)) {
            browserHint.textContent = 'Safari: Go to Safari → Settings for This Website → Camera & Microphone → Allow.';
        } else {
            browserHint.textContent = 'Open your browser settings, find permissions for this site, and allow Camera and Microphone.';
        }
    }
    async function resumeProctoringAfterVerified() {
        /* ── Block if user disabled camera/mic after granting (e.g. via browser settings) ── */
        if (!isProctoringStreamValid()) {
            const statusBox = document.getElementById('camera-permission-status');
            const startBtn = document.getElementById('start-proctoring-btn');
            const retryBtn = document.getElementById('retry-permission-btn');
            const grantedBox = document.getElementById('permission-granted-box');
            const videoPreview = document.getElementById('proctoring-video-preview');
            document.getElementById('proctoringModal').style.display = 'flex';
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
        if (startBtn) {
            startBtn.disabled = true;
            startBtn.classList.remove('btn-pulse');
            startBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Starting…';
        }

        document.getElementById('proctoringModal').style.display = 'none';
        document.getElementById('exam-content-area').style.display = 'block';

        if (!window.examProctoring) {
            document.getElementById('exam-content-area').style.display = 'none';
            document.getElementById('proctoringModal').style.display = 'flex';
            const statusBox = document.getElementById('camera-permission-status');
            if (statusBox) {
                statusBox.innerHTML = '<span class="text-danger">Proctoring is unavailable. Refresh the page.</span>';
            }
            if (startBtn) {
                startBtn.disabled = true;
                startBtn.innerHTML = '<i class="bi bi-play-circle me-1"></i>Continue to exam';
            }
            return;
        }

        if (window.proctoringStream) {
            window.examProctoring.videoStream = window.proctoringStream;
            window.examProctoring.startRecording();
            window.examProctoring.connectMonitoringServer();
            window.examProctoring.enforceStreamActivity();
        }
        window.examProctoring.startExam();
        window.examProctoring.injectWatermark(@json($studentName), @json($studentIndex));

        examStarted = true;
        initAutosave();
        updateTimer();

        if (window.proctoringStream && window.FaceDetectionProctoring) {
            try {
                const faceStatusEl = document.getElementById('face-detection-status');
                window.faceDetector = new FaceDetectionProctoring({
                    checkIntervalMs  : 2000,
                    alertCooldownMs  : 12000,
                    onStatusChange   : (state, msg) => {
                        if (faceStatusEl) {
                            faceStatusEl.title = msg;
                            faceStatusEl.dataset.state = state;
                            faceStatusEl.className = 'face-dot face-dot--' + state;
                        }
                    },
                    onEvent: (eventType, metadata) => {
                        if (window.examProctoring) {
                            window.examProctoring.sendCheatEvent(eventType, metadata);
                            const labels = {
                                no_face_detected : '⚠ No face detected - please stay in frame.',
                                multiple_faces   : '⚠ Multiple faces detected.',
                                looking_away     : '⚠ Please look at the screen.',
                            };
                            window.examProctoring.showWarningToast(labels[eventType] || eventType);
                        }
                    },
                });
                await window.faceDetector.load();
                await window.faceDetector.start(window.proctoringStream);
            } catch (e) {
                console.warn('[FaceDetection] Could not start:', e.message);
            }
        }
    }
    function initAutosave() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const attemptId = {{ $attempt->id }};
        const autosaveUrl = '{{ route("student.autosave") }}';
        const pendingPayloads = new Map(), savingLocks = new Map(), debounceTimers = new Map();
        const statusEl = document.getElementById('autosave-status'), textEl = document.getElementById('autosave-text');
        function setStatus(state, text) {
            if (!statusEl) return;
            statusEl.classList.remove('is-saving','is-saved','is-offline');
            if (state) statusEl.classList.add(state);
            if (textEl) textEl.textContent = text;
        }
        async function processQueue(qid) {
            if (savingLocks.get(qid)) return;
            savingLocks.set(qid, true);
            while (pendingPayloads.has(qid)) {
                const payload = pendingPayloads.get(qid);
                pendingPayloads.delete(qid);
                setStatus('is-saving','Saving...');
                try {
                    const fd = new FormData();
                    fd.append('_token', csrfToken);
                    fd.append('question_id', payload.questionId);
                    fd.append('answer_type', payload.type);
                    if (payload.type==='mcq') fd.append('selected_option', payload.value||'');
                    else if (payload.type==='theory'||payload.type==='fill_in') fd.append('theory_answer', payload.value||'');
                    else if (payload.type==='file'&&payload.file) fd.append('file_answer', payload.file);
                    const r = await fetch(autosaveUrl, {method:'POST',body:fd,credentials:'same-origin'});
                    const d = await r.json();
                    if (d.success) setStatus('is-saved','All changes saved.');
                    else { setStatus('is-offline','Save failed.'); pendingPayloads.set(qid, payload); break; }
                } catch(e) { setStatus('is-offline','Offline. Saving locally...'); pendingPayloads.set(qid, payload); break; }
            }
            savingLocks.delete(qid);
        }
        function queueSave(payload) {
            if (!payload.questionId||!payload.type) return;
            pendingPayloads.set(payload.questionId, payload);
            processQueue(payload.questionId);
        }
        function debounceSave(qid, payload, delay=600) {
            if (debounceTimers.has(qid)) clearTimeout(debounceTimers.get(qid));
            debounceTimers.set(qid, setTimeout(()=>queueSave(payload), delay));
        }
        document.querySelectorAll('input[type="radio"][data-answer-type="mcq"]').forEach(input => {
            input.addEventListener('change', e => queueSave({questionId:e.target.dataset.questionId, type:'mcq', value:e.target.value}));
        });
        document.querySelectorAll('textarea[data-answer-type="theory"]').forEach(ta => {
            ta.addEventListener('input', e => debounceSave(e.target.dataset.questionId, {questionId:e.target.dataset.questionId, type:'theory', value:e.target.value}));
        });
        document.querySelectorAll('input[type="text"][data-answer-type="fill_in"]').forEach(inp => {
            inp.addEventListener('input', e => debounceSave(e.target.dataset.questionId, {questionId:e.target.dataset.questionId, type:'fill_in', value:e.target.value}));
        });
        document.querySelectorAll('input[type="file"][data-answer-type="file"]').forEach(inp => {
            inp.addEventListener('change', e => { const f=e.target.files[0]; if(f) queueSave({questionId:e.target.dataset.questionId, type:'file', file:f}); });
        });
        setStatus('','Autosave enabled.');
    }
    </script>
</head>
<body>
@if($requiresProctoring)
<div id="proctoringModal" class="proctoring-overlay" style="display:none">
    <div class="proctoring-modal">

        <div class="mb-3">
            <i class="bi bi-shield-lock-fill text-primary" style="font-size:2.2rem"></i>
        </div>
        <h4 class="fw-bold mb-1">Continue to your exam</h4>
        <p class="text-muted mb-0" style="font-size:.9rem">
            Your <strong>verification photo is already on file</strong>. Allow camera and microphone again for
            <strong class="text-danger">live session monitoring</strong> while you answer questions.
        </p>

        <div class="security-notice">
            <strong><i class="bi bi-exclamation-triangle-fill me-1"></i>Security Notice:</strong>
            <ul class="mb-0 mt-1">
                <li>Your camera feed may be used for proctoring evidence (video only)</li>
                <li>Microphone permission is required, but microphone audio is not recorded</li>
                <li>Suspicious activity is automatically flagged and reported</li>
                <li>Do not switch tabs or close this window</li>
                <li>Ensure you are in a quiet, well-lit environment</li>
            </ul>
        </div>

        {{-- Camera preview --}}
        <video id="proctoring-video-preview" class="proctoring-video-preview" autoplay muted playsinline></video>

        {{-- Dynamic permission status --}}
        <div id="camera-permission-status" class="permission-status-box">
            <div class="spinner-border spinner-border-sm text-secondary" role="status" aria-hidden="true"></div>
            <span class="text-muted">Requesting camera and microphone access…</span>
        </div>

        {{-- Shown only when permissions are denied --}}
        <div id="permission-denied-guide" class="permission-denied-box" style="display:none">
            <strong><i class="bi bi-camera-video-off-fill me-1"></i>Access was blocked by your browser.</strong>
            <p class="mb-1 mt-1">To grant access, click the <strong>camera/lock icon</strong> in your browser's address bar, set both Camera and Microphone to <strong>Allow</strong>, then click <strong>Try Again</strong>.</p>
            <div id="browser-hint" class="browser-hint"></div>
        </div>

        {{-- Shown when no device is found --}}
        <div id="no-device-guide" class="no-device-guide" style="display:none">
            <strong><i class="bi bi-camera-video-off-fill me-1"></i>No camera or microphone detected.</strong>
            <p class="mb-0 mt-1">Connect a working webcam and microphone, then click <strong>Try Again</strong>. If you are on a laptop, ensure the built-in camera is not disabled in device settings.</p>
        </div>

        {{-- Shown on success --}}
        <div id="permission-granted-box" class="permission-granted-box" style="display:none">
            <i class="bi bi-check-circle-fill me-1 text-success"></i>
            <strong>Camera and microphone access granted.</strong> Press <strong>Continue to exam</strong> when you are ready.
        </div>

        {{-- Action buttons --}}
        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center mt-3">
            <button id="retry-permission-btn" class="btn btn-outline-secondary order-2 order-sm-1" style="display:none" type="button">
                <i class="bi bi-arrow-clockwise me-1"></i>Try Again
            </button>
            <button id="start-proctoring-btn" class="btn btn-success btn-lg px-4 order-1 order-sm-2 flex-grow-1 flex-sm-grow-0" style="min-height:48px" disabled type="button">
                <i class="bi bi-play-circle me-1"></i>Continue to exam
            </button>
        </div>
        <p class="text-muted mt-2 mb-0" style="font-size:.75rem">
            <i class="bi bi-lock-fill me-1"></i>Monitoring stays active for suspicious activity (tab switches, etc.).
        </p>

    </div>
</div>
@endif

<div id="exam-content-area" style="display:{{ $requiresProctoring ? 'none' : 'block' }}">
    <div class="exam-header">
        <span class="exam-header-title">{{ $exam->title }}</span>
        <span class="exam-header-student">
            <i class="bi bi-person-fill me-1"></i>{{ $studentName }} &middot; {{ $studentIndex }}
        </span>
        <div class="autosave-status" id="autosave-status">
            <span class="autosave-dot"></span>
            <span id="autosave-text">Autosave enabled.</span>
        </div>
        @if($requiresProctoring)
        <div class="autosave-status" title="Face detection status">
            <span class="face-dot face-dot--loading" id="face-detection-status" title="Loading face detection…"></span>
            <span style="font-size:.75rem;color:var(--eq-muted)">Face</span>
        </div>
        @endif
        @if(!$isUnlimited)
        <div class="timer-display" id="timer-display">
            <i class="bi bi-clock me-1" style="font-size:.875rem"></i><span id="timer">--:--</span>
        </div>
        @endif
    </div>

    <div class="container-fluid content content-safe">
        @if($exam->instructions)
        <div class="alert alert-light border mb-3 py-2 px-3" style="font-size:.9rem">
            <strong>Instructions:</strong> {{ $exam->instructions }}
        </div>
        @endif

        @if($exam->assessment_file)
        <div class="alert alert-info py-2 mb-3 d-flex flex-column flex-sm-row flex-wrap align-items-start gap-2">
            <strong class="me-0">Assessment Document:</strong>
            <a href="{{ Storage::url($exam->assessment_file) }}" class="btn btn-sm btn-primary" download>
                <i class="bi bi-download me-1"></i>Download Questions
            </a>
        </div>
        @endif

        @if($questions->count() > 0)
        <div class="q-nav" id="q-nav">
            <span class="q-nav-label"><i class="bi bi-grid-3x3-gap"></i>Questions - tap to jump</span>
            <div class="q-nav-btns">
            @foreach($questions as $i => $q)
                @php $saved = $savedAnswers->get($q->id); $isAnswered = $saved && (!empty($saved->selected_option) || !empty($saved->theory_answer) || !empty($saved->file_upload)); @endphp
                <a href="#q-card-{{ $q->id }}" class="q-nav-btn {{ $isAnswered ? 'answered' : '' }}" id="q-nav-{{ $q->id }}" title="Question {{ $i+1 }}">{{ $i+1 }}</a>
            @endforeach
            </div>
        </div>
        @endif

        <form id="examForm" action="{{ route('student.submit') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="exam_attempt_id" name="exam_attempt_id" value="{{ $attempt->id }}">
            @php $typeLabels = ['mcq'=>'Multiple Choice','theory'=>'Essay','fill_in'=>'Fill in the Blank','file'=>'File Upload']; @endphp
            @foreach($questions as $index => $q)
                @php $saved = $savedAnswers->get($q->id); @endphp
                <div class="question-card" id="q-card-{{ $q->id }}">
                    <div class="question-card-header">
                        <span class="q-number-badge">{{ $index+1 }}</span>
                        <span class="q-type-tag">{{ $typeLabels[$q->q_type->value] ?? $q->q_type->value }}</span>
                        <span class="q-marks-tag">{{ $q->marks }} mark{{ $q->marks != 1 ? 's' : '' }}</span>
                    </div>
                    <div class="question-card-body">
                        <p class="question-text">{{ $q->question_text }}</p>

                        @if($q->q_type->value === 'mcq')
                            @foreach(['A'=>$q->option_a,'B'=>$q->option_b,'C'=>$q->option_c,'D'=>$q->option_d,'E'=>$q->option_e] as $letter => $text)
                                @if($text)
                                @php $isChecked = $saved && $saved->selected_option === $letter; @endphp
                                <label class="mcq-option {{ $isChecked ? 'is-selected' : '' }}" for="q{{ $q->id . $letter }}">
                                    <input type="radio" name="answers[{{ $q->id }}]" value="{{ $letter }}"
                                           id="q{{ $q->id . $letter }}"
                                           data-question-id="{{ $q->id }}" data-answer-type="mcq"
                                           {{ $isChecked ? 'checked' : '' }}>
                                    <span class="option-letter">{{ $letter }}</span>
                                    <span class="option-text">{{ $text }}</span>
                                </label>
                                @endif
                            @endforeach

                        @elseif($q->q_type->value === 'fill_in')
                            <input type="text" name="fill_in_answers[{{ $q->id }}]" class="form-control"
                                   placeholder="Type your answer here…"
                                   value="{{ $saved?->theory_answer ?? '' }}"
                                   data-question-id="{{ $q->id }}" data-answer-type="fill_in">

                        @elseif($q->q_type->value === 'theory')
                            <textarea name="theory_answers[{{ $q->id }}]" class="form-control" rows="5"
                                      placeholder="Write your answer here…"
                                      data-question-id="{{ $q->id }}" data-answer-type="theory">{{ $saved?->theory_answer ?? '' }}</textarea>

                        @elseif($q->q_type->value === 'file')
                            <div class="border rounded p-3 bg-light">
                                <p class="mb-2 fw-semibold" style="font-size:.875rem">Upload your work (PDF, Image, or Document)</p>
                                @if($q->option_e)
                                    <a href="{{ $q->option_e }}" class="btn btn-sm btn-outline-primary mb-2" target="_blank">
                                        <i class="bi bi-file-earmark-arrow-down me-1"></i>Download Question File
                                    </a>
                                @endif
                                @if($saved?->file_upload)
                                    <div class="alert alert-success py-1 px-2 small mb-2">File saved. Upload a new one to replace it.</div>
                                    <a class="btn btn-sm btn-outline-success mb-2" href="{{ Storage::url($saved->file_upload) }}" target="_blank">View Uploaded File</a>
                                @endif
                                <input type="file" name="file_answers_{{ $q->id }}" class="form-control form-control-sm"
                                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                       data-question-id="{{ $q->id }}" data-answer-type="file">
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            <div class="submit-area">
                <div class="submit-summary">
                    <strong id="answered-count">0</strong> of <strong>{{ $questions->count() }}</strong>
                    question{{ $questions->count() != 1 ? 's' : '' }} answered
                    <div id="submit-help-text" class="mt-1 small text-warning" style="display:none;">
                        Please answer all questions before submitting.
                    </div>
                    @if($exam->submission_password_enabled)
                    <div class="mt-2">
                        <label class="form-label small fw-semibold mb-1" for="submission_password">
                            Submission password required
                        </label>
                        <input type="password" name="submission_password" id="submission_password" class="form-control form-control-sm" placeholder="Enter submission password">
                        @error('submission_password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif
                </div>
                <button id="submit-exam-btn" type="submit" class="btn-submit-exam" onclick="return confirm('Submit your exam? You cannot make changes after submission.')" disabled>
                    <i class="bi bi-check2-circle me-1"></i>Submit Exam
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const totalQuestions = {{ $questions->count() }};
    const submitBtn = document.getElementById('submit-exam-btn');
    const submitHelp = document.getElementById('submit-help-text');
    const examForm = document.getElementById('examForm');
    const submissionPasswordInput = document.getElementById('submission_password');

    function markNav(qid, answered) {
        const btn = document.getElementById('q-nav-' + qid);
        if (btn) btn.classList.toggle('answered', answered);
    }
    function updateCount() {
        let c = 0;
        document.querySelectorAll('.q-nav-btn.answered').forEach(() => c++);
        const el = document.getElementById('answered-count');
        if (el) el.textContent = c;
        const allAnswered = examTimeExpired || (totalQuestions > 0 && c >= totalQuestions);
        if (submitBtn) submitBtn.disabled = !allAnswered;
        if (submitHelp) {
            if (examTimeExpired) {
                submitHelp.style.display = 'block';
                submitHelp.textContent =
                    'Time is up. Enter the submission password, then press Submit exam.';
            } else {
                submitHelp.style.display = allAnswered ? 'none' : 'block';
                submitHelp.textContent = 'Please answer all questions before submitting.';
            }
        }
        return allAnswered;
    }
    document.querySelectorAll('.mcq-option input[type="radio"]').forEach(r => {
        if (r.checked) { r.closest('.question-card-body').querySelectorAll('.mcq-option').forEach(o=>o.classList.remove('is-selected')); r.closest('.mcq-option').classList.add('is-selected'); }
        r.addEventListener('change', function () {
            this.closest('.question-card-body').querySelectorAll('.mcq-option').forEach(o=>o.classList.remove('is-selected'));
            this.closest('.mcq-option').classList.add('is-selected');
            markNav(this.dataset.questionId, true); updateCount();
        });
    });
    document.querySelectorAll('textarea[data-answer-type="theory"],input[data-answer-type="fill_in"]').forEach(el => {
        function check() { markNav(el.dataset.questionId, el.value.trim().length > 0); updateCount(); }
        el.addEventListener('input', check); check();
    });
    document.querySelectorAll('input[type="file"][data-answer-type="file"]').forEach(input => {
        input.addEventListener('change', function () {
            const hasFile = !!(this.files && this.files.length > 0);
            markNav(this.dataset.questionId, hasFile || document.getElementById('q-nav-' + this.dataset.questionId)?.classList.contains('answered'));
            updateCount();
        });
    });

    if (examForm) {
        examForm.addEventListener('submit', function (e) {
            if (!examTimeExpired && !updateCount()) {
                e.preventDefault();
                alert('Please answer all questions before submitting.');
                return;
            }

            if (isSubmissionPasswordRequired) {
                const pwd = (submissionPasswordInput?.value || '').trim();
                if (pwd.length < 1) {
                    e.preventDefault();
                    alert('Please enter the submission password before submitting.');
                    if (submissionPasswordInput) {
                        submissionPasswordInput.focus();
                    }
                    return;
                }
            }

            if (examTimeExpired) {
                let autoSubmitInput = examForm.querySelector('input[name="auto_submit"]');
                if (!autoSubmitInput) {
                    autoSubmitInput = document.createElement('input');
                    autoSubmitInput.type = 'hidden';
                    autoSubmitInput.name = 'auto_submit';
                    examForm.appendChild(autoSubmitInput);
                }
                autoSubmitInput.value = '1';
            }
        });
    }

    updateCount();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    /* Pin dark mode, suppress the theme.js floating toggle */
    try { localStorage.setItem('ses-theme', 'dark'); } catch(e) {}
    document.addEventListener('DOMContentLoaded', function () {
        const fab = document.getElementById('themeToggleFab');
        if (fab) fab.remove();
    });
</script>
</body>
</html>
