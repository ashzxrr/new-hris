<?php

namespace Tests\Unit;

use App\Services\AttendanceDetailFormatter;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AttendanceDetailFormatterTest extends TestCase
{
    public function test_it_formats_mobile_app_sources_and_photos_for_in_and_out_events(): void
    {
        $formatter = new AttendanceDetailFormatter();

        $logs = new Collection([
            (object) [
                'status' => 'IN',
                'datetime' => '2026-08-04 08:00:00',
                'machine_name' => 'Mobile App',
                'photo_url' => 'https://example.com/in.jpg',
            ],
            (object) [
                'status' => 'OUT',
                'datetime' => '2026-08-04 17:10:00',
                'machine_name' => 'Mobile App',
                'photo_url' => 'https://example.com/out.jpg',
            ],
        ]);

        $result = $formatter->buildDayDetail($logs, strtotime('2026-08-04 08:00:00'), strtotime('2026-08-04 17:10:00'));

        $this->assertSame('IN: Mobile App / OUT: Mobile App', $result['source_summary']);
        $this->assertSame('https://example.com/in.jpg', $result['in']['photo_url']);
        $this->assertSame('https://example.com/out.jpg', $result['out']['photo_url']);
        $this->assertTrue($result['has_mobile_photo']);
        $this->assertTrue($result['in']['is_mobile_app']);
        $this->assertTrue($result['out']['is_mobile_app']);
    }

    public function test_it_keeps_cuti_day_visible_even_when_previous_shift_out_is_detected(): void
    {
        $obj = new class {
            use \App\Http\Controllers\Concerns\AttendanceShiftTrait;

            public function callGetInOutForDay($pin, $tgl, $logs, $karyawan, $absenceNote = null)
            {
                return $this->getInOutForDay($pin, $tgl, $logs, $karyawan, $absenceNote);
            }
        };

        $karyawan = (object) [
            'job_title' => 'Security',
            'job_level' => 'Security',
            'nip' => 'LMG-2024-1039',
        ];

        $logs = collect([
            'LMG-2024-1039_2026-08-14' => collect([
                (object) ['status' => 'IN', 'datetime' => '2026-08-14 22:48:00'],
            ]),
            'LMG-2024-1039_2026-08-15' => collect([
                (object) ['status' => 'OUT', 'datetime' => '2026-08-15 07:02:00'],
            ]),
        ]);

        $result = $obj->callGetInOutForDay('LMG-2024-1039', '2026-08-15', $logs, $karyawan, (object) ['code' => 'Cuti']);

        $this->assertFalse($result['skip']);
        $this->assertNull($result['in_ts']);
        $this->assertNull($result['out_ts']);
    }
}
