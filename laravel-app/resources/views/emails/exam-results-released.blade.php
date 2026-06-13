<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam Results Released</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827;">
    <h2 style="margin-bottom: 8px;">Your exam results are now available</h2>
    <p style="margin-top: 0; color: #4b5563;">
        {{ $exam->title }} ({{ $exam->course_name }})
    </p>

    @php
        $score = (float) ($attempt->score ?? 0);
        $total = (float) ($attempt->total_marks ?? 0);
        $percent = $total > 0 ? round(($score / $total) * 100, 2) : 0;
    @endphp

    <p><strong>Student:</strong> {{ $attempt->student_fullname ?: $attempt->student_index }}</p>
    <p><strong>Index Number:</strong> {{ $attempt->student_index }}</p>
    <p><strong>Score:</strong> {{ $score }} / {{ $total }} ({{ $percent }}%)</p>

    <h3 style="margin-top: 24px;">Review: Question-by-question breakdown</h3>

    @if(empty($reviewItems))
        <p>No question breakdown was found for this attempt.</p>
    @else
        <table cellpadding="8" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%; border-color: #d1d5db;">
            <thead style="background: #f3f4f6;">
                <tr>
                    <th align="left">Question</th>
                    <th align="left">Your Answer</th>
                    <th align="left">Correct Answer</th>
                    <th align="left">Result</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reviewItems as $item)
                    <tr>
                        <td>{{ $item['question'] }}</td>
                        <td>{{ $item['student_answer'] }}</td>
                        <td>{{ $item['correct_answer'] }}</td>
                        <td style="font-weight: 700; color: {{ ($item['result'] ?? '') === 'Correct' ? '#047857' : '#b91c1c' }};">
                            {{ $item['result'] ?? 'Wrong' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p style="margin-top: 24px; color: #4b5563;">
        This message was sent automatically after your lecturer released exam results.
    </p>
</body>
</html>
