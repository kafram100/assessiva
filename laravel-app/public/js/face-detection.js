/**
 * Face Detection Proctoring — face-detection.js
 *
 * Uses MediaPipe Tasks Vision (FaceLandmarker) to detect:
 *   • no_face_detected   — student not in frame
 *   • multiple_faces     — more than one person visible
 *   • looking_away       — head turned significantly left/right/up/down
 *
 * Loaded from take-exam.blade.php when proctoring is required.
 * Calls window.PROCTORING_CONFIG.cheatEventUrl to report events.
 */

'use strict';

class FaceDetectionProctoring {

    constructor(options = {}) {
        this._landmarker = null;
        this._videoEl    = null;
        this._running    = false;
        this._timer      = null;
        this._cooldowns  = {};   // { eventType: lastFiredTimestamp }
        this._streaks    = {};   // { eventType: consecutive detections }

        // ── Tuneable parameters ──────────────────────────────────────────
        this.CHECK_INTERVAL_MS   = options.checkIntervalMs  ?? 2000;
        this.ALERT_COOLDOWN_MS   = options.alertCooldownMs  ?? 12000;
        this.MAX_FACES           = options.maxFaces         ?? 3;
        this.MIN_SIGNIFICANT_FACE_AREA = options.minFaceArea ?? 0.015;

        // Normalized-coordinate thresholds for head-pose estimation.
        // Landmarks are in [0,1] where 0,0 = top-left of the cropped face box.
        this.YAW_THRESHOLD        = options.yawThreshold    ?? 0.055;  // left/right
        this.PITCH_UP_THRESHOLD   = options.pitchUpThresh   ?? -0.04;  // looking up
        this.PITCH_DOWN_THRESHOLD = options.pitchDownThresh ?? 0.26;   // looking down
        this.CONSECUTIVE_REQUIRED = {
            no_face_detected: options.noFaceStreak ?? 2,
            multiple_faces: options.multipleFacesStreak ?? 3,
            looking_away: options.lookingAwayStreak ?? 2,
        };

        // ── MediaPipe CDN ────────────────────────────────────────────────
        this._MP_CDN = 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.3';

        // Status callbacks
        this.onStatusChange = options.onStatusChange ?? (() => {});
        this.onEvent        = options.onEvent        ?? (() => {});
    }

    // ────────────────────────────────────────────────────────────────────
    // Public API
    // ────────────────────────────────────────────────────────────────────

    /**
     * Load the MediaPipe model.  Call once before start().
     * Returns `this` so you can chain:  await detector.load();
     */
    async load() {
        try {
            this.onStatusChange('loading', 'Loading face detection model…');

            // Dynamic import from CDN (ESM bundle)
            const vision = await import(`${this._MP_CDN}/vision_bundle.mjs`);
            const { FaceLandmarker, FilesetResolver } = vision;

            const resolver = await FilesetResolver.forVisionTasks(
                `${this._MP_CDN}/wasm`
            );

            this._landmarker = await FaceLandmarker.createFromOptions(resolver, {
                baseOptions: {
                    modelAssetPath:
                        'https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task',
                    delegate: 'GPU',
                },
                outputFaceBlendshapes : false,
                outputFacialTransformationMatrixes: false,
                runningMode           : 'VIDEO',
                numFaces              : this.MAX_FACES,
            });

            this.onStatusChange('ready', 'Face detection active.');
            return this;

        } catch (err) {
            console.error('[FaceDetection] Failed to load model:', err);
            this.onStatusChange('error', 'Face detection unavailable.');
            return this;
        }
    }

    /**
     * Start monitoring the given MediaStream.
     * Creates a hidden <video> element to feed frames into the model.
     */
    async start(stream) {
        if (!this._landmarker) {
            console.warn('[FaceDetection] Model not loaded — call load() first.');
            return;
        }
        if (this._running) return;

        // Hidden video for frame extraction
        this._videoEl              = document.createElement('video');
        this._videoEl.srcObject    = stream;
        this._videoEl.muted        = true;
        this._videoEl.playsInline  = true;
        this._videoEl.width        = 320;
        this._videoEl.height       = 240;
        Object.assign(this._videoEl.style, { position: 'fixed', opacity: '0', pointerEvents: 'none', top: '-9999px' });
        document.body.appendChild(this._videoEl);

        try {
            await this._videoEl.play();
        } catch (e) {
            console.warn('[FaceDetection] Video play failed:', e.message);
        }

        this._running = true;
        this._scheduleNext();
    }

    /** Stop monitoring and clean up. */
    stop() {
        this._running = false;
        if (this._timer)  { clearTimeout(this._timer); this._timer = null; }
        if (this._videoEl){ this._videoEl.remove(); this._videoEl = null; }
        this.onStatusChange('stopped', 'Face detection stopped.');
    }

    // ────────────────────────────────────────────────────────────────────
    // Internal
    // ────────────────────────────────────────────────────────────────────

    _scheduleNext() {
        if (!this._running) return;
        this._timer = setTimeout(() => this._detect(), this.CHECK_INTERVAL_MS);
    }

    async _detect() {
        if (!this._running || !this._landmarker || !this._videoEl) {
            this._scheduleNext();
            return;
        }

        // Only process when a frame is available
        if (this._videoEl.readyState < HTMLMediaElement.HAVE_CURRENT_DATA ||
            this._videoEl.paused || this._videoEl.ended) {
            this._scheduleNext();
            return;
        }

        try {
            const nowMs  = performance.now();
            const result = this._landmarker.detectForVideo(this._videoEl, nowMs);
            const count  = result.faceLandmarks?.length ?? 0;

            if (count === 0) {
                this._trackAndMaybeFire('no_face_detected', { face_count: 0 });
                this._resetStreak('multiple_faces');
                this._resetStreak('looking_away');
            } else if (this._significantFaceCount(result.faceLandmarks) > 1) {
                this._trackAndMaybeFire('multiple_faces', { face_count: count });
                this._resetStreak('no_face_detected');
                this._resetStreak('looking_away');
            } else {
                this._resetStreak('no_face_detected');
                this._resetStreak('multiple_faces');
                // Single face — check head orientation
                const direction = this._headDirection(result.faceLandmarks[0]);
                if (direction !== 'center') {
                    this._trackAndMaybeFire('looking_away', { face_count: 1, direction });
                } else {
                    this._resetStreak('looking_away');
                }
            }

        } catch (err) {
            // Silently swallow — frame might not be ready yet
            console.warn('[FaceDetection] Detection error:', err.message);
        }

        this._scheduleNext();
    }

    /**
     * Estimate head direction from key face landmarks.
     *
     * MediaPipe FaceLandmarker (478-point model) indices:
     *   4   = nose tip
     *   159 = left eye centre  (viewer's left)
     *   386 = right eye centre (viewer's right)
     *
     * Returns: 'center' | 'left' | 'right' | 'up' | 'down'
     */
    _headDirection(landmarks) {
        const nose = landmarks[4];
        const lEye = landmarks[159];
        const rEye = landmarks[386];

        if (!nose || !lEye || !rEye) return 'center';

        const eyeMidX = (lEye.x + rEye.x) / 2;
        const eyeMidY = (lEye.y + rEye.y) / 2;
        const yaw     = nose.x - eyeMidX;   // +ve = looking right
        const pitch   = nose.y - eyeMidY;   // +ve = looking down

        if (Math.abs(yaw) > this.YAW_THRESHOLD) {
            return yaw > 0 ? 'right' : 'left';
        }
        if (pitch < this.PITCH_UP_THRESHOLD)   return 'up';
        if (pitch > this.PITCH_DOWN_THRESHOLD) return 'down';
        return 'center';
    }

    /** Fires an event subject to per-type cooldown. */
    _fire(eventType, metadata = {}) {
        const now  = Date.now();
        const last = this._cooldowns[eventType] ?? 0;
        if (now - last < this.ALERT_COOLDOWN_MS) return;
        this._cooldowns[eventType] = now;
        this.onEvent(eventType, metadata);
    }

    _resetStreak(eventType) {
        this._streaks[eventType] = 0;
    }

    _trackAndMaybeFire(eventType, metadata = {}) {
        this._streaks[eventType] = (this._streaks[eventType] ?? 0) + 1;
        const needed = this.CONSECUTIVE_REQUIRED[eventType] ?? 1;
        if (this._streaks[eventType] >= needed) {
            this._streaks[eventType] = 0;
            this._fire(eventType, metadata);
        }
    }

    _significantFaceCount(faces) {
        if (!Array.isArray(faces) || faces.length === 0) return 0;
        let count = 0;
        for (const lm of faces) {
            if (!Array.isArray(lm) || lm.length === 0) continue;
            let minX = 1, minY = 1, maxX = 0, maxY = 0;
            for (const p of lm) {
                const x = Number(p?.x);
                const y = Number(p?.y);
                if (!Number.isFinite(x) || !Number.isFinite(y)) continue;
                if (x < minX) minX = x;
                if (y < minY) minY = y;
                if (x > maxX) maxX = x;
                if (y > maxY) maxY = y;
            }
            const area = Math.max(0, maxX - minX) * Math.max(0, maxY - minY);
            if (area >= this.MIN_SIGNIFICANT_FACE_AREA) {
                count++;
            }
        }
        return count;
    }
}

// ── Global exposure ─────────────────────────────────────────────────────────
window.FaceDetectionProctoring = FaceDetectionProctoring;
