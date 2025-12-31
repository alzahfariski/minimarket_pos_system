<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Login - Minimarket POS System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            padding: 20px;
            line-height: 1.6;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 16px;
            color: #333333;
            margin-bottom: 20px;
        }
        .message {
            font-size: 14px;
            color: #666666;
            margin-bottom: 30px;
        }
        .otp-container {
            background-color: #f8f9fa;
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
        }
        .otp-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999999;
            margin-bottom: 10px;
        }
        .otp-code {
            font-size: 36px;
            font-weight: 700;
            color: #667eea;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
        }
        .otp-expiry {
            font-size: 13px;
            color: #ff6b6b;
            margin-top: 15px;
            font-weight: 500;
        }
        .security-notice {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        .security-notice p {
            font-size: 13px;
            color: #856404;
            margin: 0;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 25px 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer p {
            font-size: 12px;
            color: #999999;
            margin-bottom: 8px;
        }
        .footer .company-name {
            font-weight: 600;
            color: #667eea;
        }
        @media only screen and (max-width: 600px) {
            .content {
                padding: 30px 20px;
            }
            .otp-code {
                font-size: 28px;
                letter-spacing: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>🏪 Minimarket POS System</h1>
            <p>Sistem Point of Sale Terpercaya</p>
        </div>
        
        <div class="content">
            <p class="greeting">Halo,</p>
            
            <p class="message">
                Kami menerima permintaan untuk masuk ke akun Minimarket POS System Anda. 
                Gunakan kode verifikasi di bawah ini untuk menyelesaikan proses login Anda.
            </p>
            
            <div class="otp-container">
                <div class="otp-label">Kode Verifikasi Anda</div>
                <div class="otp-code">{{ $otp }}</div>
                <div class="otp-expiry">⏱ Kode berlaku selama 5 menit</div>
            </div>
            
            <div class="security-notice">
                <p>
                    <strong>⚠️ Peringatan Keamanan:</strong> Jangan pernah membagikan kode ini kepada siapapun, 
                    termasuk karyawan Minimarket POS System. Jika Anda tidak melakukan permintaan ini, 
                    abaikan email ini atau hubungi tim support kami segera.
                </p>
            </div>
            
            <p class="message" style="margin-bottom: 0;">
                Terima kasih telah menggunakan Minimarket POS System untuk mengelola bisnis Anda.
            </p>
        </div>
        
        <div class="footer">
            <p class="company-name">Minimarket POS System</p>
            <p>Email otomatis - Mohon tidak membalas email ini</p>
            <p>&copy; {{ date('Y') }} Minimarket POS System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>