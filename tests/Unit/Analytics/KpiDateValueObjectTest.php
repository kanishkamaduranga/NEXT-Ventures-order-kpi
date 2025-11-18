<?php

namespace Tests\Unit\Analytics;

use Modules\Analytics\Domain\ValueObjects\KpiDate;
use Tests\TestCase;

class KpiDateValueObjectTest extends TestCase
{
    public function test_it_creates_kpi_date_from_string()
    {
        $kpiDate = new KpiDate('2025-11-18');

        $this->assertEquals('2025-11-18', $kpiDate->toString());
    }

    public function test_it_validates_date_format()
    {
        $this->expectException(\InvalidArgumentException::class);

        new KpiDate('invalid-date');
    }

    public function test_it_handles_today()
    {
        $today = now()->format('Y-m-d');
        $kpiDate = new KpiDate($today);

        $this->assertEquals($today, $kpiDate->toString());
    }

    public function test_it_can_be_compared()
    {
        $date1 = new KpiDate('2025-11-18');
        $date2 = new KpiDate('2025-11-18');
        $date3 = new KpiDate('2025-11-19');

        $this->assertEquals($date1->toString(), $date2->toString());
        $this->assertNotEquals($date1->toString(), $date3->toString());
    }
}

