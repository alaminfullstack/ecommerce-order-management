<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>{{ env('APP_NAME') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f308e;
            --secondary: #8f5fe8;
            --light: #f5f6fa;
            --white: #ffffff;
            --dark: #1a1a2e;
            --gradient-primary: linear-gradient(135deg, var(--primary), var(--secondary));
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.1);
            --radius-xl: 24px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Kanit', sans-serif;
            background: var(--light);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1rem;
            position: relative;
        }

        .bg-gradient {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            background: var(--gradient-primary);
            clip-path: polygon(0 0, 100% 0, 100% 60%, 0 80%);
        }

        .welcome-card {
            background: var(--white);
            color: var(--dark);
            border-radius: var(--radius-xl);
            padding: 2.5rem;
            text-align: center;
            max-width: 420px;
            width: 100%;
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 2;
        }
        
        .welcome-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: -6.5rem auto 1.5rem; /* Pulls the logo up */
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border: 4px solid var(--white);
        }

        .welcome-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .welcome-title {
            font-size: 2.2rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 1rem;
            color: var(--primary);
        }
        
        .welcome-subtitle {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: #4b5563;
        }
        
        .welcome-bonus-text {
            font-size: 1rem;
            margin-bottom: 2.5rem;
            color: var(--primary);
            font-weight: 600;
        }
        
        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1rem;
            width: 100%;
            margin-bottom: 1rem;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(79, 48, 142, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(79, 48, 142, 0.5);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        .btn-outline:hover {
            background: var(--primary);
            color: var(--white);
        }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>

    <div class="welcome-card">
        <div class="welcome-logo">
            <img src="{{ getImage(fileManager()->logoIcon()->path .'/logo.png') }}" alt="লোগো">
        </div>
        <h1 class="welcome-title">আয় করতে প্রস্তুত?</h1>
        <p class="welcome-subtitle">আমাদের প্ল্যাটফর্মে যোগ দিয়ে প্রতিদিন আয় করার নতুন সুযোগ আবিষ্কার করুন।</p>
        <p class="welcome-bonus-text">আজই যোগ দিন এবং প্রথম রেজিস্ট্রেশনে ১০০ টাকা বোনাস পান!</p>
        
        <div class="cta-buttons">
            <a href="{{ route('user.register') }}" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> বিনামূল্যে সাইন আপ করুন
            </a>
            <a href="{{route('user.login')}}" class="btn btn-outline">
                <i class="fas fa-sign-in-alt"></i> লগইন করুন
            </a>
        </div>
    </div>
</body>
</html>