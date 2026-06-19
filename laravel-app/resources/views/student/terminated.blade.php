<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Terminated · NovaGrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}</style>
</head>
<body>
    <div class="card border-0 shadow text-center p-4" style="max-width:500px;width:100%">
        <div class="text-danger mb-3"><i class="bi bi-x-octagon" style="font-size:3rem"></i></div>
        <h2 class="text-danger fw-bold mb-3">Exam Terminated</h2>
        @if($markViolated ?? false)
            <p class="lead">Your exam session has been terminated due to a security violation.</p>
        @else
            <p class="lead">Your exam session has ended.</p>
        @endif
        <div class="alert alert-warning text-start">
            <strong>Reason:</strong> {{ $reason }}
        </div>
        <p>For more information, please contact your lecturer or administrator.</p>
        <a href="{{ route('student.entry') }}" class="btn btn-light mt-2">Return to Home</a>
    </div>
</body>
</html>
