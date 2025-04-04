<?php

namespace App\Models;

/**
 * Class Order
 * Represents an order with details such as type, amount, and status.
 */
class Order
{
    /**
     * @var int The unique identifier for the order.
     */
    public $id;

    /**
     * @var string The type of the order (e.g., 'A', 'B', 'C').
     */
    public $type;

    /**
     * @var float The amount associated with the order.
     */
    public $amount;

    /**
     * @var bool|null A flag indicating additional order details (e.g., priority).
     */
    public $flag;

    /**
     * @var string The current status of the order (default: 'new').
     */
    public $status;

    /**
     * @var string The priority of the order (default: 'low').
     */
    public $priority;

    /**
     * Constants for order statuses.
     */
    public const STATUS_NEW = 'new';
    public const STATUS_EXPORTED = 'exported';
    public const STATUS_EXPORT_FAILED = 'export_failed';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_PENDING = 'pending';
    public const STATUS_ERROR = 'error';
    public const STATUS_API_ERROR = 'api_error';
    public const STATUS_API_FAILURE = 'api_failure';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_UNKNOWN_TYPE = 'unknown_type';
    public const STATUS_DB_ERROR = 'db_error';

    /**
     * Constants for order priorities.
     */
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_HIGH = 'high';

    /**
     * Order constructor.
     *
     * @param int $id The unique identifier for the order.
     * @param string $type The type of the order.
     * @param float $amount The amount associated with the order.
     * @param bool|null $flag A flag indicating additional order details.
     */
    public function __construct(int $id, string $type, float $amount, ?bool $flag)
    {
        $this->id = $id;
        $this->type = $type;
        $this->amount = $amount;
        $this->flag = $flag;
        $this->status = self::STATUS_NEW;
        $this->priority = self::PRIORITY_LOW;
    }
}
