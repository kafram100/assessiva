<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Result · Novagrade.ai</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
</head>
<body class="theme-dark min-vh-100 d-flex align-items-center justify-content-center">
<div class="container" style="max-width:520px">
    <div class="card border-0 shadow text-center p-4 bg-dark text-light">
        <div class="mb-3">
            <i class="bi bi-check-circle-fill text-success" style="font-size:3.5rem"></i>
        </div>
        <h3 class="fw-bold mb-1">Exam Submitted</h3>
        <p class="text-secondary mb-3">{{ $exam->title }}</p>
        <p class="mb-1">Student: <strong>{{ $studentName }}</strong></p>
        <p class="mb-3">Index: <strong>{{ $studentIndex }}</strong></p>

        @if($attempt)
            @php
                $rawNotes = (string)($attempt->notes ?? '');
                $deductionPattern = '/Cheating penalty deduction:\s*-(\d+(?:\.\d+)?)\s*\[(.*?)\]\.?/i';
                $legacyDeduction = 0.0;
                $legacyDetails = '';
                if (preg_match($deductionPattern, $rawNotes, $m)) {
                    $legacyDeduction = (float)($m[1] ?? 0);
                    $legacyDetails = trim((string)($m[2] ?? ''));
                    $rawNotes = trim((string)preg_replace($deductionPattern, '', $rawNotes));
                }
                $released = (bool) $exam->results_released;
                $penaltyTotal = (float)($attempt->proctoring_penalty_total ?? 0);
                if ($penaltyTotal <= 0 && $legacyDeduction > 0) {
                    $penaltyTotal = $legacyDeduction;
                }
            @endphp

            @if($attempt->status === 'completed' && ! $released)
                <div class="alert alert-success py-3 small text-center mb-3">
                    Your exam has been submitted successfully. Results will be available when released by your lecturer.
                </div>
            @endif

            @if($penaltyTotal > 0 && $released)
                <div class="alert alert-danger py-2 small text-start">
                    <div class="fw-semibold mb-1">
                        <i class="bi bi-shield-exclamation me-1"></i>Your final score reflects a proctoring adjustment of {{ rtrim(rtrim(number_format($penaltyTotal, 2), '0'), '.') }} mark(s).
                    </div>
                    @if($legacyDetails !== '')
                        <div class="text-break">(Legacy note) Events: {{ $legacyDetails }}</div>
                    @endif
                </div>
            @elseif($penaltyTotal > 0 && ! $released)
                <div class="alert alert-secondary py-2 small text-start mb-0">
                    <i class="bi bi-info-circle me-1"></i>Your attempt may be subject to review. Detailed outcomes will appear when results are released.
                </div>
            @endif

            @if($rawNotes !== '' && $released)
                <div class="alert alert-warning py-2 small">Note: {{ $rawNotes }}</div>
            @endif

            @if($released && $attempt->status === 'completed')
                <div class="card bg-secondary bg-opacity-25 border-0 p-3 mt-2 mb-3 text-light">
                    <div class="fw-bold fs-4 text-primary">
                        {{ number_format((float)($attempt->score ?? 0), 1) }} / {{ $attempt->total_marks }}
                    </div>
                    <div class="text-secondary small mb-2">Your score (after any applied reviews)</div>
                    @php $pct = $attempt->total_marks > 0 ? round(((float)$attempt->score / $attempt->total_marks) * 100) : 0; @endphp
                    <div class="progress" style="height:10px">
                        <div class="progress-bar bg-{{ $pct >= 50 ? 'success' : 'danger' }}" style="width:{{ $pct }}%"></div>
                    </div>
                    <div class="small text-secondary mt-1">{{ $pct }}%</div>
                </div>
            @endif
        @endif

        @if($attempt && $exam->results_released)
            <a href="{{ route('student.results.show', $attempt) }}" class="btn btn-outline-info btn-sm mt-2 w-100">
                <i class="bi bi-journal-text me-1"></i>Detailed results and AI feedback
            </a>
        @endif

        <a href="{{ route('student.entry') }}" class="btn btn-primary mt-2">
            <i class="bi bi-house me-1"></i>Return to Home
        </a>
    </div>
</div>
<script src="{{ asset('js/theme.js') }}" defer></script>
</body>
</html>
