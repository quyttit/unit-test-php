<?php

namespace App\Interfaces;

use App\DTOs\APIResponse;

interface APIClientInterface
{
    /**
     * Call an external API with the order ID
     *
     * @param int $orderId
     * @return APIResponse
     * @throws \App\Exceptions\APIException
     */
    public function callAPI($orderId): APIResponse;
}
