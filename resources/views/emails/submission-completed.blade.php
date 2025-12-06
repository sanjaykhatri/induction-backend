<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Induction Training Completed</title>
</head>
<body>
    <h2>Induction Training Completed</h2>
    
    <p>A user has completed an induction training:</p>
    
    <ul>
        <li><strong>User:</strong> {{ $user->name }}</li>
        <li><strong>Email:</strong> {{ $user->email }}</li>
        <li><strong>Company:</strong> {{ $user->company ?? 'N/A' }}</li>
        <li><strong>Vantage Card Number:</strong> {{ $user->vantage_card_number ?? 'N/A' }}</li>
        <li><strong>Induction:</strong> {{ $induction->title }}</li>
        <li><strong>Completed At:</strong> {{ $submission->completed_at->format('Y-m-d H:i:s') }}</li>
    </ul>
    
    <p>Please review the submission in the admin panel.</p>
</body>
</html>

