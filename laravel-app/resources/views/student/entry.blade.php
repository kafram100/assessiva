<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal · NovaGrade</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    <style>
        .entry-ai-strip {
            --ai-navy-950: #050816;
            --ai-navy-900: #071120;
            --ai-navy-800: #0A1628;
            --ai-accent: #00A3FF;
            --ai-accent-deep: #0090FF;
            --ai-glow: #5EEBFF;
            --ai-glow-soft: #4FD1FF;
            --ai-muted: #B6C2D9;
            --ai-soft: #7E8AA3;
            font-family: Inter, system-ui, sans-serif;
            position: relative;
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid rgba(0, 163, 255, 0.22);
            background: linear-gradient(168deg, var(--ai-navy-900) 0%, var(--ai-navy-800) 42%, var(--ai-navy-950) 100%);
            box-shadow:
                0 0 0 1px rgba(255, 255, 255, 0.04),
                0 20px 50px -28px rgba(0, 0, 0, 0.55),
                0 0 48px -18px rgba(0, 163, 255, 0.18);
        }
        .entry-ai-strip::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 90% 55% at 50% -30%, rgba(0, 163, 255, 0.14), transparent 55%),
                linear-gradient(135deg, rgba(94, 235, 255, 0.04) 0%, transparent 45%);
            pointer-events: none;
        }
        .entry-ai-strip::after {
            content: "";
            position: absolute;
            inset: 0;
            opacity: 0.35;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 28px 28px;
            pointer-events: none;
        }
        .entry-ai-strip__inner {
            position: relative;
            z-index: 1;
            padding: 1.25rem 1.35rem;
        }
        @media (min-width: 768px) {
            .entry-ai-strip__inner { padding: 1.5rem 1.65rem; }
        }
        .entry-ai-strip__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--ai-glow);
            margin-bottom: 0.65rem;
        }
        .entry-ai-strip__eyebrow .pulse-dot {
            position: relative;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--ai-accent);
            box-shadow: 0 0 0 0 rgba(0, 163, 255, 0.45);
            animation: entryAiPulse 2.2s ease-out infinite;
        }
        @keyframes entryAiPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(0, 163, 255, 0.45); }
            50% { box-shadow: 0 0 0 8px rgba(0, 163, 255, 0); }
        }
        .entry-ai-strip__title {
            font-family: "Space Grotesk", Inter, system-ui, sans-serif;
            font-size: 1.125rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.25;
            color: #f1f5f9;
            margin: 0 0 0.75rem;
        }
        .entry-ai-strip__title span {
            background: linear-gradient(90deg, #fff 0%, var(--ai-glow-soft) 45%, var(--ai-accent) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .entry-ai-strip__lead {
            font-size: 0.875rem;
            line-height: 1.55;
            color: var(--ai-muted);
            margin-bottom: 0.85rem;
        }
        .entry-ai-strip__lead strong {
            color: #e2e8f0;
            font-weight: 600;
        }
        .entry-ai-strip__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 1rem;
            font-size: 0.78rem;
            color: var(--ai-soft);
            margin-bottom: 1rem;
            padding-top: 0.25rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }
        .entry-ai-strip__meta span {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .entry-ai-strip__meta i {
            color: var(--ai-accent);
            opacity: 0.9;
        }
        .entry-ai-strip__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.55rem 1.15rem;
            font-size: 0.8125rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            color: #fff !important;
            text-decoration: none !important;
            border-radius: 9999px;
            border: none;
            background: linear-gradient(135deg, var(--ai-accent) 0%, var(--ai-accent-deep) 55%, #0080e6 100%);
            box-shadow: 0 0 24px -6px rgba(0, 163, 255, 0.45);
            transition: filter 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
        }
        .entry-ai-strip__btn:hover {
            color: #fff !important;
            filter: brightness(1.06);
            transform: translateY(-1px);
            box-shadow: 0 0 32px -4px rgba(94, 235, 255, 0.35);
        }
        .entry-ai-strip__icon-wrap {
            flex-shrink: 0;
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 163, 255, 0.12);
            border: 1px solid rgba(0, 163, 255, 0.28);
            color: var(--ai-glow);
            font-size: 1.25rem;
        }
    </style>
</head>
<body class="bg-light min-vh-100 d-flex align-items-center justify-content-center">
<div class="container mb-5" style="max-width:500px">
    <div class="text-center mb-4">
        <i class="bi bi-mortarboard-fill text-primary" style="font-size:3rem"></i>
        <h2 class="fw-bold mt-2">Student Portal</h2>
    </div>

    <ul class="nav nav-pills nav-fill mb-4" id="entryTabs">
        <li class="nav-item">
            <button class="nav-link {{ $action !== 'results' ? 'active' : '' }}" onclick="switchTab('exam')">
                <i class="bi bi-pencil-square me-1"></i>Take Exam
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link {{ $action === 'results' ? 'active' : '' }}" onclick="switchTab('results')">
                <i class="bi bi-clipboard-check me-1"></i>Check Results
            </button>
        </li>
    </ul>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 shadow-sm p-4">
        <form method="POST" action="{{ route('student.entry.store') }}">
            @csrf
            <input type="hidden" name="action" id="action-input" value="{{ $action !== 'results' ? 'exam' : 'results' }}">
            <div class="mb-3">
                <label class="form-label fw-semibold" id="code-label">Exam code</label>
                <input type="text" name="exam_code" id="exam_code_input" class="form-control" value="{{ old('exam_code', request('exam_code')) }}"
                       placeholder="Enter exam code" required style="text-transform:uppercase">
            </div>
            <div id="exam-only-fields" class="{{ $action === 'results' ? 'd-none' : '' }}">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Your Full Name</label>
                    <input type="text" name="student_name" class="form-control exam-only-required" value="{{ old('student_name') }}"
                           placeholder="Enter your full name" @if($action !== 'results') required @endif>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input type="email" name="student_email" class="form-control exam-only-required" value="{{ old('student_email') }}"
                           placeholder="Enter your email address" @if($action !== 'results') required @endif>
                    <div class="form-text">This email is used to receive your released result details.</div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Index number</label>
                <input type="text" name="student_index" class="form-control" value="{{ old('student_index') }}"
                       placeholder="Enter your index number" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-semibold" id="submit-btn">
                <span id="btn-label">Enter Exam</span>
            </button>
            <p class="form-text text-center small mt-2 mb-0 text-muted">
                Use one browser only while the exam is in progress. Cookies must be allowed.
            </p>
        </form>
    </div>

    <div class="entry-ai-strip mt-4" role="region" aria-label="AI results and revision workspace">
        <div class="entry-ai-strip__inner">
            <div class="d-flex gap-3 align-items-start">
                <div class="entry-ai-strip__icon-wrap" aria-hidden="true">
                    <i class="bi bi-stars"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <p class="entry-ai-strip__eyebrow mb-0">
                        <span class="pulse-dot" aria-hidden="true"></span>
                        Intelligent follow-up
                    </p>
                    <h3 class="entry-ai-strip__title">
                        <span>Results &amp; AI revision</span> workspace
                    </h3>
                    <p class="entry-ai-strip__lead mb-0">
                        Once your attempt is graded and your lecturer <strong>releases results</strong>, open the
                        interactive results view for a clear score breakdown. When enabled, you can launch an
                        <strong>optional AI revision plan</strong>-concise weak-area notes, suggested practice focus,
                        and study prompts aligned to how you answered.
                    </p>
                    <div class="entry-ai-strip__meta">
                        <span><i class="bi bi-link-45deg" aria-hidden="true"></i>Same exam code &amp; index as above</span>
                        <span><i class="bi bi-lightning-charge" aria-hidden="true"></i>Pre-filled lookup for faster access</span>
                    </div>
                    <a id="results-portal-link"
                       href="{{ route('student.results.lookup-form') }}"
                       class="entry-ai-strip__btn">
                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                        Open results workspace
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/theme.js') }}" defer></script>
<script>
function switchTab(tab) {
    document.getElementById('action-input').value = tab;
    document.getElementById('btn-label').textContent = tab === 'results' ? 'Check Results' : 'Enter Exam';
    document.getElementById('code-label').textContent = 'Exam code';
    const codeInput = document.getElementById('exam_code_input');
    codeInput.placeholder = 'Enter exam code';
    const examOnly = document.getElementById('exam-only-fields');
    examOnly.classList.toggle('d-none', tab === 'results');
    document.querySelectorAll('.exam-only-required').forEach((el) => {
        if (tab === 'results') {
            el.removeAttribute('required');
        } else {
            el.setAttribute('required', 'required');
        }
    });
    document.querySelectorAll('.nav-link').forEach((el, i) => {
        el.classList.toggle('active', (i === 0 && tab === 'exam') || (i === 1 && tab === 'results'));
    });
    syncResultsPortalLink();
}
function syncResultsPortalLink() {
    const link = document.getElementById('results-portal-link');
    if (!link) return;
    const base = @json(route('student.results.lookup-form'));
    const code = (document.getElementById('exam_code_input')?.value || '').trim();
    const idx = (document.querySelector('input[name="student_index"]')?.value || '').trim();
    const url = new URL(base, window.location.origin);
    if (code.length) url.searchParams.set('exam_code', code.toUpperCase());
    if (idx.length) url.searchParams.set('student_index', idx);
    link.href = url.toString();
}
document.getElementById('exam_code_input')?.addEventListener('input', syncResultsPortalLink);
document.querySelector('input[name="student_index"]')?.addEventListener('input', syncResultsPortalLink);
window.addEventListener('load', syncResultsPortalLink);
</script>
</body>
</html>
