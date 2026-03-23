# ThaiHelp — แอปชุมชนช่วยเหลือนักเดินทางไทย

> แพลตฟอร์มชุมชนสำหรับรายงานสถานะปั๊มน้ำมัน, แจ้งเหตุ, ค้นหาสถานพยาบาล, วางแผนเดินทาง พร้อม AI ผู้ช่วย "น้องหญิง"

**Live:** [thaihelp.xman4289.com](https://thaihelp.xman4289.com)
**Discord:** [เข้ากลุ่ม](https://discord.com/channels/1485495002024116294/1485495002699272224)
**Facebook:** [กลุ่ม ThaiHelp](https://www.facebook.com/groups/1196995685631749)

## ฟีเจอร์หลัก

### แผนที่อัจฉริยะ
- แสดงปั๊มน้ำมัน + สถานะ (มี/เหลือน้อย/หมด) สีเขียว/เหลือง/แดง
- เหตุการณ์ความรุนแรง 4 ระดับ (วิกฤต/รุนแรง/ปานกลาง/เล็กน้อย)
- Danger Zone กรอบแดงพื้นที่อันตราย (10+ คนยืนยัน)
- Google Maps Traffic Layer real-time
- ข่าวด่วนอัตโนมัติ (3+ คนรายงานเหมือนกัน → น้องหญิงเขียนข่าว)
- EV Charging stations (Open Charge Map)
- สถานพยาบาล + เตียงว่าง
- Balloon labels เปิด/ปิดได้

### Data Layers (เปิดปิดได้)
- สภาพอากาศ (Open-Meteo)
- คุณภาพอากาศ AQI/PM2.5 (WAQI)
- แผ่นดินไหว (USGS)
- เตือนน้ำท่วม (TMD)
- จราจร real-time (Google Maps)
- ขนส่งสาธารณะ
- เส้นทางจักรยาน

### น้องหญิง AI (Groq LLaMA 3.3)
- แชทภาษาไทย บุคลิกเด็กสาวน่ารัก
- รู้ทุกอย่าง: ปั๊ม, ราคาน้ำมัน, โรงพยาบาล, อากาศ, เหตุการณ์
- รับรายงานด้วยเสียง + ข้อมูลไม่ครบถามเพิ่มทีละข้อ
- จำบทสนทนา 8 ชม. (ตลอดการเดินทาง)
- นำทาง Google Maps ให้
- Text-to-Speech เสียงสาว (เปิด/ปิดได้)

### ระบบปั๊มน้ำมัน
- ค้นหาปั๊มใกล้ตัว (Google Places API)
- Crowdsource: ผู้ใช้รายงานสถานะน้ำมัน
- ยืนยันจาก 2+ IP → แสดงผล
- ราคาน้ำมันวันนี้ (PTT + Bangchak API)
- กราฟราคาย้อนหลัง 30 วัน

### สถานพยาบาล
- ER status: เปิดรับ/หนาแน่น/เตียงเต็ม
- เตียงว่าง + ICU
- Crowdsource + API
- นำทาง + โทร

### วางแผนเดินทาง
- เลือกรถ: รถยนต์ / EV / มอไซค์
- แสดงปั๊ม + ชาร์จ EV + เหตุการณ์ตลอดเส้นทาง
- น้องหญิงสรุปความปลอดภัยเส้นทาง

### Gamification
- 12 Badge achievements
- 6 ระดับดาว (สมาชิกใหม่ → ตำนาน ThaiHelp)
- ท้าทายรายวัน 3 ภารกิจ
- Leaderboard top 20

### ระบบฉุกเฉิน
- SOS กดปุ่มเดียว → โทร 1669/191/199/1784
- น้องหญิงแจ้งเหตุอัตโนมัติ (Discord + LINE + Webhook)
- Push Notification เหตุ critical ใกล้ตัว

### Progressive Web App
- ติดตั้งลงมือถือ iOS/Android
- Offline support (Service Worker)
- Push Notifications

### ข่าวอัตโนมัติ
- ดึงข่าวน้ำมัน/พลังงาน/วิกฤต ทุก 5 ชม.
- Google Trends: ตรวจ 25 keyword ฉุกเฉิน → ข่าวด่วนอัตโนมัติ

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 11 (PHP 8.3) |
| Frontend | Blade + Alpine.js + Tailwind CSS |
| Admin | Filament v3 |
| AI | Groq (LLaMA 3.3 70B) |
| Maps | Google Maps JavaScript API |
| Database | MySQL |
| Cache | Redis |
| Deploy | GitHub Actions → SSH |
| CDN | Cloudflare |
| TTS | Web Speech API |
| EV Data | Open Charge Map API |
| Weather | Open-Meteo API |
| AQI | WAQI API |
| Earthquake | USGS API |

## Installation

```bash
# Clone
git clone https://github.com/xjanova/thaihelp.git
cd thaihelp

# Install dependencies
composer install
npm install && npm run build

# Configure
cp .env.example .env
php artisan key:generate
# Edit .env with your DB, Google, LINE, Groq credentials

# Database
php artisan migrate
php artisan db:seed

# Or use web wizard
php artisan serve
# Open http://localhost:8000/setup

# Run
php artisan serve
```

## License

MIT License — see LICENSE file

## Team

- **XMAN Studio** — Design, Architecture, Product Owner
- **Claude AI (Anthropic)** — Co-developer, Code Generation

---
Built with love for Thailand
