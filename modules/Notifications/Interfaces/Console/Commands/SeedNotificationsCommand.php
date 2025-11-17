<?php

namespace Modules\Notifications\Interfaces\Console\Commands;

use Illuminate\Console\Command;
use Modules\Notifications\Infrastructure\Persistence\Seeders\NotificationSeeder;

class SeedNotificationsCommand extends Command
{
    protected $signature = 'notifications:seed 
                            {--count=20 : Number of orders to create notifications for}
                            {--clear : Clear existing notifications before seeding}';

    protected $description = 'Seed sample notifications for testing';

    public function handle(): int
    {
        if ($this->option('clear')) {
            if ($this->confirm('This will delete all existing notifications. Continue?')) {
                \Modules\Notifications\Domain\Models\Notification::truncate();
                $this->info('Cleared existing notifications.');
            } else {
                $this->info('Seeding cancelled.');
                return self::FAILURE;
            }
        }

        $this->info('Seeding sample notifications...');

        $seeder = new NotificationSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        $this->newLine();
        $this->info('Sample notifications created successfully!');
        $this->info('View them with: php artisan notifications:list');

        return self::SUCCESS;
    }
}

