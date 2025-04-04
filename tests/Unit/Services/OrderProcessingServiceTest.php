<?php

use App\DTOs\APIResponse;
use App\Exceptions\APIException;
use App\Exceptions\DatabaseException;
use App\Interfaces\APIClientInterface;
use App\Interfaces\DatabaseServiceInterface;
use App\Models\Order;
use App\Services\FileSystemService;
use App\Services\OrderProcessingService;
use Faker\Factory;

// Setup Faker for dynamic data generation
$faker = Factory::create();

// Setup shared mocks and dependencies
beforeEach(function () use ($faker) {
    $this->faker = $faker;
    $this->dbService = mock(DatabaseServiceInterface::class);
    $this->apiClient = mock(APIClientInterface::class);
    $this->userId = $this->faker->randomNumber(3);
    $fileSystemService = new FileSystemService();
    $this->service = new OrderProcessingService($this->dbService, $this->apiClient, $fileSystemService);
});

// Helper function to create an Order object
function createOrder($faker, $type, $amount, $flag) {
    return new Order(
        $faker->randomNumber(4),
        $type,
        $amount,
        $flag
    );
}

// Cleanup after each test
afterEach(function () {
    $files = glob('orders_type_A_*_*.csv');
    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

// Test Case 1: Type A with amount > 150
test('Type A with amount > 150 creates CSV with High value order note', function () {
    $order = createOrder($this->faker, 'A', 201, true);

    // Example Mock
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with($this->userId)
        ->once()
        ->andReturn([$order]);
    // Example Stub
    $responseUpdateOrderStatus = false;
    $this->dbService->shouldReceive('updateOrderStatus')
        ->andReturn($responseUpdateOrderStatus);

    // Ensure the CSV file can be created successfully
    $csvFilePath = 'orders_type_A_' . $this->userId . '_' . time() . '.csv';
    touch($csvFilePath); // Create an empty file to simulate successful creation

    $result = $this->service->handle($this->userId);

    // Example Spy
    $this->dbService->shouldHaveReceived('getOrdersByUser')
        ->withArgs(fn($userId) => $userId === $this->userId)    
        ->once();
    $this->dbService->shouldHaveReceived('updateOrderStatus')
        ->withArgs(fn($id, $status, $priority) => $id === $order->id && $status === Order::STATUS_EXPORTED && $priority === Order::PRIORITY_HIGH)    
        ->once();

    // Ensure the status is updated to 'exported'
    expect($result[0]->status)->toBe(Order::STATUS_EXPORTED);
    expect($result[0]->priority)->toBe(Order::PRIORITY_HIGH);

    // Verify the CSV file is created and contains the "High value order" note
    $latestCsvFile = glob('orders_type_A_' . $this->userId . '_*.csv')[0] ?? null;
    expect($latestCsvFile)->not->toBeNull();

    $csvContent = file_get_contents($latestCsvFile);
    expect($csvContent)->toContain('High value order');
});

// Test Case 2: Type A with amount <= 150
test('Type A with amount <= 150 creates CSV without High value order note', function () {
    $order = createOrder($this->faker, 'A', 100, false);

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with($this->userId)
        ->once()
        ->andReturn([$order]);

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, Order::STATUS_EXPORTED, Order::PRIORITY_LOW)
        ->once()
        ->andReturn(true);

    $result = $this->service->handle($this->userId);

    expect($result[0]->status)->toBe(Order::STATUS_EXPORTED);
    expect($result[0]->priority)->toBe(Order::PRIORITY_LOW);

    $latestCsvFile = glob('orders_type_A_' . $this->userId . '_*.csv')[0] ?? null;
    expect($latestCsvFile)->not->toBeNull();

    $csvContent = file_get_contents($latestCsvFile);
    expect($csvContent)->not->toContain('High value order');
});

// Test Case 3: Type A fails to create CSV
test('Type A fails to create CSV when file cannot be opened', function () {
    $order = createOrder($this->faker, 'A', 50, true);

    $this->dbService->shouldReceive('getOrdersByUser')
        ->withArgs(function ($userId) {
            expect($userId)->toBe($this->userId);
            return true;
        })
        ->once()
        ->andReturn([$order]);

    $this->dbService->shouldReceive('updateOrderStatus')
        ->withArgs(function ($orderId, $status, $priority) use ($order) {
            expect($orderId)->toBe($order->id);
            expect($status)->toBe(Order::STATUS_EXPORT_FAILED);
            expect($priority)->toBe(Order::PRIORITY_LOW);
            return true;
        })
        ->once()
        ->andReturn(true);
    $fileSystemService = mock(FileSystemService::class);
    $fileSystemService->shouldReceive('openFile')
        ->once()
        ->andReturn(false); // Simulate failure to open file

    $service = new OrderProcessingService($this->dbService, $this->apiClient, $fileSystemService);
    $result = $service->handle($this->userId);

    // Ensure the status is updated to 'export_failed'
    expect($result[0]->status)->toBe(Order::STATUS_EXPORT_FAILED);
});

// Test Case 4: Type B with API success, data >= 50, amount < 100
test('Type B with API success, data >= 50, amount < 100 sets status to processed', function () {
    $order = createOrder($this->faker, 'B', 90, false);
    $apiResponse = new APIResponse('success', createOrder($this->faker, 'X', 60, false));

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with($this->userId)
        ->once()
        ->andReturn([$order]);

    $this->apiClient->shouldReceive('callAPI')
        ->with($order->id)
        ->once()
        ->andReturn($apiResponse);

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, Order::STATUS_PROCESSED, Order::PRIORITY_LOW)
        ->once()
        ->andReturn(true);

    $result = $this->service->handle($this->userId);

    expect($result[0]->status)->toBe(Order::STATUS_PROCESSED);
    expect($result[0]->priority)->toBe(Order::PRIORITY_LOW);
});

// Test Case 5: Type B with API success, data < 50
test('Type B with API success, data < 50 sets status to pending', function () {
    $order = createOrder($this->faker, 'B', 150, false);
    $apiResponse = new APIResponse('success', createOrder($this->faker, 'X', 40, false));

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with($this->userId)
        ->once()
        ->andReturn([$order]);

    $this->apiClient->shouldReceive('callAPI')
        ->with($order->id)
        ->once()
        ->andReturn($apiResponse);

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, Order::STATUS_PENDING, Order::PRIORITY_LOW)
        ->once()
        ->andReturn(true);

    $result = $this->service->handle($this->userId);

    expect($result[0]->status)->toBe(Order::STATUS_PENDING);
    expect($result[0]->priority)->toBe(Order::PRIORITY_LOW);
});

// Test Case 6: Type B with API success, flag = true
test('Type B with API success and flag = true sets status to pending', function () {
    $order = createOrder($this->faker, 'B', 250, true);
    $apiResponse = new APIResponse('success', createOrder($this->faker, 'X', 100, false));

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with($this->userId)
        ->once()
        ->andReturn([$order]);

    $this->apiClient->shouldReceive('callAPI')
        ->with($order->id)
        ->once()
        ->andReturn($apiResponse);

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, Order::STATUS_PENDING, Order::PRIORITY_HIGH)
        ->once()
        ->andReturn(true);

    $result = $this->service->handle($this->userId);

    expect($result[0]->status)->toBe(Order::STATUS_PENDING);
    expect($result[0]->priority)->toBe(Order::PRIORITY_HIGH);
});

// Test Case 7: Type B with API success, does not meet conditions
test('Type B with API success, does not meet conditions sets status to error', function () {
    $order = createOrder($this->faker, 'B', 150, false);
    $apiResponse = new APIResponse('success', createOrder($this->faker, 'X', 60, false));

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with($this->userId)
        ->once()
        ->andReturn([$order]);

    $this->apiClient->shouldReceive('callAPI')
        ->with($order->id)
        ->once()
        ->andReturn($apiResponse);

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, Order::STATUS_ERROR, Order::PRIORITY_LOW)
        ->once()
        ->andReturn(true);

    $result = $this->service->handle($this->userId);

    expect($result[0]->status)->toBe(Order::STATUS_ERROR);
    expect($result[0]->priority)->toBe(Order::PRIORITY_LOW);
});

// Test Case 8: Type B with API failure
test('Type B with API failure sets status to api_error', function () {
    $order = createOrder($this->faker, 'B', 50, true);

    // Pass a valid Order object with default values for the APIResponse
    $responseOrder = createOrder($this->faker, 'X', 0, false);
    $apiResponse = new APIResponse('failure', $responseOrder);

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with($this->userId)
        ->once()
        ->andReturn([$order]);

    $this->apiClient->shouldReceive('callAPI')
        ->with($order->id)
        ->once()
        ->andReturn($apiResponse);

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, Order::STATUS_API_ERROR, Order::PRIORITY_LOW)
        ->once()
        ->andReturn(true);

    $result = $this->service->handle($this->userId);

    // Ensure the status is updated to 'api_error'
    expect($result[0]->status)->toBe(Order::STATUS_API_ERROR);
    expect($result[0]->priority)->toBe(Order::PRIORITY_LOW);
});

// Test Case 9: Type B with APIException
test('Type B with APIException sets status to api_failure', function () {
    $order = createOrder($this->faker, 'B', 300, false);

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with($this->userId)
        ->once()
        ->andReturn([$order]);

    $this->apiClient->shouldReceive('callAPI')
        ->with($order->id)
        ->once()
        ->andThrow(new APIException('API Exception'));

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, Order::STATUS_API_FAILURE, Order::PRIORITY_HIGH)
        ->once()
        ->andReturn(true);

    $result = $this->service->handle($this->userId);

    expect($result[0]->status)->toBe(Order::STATUS_API_FAILURE);
    expect($result[0]->priority)->toBe(Order::PRIORITY_HIGH);
});

// Test Case 10: Type C with flag = true
test('Type C with flag = true sets status to completed', function () {
    $order = createOrder($this->faker, 'C', 100, true);

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with($this->userId)
        ->once()
        ->andReturn([$order]);

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, Order::STATUS_COMPLETED, Order::PRIORITY_LOW)
        ->once()
        ->andReturn(true);

    $result = $this->service->handle($this->userId);

    expect($result[0]->status)->toBe(Order::STATUS_COMPLETED);
    expect($result[0]->priority)->toBe(Order::PRIORITY_LOW);
});

// Test Case 11: Type C with flag = false
test('Type C with flag = false sets status to in_progress', function () {
    $order = createOrder($this->faker, 'C', 250, false);

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with($this->userId)
        ->once()
        ->andReturn([$order]);

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, Order::STATUS_IN_PROGRESS, Order::PRIORITY_HIGH)
        ->once()
        ->andReturn(true);

    $result = $this->service->handle($this->userId);

    expect($result[0]->status)->toBe(Order::STATUS_IN_PROGRESS);
    expect($result[0]->priority)->toBe(Order::PRIORITY_HIGH);
});

// Test Case 12: Unknown order type
test('Unknown order type sets status to unknown_type', function () {
    $order = createOrder($this->faker, 'D', 50, true);

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with($this->userId)
        ->once()
        ->andReturn([$order]);

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, Order::STATUS_UNKNOWN_TYPE, Order::PRIORITY_LOW)
        ->once()
        ->andReturn(true);

    $result = $this->service->handle($this->userId);

    expect($result[0]->status)->toBe(Order::STATUS_UNKNOWN_TYPE);
    expect($result[0]->priority)->toBe(Order::PRIORITY_LOW);
});

// Test Case 13: No orders for user
test('No orders for user returns empty array', function () {
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with($this->userId)
        ->once()
        ->andReturn([]);

    $result = $this->service->handle($this->userId);

    expect($result)->toBe([]);
});

// Test Case 14: DatabaseService throws exception
test('DatabaseService throws exception returns false', function () {
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with($this->userId)
        ->once()
        ->andThrow(new DatabaseException('Database error'));

    $result = $this->service->handle($this->userId);

    expect($result)->toBeFalse();
});

// Test Case 15: Database update fails
test('Database update fails sets status to db_error', function () {
    $order = createOrder($this->faker, 'A', 100, true);

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with($this->userId)
        ->once()
        ->andReturn([$order]);

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, Order::STATUS_EXPORTED, Order::PRIORITY_LOW)
        ->once()
        ->andThrow(new DatabaseException('Database update error'));

    $result = $this->service->handle($this->userId);

    expect($result[0]->status)->toBe(Order::STATUS_DB_ERROR);
});

// Test Case 16: Boundary test for amount = 200 and 201
test('Boundary test for amount = 200 and 201', function () {
    $order1 = createOrder($this->faker, 'C', 200, true);
    $order2 = createOrder($this->faker, 'C', 201, true);

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with($this->userId)
        ->once()
        ->andReturn([$order1, $order2]);

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order1->id, Order::STATUS_COMPLETED, Order::PRIORITY_LOW)
        ->once()
        ->andReturn(true);

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order2->id, Order::STATUS_COMPLETED, Order::PRIORITY_HIGH)
        ->once()
        ->andReturn(true);

    $result = $this->service->handle($this->userId);

    expect($result[0]->status)->toBe(Order::STATUS_COMPLETED);
    expect($result[0]->priority)->toBe(Order::PRIORITY_LOW);
    expect($result[1]->status)->toBe(Order::STATUS_COMPLETED);
    expect($result[1]->priority)->toBe(Order::PRIORITY_HIGH);
});

// Test Case 17: Boundary test for amount = 0 and 1
test('Boundary test for amount = 0 and 1', function () {
    $order1 = createOrder($this->faker, 'C', 0, true);
    $order2 = createOrder($this->faker, 'C', 1, true);

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with($this->userId)
        ->once()
        ->andReturn([$order1, $order2]);

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order1->id, Order::STATUS_COMPLETED, Order::PRIORITY_LOW)
        ->once()
        ->andReturn(true);

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order2->id, Order::STATUS_COMPLETED, Order::PRIORITY_LOW)
        ->once()
        ->andReturn(true);

    $result = $this->service->handle($this->userId);

    expect($result[0]->status)->toBe(Order::STATUS_COMPLETED);
    expect($result[0]->priority)->toBe(Order::PRIORITY_LOW);
    expect($result[1]->status)->toBe(Order::STATUS_COMPLETED);
    expect($result[1]->priority)->toBe(Order::PRIORITY_LOW);
});

// Test Case 18: Negative amount
test('Negative amount sets status to exported', function () {
    $order = createOrder($this->faker, 'A', -50, true);

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with($this->userId)
        ->once()
        ->andReturn([$order]);

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, Order::STATUS_EXPORTED, Order::PRIORITY_LOW)
        ->once()
        ->andReturn(true);

    $result = $this->service->handle($this->userId);

    expect($result[0]->status)->toBe(Order::STATUS_EXPORTED);
    expect($result[0]->priority)->toBe(Order::PRIORITY_LOW);
});

// Test Case 19: Null flag
test('Null flag sets status to in_progress', function () {
    $order = createOrder($this->faker, 'C', 100, null);

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with($this->userId)
        ->once()
        ->andReturn([$order]);

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, Order::STATUS_IN_PROGRESS, Order::PRIORITY_LOW)
        ->once()
        ->andReturn(true);

    $result = $this->service->handle($this->userId);

    expect($result[0]->status)->toBe(Order::STATUS_IN_PROGRESS);
    expect($result[0]->priority)->toBe(Order::PRIORITY_LOW);
});

// Test Case 20: Empty or null type
test('Empty or null type sets status to unknown_type', function () {
    $order = createOrder($this->faker, '', 100, true);

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with($this->userId)
        ->once()
        ->andReturn([$order]);

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, Order::STATUS_UNKNOWN_TYPE, Order::PRIORITY_LOW)
        ->once()
        ->andReturn(true);

    $result = $this->service->handle($this->userId);

    expect($result[0]->status)->toBe(Order::STATUS_UNKNOWN_TYPE);
    expect($result[0]->priority)->toBe(Order::PRIORITY_LOW);
});
