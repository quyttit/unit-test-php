<?php

namespace App\DTOs;

use App\Models\Order;

/**
 * Class APIResponse
 * Represents the response from an API call.
 */
class APIResponse
{
    /**
     * @var string The status of the API response (e.g., 'success', 'failure').
     */
    public $status;

    /**
     * @var Order The data returned by the API, typically an Order object.
     */
    public $data;

    /**
     * APIResponse constructor.
     *
     * @param string $status The status of the API response.
     * @param Order $data The data returned by the API.
     */
    public function __construct(string $status, Order $data)
    {
        $this->status = $status;
        $this->data = $data;
    }
}
