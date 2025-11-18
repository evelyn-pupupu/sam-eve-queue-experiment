<?php
// Load configuration files
$configDir = __DIR__ . '/../config/';
$customerImgDir = __DIR__ . '/../public/customer/';

// Load the generated random sequences
$arrival_times = json_decode(file_get_contents($configDir . 'arrival_times.json'), true);
$service_times = json_decode(file_get_contents($configDir . 'service_times.json'), true);
$prices = json_decode(file_get_contents($configDir . 'prices.json'), true);
$customer_items = json_decode(file_get_contents($configDir . 'customer_items.json'), true);

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
        .cashier { display: flex; align-items: center; justify-content: flex-end; width: 100%; }
        .queue { display: flex; flex-direction: row-reverse; align-items: center; gap: 10px; background-color: #f0f0f0; padding: 5px 0; border-radius: 5px; width: 100%; }
        .queue img { width: 40px; height: 40px; border-radius: 50%; }
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
            <h3>Cart</h3>
            <div class="items-container">
                <div class="item-names">
                    <?php foreach ($current_customer_items as $item => $price): ?>
                        <div><?php echo $item; ?>: $<?php echo $price; ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="sliders">
                    <?php foreach ($current_customer_items as $item => $price): ?>
                        <div>
                            <input type="range" id="item-<?php echo $item; ?>" min="0" max="10" step="0.1" value="0">
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="slider-values">
                    <?php foreach ($current_customer_items as $item => $price): ?>
                        <div id="value-<?php echo $item; ?>">0</div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="submit">
                <button id="submit-cart" disabled>Submit Cart</button>
            </div>
        </div>
    </div>
    <div class="timer-container">
        <div id="elapsed-time">Elapsed Time: 0 seconds</div>
        <div id="next-arrival">Next customer arrival in: </div>
    </div>

    <script>
        let currentCustomerIndex = 0; // Initialize the index for the first customer
        const customerItemsArray = <?php echo json_encode($customer_items); ?>; // Load all customer items once

        // Function to check if all sliders are correct and enable the submit button
        function checkSliders() {
            const currentCustomerItems = customerItemsArray[currentCustomerIndex];
            let allCorrect = true;
            document.querySelectorAll('.sliders input[type="range"]').forEach(slider => {
                const itemKey = slider.id.split('-')[1];
                const expectedValue = currentCustomerItems[itemKey]; // Use JavaScript variable to check slider value
                if (parseFloat(slider.value) !== expectedValue) {
                    allCorrect = false;
                }
            });
            document.getElementById('submit-cart').disabled = !allCorrect;
        }

        // Attach event listeners to sliders for updating displayed values and checking if the submit button should be enabled
        document.querySelectorAll('.sliders input[type="range"]').forEach(slider => {
            slider.addEventListener('input', function() {
                const itemKey = this.id.split('-')[1];
                document.getElementById('value-' + itemKey).textContent = this.value;
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
        }
    }

    // Timer function to handle customer arrival
    function startCustomerArrivalTimer() {
        if (currentTimeIndex < arrivalTimes.length) {
            const nextArrivalTime = arrivalTimes[currentTimeIndex];
            const timeUntilNextArrival = (nextArrivalTime - lastArrivalTime) * 1000; // Convert to milliseconds
            lastArrivalTime = nextArrivalTime;

            // Update the timer display
            document.getElementById('next-arrival').textContent = 'Next customer arrival at: ' + nextArrivalTime.toFixed(2) + ' seconds';

            // Set a timeout for the next customer arrival
            setTimeout(() => {
                addCustomerToQueue();
                currentTimeIndex++;
                startCustomerArrivalTimer(); // Start the timer for the next customer
            }, timeUntilNextArrival);
        } else {
            document.getElementById('next-arrival').textContent = 'All customers have arrived.';
        }
    }

    // Function to start the elapsed time counter
    function startElapsedTimeCounter() {
        setInterval(() => {
            elapsedTime++;
            document.getElementById('elapsed-time').textContent = 'Elapsed Time: ' + elapsedTime + ' seconds';
        }, 1000); // Update every second
    }

    // Start the customer arrival timer
    startCustomerArrivalTimer();

    // Start the elapsed time counter
    startElapsedTimeCounter();

    // Function to handle cart submission and customer departure for the participant's queue (Cashier 2)
    document.getElementById('submit-cart').addEventListener('click', function() {
        const cashierQueue = document.querySelector('#cashier-1 .queue'); // subject is Cashier 1 in HTML (in Cashier 0,1,2,3)
        if (cashierQueue && cashierQueue.children.length > 0) {
            cashierQueue.removeChild(cashierQueue.children[0]); // Remove the first customer in the queue
        }

        // Check if there are more customers in the queue
        if (cashierQueue.children.length > 0) {
            loadNextCustomer(); // Load the next customer's items into the sliders
        } else {
            alert("The queue is now empty. Please wait for the next customer.");
        }
    });

    // Function to load the next customer's items into the item-names div and update sliders
    function loadNextCustomer() {
        // Update the index for the next customer
        currentCustomerIndex = (currentCustomerIndex + 1) % customerItemsArray.length;

        const nextCustomerItems = customerItemsArray[currentCustomerIndex]; // Get the items for the current customer

        // Clear the current item names
        const itemNamesDiv = document.querySelector('.item-names');
        itemNamesDiv.innerHTML = ''; // Clear the current content in item-names

        let i = 0;
        Object.keys(nextCustomerItems).forEach(itemKey => {
            const itemValue = nextCustomerItems[itemKey];
            
            // Update the item names div with the next customer's items
            const itemNameElement = document.createElement('div');
            itemNameElement.textContent = `${itemKey}: $${itemValue}`;
            itemNamesDiv.appendChild(itemNameElement);

            // Update existing sliders
            const slider = document.querySelectorAll('.sliders input[type="range"]')[i];
            slider.id = 'item-' + itemKey; // Update the slider id
            slider.min = 0;
            slider.max = 10;
            slider.value = 0;

            // Update the corresponding value div
            const valueDiv = document.querySelectorAll('.slider-values div')[i];
            valueDiv.id = 'value-' + itemKey; // Update the id to match the new item
            valueDiv.textContent = slider.value; // Update the value display div

            i++;
        });
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

    // Function to process the queue of a cashier
    function processQueue(cashierIndex) {
        const cashierQueue = document.querySelector(`#cashier-${cashierIndex} .queue`);
        if (!cashierQueue) {
            console.warn(`Cashier queue not found for cashier ${cashierIndex}`);
            return;
        }

        // Output the length of the queue for debugging
        console.log(`Initial queue length for cashier ${cashierIndex}:`, cashierQueue.children.length);

        function processNextCustomer() {
            if (cashierQueue.children.length > 0) {
                // Get the next service time from the shared serviceTimes array
                const serviceTime = serviceTimes[serviceTimeIndex % serviceTimes.length] * 1000; // Ensure index wraps around

                // Increment the global serviceTimeIndex for the next use
                serviceTimeIndex++;

                // Check if the service time is valid and not NaN
                if (isNaN(serviceTime)) {
                    console.error(`Service time for cashier ${cashierIndex} is NaN. Check the serviceTimes array. Times:`, serviceTimes);
                    return; // Skip processing this customer if the service time is invalid
                }

                console.log(`Cashier ${cashierIndex} serving customer with service time ${serviceTime / 1000} seconds`);

                // Set a timeout to remove the current customer after the service time
                setTimeout(() => {
                    // Check the queue length again before removing a customer
                    console.log(`Queue length for cashier ${cashierIndex} before removing:`, cashierQueue.children.length);

                    if (cashierQueue.children.length > 0) {
                        console.log(`Cashier ${cashierIndex} removing customer`);
                        cashierQueue.removeChild(cashierQueue.children[0]);

                        // Print the current length of the queue
                        console.log(`Queue length for cashier ${cashierIndex} after removing:`, cashierQueue.children.length);

                        // Process the next customer recursively
                        processNextCustomer();
                    } else {
                        console.log(`Queue for cashier ${cashierIndex} is already empty.`);
                    }
                }, serviceTime);
            } else {
                console.log(`No more customers for cashier ${cashierIndex}`);
            }
        }

        // Start processing the first customer
        processNextCustomer();
    }
    </script>
</body>
</html>
