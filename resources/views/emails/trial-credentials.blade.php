<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
            font-size: 24px;
        }
        .content {
            background: #f8fafc;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .credentials {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
        }
        .credentials p {
            margin: 8px 0;
        }
        .label {
            font-weight: bold;
            color: #4b5563;
        }
        .value {
            color: #1f2937;
            padding-left: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Your Trial Access Details</h1>
        </div>
        
        <p>Hello!</p>
        
        <p>Thank you for your interest. Here are your trial access credentials:</p>
        
        <div class="content">
            <div class="credentials">
                <p><span class="label">Username:</span> <span class="value">{{ $trial->username }}</span></p>
                <p><span class="label">Password:</span> <span class="value">{{ $trial->password }}</span></p>
                <p><span class="label">URL:</span> <span class="value">{{ $trial->url }}</span></p>
                <p><span class="label">M3U Link:</span> <span class="value">{{ $trial->m3u_link }}</span></p>
            </div>
        </div>
        
        <p>Your trial access will be valid for 24 hours from the time of creation.</p>
        
        <p>If you need any assistance, please don't hesitate to contact our support team.</p>
        
        <div class="footer">
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
