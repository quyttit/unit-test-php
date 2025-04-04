# Test Cases cho OrderProcessingService

Danh sách các test case dưới đây được thiết kế để kiểm tra toàn diện `OrderProcessingService`, bao gồm các trường hợp chuyên sâu và các giá trị biên trên, biên dưới cho `amount` và `data`.

---

## Test Case 1: Xử lý đơn hàng Type A với amount > 200
- **Mô tả**: Kiểm tra đơn hàng Type A có amount > 200 được xuất file CSV với ghi chú "High value order" và cập nhật trạng thái.
- **Input**:
  - `userId = 1`
  - Đơn hàng: `Order(id=1, type='A', amount=201, flag=true)`
- **Expected Output**:
  - File CSV được tạo: `orders_type_A_1_{timestamp}.csv`
  - Nội dung file: dòng dữ liệu đơn hàng + dòng "High value order"
  - Database: `status='exported'`, `priority='high'` (vì amount > 201)

---

## Test Case 2: Xử lý đơn hàng Type A với amount <= 150
- **Mô tả**: Kiểm tra đơn hàng Type A có amount <= 150 được xuất file CSV mà không có ghi chú.
- **Input**:
  - `userId = 1`
  - Đơn hàng: `Order(id=2, type='A', amount=100, flag=false)`
- **Expected Output**:
  - File CSV được tạo: `orders_type_A_1_{timestamp}.csv`
  - Nội dung file: chỉ dòng dữ liệu đơn hàng
  - Database: `status='exported'`, `priority='low'` (vì amount <= 200)

---

## Test Case 3: Xử lý đơn hàng Type A khi không thể mở file CSV
- **Mô tả**: Kiểm tra trường hợp lỗi khi không thể tạo file CSV.
- **Input**:
  - `userId = 1`
  - Đơn hàng: `Order(id=3, type='A', amount=50, flag=true)`
  - Giả lập: quyền ghi file bị từ chối
- **Expected Output**:
  - Không có file CSV
  - Database: `status='export_failed'`, `priority='low'` (vì amount <= 200)

---

## Test Case 4: Xử lý đơn hàng Type B với API success, data >= 50 và amount < 100
- **Mô tả**: Kiểm tra trường hợp Type B thỏa mãn điều kiện processed.
- **Input**:
  - `userId = 1`
  - Đơn hàng: `Order(id=4, type='B', amount=90, flag=false)`
  - API: `APIResponse(status='success', data=60)`
- **Expected Output**:
  - Database: `status='processed'`, `priority='low'` (vì amount <= 200)

---

## Test Case 5: Xử lý đơn hàng Type B với API success, data < 50
- **Mô tả**: Kiểm tra trường hợp Type B có data dưới ngưỡng.
- **Input**:
  - `userId = 1`
  - Đơn hàng: `Order(id=5, type='B', amount=150, flag=false)`
  - API: `APIResponse(status='success', data=40)`
- **Expected Output**:
  - Database: `status='pending'`, `priority='low'` (vì amount <= 200)

---

## Test Case 6: Xử lý đơn hàng Type B với API success, flag = true
- **Mô tả**: Kiểm tra trường hợp flag = true bất kể data.
- **Input**:
  - `userId = 1`
  - Đơn hàng: `Order(id=6, type='B', amount=250, flag=true)`
  - API: `APIResponse(status='success', data=100)`
- **Expected Output**:
  - Database: `status='pending'`, `priority='high'` (vì amount > 200)

---

## Test Case 7: Xử lý đơn hàng Type B với API success, không thỏa mãn điều kiện
- **Mô tả**: Kiểm tra trường hợp không thuộc processed hoặc pending.
- **Input**:
  - `userId = 1`
  - Đơn hàng: `Order(id=7, type='B', amount=150, flag=false)`
  - API: `APIResponse(status='success', data=60)`
- **Expected Output**:
  - Database: `status='error'`, `priority='low'` (vì amount <= 200)

---

## Test Case 8: Xử lý đơn hàng Type B với API failure
- **Mô tả**: Kiểm tra trường hợp API trả về lỗi.
- **Input**:
  - `userId = 1`
  - Đơn hàng: `Order(id=8, type='B', amount=50, flag=true)`
  - API: `APIResponse(status='failure', data=null)`
- **Expected Output**:
  - Database: `status='api_error'`, `priority='low'` (vì amount <= 200)

---

## Test Case 9: Xử lý đơn hàng Type B với APIException
- **Mô tả**: Kiểm tra trường hợp ngoại lệ khi gọi API.
- **Input**:
  - `userId = 1`
  - Đơn hàng: `Order(id=9, type='B', amount=300, flag=false)`
  - Giả lập: API ném `APIException`
- **Expected Output**:
  - Database: `status='api_failure'`, `priority='high'` (vì amount > 200)

---

## Test Case 10: Xử lý đơn hàng Type C với flag = true
- **Mô tả**: Kiểm tra Type C hoàn thành khi flag = true.
- **Input**:
  - `userId = 1`
  - Đơn hàng: `Order(id=10, type='C', amount=100, flag=true)`
- **Expected Output**:
  - Database: `status='completed'`, `priority='low'` (vì amount <= 200)

---

## Test Case 11: Xử lý đơn hàng Type C với flag = false
- **Mô tả**: Kiểm tra Type C đang xử lý khi flag = false.
- **Input**:
  - `userId = 1`
  - Đơn hàng: `Order(id=11, type='C', amount=250, flag=false)`
- **Expected Output**:
  - Database: `status='in_progress'`, `priority='high'` (vì amount > 200)

---

## Test Case 12: Xử lý đơn hàng với type không xác định
- **Mô tả**: Kiểm tra type không hợp lệ.
- **Input**:
  - `userId = 1`
  - Đơn hàng: `Order(id=12, type='D', amount=50, flag=true)`
- **Expected Output**:
  - Database: `status='unknown_type'`, `priority='low'` (vì amount <= 200)

---

## Test Case 13: Xử lý khi không có đơn hàng
- **Mô tả**: Kiểm tra khi không có dữ liệu cho userId.
- **Input**:
  - `userId = 2`
  - Database trả về mảng rỗng
- **Expected Output**:
  - Trả về mảng rỗng, không thay đổi database, không tạo file CSV

---

## Test Case 14: Xử lý khi DatabaseService ném Exception
- **Mô tả**: Kiểm tra lỗi khi lấy dữ liệu từ database.
- **Input**:
  - `userId = 3`
  - Giả lập: `DatabaseException` khi gọi `getOrdersByUser`
- **Expected Output**:
  - Trả về `false`, không tạo file CSV, không cập nhật database

---

## Test Case 15: Xử lý khi cập nhật database thất bại
- **Mô tả**: Kiểm tra lỗi khi cập nhật trạng thái.
- **Input**:
  - `userId = 1`
  - Đơn hàng: `Order(id=13, type='A', amount=100, flag=true)`
  - Giả lập: `DatabaseException` khi gọi `updateOrderStatus`
- **Expected Output**:
  - File CSV được tạo, `status='db_error'` trong object (không lưu database)

---

## Test Case 16: Kiểm tra biên trên cho amount
- **Mô tả**: Kiểm tra ngưỡng priority tại amount = 200 và 201.
- **Input**:
  - Đơn hàng 1: `Order(id=14, type='C', amount=200, flag=true)`
  - Đơn hàng 2: `Order(id=15, type='C', amount=201, flag=true)`
- **Expected Output**:
  - Đơn hàng 1: `status='completed'`, `priority='low'`
  - Đơn hàng 2: `status='completed'`, `priority='high'`

---

## Test Case 17: Kiểm tra biên dưới cho amount
- **Mô tả**: Kiểm tra ngưỡng dưới của amount.
- **Input**:
  - Đơn hàng 1: `Order(id=16, type='C', amount=0, flag=true)`
  - Đơn hàng 2: `Order(id=17, type='C', amount=1, flag=true)`
- **Expected Output**:
  - Cả hai: `status='completed'`, `priority='low'`

---

## Test Case 18: Kiểm tra với amount âm
- **Mô tả**: Kiểm tra xử lý amount âm (nếu hệ thống cho phép).
- **Input**:
  - `userId = 1`
  - Đơn hàng: `Order(id=18, type='A', amount=-50, flag=true)`
- **Expected Output**:
  - File CSV chứa amount âm, `status='exported'`, `priority='low'`

---

## Test Case 19: Kiểm tra với flag = null
- **Mô tả**: Kiểm tra xử lý flag = null (nếu hệ thống cho phép).
- **Input**:
  - `userId = 1`
  - Đơn hàng: `Order(id=19, type='C', amount=100, flag=null)`
- **Expected Output**:
  - Database: `status='in_progress'`, `priority='low'`

---

## Test Case 20: Kiểm tra với type rỗng hoặc null
- **Mô tả**: Kiểm tra type không hợp lệ (rỗng hoặc null).
- **Input**:
  - `userId = 1`
  - Đơn hàng: `Order(id=20, type='', amount=100, flag=true)`
- **Expected Output**:
  - Database: `status='unknown_type'`, `priority='low'`
