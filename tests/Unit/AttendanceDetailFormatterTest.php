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
}
