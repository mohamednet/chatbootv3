{!! '<!DOCTYPE html>' !!}
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f8fafc;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
        <!-- Header -->
        <div style="background-color: #1e40af; color: white; text-align: center; padding: 20px;">
            <h1 style="margin: 0; font-size: 24px;">Welcome to Your IPTV Service!</h1>
        </div>
        
        <!-- Content -->
        <div style="padding: 20px;">
            <p style="color: #374151;">Hi there!</p>
            
            <p style="color: #374151;">Thank you for choosing our service. Here are your setup instructions for <strong>{{ $device ?? 'your device' }}</strong>:</p>
            
            <!-- Steps -->
            <div style="margin: 25px 0;">
                <!-- Step 1 -->
                <div style="margin-bottom: 15px; padding: 15px; background-color: #f8fafc; border-radius: 6px; border-left: 4px solid #1e40af;">
                    <strong style="color: #1e40af;">1. Download and install the app</strong>
                </div>
                
                <!-- Step 2 -->
                <div style="margin-bottom: 15px; padding: 15px; background-color: #f8fafc; border-radius: 6px; border-left: 4px solid #1e40af;">
                    <strong style="color: #1e40af;">2. Open the app and sign in with your credentials:</strong>
                    <div style="margin: 10px 0; padding: 15px; background-color: #f0f9ff; border-radius: 6px; border: 1px dashed #93c5fd;">
                        <p style="margin: 5px 0;"><strong>Username:</strong> [Your Username]</p>
                        <p style="margin: 5px 0;"><strong>Password:</strong> [Your Password]</p>
                    </div>
                </div>
                
                <!-- Step 3 -->
                <div style="margin-bottom: 15px; padding: 15px; background-color: #f8fafc; border-radius: 6px; border-left: 4px solid #1e40af;">
                    <strong style="color: #1e40af;">3. Go to Settings > Add Source</strong>
                </div>
                
                <!-- Step 4 -->
                <div style="margin-bottom: 15px; padding: 15px; background-color: #f8fafc; border-radius: 6px; border-left: 4px solid #1e40af;">
                    <strong style="color: #1e40af;">4. Enter the following URL:</strong>
                    <div style="margin: 10px 0; padding: 15px; background-color: #f0f9ff; border-radius: 6px; border: 1px dashed #93c5fd;">
                        <p style="margin: 5px 0;"><strong>Source URL:</strong> [IPTV Source URL]</p>
                    </div>
                </div>
                
                <!-- Step 5 -->
                <div style="margin-bottom: 15px; padding: 15px; background-color: #f8fafc; border-radius: 6px; border-left: 4px solid #1e40af;">
                    <strong style="color: #1e40af;">5. Click Save and enjoy your content!</strong>
                </div>
            </div>
            
            <!-- Support Section -->
            <div style="text-align: center; margin-top: 30px; padding: 20px; background-color: #f8fafc; border-radius: 6px;">
                <p style="margin: 0 0 15px 0; color: #374151;">Need help? We're here for you!</p>
                <a href="#" style="background-color: #1e40af; border-radius: 4px; color: #ffffff; display: inline-block; font-weight: bold; padding: 12px 24px; text-decoration: none;">Contact Support</a>
            </div>
        </div>
        
        <!-- Footer -->
        <div style="text-align: center; padding: 20px; color: #6b7280;">
            <p style="margin: 0;">Best regards,<br>Your IPTV Support Team</p>
            <p style="font-size: 12px; color: #9ca3af; margin-top: 10px;">This email was sent to you as part of your IPTV service subscription.</p>
        </div>
    </div>
</body>
</html>
