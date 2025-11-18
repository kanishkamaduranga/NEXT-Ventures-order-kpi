<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
    Modules\Analytics\Providers\AnalyticsServiceProvider::class,
    Modules\Orders\Providers\OrdersServiceProvider::class,
    Modules\Notifications\Providers\NotificationsServiceProvider::class,
    Modules\Refunds\Providers\RefundsServiceProvider::class,
];
