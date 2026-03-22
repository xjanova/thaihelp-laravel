<p align="center">
  <img src="public/images/logo.png" width="80" alt="ThaiHelp Logo" />
</p>

<h1 align="center">ThaiHelp</h1>

<p align="center">
  <strong>ชุมชนช่วยเหลือนักเดินทาง</strong><br/>
  แจ้งเหตุบนถนน | รายงานปั๊มน้ำมัน | สั่งงานด้วยเสียง AI
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11-ff2d20?logo=laravel" alt="Laravel" />
  <img src="https://img.shields.io/badge/Livewire-3-4e56a6?logo=livewire" alt="Livewire" />
  <img src="https://img.shields.io/badge/Filament-3-fdae4b?logo=filament" alt="Filament" />
  <img src="https://img.shields.io/badge/MySQL-8-4479a1?logo=mysql&logoColor=white" alt="MySQL" />
  <img src="https://img.shields.io/badge/TailwindCSS-3-38bdf8?logo=tailwindcss" alt="Tailwind" />
  <img src="https://img.shields.io/badge/PWA-Ready-f97316" alt="PWA" />
</p>

---

## Tech Stack

```
Backend:    Laravel 11 (PHP 8.2+)
Frontend:   Blade + Livewire 3 + Alpine.js
Admin:      Filament 3
Styling:    Tailwind CSS 3 + Metal Dark Theme
Database:   MySQL 8+ / MariaDB
Auth:       Laravel Socialite (Google + LINE) + Nickname login
AI:         Groq API (Llama 3.3) — น้องหญิง voice assistant
Maps:       Google Maps JavaScript API + Places API
PWA:        Service Worker + Web App Manifest
Deploy:     deploy.sh (auto migration + rollback)
```

---

## Quick Start

### 1. Clone & Install

```bash
git clone https://github.com/xjanova/thaihelp.git
cd thaihelp
composer install
npm install && npm run build
```

### 2. Configure

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your credentials (DB, Google, LINE, Groq).

### 3. First-time Setup

```bash
# Option A: Via web wizard
php artisan serve
# Open http://localhost:8000/setup

# Option B: Via CLI
php artisan migrate
php artisan db:seed
```

### 4. Run

```bash
php artisan serve
```

---

## Deployment

```bash
bash deploy.sh main
```

The deploy script handles:
- Git pull
- Composer + NPM install
- Database backup + smart migration
- Asset build
- Cache optimization
- Permission fix
- Automatic rollback on failure

---

## Project Structure

```
app/
├── Filament/Resources/     # Admin panel (Incidents, Reports, Users, Settings)
├── Http/Controllers/       # Web + API controllers
├── Http/Middleware/         # CheckSetup middleware
├── Livewire/               # Real-time components
├── Models/                 # Eloquent models (7 models)
├── Providers/Filament/     # Admin panel config
└── Services/               # GooglePlaces, GroqAI, VoiceCommand

database/
├── migrations/             # 10 migration files (tracked!)
└── seeders/                # Default settings + admin user

resources/views/
├── layouts/app.blade.php   # Main layout (metal theme)
├── components/             # Header, BottomNav, NongYingAvatar
├── pages/                  # Home, Login, Stations, Report, Chat, Setup, Offline
└── livewire/               # Real-time view components

routes/
├── web.php                 # Page routes + auth + setup
└── api.php                 # JSON API routes (throttled)
```

---

## Features

| Feature | Description |
|---------|-------------|
| **First-time Setup** | Web wizard สร้าง DB + admin ครั้งแรก |
| **Google/LINE Login** | OAuth via Laravel Socialite |
| **แจ้งเหตุบนถนน** | 6 ประเภท, auto-expire 4 ชม., upvote |
| **รายงานปั๊มน้ำมัน** | 9 ชนิดน้ำมัน, Google Places integration |
| **น้องหญิง AI** | Voice assistant + animated avatar (ying.png) |
| **Admin Panel** | Filament 3 — dashboard, CRUD, settings |
| **PWA** | ติดตั้งบนมือถือ, offline support |
| **Smart Deploy** | Auto migration + rollback + backup |

---

## API Endpoints

| Method | Endpoint | Description | Rate Limit |
|--------|----------|-------------|------------|
| GET | /api/incidents | Active incidents | 30/min |
| POST | /api/incidents | Create incident | 5/min |
| POST | /api/incidents/{id}/vote | Upvote | 10/min |
| GET | /api/stations | Search stations | 20/min |
| POST | /api/stations/report | Report fuel | 5/min |
| POST | /api/chat | AI chat | 10/min |
| POST | /api/voice-command | Voice command | 15/min |

---

## License

MIT

---

<p align="center">
  Made with ❤️ by <strong>XMAN Studio</strong>
</p>
