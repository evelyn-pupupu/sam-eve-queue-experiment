<?php
// Load configuration files
$configDir = __DIR__ . '/../config/';
$customerImgDir = __DIR__ . '/../public/customer/';

// Load the generated random sequences
$arrival_times = json_decode(file_get_contents($configDir . 'arrival_times_speed_up.json'), true);
$service_times = json_decode(file_get_contents($configDir . 'service_times_speed_up.json'), true);
// $prices = json_decode(file_get_contents($configDir . 'prices.json'), true);
$customer_items = json_decode(file_get_contents($configDir . 'formal_customer_items_pooled.json'), true);

// Get all customer images from the directory
$customer_images = array_values(array_diff(scandir($customerImgDir), array('.', '..')));

// Initialize the pooled queue with initial customers
$num_cashiers = 4;
$initial_customers = 15;
$pooled_queue = [];

for ($j = 0; $j < $initial_customers; $j++) {
    $pooled_queue[] = $customer_images[array_rand($customer_images)];
}

$cashier_queues = [];

for ($i = 0; $i < $num_cashiers; $i++) {
    if (count($pooled_queue) > 0) {
        // 从pooled_queue中取出一个客户给收银员
        $cashier_queues[$i][] = array_shift($pooled_queue);
    } else {
        // 如果pooled_queue为空，随机分配一个客户
        $cashier_queues[$i][] = $customer_images[array_rand($customer_images)];
    }
}

// Example: Start with the first customer for your cashier (cashier 2)
$current_customer_items = $customer_items[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pooled Queue Simulation</title>
    <style>
        /* Basic layout styling */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        .main-container {
            flex-grow: 1;
            display: flex;
        }
        .left, .right {
            width: 50%;
            box-sizing: border-box;
            padding: 20px;
        }

        /* New container to wrap the queue and cashier area */
        .queue-cashier-container {
            display: flex;
            justify-content: flex-start; /* Align items to the start horizontally */
            align-items: flex-start;     /* Align items to the start vertically */
            width: 100%;
        }

        /* Queue Wrapper */
        .queue-wrapper {
            flex-grow: 1;
            display: flex;
            justify-content: flex-end; /* Align the queue to the right */
            background-color: #f0f0f0; 
        }

        /* Pooled queue styling */
        .queue {
            display: grid;
            grid-template-columns: repeat(5, 40px);
            grid-auto-rows: 40px;
            gap: 10px;
            padding: 10px;
            background-color: #f0f0f0;
            /* Remove margin-left and width */
            /* width: max-content; */
            /* margin-left: auto; */
        }

        .queue img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        /* Position the first five images from right to left */
        .queue img:nth-child(1) {
            grid-column: 5;
            grid-row: 1;
        }
        .queue img:nth-child(2) {
            grid-column: 4;
            grid-row: 1;
        }
        .queue img:nth-child(3) {
            grid-column: 3;
            grid-row: 1;
        }
        .queue img:nth-child(4) {
            grid-column: 2;
            grid-row: 1;
        }
        .queue img:nth-child(5) {
            grid-column: 1;
            grid-row: 1;
        }

        /* Position the remaining images in the first column, stacking vertically */
        .queue img:nth-child(n+6) {
            grid-column: 1;
            grid-row: auto;
        }

        .cashier-area {
            /* Ensure the cashier area doesn't grow to fill space */
            flex-shrink: 0;
            margin-left: 20px; /* Add some space between queue and cashiers */
            display: flex;
            flex-direction: column;
            gap: 20px;
            align-items: flex-start; /* Align to the start to match left alignment */
        }
        .cashier {
            display: flex;
            align-items: center;
            /* justify-content: flex-end; */ /* No longer needed */
            width: 100%;
            height: 40px;
        }
        .cashier-name {
            margin-left: 20px;
            font-size: 18px;
            /* width: 120px; */ /* Adjust as needed */
            text-align: left;
        }
        .items-container {
            display: flex;
            align-items: center;
        }
        .item-names {
            width: 30%;
            text-align: left;
        }
        .item-names div {
            margin-bottom: 10px;
        }
        .sliders {
            width: 60%;
        }
        .sliders div {
            margin-bottom: 10px;
        }
        .slider-values {
            width: 10%;
            text-align: left;
        }
        .slider-values div {
            margin-bottom: 10px;
            min-width: 30px;
        }
        input[type="range"] {
            width: 90%;
        }
        .submit {
            text-align: center;
            margin-top: 20px;
        }
        .submit button {
            padding: 10px 20px;
        }
        .timer-container {
            display: flex;
            justify-content: space-between;
            padding: 10px 20px;
            border-top: 1px solid #ccc;
            font-size: 18px;
        }
        /* 让 items-container 以列的方式排列 */
        .items-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: 100%; /* 占据父容器的全部宽度 */
            box-sizing: border-box;
        }

        /* 为每个 item-row 添加浅色背景和边框 */
        .item-row {
            display: flex;
            align-items: center;
            justify-content: flex-start; /* 子元素从左向右排列 */
            background-color: #f9f9f9;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 100%; /* 占据父容器的全部宽度 */
            box-sizing: border-box; /* 包括内边距和边框在内 */
            padding: 15px 10px 25px 10px; /* 调整内边距，上 15px，右 10px，下 25px，左 10px */
        }

        /* 设置 item-name 的样式 */
        .item-name {
            flex: 0 0 30%; /* 不允许增长或缩小，固定占据 30% 的宽度 */
            text-align: left;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap; /* 防止文本换行 */
        }

        /* 设置 slider-container 的样式 */
        .slider-container {
            flex: 0 0 60%; /* 不允许增长或缩小，固定占据 60% 的宽度 */
        }

        /* 调整滑块的样式，使其更高 */
        .slider-container input[type="range"] {
            width: 100%;
            height: 25px; /* 增加滑块的高度 */
        }

        /* 设置 slider-value 的样式 */
        .slider-value {
            flex: 0 0 10%; /* 不允许增长或缩小，固定占据 10% 的宽度 */
            text-align: left;
            min-width: 30px;
            margin-left: 12px; /* 保持之前的水平间距 */
        }

        .item-name, .slider-container, .slider-value {
            text-align: left;
        }

        /* 可选：调整滑块的样式（颜色、圆角等） */
        .slider-container input[type="range"] {
            -webkit-appearance: none; /* 移除默认样式，方便自定义 */
            background: #ddd;
            border-radius: 5px;
        }

        .slider-container input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 25px; /* 与滑块高度一致 */
            background: #4CAF50; /* 滑块颜色 */
            cursor: pointer;
            border-radius: 5px;
        }

        .slider-container input[type="range"]::-moz-range-thumb {
            width: 20px;
            height: 25px;
            background: #4CAF50;
            cursor: pointer;
            border-radius: 5px;
        }
        .submit-hint {
            margin-top: 10px;
            font-size: 14px;
            color: #555;
            text-align: center;
        }
        .cashier-row {
            display: flex;
            align-items: center;
        }

        .cashier-queue {
            width: 40px;
            height: 40px;
            margin-right: 10px; /* 与收银员之间的间距 */
        }

        .cashier-queue img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        /* 对除参与者收银员外的其他收银员及其客户进行灰度处理 */
        .cashier-row:not(#cashier-row-1) .cashier-name,
        .cashier-row:not(#cashier-row-1) .cashier-queue img {
            filter: grayscale(100%);
        }

        /* 给正在服务的客户的图片添加黑色边框 */
        .cashier-queue img:first-child {
            border: 2px solid black;
        }

        /* 确保所有收银员行具有相同的宽度和对齐方式 */
        .cashier-row {
            display: flex;
            align-items: center;
            width: 100%; /* 让每个收银员行占据父容器的全部宽度 */
            box-sizing: border-box;
            position: relative; /* 为了定位伪元素 */
        }

        /* 对非参与者的收银员行添加灰色背景 */
        .cashier-row:not(#cashier-row-1)::before {
            content: '';
            position: absolute;
            top: -5px;      /* 向上扩展灰色背景 */
            left: 0;
            right: 0;
            bottom: -5px;   /* 向下扩展灰色背景 */
            background-color: rgba(200, 200, 200, 0.5); /* 半透明的灰色背景 */
            z-index: -1;     /* 确保背景在内容后面 */
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="left">
            <!-- New container to wrap the queue and cashier area -->
            <div class="queue-cashier-container">
                <!-- Queue Wrapper -->
                <div class="queue-wrapper">
                    <div class="queue" id="pooled-queue">
                        <?php foreach ($pooled_queue as $customer_img): ?>
                            <img src="../public/customer/<?php echo $customer_img; ?>" alt="Customer">
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Cashier Area -->
                <div class="cashier-area" id="cashier-area">
                    <?php for ($i = 0; $i < $num_cashiers; $i++): ?>
                        <div class="cashier-row" id="cashier-row-<?php echo $i; ?>">
                            <div class="cashier-queue" id="cashier-queue-<?php echo $i; ?>">
                                <!-- 在这里显示收银员队列中的客户 -->
                                <?php if (isset($cashier_queues[$i]) && count($cashier_queues[$i]) > 0): ?>
                                    <img src="../public/customer/<?php echo $cashier_queues[$i][0]; ?>" alt="Customer">
                                <?php endif; ?>
                            </div>
                            <div class="cashier" id="cashier-<?php echo $i; ?>">
                                <div class="cashier-name">Cashier <?php echo $i + 1; ?><?php echo $i == 1 ? ' (You)' : ''; ?></div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        <div class="right">
            <div class="items-container">
                <?php foreach ($current_customer_items as $item => $price): ?>
                        <div class="item-row">
                            <div class="item-name">
                                <?php echo $item; ?>: $<?php echo $price; ?>
                            </div>
                            <div class="slider-container">
                                <input type="range" id="item-<?php echo $item; ?>" min="0" max="6" step="0.1" value="0">
                            </div>
                            <div class="slider-value" id="value-<?php echo $item; ?>">$0</div>
                        </div>
                    <?php endforeach; ?>
            </div>
            <div class="submit">
                <button id="submit-cart" disabled>Submit Cart</button>
                <p class="submit-hint">Submit button becomes active after you set all prices correctly.</p>
            </div>
            <div id="waiting-message" style="display: none; text-align: center; font-size: 18px; color: gray;">
                Waiting for customers ...
            </div>
        </div>
    </div>
    <!-- <div class="timer-container">
        <div id="elapsed-time">Elapsed Time: 0 seconds</div>
        <div id="next-arrival">Next customer arrival in: </div>
    </div> -->

    <script>
        let currentCustomerIndex = 0; // Initialize the index for the first customer
        const customerItemsArray = <?php echo json_encode($customer_items); ?>; // Load all customer items once

        // Function to check if all sliders are correct and enable the submit button
        function checkSliders() {
            const currentCustomerItems = customerItemsArray[currentCustomerIndex];
            let allCorrect = true;
            document.querySelectorAll('.slider-container input[type="range"]').forEach(slider => {
                const itemKey = slider.id.split('-')[1];
                const expectedValue = currentCustomerItems[itemKey]; // Use JavaScript variable to check slider value
                if (parseFloat(slider.value) !== expectedValue) {
                    allCorrect = false;
                }
            });
            // Temporarily comment out the disabling logic
            document.getElementById('submit-cart').disabled = !allCorrect;

            // Keep the button always enabled for testing
            // document.getElementById('submit-cart').disabled = false;
        }

        // Attach event listeners to sliders for updating displayed values and checking if the submit button should be enabled
        document.querySelectorAll('.slider-container input[type="range"]').forEach(slider => {
            slider.addEventListener('input', function() {
                const itemKey = this.id.split('-')[1];
                document.getElementById('value-' + itemKey).textContent = '$' + this.value;
                checkSliders(); // Check if all sliders are correct after each input
            });
        });

        function toggleCartVisibility() {
            const cashierQueue = document.getElementById('cashier-queue-1'); // Queue for the participant's cashier
            const pooledQueue = document.getElementById('pooled-queue');    // Global pooled queue
            const cartContainer = document.querySelector('.items-container');
            const submitContainer = document.querySelector('.submit');
            const waitingMessage = document.getElementById('waiting-message');

            // Check if participant's cashier has no customer and pooled queue is empty
            const noCustomerInCashier = !cashierQueue || cashierQueue.children.length === 0;
            const noCustomerInPooledQueue = !pooledQueue || pooledQueue.children.length === 0;

            if (noCustomerInCashier && noCustomerInPooledQueue) {
                // Hide cart and submit button, show waiting message
                cartContainer.style.display = 'none';
                submitContainer.style.display = 'none';
                if (waitingMessage) waitingMessage.style.display = 'block';
            } else {
                // Show cart and submit button, hide waiting message
                cartContainer.style.display = 'block';
                submitContainer.style.display = 'block';
                if (waitingMessage) waitingMessage.style.display = 'none';
            }
        }

        window.addEventListener('DOMContentLoaded', () => {
            toggleCartVisibility();
        });


        // Load arrival times from PHP
        const arrivalTimes = <?php echo json_encode($arrival_times); ?>;
        let currentTimeIndex = 0;
        let lastArrivalTime = 0;
        let elapsedTime = 0;

        // Function to add a new customer to the pooled queue
        function addCustomerToQueue() {
            const pooledQueue = document.getElementById('pooled-queue');

            const customerImg = document.createElement('img');

            // Randomly select a customer image
            const customerImages = <?php echo json_encode(array_values($customer_images)); ?>;
            const randomIndex = Math.floor(Math.random() * customerImages.length);
            const randomImage = customerImages[randomIndex];

            customerImg.src = '../public/customer/' + randomImage;
            customerImg.alt = 'Customer';
            customerImg.style.width = '40px';
            customerImg.style.height = '40px';
            customerImg.style.borderRadius = '50%';

            pooledQueue.appendChild(customerImg);
        }

        // Timer function to handle customer arrival
        function startCustomerArrivalTimer() {
            if (currentTimeIndex < arrivalTimes.length) {
                const nextArrivalTime = arrivalTimes[currentTimeIndex];
                const timeUntilNextArrival = (nextArrivalTime - lastArrivalTime) * 1000; // Convert to milliseconds
                lastArrivalTime = nextArrivalTime;

                // Update the timer display
                // document.getElementById('next-arrival').textContent = 'Next customer arrival at: ' + nextArrivalTime.toFixed(2) + ' seconds';

                // Set a timeout for the next customer arrival
                setTimeout(() => {
                    addCustomerToQueue();
                    currentTimeIndex++;
                    startCustomerArrivalTimer(); // Start the timer for the next customer
                }, timeUntilNextArrival);
            } else {
                // document.getElementById('next-arrival').textContent = 'All customers have arrived.';
            }
        }

        // Function to start the elapsed time counter
        // function startElapsedTimeCounter() {
        //     setInterval(() => {
        //         elapsedTime++;
        //         document.getElementById('elapsed-time').textContent = 'Elapsed Time: ' + elapsedTime + ' seconds';
        //     }, 1000); // Update every second
        // }

        // Start the customer arrival timer
        startCustomerArrivalTimer();

        // Start the elapsed time counter
        // startElapsedTimeCounter();

        // Function to handle cart submission and customer departure for the participant's cashier (Cashier 1)
        document.getElementById('submit-cart').addEventListener('click', function() {
            // 移除收银员队列中的客户
            removeCustomerFromCashierQueue(1); // 1表示Cashier 2
            if (lastCustomerStartTime === null) {
                console.error('Service start time is not set.');
                return;
            }

            // Calculate service time for the current customer
            const submitTime = Date.now();
            const serviceTime = (submitTime - lastCustomerStartTime) / 1000; // Convert to seconds

            // Store the service time in the `submitTimes` array
            submitTimes.push(serviceTime);
            console.log(`Service time for the current customer (seconds): ${serviceTime}`);
            console.log('Current submitTimes array:', submitTimes);

            // 尝试将下一个客户移动到收银员的队列
            moveCustomerToCashierQueue(1);

            // 加载下一个客户的商品
            loadNextCustomer();
        });
        // Function to load the next customer's items into the item-names div and update sliders
        function loadNextCustomer() {
        // Update the index for the next customer
        currentCustomerIndex = (currentCustomerIndex + 1) % customerItemsArray.length;

        const nextCustomerItems = customerItemsArray[currentCustomerIndex]; // Get the items for the current customer

        // Clear the current items container
        const itemsContainer = document.querySelector('.items-container');
        itemsContainer.innerHTML = ''; // Clear the current content

        // Rebuild the items for the next customer
        Object.keys(nextCustomerItems).forEach(itemKey => {
            const itemValue = nextCustomerItems[itemKey];

            // Create the item row container
            const itemRow = document.createElement('div');
            itemRow.classList.add('item-row');

            // Create the item name div
            const itemNameDiv = document.createElement('div');
            itemNameDiv.classList.add('item-name');
            itemNameDiv.textContent = `${itemKey}: $${itemValue}`;

            // Create the slider container
            const sliderContainer = document.createElement('div');
            sliderContainer.classList.add('slider-container');

            // Create the slider input
            const sliderInput = document.createElement('input');
            sliderInput.type = 'range';
            sliderInput.id = 'item-' + itemKey;
            sliderInput.min = 0;
            sliderInput.max = 6;
            sliderInput.step = 0.1;
            sliderInput.value = 0;

            // Attach event listener to the slider
            sliderInput.addEventListener('input', function() {
                document.getElementById('value-' + itemKey).textContent = '$' + this.value;
                checkSliders(); // Check if all sliders are correct after each input
            });

            // Append the slider input to the slider container
            sliderContainer.appendChild(sliderInput);

            // Create the slider value div
            const sliderValueDiv = document.createElement('div');
            sliderValueDiv.classList.add('slider-value');
            sliderValueDiv.id = 'value-' + itemKey;
            sliderValueDiv.textContent = '0';

            // Append all elements to the item row
            itemRow.appendChild(itemNameDiv);
            itemRow.appendChild(sliderContainer);
            itemRow.appendChild(sliderValueDiv);

            // Append the item row to the items container
            itemsContainer.appendChild(itemRow);
        });
        checkSliders();
    }

        // Convert PHP array to JavaScript object for service times
        const serviceTimes = <?php echo json_encode($service_times); ?>;

        // Define a global index to track which service time we're using
        let serviceTimeIndex = 0;

        // Start simulating cashiers (excluding the participant's cashier)
        simulateCashiers();

        // Function to simulate other cashiers based on service times
        function simulateCashiers() {
            console.log("Simulating cashiers...");

            [0, 2, 3].forEach(cashierIndex => {
                processQueue(cashierIndex);
            });
        }

        // 修改模拟收银员的函数
        function processQueue(cashierIndex) {
            function processNextCustomer() {
                // 移除收银员队列中的客户
                removeCustomerFromCashierQueue(cashierIndex);

                // 尝试将下一个客户移动到收银员的队列
                moveCustomerToCashierQueue(cashierIndex);

                // 获取服务时间
                const serviceTime = serviceTimes[serviceTimeIndex % serviceTimes.length] * 1000;
                serviceTimeIndex++;

                // 服务完成后处理下一个客户
                setTimeout(() => {
                    processNextCustomer();
                }, serviceTime);
            }

            // 开始处理第一个客户
            processNextCustomer();
        }

        // Function to remove a customer from the pooled queue
        function removeCustomerFromQueue() {
            const pooledQueue = document.getElementById('pooled-queue');
            if (pooledQueue.children.length > 0) {
                pooledQueue.removeChild(pooledQueue.children[0]);
            }
            toggleCartVisibility(); // Update visibility
        }

        let lastCustomerStartTime = Date.now(); // Timestamp when the current customer starts being served
        let submitTimes = []; // Array to store service times for debugging and submission

        // Add a message listener to receive requests from the parent window
        window.addEventListener('message', function(event) {
            console.log('Iframe received a message:', event.data);

            if (event.data && event.data.type === 'REQUEST_SUBMIT_TIMES') {
                console.log('REQUEST_SUBMIT_TIMES received. Current submitTimes:', submitTimes);

                // Prepare the data to send
                var data = {
                    type: 'RESPONSE_SUBMIT_TIMES',
                    submitTimes: submitTimes
                };

                console.log('Sending RESPONSE_SUBMIT_TIMES message back to parent:', data);
                // Send the data back to the parent window via postMessage
                window.parent.postMessage(data, '*'); 
                console.log('Message posted to parent');
            }
        });
        // 定义函数来移除收银员队列中的客户
        function removeCustomerFromCashierQueue(cashierIndex) {
            const cashierQueue = document.getElementById('cashier-queue-' + cashierIndex);
            if (cashierQueue.children.length > 0) {
                cashierQueue.removeChild(cashierQueue.children[0]);
            }
            toggleCartVisibility(); // Update visibility
        }
        // 定义函数来将客户从pooled queue移动到收银员的队列
        function moveCustomerToCashierQueue(cashierIndex) {
            const pooledQueue = document.getElementById('pooled-queue');
            const cashierQueue = document.getElementById('cashier-queue-' + cashierIndex);

            if (cashierQueue.children.length > 0) {
                // 如果该收银员的队列中已经有客户了，就不需要轮询了
                return;
            }

            if (pooledQueue.children.length > 0) {
                const customer = pooledQueue.children[0];
                pooledQueue.removeChild(customer);
                cashierQueue.appendChild(customer);
                // Update the service start time for the participant's cashier
                if (cashierIndex === 1) { // Assuming cashier 1 is the participant
                    lastCustomerStartTime = Date.now();
                    console.log(`Service started for a new customer at: ${new Date(lastCustomerStartTime).toLocaleString()}`);
                }   
                toggleCartVisibility(); // Update visibility
            } else {
                // 如果pooled queue为空，1秒后重试
                setTimeout(() => {
                    moveCustomerToCashierQueue(cashierIndex);
                }, 1000);
            }
        }
    </script>
</body>
</html>


