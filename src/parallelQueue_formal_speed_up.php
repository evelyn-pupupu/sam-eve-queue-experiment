<?php
// Load configuration files
$configDir = __DIR__ . '/../config/';
$customerImgDir = __DIR__ . '/../public/customer/';

// Load the generated random sequences
$arrival_times = json_decode(file_get_contents($configDir . 'arrival_times_speed_up.json'), true);
$service_times = json_decode(file_get_contents($configDir . 'service_times_speed_up.json'), true);
// $prices = json_decode(file_get_contents($configDir . 'prices.json'), true);
$customer_items = json_decode(file_get_contents($configDir . 'formal_customer_items_parallel.json'), true);

// Get all customer images from the directory
$customer_images = array_diff(scandir($customerImgDir), array('.', '..'));

// Initialize each cashier with 4 customers
$num_cashiers = 4;
$initial_customers_per_cashier = 4;
$cashiers = [];

for ($i = 0; $i < $num_cashiers; $i++) {
    $cashiers[$i] = [];
    for ($j = 0; $j < $initial_customers_per_cashier; $j++) {
        $cashiers[$i][] = $customer_images[array_rand($customer_images)];
    }
}

// Example: Start with the first customer for your cashier (cashier 2)
$current_customer_items = $customer_items[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parallel Queue Simulation</title>
    <style>
        /* Basic layout styling */
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; display: flex; flex-direction: column; height: 100vh; }
        .main-container { flex-grow: 1; display: flex; }
        .left, .right { width: 50%; box-sizing: border-box; padding: 20px; }
        .cashier-area { display: flex; flex-direction: column; gap: 20px; align-items: flex-end; }
        .cashier {
            display: flex; 
            align-items: center; 
            justify-content: flex-end; 
            width: 100%; 
            padding: 10px;
        }
        .cashier:not(#cashier-1) {
            background-color: rgba(200, 200, 200, 0.5); /* 半透明的灰色背景 */
        }
        #cashier-1 {
            border-top: 3px solid rgba(200, 200, 200, 0.5);    /* 上边框，2像素，灰色 */
            border-bottom: 3px solid rgba(200, 200, 200, 0.5); /* 下边框，2像素，灰色 */
        }
        .queue { display: flex; flex-direction: row-reverse; align-items: center; gap: 10px; padding: 5px 0; border-radius: 5px; width: 100%; }
        .queue img { width: 40px; height: 40px; border-radius: 50%; }
        .queue img:first-child {margin-left: 20px;border: 2px solid grey;}
        #cashier-0 .queue img,
        #cashier-2 .queue img,
        #cashier-3 .queue img {
            filter: grayscale(100%);
        }
        .cashier-name { margin-left: 20px; font-size: 18px; width: 120px; text-align: right; }
        .items-container { display: flex; align-items: center; }
        .item-names { width: 30%; text-align: left; }
        .item-names div { margin-bottom: 10px; }
        .sliders { width: 60%; }
        .sliders div { margin-bottom: 10px; }
        .slider-values { width: 10%; text-align: left; }
        .slider-values div { margin-bottom: 10px; min-width: 30px; }
        input[type="range"] { width: 90%; } /* Ensure sliders occupy 90% of their container's width */
        .submit { text-align: center; margin-top: 20px; }
        .submit button { padding: 10px 20px; }
        .timer-container { display: flex; justify-content: space-between; padding: 10px 20px; border-top: 1px solid #ccc; font-size: 18px; }
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
        #waiting-message {
            font-size: 18px;
            color: gray;
            margin-top: 20px;
        }   
    </style>
</head>
<body>
    <div class="main-container">
        <div class="left">
            <div class="cashier-area" id="cashier-area">
                <?php for ($i = 0; $i < $num_cashiers; $i++): ?>
                    <div class="cashier" id="cashier-<?php echo $i; ?>">
                        <div class="queue">
                            <?php foreach ($cashiers[$i] as $customer_img): ?>
                                <img src="../public/customer/<?php echo $customer_img; ?>" alt="Customer">
                            <?php endforeach; ?>
                        </div>
                        <div class="cashier-name">Cashier <?php echo $i + 1; ?><?php echo $i == 1 ? ' (You)' : ''; ?></div>
                    </div>
                <?php endfor; ?>
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
            <div class="items-container">
                <!-- Existing cart content -->
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

        // Load arrival times from PHP
        const arrivalTimes = <?php echo json_encode($arrival_times); ?>;
        let currentTimeIndex = 0;
        let lastArrivalTime = 0;
        let elapsedTime = 0;

        // Function to find the cashier with the shortest queue
        function findShortestQueue() {
            let minQueueLength = Infinity;
            let selectedCashier = null;

            document.querySelectorAll('.cashier').forEach((cashier, index) => {
                const queueLength = cashier.querySelectorAll('.queue img').length;
                if (queueLength < minQueueLength) {
                    minQueueLength = queueLength;
                    selectedCashier = cashier;
                }
            });

            return selectedCashier;
        }

        // Function to toggle the visibility of the cart
        function toggleCartVisibility() {
            const cashierQueue = document.querySelector('#cashier-1 .queue');
            const cartContainer = document.querySelector('.items-container');
            const submitContainer = document.querySelector('.submit');
            const waitingMessage = document.getElementById('waiting-message');
            
            // Check if the queue has customers
            if (cashierQueue && cashierQueue.children.length > 0) {
                cartContainer.style.display = 'block'; // Show cart
                submitContainer.style.display = 'block'; // Show submit button and hint
                waitingMessage.style.display = 'none'; // Hide waiting message
            } else {
                cartContainer.style.display = 'none'; // Hide cart
                submitContainer.style.display = 'none'; // Hide submit button and hint
                waitingMessage.style.display = 'block'; // Show waiting message
            }
        }

        window.addEventListener('DOMContentLoaded', () => {
            toggleCartVisibility(); // Ensure correct cart visibility on page load
        });

        // Function to add a new customer to the shortest queue
        function addCustomerToQueue() {
        const selectedCashier = findShortestQueue();
        if (selectedCashier) {
            const customerImg = document.createElement('img');

            // Move the random image selection logic to JavaScript
            const customerImages = <?php echo json_encode(array_values($customer_images)); ?>;
            const randomIndex = Math.floor(Math.random() * customerImages.length);
            const randomImage = customerImages[randomIndex];

            customerImg.src = '../public/customer/' + randomImage;
            customerImg.alt = 'Customer';
            customerImg.style.width = '40px';
            customerImg.style.height = '40px';
            customerImg.style.borderRadius = '50%';

            selectedCashier.querySelector('.queue').appendChild(customerImg);

            // Check cart visibility after a new customer arrives
            toggleCartVisibility();
        }
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

    let lastCustomerStartTime = Date.now(); // Track the start time of the current customer's service

    // Function to update the service start time
    function updateServiceStartTime() {
        const cashierQueue = document.querySelector('#cashier-1 .queue');

        if (cashierQueue && cashierQueue.children.length > 0) {
            // If there's a customer in the queue, update the start time
            lastCustomerStartTime = Date.now();
            console.log('Service started for the next customer at:', new Date(lastCustomerStartTime).toLocaleString());
        } else {
            // If the queue is empty, wait for a new customer
            console.log('Waiting for the next customer to arrive...');
        }
    }

    // Function to handle cart submission and customer departure for the participant's queue (Cashier 2)
    // Attach an event listener to the submit button
    document.getElementById('submit-cart').addEventListener('click', function() {
        if (lastCustomerStartTime === null) {
            console.error('Service start time is not set. Please check the logic.');
            return;
        }

        // Calculate the service time for the current customer
        const submitTime = Date.now(); // Current timestamp
        const serviceTime = (submitTime - lastCustomerStartTime) / 1000; // Calculate service time in seconds

        // Store the service time in the `submitTimes` array
        submitTimes.push(serviceTime);
        console.log('Service time for the current customer (seconds):', serviceTime);
        // Print the full `submitTimes` array for debugging
        console.log('Current submitTimes array:', submitTimes);

        // Remove the first customer from the queue
        const cashierQueue = document.querySelector('#cashier-1 .queue');
        if (cashierQueue && cashierQueue.children.length > 0) {
            cashierQueue.removeChild(cashierQueue.children[0]); // Remove the first customer
        }

        toggleCartVisibility(); // if no customer, then make cart invisible

        // Check if there are more customers in the queue
        if (cashierQueue.children.length > 0) {
            updateServiceStartTime(); // Update the start time for the next customer
            loadNextCustomer(); // Load the next customer's items
        } else {
            // alert("The queue is now empty. Please wait for the next customer.");

            // Use a MutationObserver to wait for a new customer
            const observer = new MutationObserver(() => {
                if (cashierQueue.children.length > 0) {
                    observer.disconnect(); // Stop observing once a new customer arrives
                    updateServiceStartTime(); // Update the service start time
                    loadNextCustomer(); // Load the next customer's items
                }
            });
            observer.observe(cashierQueue, { childList: true });
        }
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

    // Start simulating cashiers (cashier 0, 1, 2 will share the service times)
    simulateCashiers();

    // Function to simulate other cashiers based on shared service times
    function simulateCashiers() {
        console.log("Simulating cashiers...");

        [0, 2, 3].forEach(cashierIndex => {
            processQueue(cashierIndex);
        });
    }

    // Function to process a queue
    function processQueue(cashierIndex) {
        const cashierQueue = document.querySelector(`#cashier-${cashierIndex} .queue`);
        if (!cashierQueue) {
            console.warn(`Cashier queue not found for cashier ${cashierIndex}`);
            return;
        }

        let isProcessing = false; // State variable to track if the queue is being processed

        // Define the function to process the next customer
        function processNextCustomer() {
            if (cashierQueue.children.length > 0) {
                isProcessing = true; // Mark as processing
                const serviceTime = serviceTimes[serviceTimeIndex % serviceTimes.length] * 1000; // Get service time
                serviceTimeIndex++;

                if (isNaN(serviceTime)) {
                    console.error(`Invalid service time for cashier ${cashierIndex+1}`);
                    isProcessing = false; // Reset processing state on error
                    return;
                }

                // Process the first customer after the service time
                setTimeout(() => {
                    if (cashierQueue.children.length > 0) {
                        cashierQueue.removeChild(cashierQueue.children[0]); // Remove the customer
                        processNextCustomer(); // Process the next customer
                    } else {
                        isProcessing = false; // Reset processing state when queue is empty
                        console.log(`Queue for cashier ${cashierIndex+1} is empty.`);
                    }
                }, serviceTime);
            } else {
                isProcessing = false; // Reset processing state when queue is empty
                console.log(`Queue for cashier ${cashierIndex+1} is empty.`);
            }
        }

        // Monitor the queue for changes (e.g., new customers)
        const observer = new MutationObserver(() => {
            if (!isProcessing && cashierQueue.children.length > 0) {
                console.log(`New customer arrived in cashier ${cashierIndex+1}'s queue. Resuming service.`);
                processNextCustomer(); // Resume processing when a new customer arrives
            }
        });

        // Observe the cashier's queue for child changes
        observer.observe(cashierQueue, { childList: true });

        // Start processing the first customer if the queue is not empty
        if (cashierQueue.children.length > 0) {
            processNextCustomer();
        }
    }


    let submitTimes = [];  // Array to store the submit times

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
    </script>
</body>
</html>
