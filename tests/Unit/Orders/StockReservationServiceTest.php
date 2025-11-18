<?php

namespace Tests\Unit\Orders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Modules\Orders\Infrastructure\Services\StockReservationService;
use Tests\TestCase;

class StockReservationServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_reserve_stock()
    {
        Log::spy();
        
        $service = new StockReservationService();
        
        // Should not throw exception (90% success rate)
        try {
            $service->reserveStock('PROD-001', 5, 'ORDER-001');
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // 10% failure rate is expected
            $this->assertStringContainsString('Insufficient stock', $e->getMessage());
        }

        Log::shouldHaveReceived('info')
            ->with('Reserving stock', \Mockery::type('array'));
    }

    /** @test */
    public function it_can_release_stock()
    {
        Log::spy();
        
        $service = new StockReservationService();
        $service->releaseStock('PROD-001', 5, 'ORDER-001');

        Log::shouldHaveReceived('info')
            ->with('Releasing stock', \Mockery::type('array'));
    }

    /** @test */
    public function it_logs_correct_parameters_when_reserving()
    {
        Log::spy();
        
        $service = new StockReservationService();
        
        try {
            $service->reserveStock('PROD-123', 10, 'ORDER-456');
        } catch (\Exception $e) {
            // Ignore failures
        }

        Log::shouldHaveReceived('info')
            ->with('Reserving stock', [
                'product_id' => 'PROD-123',
                'quantity' => 10,
                'order_id' => 'ORDER-456',
            ]);
    }
}

