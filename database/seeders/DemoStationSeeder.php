<?php

namespace Database\Seeders;

use App\Models\FuelReport;
use App\Models\Incident;
use App\Models\StationReport;
use Illuminate\Database\Seeder;

class DemoStationSeeder extends Seeder
{
    public function run(): void
    {
        // Clear old demo data first
        FuelReport::whereHas('stationReport', fn($q) => $q->where('is_demo', true))->delete();
        StationReport::where('is_demo', true)->delete();
        Incident::where('is_demo', true)->delete();

        $this->seedStationReports();
        $this->seedIncidents();

        $this->command->info('Demo data seeded: ' . count($this->getStations()) . ' stations, ' . count($this->getIncidents()) . ' incidents');
    }

    private function seedStationReports(): void
    {
        foreach ($this->getStations() as $station) {
            $report = StationReport::create([
                'place_id'           => 'demo_' . md5($station['name'] . $station['lat']),
                'station_name'       => $station['name'],
                'reporter_name'      => $station['reporter'],
                'note'               => $station['note'],
                'latitude'           => $station['lat'],
                'longitude'          => $station['lng'],
                'is_demo'            => true,
                'is_verified'        => true,
                'confirmation_count' => rand(2, 8),
                'confirmed_ips'      => ['demo-1', 'demo-2'],
                'created_at'         => now()->subMinutes(rand(5, 120)),
            ]);

            foreach ($station['fuels'] as $fuel) {
                FuelReport::create([
                    'report_id' => $report->id,
                    'fuel_type' => $fuel['type'],
                    'status'    => $fuel['status'],
                    'price'     => $fuel['price'] ?? null,
                ]);
            }
        }
    }

    private function seedIncidents(): void
    {
        foreach ($this->getIncidents() as $incident) {
            Incident::create([
                'category'    => $incident['category'],
                'title'       => $incident['title'],
                'description' => $incident['description'],
                'latitude'    => $incident['lat'],
                'longitude'   => $incident['lng'],
                'is_active'   => true,
                'is_demo'     => true,
                'upvotes'     => rand(1, 15),
                'expires_at'  => now()->addHours(4),
                'created_at'  => now()->subMinutes(rand(10, 90)),
            ]);
        }
    }

    private function getStations(): array
    {
        return [
            // === กรุงเทพ - ย่านสุขุมวิท ===
            [
                'name' => 'PTT Station สุขุมวิท 39',
                'lat' => 13.7380, 'lng' => 100.5680,
                'reporter' => 'คุณสมชาย', 'note' => 'น้ำมันเต็มทุกหัวจ่าย คิวไม่ยาว',
                'fuels' => [
                    ['type' => 'gasohol95', 'status' => 'available', 'price' => 36.04],
                    ['type' => 'gasohol91', 'status' => 'available', 'price' => 33.54],
                    ['type' => 'diesel', 'status' => 'available', 'price' => 29.94],
                    ['type' => 'diesel_b7', 'status' => 'available', 'price' => 29.94],
                ],
            ],
            [
                'name' => 'Shell สุขุมวิท 71',
                'lat' => 13.7250, 'lng' => 100.5850,
                'reporter' => 'คุณนภา', 'note' => 'ดีเซล B7 หมดแล้ว รอเติมพรุ่งนี้เช้า',
                'fuels' => [
                    ['type' => 'gasohol95', 'status' => 'available', 'price' => 36.84],
                    ['type' => 'gasohol91', 'status' => 'available', 'price' => 34.34],
                    ['type' => 'diesel', 'status' => 'low', 'price' => 30.44],
                    ['type' => 'diesel_b7', 'status' => 'empty', 'price' => null],
                ],
            ],

            // === ย่านสีลม-สาทร ===
            [
                'name' => 'Bangchak สีลม',
                'lat' => 13.7270, 'lng' => 100.5330,
                'reporter' => 'คุณวิภา', 'note' => 'E20 ราคาถูกมาก คิว 5 คัน',
                'fuels' => [
                    ['type' => 'gasohol95', 'status' => 'available', 'price' => 35.94],
                    ['type' => 'e20', 'status' => 'available', 'price' => 32.04],
                    ['type' => 'diesel', 'status' => 'available', 'price' => 29.94],
                ],
            ],
            [
                'name' => 'Esso สาทร',
                'lat' => 13.7180, 'lng' => 100.5270,
                'reporter' => 'คุณธนา', 'note' => 'ปั๊มปิดปรับปรุง 1 หัวจ่าย น้ำมันยังมี',
                'fuels' => [
                    ['type' => 'gasohol95', 'status' => 'low', 'price' => 36.04],
                    ['type' => 'gasohol91', 'status' => 'available', 'price' => 33.54],
                    ['type' => 'diesel', 'status' => 'available', 'price' => 29.94],
                ],
            ],

            // === ย่านรัชดา-ลาดพร้าว ===
            [
                'name' => 'PTT Station รัชดาภิเษก 36',
                'lat' => 13.7820, 'lng' => 100.5740,
                'reporter' => 'คุณกิตติ', 'note' => 'แก๊ส NGV หมดแล้ว 2 วัน',
                'fuels' => [
                    ['type' => 'gasohol95', 'status' => 'available', 'price' => 36.04],
                    ['type' => 'diesel', 'status' => 'available', 'price' => 29.94],
                    ['type' => 'ngv', 'status' => 'empty', 'price' => null],
                    ['type' => 'lpg', 'status' => 'available', 'price' => 23.47],
                ],
            ],
            [
                'name' => 'Caltex ลาดพร้าว 80',
                'lat' => 13.7950, 'lng' => 100.6050,
                'reporter' => 'คุณแอน', 'note' => 'ปกติดี ว่างมาก ไม่ต้องรอ',
                'fuels' => [
                    ['type' => 'gasohol95', 'status' => 'available', 'price' => 36.24],
                    ['type' => 'gasohol91', 'status' => 'available', 'price' => 33.74],
                    ['type' => 'diesel', 'status' => 'available', 'price' => 30.14],
                    ['type' => 'e20', 'status' => 'available', 'price' => 32.24],
                ],
            ],

            // === ย่านบางนา-ศรีนครินทร์ ===
            [
                'name' => 'PTT Station บางนา กม.3',
                'lat' => 13.6620, 'lng' => 100.6340,
                'reporter' => 'คุณปริญญา', 'note' => 'คิวยาวมาก 20+ คัน ดีเซลใกล้หมด',
                'fuels' => [
                    ['type' => 'gasohol95', 'status' => 'available', 'price' => 36.04],
                    ['type' => 'diesel', 'status' => 'low', 'price' => 29.94],
                    ['type' => 'diesel_b7', 'status' => 'low', 'price' => 29.94],
                    ['type' => 'premium_diesel', 'status' => 'empty', 'price' => null],
                ],
            ],
            [
                'name' => 'Shell ศรีนครินทร์',
                'lat' => 13.6800, 'lng' => 100.6450,
                'reporter' => 'คุณมาลี', 'note' => 'เปิด 24 ชม. น้ำมันครบทุกชนิด',
                'fuels' => [
                    ['type' => 'gasohol95', 'status' => 'available', 'price' => 36.84],
                    ['type' => 'gasohol91', 'status' => 'available', 'price' => 34.34],
                    ['type' => 'diesel', 'status' => 'available', 'price' => 30.44],
                    ['type' => 'e85', 'status' => 'available', 'price' => 25.04],
                ],
            ],

            // === ย่านงามวงศ์วาน-แจ้งวัฒนะ ===
            [
                'name' => 'Bangchak งามวงศ์วาน',
                'lat' => 13.8520, 'lng' => 100.5690,
                'reporter' => 'คุณเอก', 'note' => 'น้ำมันหมดหลายตัว เหลือแค่ 95',
                'fuels' => [
                    ['type' => 'gasohol95', 'status' => 'available', 'price' => 35.94],
                    ['type' => 'gasohol91', 'status' => 'empty', 'price' => null],
                    ['type' => 'diesel', 'status' => 'empty', 'price' => null],
                    ['type' => 'e20', 'status' => 'empty', 'price' => null],
                ],
            ],
            [
                'name' => 'PTT Station แจ้งวัฒนะ',
                'lat' => 13.8680, 'lng' => 100.5820,
                'reporter' => 'คุณพิม', 'note' => 'เพิ่งเติมมาเมื่อเช้า ครบหมด',
                'fuels' => [
                    ['type' => 'gasohol95', 'status' => 'available', 'price' => 36.04],
                    ['type' => 'gasohol91', 'status' => 'available', 'price' => 33.54],
                    ['type' => 'diesel', 'status' => 'available', 'price' => 29.94],
                    ['type' => 'ngv', 'status' => 'available', 'price' => 18.59],
                ],
            ],

            // === ย่านพระราม 2-ถนนพุทธมณฑล ===
            [
                'name' => 'Esso พระราม 2',
                'lat' => 13.6400, 'lng' => 100.4700,
                'reporter' => 'คุณโจ้', 'note' => 'ปั๊มเงียบ เติมได้ทันที ไม่ต้องรอ',
                'fuels' => [
                    ['type' => 'gasohol95', 'status' => 'available', 'price' => 36.04],
                    ['type' => 'gasohol91', 'status' => 'available', 'price' => 33.54],
                    ['type' => 'diesel', 'status' => 'available', 'price' => 29.94],
                ],
            ],
            [
                'name' => 'Susco พุทธมณฑล สาย 4',
                'lat' => 13.7300, 'lng' => 100.3520,
                'reporter' => 'คุณหนุ่ม', 'note' => 'ราคาถูกกว่าที่อื่น 50 สตางค์',
                'fuels' => [
                    ['type' => 'gasohol95', 'status' => 'available', 'price' => 35.54],
                    ['type' => 'gasohol91', 'status' => 'available', 'price' => 33.04],
                    ['type' => 'diesel', 'status' => 'available', 'price' => 29.44],
                ],
            ],

            // === ปริมณฑล - นนทบุรี ===
            [
                'name' => 'PTT Station ติวานนท์',
                'lat' => 13.8620, 'lng' => 100.5150,
                'reporter' => 'คุณนิด', 'note' => 'แก๊ส LPG เหลือน้อย รีบมาเลย',
                'fuels' => [
                    ['type' => 'gasohol95', 'status' => 'available', 'price' => 36.04],
                    ['type' => 'diesel', 'status' => 'available', 'price' => 29.94],
                    ['type' => 'lpg', 'status' => 'low', 'price' => 23.47],
                ],
            ],

            // === สมุทรปราการ ===
            [
                'name' => 'Shell เทพารักษ์',
                'lat' => 13.6100, 'lng' => 100.6650,
                'reporter' => 'คุณต้อม', 'note' => 'ดีเซลพรีเมียม หมดแล้ว 3 วัน',
                'fuels' => [
                    ['type' => 'gasohol95', 'status' => 'available', 'price' => 36.84],
                    ['type' => 'diesel', 'status' => 'available', 'price' => 30.44],
                    ['type' => 'premium_diesel', 'status' => 'empty', 'price' => null],
                ],
            ],

            // === ปทุมธานี ===
            [
                'name' => 'Bangchak รังสิต-นครนายก',
                'lat' => 13.9580, 'lng' => 100.6200,
                'reporter' => 'คุณเบนซ์', 'note' => 'ปั๊มใหม่ สะอาดมาก ครบทุกชนิด',
                'fuels' => [
                    ['type' => 'gasohol95', 'status' => 'available', 'price' => 35.94],
                    ['type' => 'gasohol91', 'status' => 'available', 'price' => 33.44],
                    ['type' => 'e20', 'status' => 'available', 'price' => 31.94],
                    ['type' => 'diesel', 'status' => 'available', 'price' => 29.94],
                    ['type' => 'diesel_b7', 'status' => 'available', 'price' => 29.94],
                ],
            ],
        ];
    }

    private function getIncidents(): array
    {
        return [
            [
                'category' => 'flood',
                'title' => 'น้ำท่วมถนนพหลโยธิน ขาเข้า',
                'description' => 'น้ำท่วมสูง 30 ซม. บริเวณซอยพหลโยธิน 32 รถเก๋งไม่ควรผ่าน ใช้ทางเลี่ยงได้ทางวิภาวดี',
                'lat' => 13.8150, 'lng' => 100.5610,
            ],
            [
                'category' => 'accident',
                'title' => 'รถชนกัน 3 คัน ถนนรัชดาภิเษก',
                'description' => 'รถชนกัน 3 คัน เลนซ้ายปิด กู้ภัยกำลังดำเนินการ คาดเปิดใช้ได้ใน 1 ชม.',
                'lat' => 13.7680, 'lng' => 100.5730,
            ],
            [
                'category' => 'roadblock',
                'title' => 'ถนนสาทรปิดซ่อม',
                'description' => 'ปิดซ่อมท่อประปา ถนนสาทรเหนือ ฝั่งขาออก ตั้งแต่ 22:00-06:00',
                'lat' => 13.7210, 'lng' => 100.5290,
            ],
            [
                'category' => 'checkpoint',
                'title' => 'ตั้งด่านตรวจ ถนนวิภาวดี',
                'description' => 'ด่านตรวจแอลกอฮอล์ บริเวณหน้าเซ็นทรัลลาดพร้าว ตรวจทุกคัน',
                'lat' => 13.8160, 'lng' => 100.5620,
            ],
            [
                'category' => 'construction',
                'title' => 'ก่อสร้างรถไฟฟ้า สายสีส้ม',
                'description' => 'ก่อสร้างสถานีรถไฟฟ้าบริเวณแยกลำสาลี จราจรคับคั่งช่วง 07:00-09:00',
                'lat' => 13.7560, 'lng' => 100.5950,
            ],
        ];
    }
}
