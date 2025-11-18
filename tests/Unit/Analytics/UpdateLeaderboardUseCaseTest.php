<?php

namespace Tests\Unit\Analytics;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Analytics\Application\UseCases\UpdateLeaderboardUseCase;
use Modules\Analytics\Domain\Repositories\LeaderboardRepositoryInterface;
use Tests\TestCase;

class UpdateLeaderboardUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the leaderboard repository
        $mockRepository = \Mockery::mock(LeaderboardRepositoryInterface::class);
        $this->app->instance(LeaderboardRepositoryInterface::class, $mockRepository);
    }

    public function test_it_updates_customer_spending()
    {
        $date = now()->format('Y-m-d');

        $mockRepository = \Mockery::mock(LeaderboardRepositoryInterface::class);
        $mockRepository->shouldReceive('updateCustomerSpending')
            ->once()
            ->with('1001', $date, 100.00);

        $mockRepository->shouldReceive('getTopCustomers')
            ->once()
            ->with($date, 10)
            ->andReturn(['1001' => 100.00]);

        $this->app->instance(LeaderboardRepositoryInterface::class, $mockRepository);

        $useCase = app(UpdateLeaderboardUseCase::class);
        $useCase->execute('1001', $date, 100.00);

        $topCustomers = $useCase->getTopCustomers($date, 10);

        $this->assertNotEmpty($topCustomers);
        $this->assertArrayHasKey('1001', $topCustomers);
        $this->assertEquals(100.00, $topCustomers['1001']);
    }

    public function test_it_handles_multiple_customers()
    {
        $date = now()->format('Y-m-d');

        $mockRepository = \Mockery::mock(LeaderboardRepositoryInterface::class);
        $mockRepository->shouldReceive('updateCustomerSpending')
            ->times(3)
            ->with(\Mockery::any(), $date, \Mockery::any());

        $mockRepository->shouldReceive('getTopCustomers')
            ->once()
            ->with($date, 10)
            ->andReturn([
                '1001' => 300.00,
                '1002' => 200.00,
                '1003' => 100.00,
            ]);

        $this->app->instance(LeaderboardRepositoryInterface::class, $mockRepository);

        $useCase = app(UpdateLeaderboardUseCase::class);
        $useCase->execute('1001', $date, 300.00);
        $useCase->execute('1002', $date, 200.00);
        $useCase->execute('1003', $date, 100.00);

        $topCustomers = $useCase->getTopCustomers($date, 10);

        $this->assertCount(3, $topCustomers);
        $this->assertEquals(300.00, $topCustomers['1001']);
        $this->assertEquals(200.00, $topCustomers['1002']);
        $this->assertEquals(100.00, $topCustomers['1003']);
    }

    public function test_it_handles_refund()
    {
        $date = now()->format('Y-m-d');

        $mockRepository = \Mockery::mock(LeaderboardRepositoryInterface::class);
        $mockRepository->shouldReceive('updateCustomerSpending')
            ->once()
            ->with('1001', $date, 100.00);

        $mockRepository->shouldReceive('removeCustomerSpending')
            ->once()
            ->with('1001', $date, 50.00);

        $mockRepository->shouldReceive('getTopCustomers')
            ->once()
            ->with($date, 10)
            ->andReturn(['1001' => 50.00]);

        $this->app->instance(LeaderboardRepositoryInterface::class, $mockRepository);

        $useCase = app(UpdateLeaderboardUseCase::class);
        $useCase->execute('1001', $date, 100.00);
        $useCase->handleRefund('1001', $date, 50.00);

        $topCustomers = $useCase->getTopCustomers($date, 10);

        $this->assertArrayHasKey('1001', $topCustomers);
        $this->assertEquals(50.00, $topCustomers['1001']);
    }

    public function test_it_returns_customer_rank()
    {
        $date = now()->format('Y-m-d');

        $mockRepository = \Mockery::mock(LeaderboardRepositoryInterface::class);
        $mockRepository->shouldReceive('updateCustomerSpending')
            ->times(3)
            ->with(\Mockery::any(), $date, \Mockery::any());

        $mockRepository->shouldReceive('getCustomerRank')
            ->with('1001', $date)
            ->andReturn(1);
        $mockRepository->shouldReceive('getCustomerRank')
            ->with('1002', $date)
            ->andReturn(2);
        $mockRepository->shouldReceive('getCustomerRank')
            ->with('1003', $date)
            ->andReturn(3);

        $this->app->instance(LeaderboardRepositoryInterface::class, $mockRepository);

        $useCase = app(UpdateLeaderboardUseCase::class);
        $useCase->execute('1001', $date, 300.00);
        $useCase->execute('1002', $date, 200.00);
        $useCase->execute('1003', $date, 100.00);

        $rank1 = $useCase->getCustomerRank('1001', $date);
        $rank2 = $useCase->getCustomerRank('1002', $date);
        $rank3 = $useCase->getCustomerRank('1003', $date);

        $this->assertEquals(1, $rank1);
        $this->assertEquals(2, $rank2);
        $this->assertEquals(3, $rank3);
    }

    public function test_it_limits_top_customers()
    {
        $date = now()->format('Y-m-d');

        $mockRepository = \Mockery::mock(LeaderboardRepositoryInterface::class);
        $mockRepository->shouldReceive('updateCustomerSpending')
            ->times(15)
            ->with(\Mockery::any(), $date, \Mockery::any());

        // Return only 10 customers even though 15 were added
        $topCustomers = [];
        for ($i = 15; $i >= 6; $i--) {
            $topCustomers[(string)(1000 + $i)] = (float)($i * 10);
        }

        $mockRepository->shouldReceive('getTopCustomers')
            ->once()
            ->with($date, 10)
            ->andReturn($topCustomers);

        $this->app->instance(LeaderboardRepositoryInterface::class, $mockRepository);

        $useCase = app(UpdateLeaderboardUseCase::class);
        for ($i = 1; $i <= 15; $i++) {
            $useCase->execute((string)(1000 + $i), $date, (float)($i * 10));
        }

        $result = $useCase->getTopCustomers($date, 10);

        $this->assertCount(10, $result);
    }

    public function test_it_handles_accumulated_spending()
    {
        $date = now()->format('Y-m-d');

        $mockRepository = \Mockery::mock(LeaderboardRepositoryInterface::class);
        $mockRepository->shouldReceive('updateCustomerSpending')
            ->times(3)
            ->with('1001', $date, \Mockery::any());

        $mockRepository->shouldReceive('getTopCustomers')
            ->once()
            ->with($date, 10)
            ->andReturn(['1001' => 175.00]);

        $this->app->instance(LeaderboardRepositoryInterface::class, $mockRepository);

        $useCase = app(UpdateLeaderboardUseCase::class);
        $useCase->execute('1001', $date, 100.00);
        $useCase->execute('1001', $date, 50.00);
        $useCase->execute('1001', $date, 25.00);

        $topCustomers = $useCase->getTopCustomers($date, 10);

        $this->assertArrayHasKey('1001', $topCustomers);
        $this->assertEquals(175.00, $topCustomers['1001']);
    }
}

