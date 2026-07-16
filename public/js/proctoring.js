/**
 * Proctoring system for examination security.
 *
 * URL configuration is injected by the blade template via window.PROCTORING_CONFIG
 * so this file stays free of hard-coded paths.
 *
 * Expected shape of window.PROCTORING_CONFIG:
 * {
 *   logUrl        : '/proctoring/log',
 *   captureUrl    : '/proctoring/capture',
 *   recordingUrl  : '/proctoring/recording',
 *   enableUrl     : '/proctoring/enable',
 *   terminatedUrl : '/exam/terminated',
 *   cheatEventUrl : '/proctoring/cheat-event',
 *   csrfToken     : '...',
 * }
 */

// Resolve config at module load-time (safe even if the object isn't set yet —
// individual methods re-read it at call-time via _cfg()).
function _cfg(key) {
    return (window.PROCTORING_CONFIG && window.PROCTORING_CONFIG[key]) || '';
}

class ExamProctoring {
    constructor() {
        this.isRecording = false;
        this.videoStream = null;
        this.audioStream = null;
        this.recordingStream = null;
        this.recordingChunks = [];
        this.mediaRecorder = null;
        this.webSocket = null;
        this.examStarted = false;
        this.lastEvidenceUploadAt = 0;
        this.evidenceUploadCooldownMs = 12000;
        this.submitHooksAttached = false;

        // Audio monitoring state (active only when student leaves the tab)
        this.audioContext = null;
        this.audioMonitorInterval = null;
        this.audioAlertCooldown = 0;
        this.audioMonitoringEnabled = false;

        // ── Fullscreen support flags ─────────────────────────────────────
        // Set to true if requestFullscreen() was denied or not supported,
        // so we never penalise students when the browser can't do fullscreen.
        this.fullscreenEnforcementDisabled = false;
        // Set to true only after fullscreen is confirmed active at least once.
        // We never flag an exit unless entry was first confirmed.
        this.fullscreenEverEntered = false;
        // ─────────────────────────────────────────────────────────────────

        // ── Flexible violation tracking ──────────────────────────────────
        // How many times each violation type has been counted (after cooldown)
        this.violationCounts = {};
        // Timestamp of the last counted violation per type (for per-type cooldown)
        this.lastViolationByType = {};
        // Minimum gap between two counted violations of the same type (ms)
        this.VIOLATION_COOLDOWN_MS = 12000;   // 12 seconds
        // How many toast warnings are shown before the blocking overlay appears
        this.WARNINGS_BEFORE_OVERLAY = 1;
        // Fullscreen grace period: student has this many ms to return before overlay
        this.FULLSCREEN_GRACE_MS = 4000;
        this.fullscreenGraceTimer = null;
        this.fullscreenCountdownInterval = null;
        this.lastTabSwitchBeepAt = 0;
        this.tabSwitchBeepCooldownMs = 700;
        this.isFinalizingSubmission = false;
        this.pendingSubmissionIntent = false;
        this.submissionIntentResetTimer = null;
        // ─────────────────────────────────────────────────────────────────

        /** When true, server returned captures_full — skip further JPEG uploads. */
        this.imageCapturesQuotaReached = false;

        this.init();
    }

    init() {
        // Check for required browser features
        if (!this.checkBrowserSupport()) {
            alert('Your browser does not support the required features for this exam. Please use a modern browser like Chrome or Firefox.');
            return;
        }

        // Set up event listeners for security measures
        this.setupSecurityListeners();
    }

    checkBrowserSupport() {
        return navigator.mediaDevices &&
            navigator.mediaDevices.getUserMedia &&
            window.MediaRecorder &&
            WebSocket;
    }

    async requestMediaAccess() {
        try {
            // Check if block overlay exists from previous error and remove it safely
            const oldOverlay = document.getElementById('proctoring-media-blocker');
            if (oldOverlay) oldOverlay.remove();

            // Request video and audio access
            this.videoStream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 640 }, height: { ideal: 480 } },
                audio: true
            });

            // Display video preview
            const videoPreview = document.getElementById('proctoring-video-preview');
            if (videoPreview) {
                videoPreview.srcObject = this.videoStream;
                videoPreview.style.display = 'block';
            }

            // Start recording
            this.startRecording();

            // Connect to monitoring server
            this.connectMonitoringServer();

            // Check continually if stream is killed abruptly
            this.enforceStreamActivity();

        } catch (error) {
            console.error('Error accessing media devices:', error);
            this.showMediaBlockerOverlay();
        }
    }

    enforceStreamActivity() {
        // Require the stream to be dead for at least 3 consecutive checks
        // (≈ 9 seconds) before blocking the exam.  A single-frame glitch or
        // brief browser permission re-check should not lock the student out.
        let consecutiveFailures = 0;
        const FAILURES_BEFORE_BLOCK = 3;

        setInterval(() => {
            if (!this.examStarted) return;
            if (this.isFinalizingSubmission) return;
            if (document.getElementById('proctoring-media-blocker')) return;

            const streamDead = !this.videoStream || !this.videoStream.active;
            const trackDead = (() => {
                if (streamDead) return false; // already counted above
                const tracks = this.videoStream.getVideoTracks();
                return tracks.length === 0 ||
                       tracks[0].readyState === 'ended' ||
                       !tracks[0].enabled;
            })();

            if (streamDead || trackDead) {
                consecutiveFailures++;
                if (consecutiveFailures >= FAILURES_BEFORE_BLOCK) {
                    const reason = streamDead ? 'camera or microphone' : 'camera stream';
                    this.logSuspiciousActivity('camera_disabled', `Student disabled ${reason} during exam`);
                    this.sendSecurityAlert(`Student disabled ${reason}`);
                    this.showMediaBlockerOverlay();
                    consecutiveFailures = 0;
                }
            } else {
                consecutiveFailures = 0; // stream is healthy — reset counter
            }
        }, 3000);
    }

    showMediaBlockerOverlay() {
        // Prevent multiple overlays
        if (document.getElementById('proctoring-media-blocker')) return;

        const overlay = document.createElement('div');
        overlay.id = 'proctoring-media-blocker';
        Object.assign(overlay.style, {
            position: 'fixed',
            top: '0',
            left: '0',
            width: '100%',
            height: '100%',
            backgroundColor: 'rgba(15, 23, 42, 0.98)',
            backdropFilter: 'blur(15px)',
            zIndex: '9999999',
            display: 'flex',
            flexDirection: 'column',
            justifyContent: 'center',
            alignItems: 'center',
            padding: '2rem',
            textAlign: 'center'
        });

        overlay.innerHTML = `
            <div style="background: #1e293b; border: 1px solid #eab308; border-radius: 16px; padding: 3rem; max-width: 600px; box-shadow: 0 25px 50px -12px rgba(234, 179, 8, 0.25);">
                <i class="bi bi-camera-video-off" style="font-size: 5rem; color: #eab308; margin-bottom: 1.5rem; display: block;"></i>
                <h2 style="font-weight: 700; margin-bottom: 1rem; color: #f8fafc; font-family: 'Inter', sans-serif;">Hardware Access Required</h2>
                <p style="color: #cbd5e1; margin-bottom: 2rem; font-size: 1.15rem; line-height: 1.6;">You must enable your camera and microphone to take this proctored examination. You cannot proceed until access is granted through your browser settings.</p>
                
                <div style="padding: 1rem; background: rgba(234, 179, 8, 0.1); border-radius: 8px; margin-bottom: 2.5rem; border-left: 4px solid #eab308; text-align: left;">
                    <strong style="color: #eab308; display: block; margin-bottom: 0.5rem;"><i class="bi bi-info-circle"></i> Instructions:</strong>
                    <span style="color: #cbd5e1; font-size: 0.95rem;">Click the lock icon in your browser URL bar at the top, change Camera and Microphone to "Allow", and then click the button below to re-verify.</span>
                </div>
                
                <button id="btn-reverify-hardware" style="background: #eab308; color: #1e293b; border: none; padding: 1rem 2.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 1.1rem; transition: filter 0.2s;">
                    Verify Hardware Access
                </button>
            </div>
        `;

        document.body.appendChild(overlay);

        // Block all scrolling
        document.body.style.overflow = 'hidden';

        // Button Event Listener
        document.getElementById('btn-reverify-hardware').addEventListener('click', () => {
            this.requestMediaAccess();
        });
    }

    startRecording() {
        if (!this.videoStream) return;
        const streamForRecording = this.getVideoOnlyRecordingStream();
        this.recordingStream = streamForRecording;

        const recorderOptions = {};
        if (MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported('video/webm;codecs=vp8,opus')) {
            recorderOptions.mimeType = 'video/webm;codecs=vp8,opus';
        }

        this.mediaRecorder = Object.keys(recorderOptions).length > 0
            ? new MediaRecorder(streamForRecording, recorderOptions)
            : new MediaRecorder(streamForRecording);
        this.recordingChunks = [];
        this.mediaRecorder.ondataavailable = (event) => {
            if (event.data.size > 0) {
                this.recordingChunks.push(event.data);
                // Keep the rolling buffer at approximately the last 10 seconds 
                // 5 chunks x 2000ms timeslices = ~10s of memory
                if (this.recordingChunks.length > 5) {
                    this.recordingChunks.shift();
                }
            }
        };

        this.mediaRecorder.onstop = () => {
            this.saveEvidence('final_submission', true);
        };

        this.mediaRecorder.start(2000); // Trigger data chunk every 2 seconds
        this.isRecording = true;
    }

    stopRecording() {
        this.stopAudioMonitoring();

        if (this.mediaRecorder && this.isRecording) {
            this.mediaRecorder.stop();
            this.isRecording = false;
        }

        if (this.videoStream) {
            this.videoStream.getTracks().forEach(track => track.stop());
        }
        if (this.recordingStream && this.recordingStream !== this.videoStream) {
            this.recordingStream.getTracks().forEach(track => track.stop());
        }
        this.recordingStream = null;
    }

    getVideoOnlyRecordingStream() {
        // Keep mic permission required for proctoring checks, but avoid storing audio in evidence files.
        const videoTracks = this.videoStream?.getVideoTracks?.() || [];
        if (!videoTracks.length) {
            return this.videoStream;
        }
        if (typeof MediaStream === 'undefined') {
            return this.videoStream;
        }
        try {
            const track = videoTracks[0].clone ? videoTracks[0].clone() : videoTracks[0];
            return new MediaStream([track]);
        } catch (_) {
            return this.videoStream;
        }
    }

    beepOnTabSwitch() {
        // Audio notifications intentionally disabled.
        return;
    }

    armSubmissionIntent() {
        this.pendingSubmissionIntent = true;
        if (this.submissionIntentResetTimer) {
            clearTimeout(this.submissionIntentResetTimer);
        }
        // Covers native confirm/fullscreen transitions before submit fires.
        this.submissionIntentResetTimer = setTimeout(() => {
            this.pendingSubmissionIntent = false;
            this.submissionIntentResetTimer = null;
        }, 4000);
    }

    beginSubmissionFlow() {
        this.isFinalizingSubmission = true;
        this.pendingSubmissionIntent = false;
        if (this.submissionIntentResetTimer) {
            clearTimeout(this.submissionIntentResetTimer);
            this.submissionIntentResetTimer = null;
        }

        // Prevent false proctoring violations while submitting the exam.
        this.examStarted = false;
        this.stopAudioMonitoring();
        this.cancelFullscreenGrace();
    }

    captureImageEvidence(violationType, description) {
        // Capture an image from the current video stream
        if (!this.videoStream || this.imageCapturesQuotaReached) return;

        const canvas = document.createElement('canvas');
        const videoElement = document.createElement('video');
        
        // Get the video track from the stream
        const videoTrack = this.videoStream.getVideoTracks()[0];
        if (!videoTrack) return;
        
        // Create a temporary video element
        videoElement.srcObject = this.videoStream;
        videoElement.play();
        
        videoElement.addEventListener('loadedmetadata', () => {
            canvas.width = videoElement.videoWidth;
            canvas.height = videoElement.videoHeight;
            
            const ctx = canvas.getContext('2d');
            ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
            
            // Convert to data URL
            const imageData = canvas.toDataURL('image/jpeg', 0.8);
            
            // Send image to server
            this.sendImageToServer(imageData, violationType, description);
        });
    }

    /**
     * Capture a required "pre-start" verification photo and wait for upload.
     * Returns a Promise that resolves on successful upload, rejects otherwise.
     */
    capturePreStartPhoto() {
        return new Promise((resolve, reject) => {
            if (!this.videoStream) {
                reject(new Error('No camera stream'));
                return;
            }

            const videoTrack = this.videoStream.getVideoTracks()[0];
            if (!videoTrack) {
                reject(new Error('No video track'));
                return;
            }

            const canvas = document.createElement('canvas');
            const videoElement = document.createElement('video');
            videoElement.srcObject = this.videoStream;

            const cleanup = () => {
                try { videoElement.pause(); } catch (_) {}
                videoElement.srcObject = null;
            };

            const onReady = () => {
                try {
                    canvas.width = videoElement.videoWidth || 640;
                    canvas.height = videoElement.videoHeight || 480;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
                    const imageData = canvas.toDataURL('image/jpeg', 0.85);

                    this.sendImageToServer(imageData, 'pre_start', 'Pre-start identity verification')
                        .then(() => { cleanup(); resolve(); })
                        .catch((e) => { cleanup(); reject(e); });
                } catch (e) {
                    cleanup();
                    reject(e);
                }
            };

            // Some browsers need play() to populate videoWidth/videoHeight reliably.
            videoElement.addEventListener('loadedmetadata', () => {
                const p = videoElement.play();
                if (p && typeof p.then === 'function') {
                    p.then(() => setTimeout(onReady, 250)).catch(() => setTimeout(onReady, 250));
                } else {
                    setTimeout(onReady, 250);
                }
            }, { once: true });
        });
    }
    
    sendImageToServer(imageData, violationType, description) {
        const formData = new FormData();
        formData.append('image_data', imageData);
        formData.append('exam_attempt_id', this.getExamAttemptId());
        formData.append('activity_type', violationType);
        formData.append('description', description);
        formData.append('severity', this.getSeverityForActivity(violationType));

        const captureUrl = _cfg('captureUrl') || '/proctoring/capture';
        return fetch(captureUrl, {
            method      : 'POST',
            headers     : { 'X-CSRF-TOKEN': this.getCSRFToken() },
            body        : formData,
            credentials : 'same-origin',
        }).then(async (r) => {
            let data = {};
            try {
                data = await r.json();
            } catch (_) {
                data = {};
            }
            if (!r.ok) {
                throw new Error('Capture upload failed (' + r.status + ')');
            }
            if (data.captures_full) {
                this.imageCapturesQuotaReached = true;
            }
            return data;
        });
    }
    
    getSeverityForActivity(activityType) {
        const severityMap = {
            'tab_switch': 'high',
            'window_blur': 'medium',
            'print_attempt': 'medium',
            'right_click': 'low',
            'dev_tools': 'high',
            'copy_attempt': 'medium',
            'paste_attempt': 'medium',
            'screenshot_attempt': 'high',
            'exit_fullscreen': 'high',
            'camera_disabled': 'low'
        };
        return severityMap[activityType] || 'medium';
    }

    connectMonitoringServer() {
        // Connect to WebSocket server for real-time monitoring
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        this.webSocket = new WebSocket(`${protocol}//${window.location.host}/ws/proctoring`);

        this.webSocket.onopen = () => {
            console.log('Connected to proctoring server');
        };

        this.webSocket.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleMonitoringMessage(data);
        };

        this.webSocket.onerror = (error) => {
            console.error('WebSocket error:', error);
        };
    }

    handleMonitoringMessage(data) {
        // Handle messages from monitoring server
        switch (data.type) {
            case 'alert':
                this.showSecurityAlert(data.message);
                break;
            case 'terminate':
                this.terminateExam(data.reason);
                break;
        }
    }

    blockScreenshot() {
        if (!this.examStarted) return;

        // Immediately cover all content so the screenshot captures nothing
        let blocker = document.getElementById('screenshot-blocker');
        if (!blocker) {
            blocker = document.createElement('div');
            blocker.id = 'screenshot-blocker';
            Object.assign(blocker.style, {
                position: 'fixed',
                inset: '0',
                zIndex: '2147483647',
                background: '#0f172a',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                flexDirection: 'column',
                color: '#ef4444',
                fontFamily: 'Inter, sans-serif',
                textAlign: 'center',
                padding: '2rem'
            });
            blocker.innerHTML = `
                <i class="bi bi-slash-circle" style="font-size:4rem;margin-bottom:1rem;"></i>
                <h2 style="font-weight:700;font-size:1.5rem;margin:0 0 0.5rem;">Screenshots are not permitted</h2>
                <p style="color:#94a3b8;margin:0;">This attempt has been logged and reported to your lecturer.</p>
            `;
            document.body.appendChild(blocker);
        }
        blocker.style.display = 'flex';

        this.logSuspiciousActivity('screenshot_attempt', 'Student attempted to take a screenshot');
        this.sendSecurityAlert('Student attempted to take a screenshot');
        this.captureImageEvidence('screenshot_attempt', 'Screenshot key pressed');
    }

    restoreAfterScreenshot() {
        // Brief delay so the blackout is what gets captured, then restore
        setTimeout(() => {
            const blocker = document.getElementById('screenshot-blocker');
            if (blocker) blocker.style.display = 'none';
        }, 800);
    }

    injectWatermark(studentName, studentIndex) {
        if (document.getElementById('exam-watermark')) return;

        const wm = document.createElement('div');
        wm.id = 'exam-watermark';
        const text = `${studentName} · ${studentIndex}`;

        Object.assign(wm.style, {
            position: 'fixed',
            inset: '0',
            zIndex: '9998',
            pointerEvents: 'none',
            overflow: 'hidden',
            userSelect: 'none'
        });

        // Tile repeating diagonal text across the whole viewport
        let html = '';
        for (let row = -2; row < 18; row++) {
            for (let col = -2; col < 8; col++) {
                html += `<span style="
                    position: absolute;
                    left: ${col * 280}px;
                    top: ${row * 110}px;
                    transform: rotate(-30deg);
                    font-size: 13px;
                    font-family: Inter, sans-serif;
                    font-weight: 600;
                    color: rgba(0,0,0,0.07);
                    white-space: nowrap;
                    letter-spacing: 0.05em;
                ">${text}</span>`;
            }
        }
        wm.innerHTML = html;
        document.body.appendChild(wm);
    }

    // Audio monitoring intentionally disabled.
    startAudioMonitoring() {
        return;
    }

    // Stop audio monitoring — called when student returns to the tab/window
    stopAudioMonitoring() {
        if (this.audioMonitorInterval) {
            clearInterval(this.audioMonitorInterval);
            this.audioMonitorInterval = null;
        }
        if (this.audioContext) {
            this.audioContext.close().catch(() => {});
            this.audioContext = null;
        }
    }

    /**
     * Enable or disable microphone tracks on all proctoring streams.
     * While the exam tab is in the background, turning capture off avoids
     * browser/OS feedback and stray noise; tracks are restored when the tab is visible again.
     */
    setProctoringMicrophoneCaptureEnabled(enabled) {
        const streams = new Set();
        if (this.videoStream) streams.add(this.videoStream);
        if (typeof window !== 'undefined' && window.proctoringStream) {
            streams.add(window.proctoringStream);
        }
        streams.forEach((stream) => {
            if (!stream || typeof stream.getAudioTracks !== 'function') return;
            stream.getAudioTracks().forEach((track) => {
                track.enabled = enabled;
            });
        });
        const preview = document.getElementById('proctoring-video-preview');
        if (preview) {
            preview.muted = true;
        }
    }

    setupSecurityListeners() {
        // Detect tab switching
        document.addEventListener('visibilitychange', () => {
            if (this.examStarted) {
                if (document.hidden) {
                    if (this.isFinalizingSubmission || this.pendingSubmissionIntent) return;
                    this.setProctoringMicrophoneCaptureEnabled(false);
                    this.stopAudioMonitoring();
                    this.captureImageEvidence('tab_switch', 'Student switched away from exam tab');
                    this.logSuspiciousActivity('tab_switch', 'Student switched away from exam tab');
                    this.sendSecurityAlert('Student switched away from exam tab');
                    this.handleViolation(
                        'tab_switch',
                        'Tab switching detected.',
                        'You switched away from the exam tab. Please stay on this page at all times.',
                        true
                    );
                } else {
                    this.setProctoringMicrophoneCaptureEnabled(true);
                    this.stopAudioMonitoring();
                    this.reenterFullscreen();
                }
            }
        });

        // Detect window focus loss / return
        window.addEventListener('blur', () => {
            if (this.examStarted) {
                if (this.isFinalizingSubmission || this.pendingSubmissionIntent) return;
                this.captureImageEvidence('window_blur', 'Exam window lost focus');
                this.logSuspiciousActivity('window_blur', 'Exam window lost focus');
                this.sendSecurityAlert('Exam window lost focus');
                this.handleViolation(
                    'window_blur',
                    'The exam window lost focus.',
                    'You left the exam window. Please keep this window active during your exam.',
                    true
                );
            }
        });

        window.addEventListener('focus', () => {
            if (this.examStarted) {
                if (!document.hidden) {
                    this.setProctoringMicrophoneCaptureEnabled(true);
                }
                this.stopAudioMonitoring();
                this.reenterFullscreen();
            }
        });

        // Prevent print
        window.addEventListener('beforeprint', (e) => {
            e.preventDefault();
            this.captureImageEvidence('print_attempt', 'Student attempted to print');
            this.logSuspiciousActivity('print_attempt', 'Student attempted to print');
            this.sendSecurityAlert('Student attempted to print exam');
        });

        // Prevent right-click
        document.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            this.captureImageEvidence('right_click', 'Student attempted to right-click');
            this.logSuspiciousActivity('right_click', 'Student attempted to right-click');
            this.sendSecurityAlert('Student attempted to right-click');
        });

        // Prevent F12, dev tools, and screenshot keys
        document.addEventListener('keydown', (e) => {
            // F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
            if (e.keyCode === 123 ||
                (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74)) ||
                (e.ctrlKey && e.keyCode === 85)) {
                e.preventDefault();
                this.captureImageEvidence('dev_tools', 'Student attempted to open developer tools');
                this.logSuspiciousActivity('dev_tools', 'Student attempted to open developer tools');
                this.sendSecurityAlert('Student attempted to open developer tools');
            }

            // PrintScreen / Snipping Tool shortcuts
            if (e.key === 'PrintScreen' ||
                (e.metaKey && e.shiftKey && (e.keyCode === 51 || e.keyCode === 52 || e.keyCode === 83)) || // macOS Cmd+Shift+3/4/S
                (e.ctrlKey && e.shiftKey && e.keyCode === 83)) { // Ctrl+Shift+S (some tools)
                e.preventDefault();
                this.blockScreenshot();
            }
        });

        // Restore content after PrintScreen key is released
        document.addEventListener('keyup', (e) => {
            if (e.key === 'PrintScreen') {
                this.restoreAfterScreenshot();
            }
        });

        // Detect image data pasted into clipboard (student screenshotted elsewhere and tried to paste)
        document.addEventListener('paste', (e) => {
            const items = e.clipboardData?.items || [];
            for (const item of items) {
                if (item.type.startsWith('image/')) {
                    e.preventDefault();
                    this.logSuspiciousActivity('screenshot_attempt', 'Image pasted from clipboard — possible screenshot detected');
                    this.sendSecurityAlert('Student pasted an image from clipboard');
                    return;
                }
            }
            e.preventDefault();
            this.captureImageEvidence('paste_attempt', 'Student attempted to paste content');
            this.logSuspiciousActivity('paste_attempt', 'Student attempted to paste content');
            this.sendSecurityAlert('Student attempted to paste content');
        });

        // Prevent copy / cut
        document.addEventListener('copy', (e) => {
            e.preventDefault();
            this.captureImageEvidence('copy_attempt', 'Student attempted to copy content');
            this.logSuspiciousActivity('copy_attempt', 'Student attempted to copy content');
            this.sendSecurityAlert('Student attempted to copy content');
        });

        document.addEventListener('cut', (e) => {
            e.preventDefault();
            this.logSuspiciousActivity('cut_attempt', 'Student attempted to cut content');
            this.sendSecurityAlert('Student attempted to cut content');
        });

    }

    logSuspiciousActivity(type, description) {
        // Capture image snapshot and rolling video clip for this violation
        this.captureImageEvidence(type, description);
        this.saveEvidence(type);

        // ── Send to the unified cheat-event endpoint ──────────────────
        this.sendCheatEvent(type, { description });

        // ── Also POST to the legacy Laravel log endpoint ──────────────
        const logUrl = _cfg('logUrl') || '/proctoring/log';
        fetch(logUrl, {
            method : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN' : _cfg('csrfToken'),
            },
            body: JSON.stringify({
                type,
                description,
                timestamp       : new Date().toISOString(),
                exam_attempt_id : this.getExamAttemptId(),
            }),
        }).catch(err => console.error('[Proctoring] Log error:', err));
    }

    /**
     * Send a structured cheat event to POST /proctoring/cheat-event.
     * Used by: logSuspiciousActivity, face detection callbacks, audio alerts.
     */
    sendCheatEvent(eventType, metadata = {}) {
        const url = _cfg('cheatEventUrl') || '/proctoring/cheat-event';
        const csrf = _cfg('csrfToken')    ||
            document.querySelector('meta[name="csrf-token"]')?.content || '';

        const payload = JSON.stringify({ event_type: eventType, metadata });

        fetch(url, {
            method : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN' : csrf,
            },
            body        : payload,
            credentials : 'same-origin',
            keepalive   : true,
        }).catch(err => console.warn('[Proctoring] cheat-event error:', err));
    }

    sendSecurityAlert(message) {
        if (this.webSocket && this.webSocket.readyState === WebSocket.OPEN) {
            this.webSocket.send(JSON.stringify({
                type: 'security_alert',
                message: message,
                exam_attempt_id: this.getExamAttemptId(),
                timestamp: new Date().toISOString()
            }));
        }
    }

    showSecurityAlert(message) {
        // Show a visual alert to the student
        const alertDiv = document.createElement('div');
        alertDiv.className = 'proctoring-alert';
        alertDiv.innerHTML = `
            <div class="alert alert-warning" style="position: fixed; top: 20px; right: 20px; z-index: 999999; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <strong>Security Notice:</strong> ${message}
            </div>
        `;

        document.body.appendChild(alertDiv);

        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

    showViolationOverlay(message) {
        // Only hide exam content when the blocking overlay is actually shown
        // (i.e. after warnings have been exhausted — not on first occurrence).
        const contentArea = document.getElementById('exam-content-area');
        if (contentArea) contentArea.style.display = 'none';

        // Prevent multiple overlays
        if (document.getElementById('proctoring-violation-overlay')) return;

        const overlay = document.createElement('div');
        overlay.id = 'proctoring-violation-overlay';
        Object.assign(overlay.style, {
            position: 'fixed',
            top: '0',
            left: '0',
            width: '100%',
            height: '100%',
            backgroundColor: 'rgba(15, 23, 42, 0.98)',
            backdropFilter: 'blur(15px)',
            zIndex: '9999999',
            display: 'flex',
            flexDirection: 'column',
            justifyContent: 'flex-start',
            alignItems: 'center',
            padding: '1rem',
            overflowY: 'auto',
            WebkitOverflowScrolling: 'touch',
            textAlign: 'center'
        });

        overlay.innerHTML = `
            <div style="
                background: #1e293b;
                border: 1px solid #ef4444;
                border-radius: 16px;
                padding: clamp(1rem, 3.5vw, 2.2rem);
                width: min(92vw, 600px);
                max-height: calc(100dvh - 2rem);
                overflow-y: auto;
                box-shadow: 0 25px 50px -12px rgba(239, 68, 68, 0.25);
            ">
                <i class="bi bi-exclamation-octagon-fill" style="font-size: 5rem; color: #ef4444; margin-bottom: 1.5rem; display: block;"></i>
                <h2 style="font-weight: 700; margin-bottom: 1rem; color: #f8fafc; font-family: 'Inter', sans-serif;">Security Violation Detected</h2>
                <p style="color: #cbd5e1; margin-bottom: 2rem; font-size: 1.15rem; line-height: 1.6;">${message}</p>
                
                <div style="padding: 1rem; background: rgba(239, 68, 68, 0.1); border-radius: 8px; margin-bottom: 2.5rem; border-left: 4px solid #ef4444; text-align: left;">
                    <strong style="color: #ef4444; display: block; margin-bottom: 0.5rem;"><i class="bi bi-camera-video"></i> Proctoring Note:</strong>
                    <span style="color: #cbd5e1; font-size: 0.95rem;">Please be advised that your professor has been instantly notified of this violation. Repeated violations may result in immediate automatic exam submission and academic review.</span>
                </div>
                
                <button id="btn-return-exam" style="background: #ef4444; color: white; border: none; width: 100%; min-height: 48px; padding: 0.9rem 1rem; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 1.05rem; transition: background 0.2s;">
                    I Understand, Return to Exam
                </button>
            </div>
        `;

        document.body.appendChild(overlay);

        // Button Event Listener
        document.getElementById('btn-return-exam').addEventListener('click', () => {
            overlay.remove();

            // Only re-request fullscreen if it was previously supported and entered.
            // Avoids accumulating duplicate fullscreenchange listeners.
            if (!this.fullscreenEnforcementDisabled) {
                const elem = document.documentElement;
                const requestFn = elem.requestFullscreen ||
                                  elem.mozRequestFullScreen ||
                                  elem.webkitRequestFullscreen ||
                                  elem.msRequestFullscreen;
                if (requestFn) {
                    try { requestFn.call(elem); } catch (e) { /* ignore */ }
                }
            }

            setTimeout(() => {
                const contentArea = document.getElementById('exam-content-area');
                if (contentArea) contentArea.style.display = 'block';
            }, 500);
        });
    }

    /**
     * Central violation handler.
     *
     * On the first (WARNINGS_BEFORE_OVERLAY) occurrences of a given type
     * (each separated by at least VIOLATION_COOLDOWN_MS) a non-blocking
     * toast warning is shown.  Only after that threshold is the full
     * blocking overlay triggered.  This prevents accidental OS events
     * (notifications, dialogs, alt-tab) from immediately locking out
     * the student.
     */
    handleViolation(type, toastMessage, overlayMessage, forceOverlay = false) {
        const now = Date.now();
        const last = this.lastViolationByType[type] || 0;

        // Ignore if the same violation type fired too recently (noise suppression).
        if (now - last < this.VIOLATION_COOLDOWN_MS) return;

        this.lastViolationByType[type] = now;
        this.violationCounts[type] = (this.violationCounts[type] || 0) + 1;
        const count = this.violationCounts[type];

        if (forceOverlay) {
            this.showViolationOverlay(overlayMessage);
            return;
        }

        if (count <= this.WARNINGS_BEFORE_OVERLAY) {
            // Show a non-blocking warning toast — exam content stays visible.
            const remaining = this.WARNINGS_BEFORE_OVERLAY - count + 1;
            const suffix = remaining > 0
                ? ` (warning ${count} of ${this.WARNINGS_BEFORE_OVERLAY})`
                : '';
            this.showWarningToast(`⚠ ${toastMessage}${suffix}`);
        } else {
            // Threshold exceeded — show the blocking overlay.
            this.showViolationOverlay(overlayMessage);
        }
    }

    /**
     * Non-blocking floating warning toast that auto-dismisses after 6 seconds.
     * Does NOT hide exam content.
     */
    showWarningToast(message) {
        const toast = document.createElement('div');
        Object.assign(toast.style, {
            position: 'fixed',
            top: '80px',
            left: '50%',
            transform: 'translateX(-50%)',
            background: '#92400e',
            color: '#fff',
            padding: '0.75rem 1.5rem',
            borderRadius: '8px',
            fontWeight: '600',
            fontSize: '0.9375rem',
            zIndex: '9999997',
            boxShadow: '0 4px 16px rgba(0,0,0,0.3)',
            textAlign: 'center',
            maxWidth: '90vw',
            transition: 'opacity 0.4s'
        });
        toast.textContent = message;
        document.body.appendChild(toast);

        // Auto-dismiss
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 450);
        }, 6000);
    }

    terminateExam(reason) {
        alert(`Exam terminated: ${reason}`);

        // Stop recording
        this.stopRecording();

        // Submit exam if possible
        if (typeof submitExam !== 'undefined') {
            submitExam();
        }

        // Redirect to the Laravel terminated page
        const terminatedUrl = _cfg('terminatedUrl') || '/exam/terminated';
        window.location.href = terminatedUrl + '?reason=' + encodeURIComponent(reason);
    }

    getExamAttemptId() {
        // 1. Injected by blade via window.PROCTORING_CONFIG (most reliable on Laravel)
        if (window.PROCTORING_CONFIG && window.PROCTORING_CONFIG.attemptId) {
            return String(window.PROCTORING_CONFIG.attemptId);
        }
        // 2. Legacy fallbacks
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('attempt_id') ||
            document.getElementById('exam_attempt_id')?.value ||
            'unknown';
    }

    startExam() {
        this.examStarted = true;
        this.setProctoringMicrophoneCaptureEnabled(!document.hidden);
        this.attachSubmissionHooks();

        // Enter fullscreen mode
        this.enterFullscreen();

        // Additional security measures during exam
        this.enforceSecurityMeasures();

        // Make an AJAX call to set the session variable
        this.enableProctoringSession();
    }

    
    attachSubmissionHooks() {
        if (this.submitHooksAttached) {
            return;
        }

        this.submitHooksAttached = true;

        const form = document.getElementById('examForm');
        if (form) {
            // Arm a short grace window before native confirm + submit cycle.
            form.addEventListener('click', (event) => {
                const target = event.target && event.target.closest
                    ? event.target.closest('button[type="submit"], input[type="submit"]')
                    : null;
                if (target) {
                    this.armSubmissionIntent();
                }
            }, true);

            form.addEventListener('submit', (event) => {
                this.beginSubmissionFlow();
                if (!this.isRecording) {
                    return;
                }

                event.preventDefault();
                this.saveEvidence('exam_submit', true);
                this.stopRecording();

                setTimeout(() => {
                    form.submit();
                }, 450);
            }, { once: true });
        }

        window.addEventListener('beforeunload', () => {
            this.saveEvidence('page_unload', true);
        });
    }

    saveEvidence(reason, force = false) {
        const attemptId = this.getExamAttemptId();
        if (!attemptId || attemptId === 'unknown') {
            return;
        }

        if (!Array.isArray(this.recordingChunks) || this.recordingChunks.length < 1) {
            return;
        }

        const now = Date.now();
        if (!force && (now - this.lastEvidenceUploadAt) < this.evidenceUploadCooldownMs) {
            return;
        }

        const mimeType = (this.mediaRecorder && this.mediaRecorder.mimeType)
            ? this.mediaRecorder.mimeType
            : 'video/webm';

        const recordingBlob = new Blob(this.recordingChunks, { type: mimeType });
        if (recordingBlob.size < 1024) {
            return;
        }

        const formData = new FormData();
        const safeReason = String(reason || 'evidence').replace(/[^a-zA-Z0-9_-]/g, '_');
        const filename = 'evidence_' + safeReason + '_' + now + '.webm';

        formData.append('recording', recordingBlob, filename);
        formData.append('exam_attempt_id', String(attemptId));
        formData.append('reason', safeReason);

        this.lastEvidenceUploadAt = now;

        const recordingUrl = _cfg('recordingUrl') || '/proctoring/recording';
        const csrf = this.getCSRFToken();

        if (force && navigator.sendBeacon) {
            // sendBeacon can't set headers — append token as a form field instead
            formData.append('_token', csrf);
            const sent = navigator.sendBeacon(recordingUrl, formData);
            if (sent) return;
            formData.delete('_token'); // clean up before the fetch fallback
        }

        fetch(recordingUrl, {
            method    : 'POST',
            headers   : { 'X-CSRF-TOKEN': csrf },
            body      : formData,
            keepalive : true,
        }).catch(error => {
            console.error('Error saving evidence recording:', error);
        });
    }
    enableProctoringSession() {
        const enableUrl = _cfg('enableUrl') || '/proctoring/enable';
        const csrf = _cfg('csrfToken') || this.getCSRFToken();
        fetch(enableUrl, {
            method : 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body   : '_token=' + encodeURIComponent(csrf),
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Failed to enable proctoring session:', data.message);
                }
            })
            .catch(error => {
                console.error('Error enabling proctoring session:', error);
            });
    }

    getCSRFToken() {
        return _cfg('csrfToken') ||
            document.querySelector('meta[name="csrf-token"]')?.content ||
            document.querySelector('input[name="_token"]')?.value ||
            '';
    }

    /**
     * Re-request fullscreen when user returns to tab/window.
     * Browsers often exit fullscreen when the tab loses focus; this restores it.
     */
    reenterFullscreen() {
        if (this.fullscreenEnforcementDisabled) return;
        const elem = document.documentElement;
        const requestFn = elem.requestFullscreen ||
                          elem.mozRequestFullScreen ||
                          elem.webkitRequestFullscreen ||
                          elem.msRequestFullscreen;
        if (!requestFn) return;
        const inFullscreen = !!(
            document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement ||
            document.msFullscreenElement
        );
        if (inFullscreen) return; // already in fullscreen
        try {
            const result = requestFn.call(elem);
            if (result && typeof result.then === 'function') {
                result.catch(() => {}); // ignore if denied (e.g. not from user gesture)
            }
        } catch (_) { /* ignore */ }
    }

    enterFullscreen() {
        const elem = document.documentElement;
        const requestFn = elem.requestFullscreen ||
                          elem.mozRequestFullScreen ||
                          elem.webkitRequestFullscreen ||
                          elem.msRequestFullscreen;

        if (!requestFn) {
            // Browser does not support fullscreen (e.g. iOS Safari) —
            // disable enforcement so students are never falsely penalised.
            this.fullscreenEnforcementDisabled = true;
            return;
        }

        // Listen for the actual fullscreen state change (fired by the browser
        // when fullscreen is granted, not just when requested).
        const onFsChange = () => {
            const isNowFull = !!(
                document.fullscreenElement ||
                document.webkitFullscreenElement ||
                document.mozFullScreenElement ||
                document.msFullscreenElement
            );
            if (isNowFull) {
                this.fullscreenEverEntered = true;
                this.cancelFullscreenGrace();
            }
        };
        document.addEventListener('fullscreenchange', onFsChange);
        document.addEventListener('webkitfullscreenchange', onFsChange);
        document.addEventListener('mozfullscreenchange', onFsChange);
        document.addEventListener('MSFullscreenChange', onFsChange);

        try {
            const result = requestFn.call(elem);
            if (result && typeof result.then === 'function') {
                result.catch(() => {
                    // Browser denied the request (not triggered by a user gesture,
                    // permission denied, or policy blocked it).
                    this.fullscreenEnforcementDisabled = true;
                });
            }
        } catch (e) {
            this.fullscreenEnforcementDisabled = true;
        }
    }

    enforceSecurityMeasures() {
        // Wait 5 seconds after startExam() before the first check.
        // This gives requestFullscreen() time to be granted by the browser
        // and prevents false alarms during the transition animation.
        setTimeout(() => {
            setInterval(() => {
                if (!this.examStarted) return;
                if (this.isFinalizingSubmission) return;

                // If the browser can't do fullscreen, never penalise the student.
                if (this.fullscreenEnforcementDisabled) return;

                // Only enforce if fullscreen was actually confirmed at least once.
                // If requestFullscreen() was called but the browser never granted
                // it (e.g. mobile, or user dismissed the prompt), do nothing.
                if (!this.fullscreenEverEntered) return;

                const inFullscreen = !!(
                    document.fullscreenElement ||
                    document.mozFullScreenElement ||
                    document.webkitFullscreenElement ||
                    document.msFullscreenElement
                );

                if (!inFullscreen) {
                    // Skip if a blocking overlay is already on screen.
                    if (document.getElementById('proctoring-violation-overlay') ||
                        document.getElementById('proctoring-media-blocker')) {
                        return;
                    }
                    // Start grace-period countdown if not already running.
                    if (!this.fullscreenGraceTimer) {
                        this.startFullscreenGrace();
                    }
                } else {
                    // Back in fullscreen — cancel any pending grace timer.
                    this.cancelFullscreenGrace();
                }
            }, 2000);
        }, 5000);
    }

    startFullscreenGrace() {
        // Show a non-blocking countdown toast giving the student time to return.
        let secondsLeft = Math.max(1, Math.ceil(this.FULLSCREEN_GRACE_MS / 1000));

        const toastId = 'fullscreen-grace-toast';
        let toast = document.getElementById(toastId);
        if (!toast) {
            toast = document.createElement('div');
            toast.id = toastId;
            Object.assign(toast.style, {
                position: 'fixed',
                bottom: '24px',
                left: '50%',
                transform: 'translateX(-50%)',
                background: '#b45309',
                color: '#fff',
                padding: '0.75rem 1.5rem',
                borderRadius: '8px',
                fontWeight: '600',
                fontSize: '0.9375rem',
                zIndex: '9999998',
                boxShadow: '0 4px 16px rgba(0,0,0,0.25)',
                textAlign: 'center',
                pointerEvents: 'none'
            });
            document.body.appendChild(toast);
        }

        const updateToast = () => {
            toast.textContent = `⚠ Fullscreen required — returning in ${secondsLeft}s…`;
        };
        updateToast();

        this.fullscreenCountdownInterval = setInterval(() => {
            secondsLeft--;
            if (secondsLeft > 0) {
                updateToast();
            } else {
                this.cancelFullscreenGrace();
                // Grace period expired — show the blocking overlay now.
                this.logSuspiciousActivity('exit_fullscreen', 'Student exited fullscreen mode');
                this.sendSecurityAlert('Student exited fullscreen mode');
                this.handleViolation(
                    'exit_fullscreen',
                    'Fullscreen mode exited.',
                    'You exited fullscreen view. Exams must be taken in fullscreen mode to ensure integrity.'
                );
            }
        }, 1000);

        // Also set a safety timer in case the interval drifts.
        this.fullscreenGraceTimer = setTimeout(() => {
            this.cancelFullscreenGrace();
        }, this.FULLSCREEN_GRACE_MS + 500);
    }

    cancelFullscreenGrace() {
        if (this.fullscreenGraceTimer) {
            clearTimeout(this.fullscreenGraceTimer);
            this.fullscreenGraceTimer = null;
        }
        if (this.fullscreenCountdownInterval) {
            clearInterval(this.fullscreenCountdownInterval);
            this.fullscreenCountdownInterval = null;
        }
        const toast = document.getElementById('fullscreen-grace-toast');
        if (toast) toast.remove();
    }
}

// window.examProctoring is created by take-exam.blade.php at the correct moment.
// This file only defines and exports the class.

// Allow Jest (CommonJS transform) to import the class for unit-testing.
// In the browser `module` is never defined, so this block never runs there.
if (typeof module !== 'undefined' && module.exports !== null) {
    module.exports = { ExamProctoring };
}


