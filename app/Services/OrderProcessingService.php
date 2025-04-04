<?php

namespace App\Services;

use App\Exceptions\APIException;
use App\Exceptions\DatabaseException;
use App\Interfaces\APIClientInterface;
use App\Interfaces\DatabaseServiceInterface;
use App\Services\FileSystemService;
use App\Models\Order;

/**
 * Class OrderProcessingService
 * Handles the processing of orders based on their type and updates their status.
 */
class OrderProcessingService
{
    /**
     * @var DatabaseServiceInterface The database service for interacting with orders.
     */
    private $dbService;

    /**
     * @var APIClientInterface The API client for external API calls.
     */
    private $apiClient;

    /**
     * @var FileSystemService The file system service for file operations.
     */
    private $fileSystem;

    /**
     * OrderProcessingService constructor.
     *
     * @param DatabaseServiceInterface $dbService The database service.
     * @param APIClientInterface $apiClient The API client.
     * @param FileSystemService $fileSystem The file system service.
     */
    public function __construct(
        DatabaseServiceInterface $dbService,
        APIClientInterface $apiClient,
        FileSystemService $fileSystem
    ) {
        $this->dbService = $dbService;
        $this->apiClient = $apiClient;
        $this->fileSystem = $fileSystem;
    }

    /**
     * Processes orders for a given user and updates their status.
     *
     * @param int $userId The ID of the user whose orders are to be processed.
     * @return array|false The processed orders, or false on failure.
     */
    public function handle(int $userId)
    {
        try {
            $orders = $this->dbService->getOrdersByUser($userId);

            foreach ($orders as $order) {
                switch ($order->type) {
                    case 'A':
                        $this->processTypeAOrder($order, $userId);
                        break;

                    case 'B':
                        $this->processTypeBOrder($order);
                        break;

                    case 'C':
                        $this->processTypeCOrder($order);
                        break;

                    default:
                        $order->status = Order::STATUS_UNKNOWN_TYPE;
                        break;
                }

                $this->setPriority($order);
                $this->updateOrderInDatabase($order);
            }
            return $orders;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Processes orders of type 'A'.
     *
     * @param object $order The order to process.
     * @param int $userId The ID of the user.
     * @return void
     */
    private function processTypeAOrder($order, int $userId): void
    {
        $csvFile = 'orders_type_A_' . $userId . '_' . time() . '.csv';
        $fileHandle = $this->fileSystem->openFile($csvFile, 'w');

        if ($fileHandle !== false) {
            $this->fileSystem->writeCsv($fileHandle, ['ID', 'Type', 'Amount', 'Flag', 'Status', 'Priority']);

            $this->fileSystem->writeCsv($fileHandle, [
                $order->id,
                $order->type,
                $order->amount,
                $order->flag ? 'true' : 'false',
                $order->status,
                $order->priority
            ]);

            if ($order->amount > 150) {
                $this->fileSystem->writeCsv($fileHandle, ['', '', '', '', 'Note', 'High value order']);
            }

            $this->fileSystem->closeFile($fileHandle);
            $order->status = Order::STATUS_EXPORTED;
        } else {
            $order->status = Order::STATUS_EXPORT_FAILED;
        }
    }

    /**
     * Processes orders of type 'B'.
     *
     * @param object $order The order to process.
     * @return void
     */
    private function processTypeBOrder($order): void
    {
        try {
            $apiResponse = $this->apiClient->callAPI($order->id);

            if ($apiResponse->status === 'success') {
                if ($apiResponse->data->amount >= 50 && $order->amount < 100) {
                    $order->status = Order::STATUS_PROCESSED;
                } elseif ($apiResponse->data->amount < 50 || $order->flag) {
                    $order->status = Order::STATUS_PENDING;
                } else {
                    $order->status = Order::STATUS_ERROR;
                }
            } else {
                $order->status = Order::STATUS_API_ERROR;
            }
        } catch (APIException $e) {
            $order->status = Order::STATUS_API_FAILURE;
        }
    }

    /**
     * Processes orders of type 'C'.
     *
     * @param object $order The order to process.
     * @return void
     */
    private function processTypeCOrder($order): void
    {
        if ($order->flag) {
            $order->status = Order::STATUS_COMPLETED;
        } else {
            $order->status = Order::STATUS_IN_PROGRESS;
        }
    }

    /**
     * Sets the priority of an order based on its amount.
     *
     * @param object $order The order whose priority is to be set.
     * @return void
     */
    private function setPriority($order): void
    {
        if ($order->amount > 200) {
            $order->priority = Order::PRIORITY_HIGH;
        } else {
            $order->priority = Order::PRIORITY_LOW;
        }
    }

    /**
     * Updates the order status in the database.
     *
     * @param object $order The order to update.
     * @return void
     */
    private function updateOrderInDatabase($order): void
    {
        try {
            $this->dbService->updateOrderStatus($order->id, $order->status, $order->priority);
        } catch (DatabaseException $e) {
            $order->status = Order::STATUS_DB_ERROR;
        }
    }
}
