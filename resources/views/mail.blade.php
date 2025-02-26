<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mail Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: #f2f2f2;
            padding: 20px;
        }
        h1 {
            color: #444;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Mail Notification</h1>
        <p>Hello,</p>
        <p>This is a sample email notification to demonstrate sending emails with Laravel.</p>
        
        {{-- Example of using a variable passed to the view --}}
        {{-- <p>{{ $messageContent }}</p> --}}

        <p>Thank you for using our application!</p>
    </div>
</body>
</html>