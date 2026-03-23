@extends('layouts.app')

@section('content')
<div class="min-h-screen px-4 py-8 max-w-2xl mx-auto">
    <div class="metal-panel rounded-2xl p-5 border border-slate-700">
        <h1 class="text-xl font-bold text-white mb-1 text-center">นโยบายความเป็นส่วนตัว</h1>
        <p class="text-[10px] text-slate-500 text-center mb-6">อัปเดตล่าสุด: 24 มีนาคม 2026</p>

        <div class="space-y-5 text-xs text-slate-300 leading-relaxed">

            {{-- Introduction --}}
            <div>
                <p>ThaiHelp ("เรา", "แอปพลิเคชัน") ให้ความสำคัญกับความเป็นส่วนตัวของผู้ใช้งาน ("คุณ", "ผู้ใช้") ตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA) นโยบายนี้อธิบายวิธีการเก็บรวบรวม ใช้ และปกป้องข้อมูลของคุณ</p>
            </div>

            {{-- Data Collection --}}
            <div>
                <h2 class="text-sm font-bold text-white mb-2">1. ข้อมูลที่เราเก็บรวบรวม</h2>
                <ul class="list-disc list-inside space-y-1 text-slate-400">
                    <li><strong class="text-slate-300">ข้อมูลตำแหน่ง (GPS):</strong> ใช้เพื่อแสดงปั๊มน้ำมัน สถานพยาบาล และเหตุการณ์ใกล้ตัวคุณ เก็บเฉพาะเมื่อคุณอนุญาต</li>
                    <li><strong class="text-slate-300">IP Address:</strong> ใช้เพื่อป้องกันการรายงานซ้ำและตรวจสอบความถูกต้องของข้อมูล</li>
                    <li><strong class="text-slate-300">ข้อมูลรายงาน:</strong> เนื้อหาที่คุณรายงาน เช่น สถานะปั๊มน้ำมัน เหตุการณ์บนท้องถนน</li>
                    <li><strong class="text-slate-300">ข้อมูลแชท:</strong> บทสนทนากับน้องหญิง AI เก็บในเซสชันเท่านั้น (8 ชม.) ไม่เก็บถาวร</li>
                    <li><strong class="text-slate-300">ข้อมูลบัญชี:</strong> ชื่อเล่น, อีเมล (กรณีล็อกอินผ่าน Google/LINE)</li>
                    <li><strong class="text-slate-300">ข้อมูลการใช้งาน:</strong> หน้าที่เข้าชม, ฟีเจอร์ที่ใช้, เวลาใช้งาน</li>
                </ul>
            </div>

            {{-- How We Use --}}
            <div>
                <h2 class="text-sm font-bold text-white mb-2">2. วัตถุประสงค์ในการใช้ข้อมูล</h2>
                <ul class="list-disc list-inside space-y-1 text-slate-400">
                    <li>แสดงข้อมูลปั๊มน้ำมัน สถานพยาบาล และเหตุการณ์ที่เกี่ยวข้องกับตำแหน่งของคุณ</li>
                    <li>ให้บริการ AI ผู้ช่วย (น้องหญิง) ตอบคำถามและช่วยวางแผนเดินทาง</li>
                    <li>ระบบ Gamification: คำนวณดาว, badge, leaderboard</li>
                    <li>ส่ง Push Notification เหตุการณ์ฉุกเฉินใกล้ตัว</li>
                    <li>ปรับปรุงคุณภาพบริการ และแก้ไขปัญหาทางเทคนิค</li>
                    <li>ป้องกันการใช้งานที่ไม่เหมาะสม (spam, fake reports)</li>
                </ul>
            </div>

            {{-- Data Protection --}}
            <div>
                <h2 class="text-sm font-bold text-white mb-2">3. การปกป้องข้อมูล</h2>
                <ul class="list-disc list-inside space-y-1 text-slate-400">
                    <li>ข้อมูลส่งผ่าน HTTPS (SSL/TLS) เสมอ</li>
                    <li>ฐานข้อมูลเข้ารหัสและจำกัดการเข้าถึง</li>
                    <li>ใช้ Cloudflare CDN เพื่อป้องกันภัยคุกคามทางไซเบอร์</li>
                    <li>IP Address จะถูก hash ก่อนจัดเก็บ ไม่เก็บ IP จริง</li>
                    <li>ข้อมูลแชทกับ AI ไม่ถูกบันทึกถาวร จะลบหลัง 8 ชั่วโมง</li>
                    <li>ไม่ขายหรือแบ่งปันข้อมูลส่วนบุคคลให้บุคคลที่สาม</li>
                </ul>
            </div>

            {{-- User Rights --}}
            <div>
                <h2 class="text-sm font-bold text-white mb-2">4. สิทธิของผู้ใช้ตาม PDPA</h2>
                <ul class="list-disc list-inside space-y-1 text-slate-400">
                    <li><strong class="text-slate-300">สิทธิในการเข้าถึง:</strong> ขอดูข้อมูลส่วนบุคคลของคุณที่เราเก็บ</li>
                    <li><strong class="text-slate-300">สิทธิในการแก้ไข:</strong> ขอแก้ไขข้อมูลที่ไม่ถูกต้อง</li>
                    <li><strong class="text-slate-300">สิทธิในการลบ:</strong> ขอให้ลบข้อมูลส่วนบุคคลของคุณ</li>
                    <li><strong class="text-slate-300">สิทธิในการโอนย้าย:</strong> ขอรับข้อมูลในรูปแบบที่สามารถอ่านได้</li>
                    <li><strong class="text-slate-300">สิทธิในการคัดค้าน:</strong> คัดค้านการเก็บรวบรวมหรือใช้ข้อมูล</li>
                    <li><strong class="text-slate-300">สิทธิในการถอนความยินยอม:</strong> ถอนความยินยอมได้ทุกเมื่อ</li>
                </ul>
            </div>

            {{-- Contact for Data Requests --}}
            <div>
                <h2 class="text-sm font-bold text-white mb-2">5. ช่องทางขอใช้สิทธิข้อมูล</h2>
                <p class="text-slate-400">หากต้องการใช้สิทธิตาม PDPA สามารถติดต่อได้ที่:</p>
                <div class="mt-2 bg-slate-800/50 rounded-xl p-3">
                    <p class="text-slate-300">อีเมล: <span class="text-orange-400">contact@xman4289.com</span></p>
                    <p class="text-slate-400 mt-1">เราจะดำเนินการภายใน 30 วันหลังได้รับคำร้อง</p>
                </div>
            </div>

            {{-- Cookie Policy --}}
            <div>
                <h2 class="text-sm font-bold text-white mb-2">6. นโยบายคุกกี้</h2>
                <p class="text-slate-400 mb-2">เราใช้คุกกี้และ Local Storage เพื่อ:</p>
                <ul class="list-disc list-inside space-y-1 text-slate-400">
                    <li><strong class="text-slate-300">คุกกี้ที่จำเป็น:</strong> เซสชันล็อกอิน, CSRF token (ไม่สามารถปิดได้)</li>
                    <li><strong class="text-slate-300">คุกกี้การทำงาน:</strong> จดจำการตั้งค่า เช่น เสียงน้องหญิง, ธีม</li>
                    <li><strong class="text-slate-300">Local Storage:</strong> ข้อมูลชั่วคราว เช่น บทสนทนาแชท, PWA state</li>
                </ul>
                <p class="text-slate-500 mt-2">คุณสามารถเลือกยอมรับหรือปฏิเสธคุกกี้ที่ไม่จำเป็นได้ผ่านแบนเนอร์คุกกี้เมื่อเข้าใช้งานครั้งแรก</p>
            </div>

            {{-- Third Party --}}
            <div>
                <h2 class="text-sm font-bold text-white mb-2">7. บริการของบุคคลที่สาม</h2>
                <p class="text-slate-400 mb-2">เราใช้บริการภายนอกดังนี้:</p>
                <ul class="list-disc list-inside space-y-1 text-slate-400">
                    <li>Google Maps API — แสดงแผนที่และค้นหาสถานที่</li>
                    <li>Google/LINE OAuth — ระบบล็อกอิน</li>
                    <li>Groq AI — ประมวลผลแชท AI (ไม่ส่งข้อมูลส่วนบุคคล)</li>
                    <li>Cloudflare — CDN และการป้องกันภัยคุกคาม</li>
                </ul>
            </div>

            {{-- Changes --}}
            <div>
                <h2 class="text-sm font-bold text-white mb-2">8. การเปลี่ยนแปลงนโยบาย</h2>
                <p class="text-slate-400">เราอาจปรับปรุงนโยบายนี้เป็นครั้งคราว การเปลี่ยนแปลงจะประกาศผ่านแอปพลิเคชัน การใช้งานต่อหลังการเปลี่ยนแปลงถือว่าคุณยอมรับนโยบายใหม่</p>
            </div>

        </div>
    </div>

    {{-- Back Button --}}
    <div class="text-center mt-6 mb-4">
        <a href="/" class="inline-block metal-btn px-6 py-2 rounded-xl text-sm text-slate-300 hover:text-white transition-colors">
            ← กลับหน้าหลัก
        </a>
    </div>
</div>
@endsection
