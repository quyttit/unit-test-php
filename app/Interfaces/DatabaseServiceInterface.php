<?php

namespace App\Interfaces;

use App\Models\Order;

interface DatabaseServiceInterface
{
    /**
     * Summary of getOrdersByUser
     * @param mixed $userId
     * @return Order[]
     */
    public function getOrdersByUser($userId): array;

    /**
     * Summary of updateOrderStatus
     * @param mixed $orderId
     * @param mixed $status
     * @param mixed $priority
     * @return bool
     */
    public function updateOrderStatus($orderId, $status, $priority): bool;
}
