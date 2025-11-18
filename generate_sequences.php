<?php
// Setting the Path
$configDir = __DIR__ . '/../config/';

// Set the experiment time to 10 minutes (600 seconds)
$experiment_duration = 600;

// Generating customer arrival time intervals (Poisson process)
$lambda = 4.5; // The average inter-arrival time is 5.5 seconds
$arrival_times = [];
$current_time = 0;

while ($current_time < $experiment_duration) {
    // Simulating a Poisson Process Using an Exponential Distribution
    $inter_arrival_time = -log(1.0 - mt_rand() / mt_getrandmax()) * $lambda;
    $current_time += $inter_arrival_time;
    if ($current_time < $experiment_duration) {
        $arrival_times[] = $current_time;
    }
}

// Number of customers based on the length of $arrival_times plus init customers
$num_customers = count($arrival_times) + 10; 

// Generate service time (10 seconds + exponential random variable)
$service_times = [];
for ($i = 0; $i < $num_customers; $i++) {
    // $service_time = 10 + (-log(1.0 - mt_rand() / mt_getrandmax()) * 10);
    $service_time = 10 + (-log(1.0 - mt_rand() / mt_getrandmax()) * 8);
    $service_times[] = $service_time;
}

// Common grocery item names
$items = [
    'Egg', 'Milk', 'Bread', 'Butter', 'Cheese', 
    'Chicken', 'Beef', 'Pork', 'Fish', 'Rice', 
    'Pasta', 'Apple', 'Banana', 'Orange', 'Tomato', 
    'Potato', 'Onion', 'Carrot', 'Broccoli', 'Lettuce', 
    'Cucumber', 'Cabbage', 'Pepper', 'Mushroom', 'Garlic', 
    'Yogurt', 'Juice', 'Cereal', 'Soda', 'Water'
];

// Generate prices for 30 items, ranging from 1 to 5, with increments of 1
$prices = [];
foreach ($items as $item) {
    $prices[$item] = mt_rand(1, 5); // Associate item names with prices
}

// Initialize the customer item selection list
$customer_items = [];

for ($i = 0; $i < $num_customers; $i++) {
    // Randomly select 5 items for each customer
    $random_keys = array_rand($prices, 5);
    $selected_items = [];
    foreach ($random_keys as $key) {
        $selected_items[$key] = $prices[$key];
    }
    $customer_items[] = $selected_items;
}

// Save the sequences to files in the config directory
file_put_contents($configDir . 'arrival_times_speed_up.json', json_encode($arrival_times));
file_put_contents($configDir . 'service_times_speed_up.json', json_encode($service_times));
file_put_contents($configDir . 'prices.json', json_encode($prices));
file_put_contents($configDir . 'customer_items.json', json_encode($customer_items));

echo "Random sequences have been generated and saved to the config directory.";

?>
