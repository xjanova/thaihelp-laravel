@extends('layouts.app')

@section('content')
<div class="min-h-screen px-4 py-8 max-w-2xl mx-auto">
    <div class="metal-panel rounded-2xl p-5 border border-slate-700">
        <h1 class="text-xl font-bold text-white mb-1 text-center">ข้อตกลงการใช้งาน</h1>
        <p class="text-[10px] text-slate-500 text-center mb-6">อัปเดตล่าสุด: 24 มีนาคม 2026</p>

        <div class="space-y-5 text-xs text-slate-300 leading-relaxed">

            {{-- Acceptance --}}
            <div>
                <h2 class="text-sm font-bold text-white mb-2">1. การยอมรับข้อตกลง</h2>
                <p class="text-slate-400">การเข้าใช้งาน ThaiHelp ("แอปพลิเคชัน") ถือว่าคุณยอมรับข้อตกลงการใช้งานนี้ทั้งหมด หากไม่ยอมรับ กรุณาหยุดใช้งานแอปพลิเคชัน ข้อตกลงนี้มีผลบังคับใช้ตั้งแต่วันที่คุณเริ่มใช้งาน</p>
            </div>

            {{-- Service Description --}}
            <div>
                <h2 class="text-sm font-bold text-white mb-2">2. คำอธิบายบริการ</h2>
                <p class="text-slate-400 mb-2">ThaiHelp เป็นแพลตฟอร์มชุมชนที่ให้บริการ:</p>
                <ul class="list-disc list-inside space-y-1 text-slate-400">
                    <li>รายงานและค้นหาสถานะปั๊มน้ำมัน</li>
                    <li>แจ้งเหตุการณ์บนท้องถนน</li>
                    <li>ค้นหาสถานพยาบาลและสถานะเตียง</li>
                    <li>วางแผนเส้นทางการเดินทาง</li>
                    <li>ผู้ช่วย AI "น้องหญิง" สำหรับให้ข้อมูลและช่วยเหลือ</li>
                    <li>ข้อมูลสภาพอากาศ คุณภาพอากาศ แผ่นดินไหว</li>
                    <li>ข่าวสารและแจ้งเตือนฉุกเฉิน</li>
                </ul>
            </div>

            {{-- User Responsibilities --}}
            <div>
                <h2 class="text-sm font-bold text-white mb-2">3. ความรับผิดชอบของผู้ใช้</h2>
                <ul class="list-disc list-inside space-y-1 text-slate-400">
                    <li>รายงานข้อมูลตามความเป็นจริงเท่านั้น</li>
                    <li>ไม่รายงานข้อมูลเท็จ หรือสร้างความตื่นตระหนกโดยไม่มีเหตุผล</li>
                    <li>ไม่ใช้แอปพลิเคชันขณะขับรถ (ให้ผู้โดยสารใช้แทน)</li>
                    <li>รักษาความปลอดภัยของบัญชี ไม่แชร์ข้อมูลล็อกอิน</li>
                    <li>เคารพผู้ใช้งานคนอื่น ไม่ใช้ภาษาหยาบคายหรือข่มขู่</li>
                    <li>ไม่พยายามเข้าถึงระบบโดยไม่ได้รับอนุญาต</li>
                </ul>
            </div>

            {{-- Content Guidelines --}}
            <div>
                <h2 class="text-sm font-bold text-white mb-2">4. แนวทางเนื้อหา</h2>
                <p class="text-slate-400 mb-2">เนื้อหาที่ห้ามโพสต์หรือรายงาน:</p>
                <ul class="list-disc list-inside space-y-1 text-slate-400">
                    <li>ข้อมูลเท็จหรือทำให้เข้าใจผิด</li>
                    <li>เนื้อหาที่ผิดกฎหมายหรือส่งเสริมกิจกรรมผิดกฎหมาย</li>
                    <li>เนื้อหาลามก อนาจาร หรือรุนแรง</li>
                    <li>ข้อมูลส่วนบุคคลของผู้อื่นโดยไม่ได้รับอนุญาต</li>
                    <li>สแปม โฆษณา หรือเนื้อหาเชิงพาณิชย์ที่ไม่ได้รับอนุญาต</li>
                    <li>เนื้อหาที่เหยียดเชื้อชาติ ศาสนา หรือเพศ</li>
                </ul>
                <p class="text-slate-500 mt-2">เราสงวนสิทธิ์ในการลบเนื้อหาที่ละเมิดแนวทางนี้โดยไม่ต้องแจ้งล่วงหน้า</p>
            </div>

            {{-- Disclaimer --}}
            <div>
                <h2 class="text-sm font-bold text-white mb-2">5. ข้อจำกัดความรับผิดชอบ</h2>
                <ul class="list-disc list-inside space-y-1 text-slate-400">
                    <li>ข้อมูลปั๊มน้ำมันและสถานพยาบาลมาจากชุมชน อาจไม่ถูกต้อง 100%</li>
                    <li>น้องหญิง AI เป็นผู้ช่วยเท่านั้น ไม่ใช่ที่ปรึกษาทางการแพทย์หรือกฎหมาย</li>
                    <li>ข้อมูลสภาพอากาศ แผ่นดินไหว มาจาก API ภายนอก อาจมีความล่าช้า</li>
                    <li>การนำทางควรใช้วิจารณญาณของตนเองร่วมด้วยเสมอ</li>
                    <li>ในสถานการณ์ฉุกเฉิน ให้โทรสายด่วนโดยตรง (1669, 191, 199)</li>
                </ul>
            </div>

            {{-- Limitation of Liability --}}
            <div>
                <h2 class="text-sm font-bold text-white mb-2">6. ข้อจำกัดความรับผิด</h2>
                <p class="text-slate-400">ThaiHelp และทีมพัฒนาไม่รับผิดชอบต่อ:</p>
                <ul class="list-disc list-inside space-y-1 text-slate-400 mt-1">
                    <li>ความเสียหายที่เกิดจากการใช้ข้อมูลจากแอปพลิเคชัน</li>
                    <li>ความไม่ถูกต้องของข้อมูลที่ผู้ใช้รายงาน</li>
                    <li>การหยุดทำงานของระบบหรือบริการชั่วคราว</li>
                    <li>ความเสียหายทางอ้อม ผลสืบเนื่อง หรือค่าเสียโอกาส</li>
                </ul>
                <p class="text-slate-500 mt-2">บริการให้ "ตามสภาพ" (as-is) โดยไม่มีการรับประกันใดๆ ทั้งโดยชัดแจ้งและโดยปริยาย</p>
            </div>

            {{-- Intellectual Property --}}
            <div>
                <h2 class="text-sm font-bold text-white mb-2">7. ทรัพย์สินทางปัญญา</h2>
                <p class="text-slate-400">โลโก้ ชื่อ "ThaiHelp" ตัวละคร "น้องหญิง" และส่วนประกอบทางกราฟิกทั้งหมดเป็นทรัพย์สินของ XMAN Studio สงวนลิขสิทธิ์ ห้ามนำไปใช้ซ้ำโดยไม่ได้รับอนุญาต</p>
            </div>

            {{-- Termination --}}
            <div>
                <h2 class="text-sm font-bold text-white mb-2">8. การยกเลิกบัญชี</h2>
                <p class="text-slate-400">เราสงวนสิทธิ์ในการระงับหรือยกเลิกบัญชีผู้ใช้ที่ละเมิดข้อตกลง รายงานข้อมูลเท็จซ้ำ หรือกระทำการที่เป็นอันตรายต่อชุมชน โดยแจ้งให้ทราบล่วงหน้าตามสมควร</p>
            </div>

            {{-- Changes --}}
            <div>
                <h2 class="text-sm font-bold text-white mb-2">9. การเปลี่ยนแปลงข้อตกลง</h2>
                <p class="text-slate-400">เราอาจปรับปรุงข้อตกลงนี้เป็นครั้งคราว การเปลี่ยนแปลงจะมีผลทันทีเมื่อประกาศผ่านแอปพลิเคชัน การใช้งานต่อหลังการเปลี่ยนแปลงถือว่าคุณยอมรับข้อตกลงใหม่</p>
            </div>

            {{-- Contact --}}
            <div>
                <h2 class="text-sm font-bold text-white mb-2">10. ติดต่อเรา</h2>
                <div class="bg-slate-800/50 rounded-xl p-3">
                    <p class="text-slate-300">หากมีคำถามเกี่ยวกับข้อตกลงนี้ สามารถติดต่อได้ที่:</p>
                    <p class="text-orange-400 mt-1">contact@xman4289.com</p>
                    <p class="text-slate-400 mt-1">Discord: <a href="https://discord.com/channels/1485495002024116294/1485495002699272224" target="_blank" class="text-blue-400 underline">กลุ่ม ThaiHelp</a></p>
                </div>
            </div>

            {{-- Governing Law --}}
            <div>
                <h2 class="text-sm font-bold text-white mb-2">11. กฎหมายที่ใช้บังคับ</h2>
                <p class="text-slate-400">ข้อตกลงนี้อยู่ภายใต้กฎหมายแห่งราชอาณาจักรไทย ข้อพิพาทที่เกิดขึ้นจะอยู่ภายใต้เขตอำนาจของศาลไทย</p>
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
