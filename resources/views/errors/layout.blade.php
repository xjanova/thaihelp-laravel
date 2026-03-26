<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ThaiHelp - @yield('title')</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;600;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Noto Sans Thai', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f172a;
            overflow: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 600px 400px at 20% 80%, @yield('glow-color', 'rgba(249, 115, 22, 0.12)') 0%, transparent 70%),
                radial-gradient(ellipse 500px 500px at 80% 20%, rgba(59, 130, 246, 0.08) 0%, transparent 70%),
                radial-gradient(ellipse 400px 300px at 50% 50%, rgba(139, 92, 246, 0.06) 0%, transparent 70%);
            animation: bgPulse 8s ease-in-out infinite alternate;
        }

        @keyframes bgPulse { 0% { opacity: 0.6; } 100% { opacity: 1; } }

        .particles { position: absolute; inset: 0; pointer-events: none; }
        .particle {
            position: absolute; width: 4px; height: 4px; border-radius: 50%;
            background: rgba(249, 115, 22, 0.3); animation: float linear infinite;
        }
        @keyframes float {
            0% { transform: translateY(100vh) scale(0); opacity: 0; }
            10% { opacity: 1; } 90% { opacity: 1; }
            100% { transform: translateY(-10vh) scale(1); opacity: 0; }
        }

        .container {
            position: relative; z-index: 1; text-align: center;
            padding: 2rem; max-width: 480px; width: 100%;
        }

        .logo { width: 80px; height: 80px; margin: 0 auto 1rem; animation: logoIn 0.8s ease-out; }
        .logo img { width: 100%; height: 100%; object-fit: contain; filter: drop-shadow(0 4px 12px rgba(249, 115, 22, 0.3)); }
        @keyframes logoIn { from { transform: scale(0.5) translateY(-20px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }

        .ying-container { position: relative; display: inline-block; margin: 1.5rem 0; animation: yingIn 1s ease-out 0.3s both; }
        @keyframes yingIn { from { transform: scale(0.8) translateY(30px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }

        .ying-glow {
            position: absolute; inset: -20px;
            background: radial-gradient(circle, @yield('glow-color', 'rgba(249, 115, 22, 0.15)') 0%, transparent 70%);
            border-radius: 50%; animation: glowPulse 3s ease-in-out infinite;
        }
        @keyframes glowPulse { 0%, 100% { transform: scale(1); opacity: 0.5; } 50% { transform: scale(1.1); opacity: 1; } }

        .ying-img {
            position: relative; width: 180px; height: 180px; border-radius: 50%;
            object-fit: cover; border: 3px solid @yield('border-color', 'rgba(249, 115, 22, 0.4)');
            box-shadow: 0 0 40px @yield('glow-color', 'rgba(249, 115, 22, 0.15)'), 0 20px 40px rgba(0, 0, 0, 0.3);
            animation: yingBounce 4s ease-in-out infinite;
        }
        @keyframes yingBounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }

        .speech-bubble {
            position: absolute; top: -10px; right: -60px;
            background: white; color: #1e293b; padding: 8px 14px;
            border-radius: 16px; font-size: 13px; font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            animation: bubbleIn 0.6s ease-out 1s both; white-space: nowrap;
        }
        .speech-bubble::after {
            content: ''; position: absolute; bottom: -6px; left: 20px;
            width: 12px; height: 12px; background: white;
            transform: rotate(45deg); border-radius: 2px;
        }
        @keyframes bubbleIn { from { transform: scale(0); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        .error-code {
            font-size: 4rem; font-weight: 700; line-height: 1;
            background: linear-gradient(135deg, @yield('accent-color', '#f97316'), @yield('accent-color-light', '#fb923c'));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; margin-bottom: 0.5rem;
            animation: textIn 0.8s ease-out 0.5s both;
        }

        .title {
            font-size: 1.4rem; font-weight: 700; color: #f8fafc;
            margin-bottom: 0.5rem; animation: textIn 0.8s ease-out 0.6s both;
        }

        .description {
            font-size: 0.875rem; color: #94a3b8; line-height: 1.7;
            margin-bottom: 2rem; animation: textIn 0.8s ease-out 0.8s both;
        }

        @keyframes textIn { from { transform: translateY(15px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .actions {
            display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;
            animation: textIn 0.8s ease-out 1s both;
        }

        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 10px 24px; border-radius: 12px; font-size: 0.875rem;
            font-weight: 600; text-decoration: none; transition: all 0.2s;
            cursor: pointer; border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white; box-shadow: 0 4px 16px rgba(249, 115, 22, 0.3);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(249, 115, 22, 0.4); }

        .btn-secondary {
            background: #1e293b; color: #94a3b8;
            border: 1px solid #334155;
        }
        .btn-secondary:hover { background: #334155; color: #e2e8f0; transform: translateY(-2px); }

        .footer {
            font-size: 0.7rem; color: #475569; margin-top: 2rem;
            animation: textIn 0.8s ease-out 1.2s both;
        }
        .footer a { color: #f97316; text-decoration: none; }
        .footer a:hover { text-decoration: underline; }

        @media (max-width: 480px) {
            .ying-img { width: 150px; height: 150px; }
            .speech-bubble { right: -30px; font-size: 11px; padding: 6px 10px; }
            .error-code { font-size: 3rem; }
            .title { font-size: 1.15rem; }
        }
    </style>
</head>
<body>
    <div class="particles">
        <div class="particle" style="left:10%;animation-duration:8s;animation-delay:0s;width:3px;height:3px;"></div>
        <div class="particle" style="left:25%;animation-duration:12s;animation-delay:2s;background:rgba(59,130,246,0.2);width:5px;height:5px;"></div>
        <div class="particle" style="left:40%;animation-duration:10s;animation-delay:1s;width:4px;height:4px;"></div>
        <div class="particle" style="left:55%;animation-duration:9s;animation-delay:3s;background:rgba(139,92,246,0.2);width:3px;height:3px;"></div>
        <div class="particle" style="left:70%;animation-duration:11s;animation-delay:0.5s;width:6px;height:6px;"></div>
        <div class="particle" style="left:85%;animation-duration:7s;animation-delay:2.5s;background:rgba(59,130,246,0.15);width:4px;height:4px;"></div>
        <div class="particle" style="left:50%;animation-duration:13s;animation-delay:4s;width:5px;height:5px;"></div>
        <div class="particle" style="left:65%;animation-duration:8s;animation-delay:1.5s;background:rgba(139,92,246,0.15);width:3px;height:3px;"></div>
    </div>

    <div class="container">
        <div class="logo">
            <img src="/images/logo.png" alt="ThaiHelp">
        </div>

        <div class="ying-container">
            <div class="ying-glow"></div>
            <img src="/images/ying.webp" alt="น้องหญิง" class="ying-img">
            <div class="speech-bubble">@yield('bubble')</div>
        </div>

        <div class="error-code">@yield('code')</div>
        <h1 class="title">@yield('title')</h1>
        <p class="description">@yield('message')</p>

        <div class="actions">
            @yield('actions')
        </div>

        <p class="footer">
            ชุมชนช่วยเหลือนักเดินทาง &mdash; <a href="/">thaihelp.app</a>
        </p>
    </div>
</body>
</html>
