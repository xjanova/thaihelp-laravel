<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>ออฟไลน์ - ThaiHelp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Noto Sans Thai', 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0a0e17 0%, #111827 50%, #0a0e17 100%);
            color: #e2e8f0;
            -webkit-font-smoothing: antialiased;
        }
        .container { text-align: center; padding: 2rem; }
        .icon {
            width: 80px; height: 80px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(145deg, #1a2235 0%, #0f172a 50%, #1a2235 100%);
            border: 1px solid rgba(148, 163, 184, 0.12);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.5);
        }
        .icon svg { width: 40px; height: 40px; color: #94a3b8; }
        h1 { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; }
        p { font-size: 0.875rem; color: #94a3b8; margin-bottom: 2rem; }
        .retry-btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: white;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: linear-gradient(180deg, #fb923c 0%, #f97316 40%, #ea580c 100%);
            box-shadow: 0 1px 0 0 rgba(255, 255, 255, 0.2) inset, 0 -2px 0 0 rgba(0, 0, 0, 0.2) inset, 0 4px 14px -2px rgba(249, 115, 22, 0.4);
            transition: all 0.15s ease;
        }
        .retry-btn:hover {
            background: linear-gradient(180deg, #fdba74 0%, #fb923c 40%, #f97316 100%);
            box-shadow: 0 6px 20px -2px rgba(249, 115, 22, 0.5);
        }
        .retry-btn:active { transform: translateY(1px); }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636a9 9 0 010 12.728M5.636 18.364a9 9 0 010-12.728"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.464 15.536a5 5 0 010-7.072M15.536 8.464a5 5 0 010 7.072"/>
                <circle cx="12" cy="12" r="1"/>
                <line x1="4" y1="4" x2="20" y2="20" stroke-width="2.5" stroke="#ef4444"/>
            </svg>
        </div>

        <h1>ไม่มีสัญญาณอินเทอร์เน็ต</h1>
        <p>กรุณาตรวจสอบการเชื่อมต่อแล้วลองใหม่</p>

        <button class="retry-btn" onclick="window.location.reload();">
            ลองใหม่
        </button>
    </div>
</body>
</html>
