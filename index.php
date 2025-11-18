<!DOCTYPE html>
<html>
<head>
    <title>Choose Experiment</title>
    <style>
        .container-wrapper {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 50px;
            margin-top: 50px;
        }
        .container {
            width: 300px;
            text-align: center;
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 10px;
            background-color: #f9f9f9;
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        /* echo design */
        @media (max-width: 600px) {
            .container-wrapper {
                flex-direction: column;
                align-items: center;
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container-wrapper">
        <div class="container">
            <h1>Parallel Experiment</h1>
            <p>Click the button below to start the simulation.</p>
            <button onclick="window.location.href='/src/parallelQueue.php'">Start Simulation</button>
        </div>
        
        <div class="container">
            <h1>Pooled Experiment</h1>
            <p>Click the button below to start the simulation.</p>
            <button onclick="window.location.href='/src/pooledQueue.php'">Start Simulation</button>
        </div>
    </div>

    <div class="container-wrapper">
        <div class="container">
            <h1>Parallel Formal</h1>
            <p>Click the button below to start the simulation.</p>
            <button onclick="window.location.href='/src/parallelQueue_formal.php'">Start Simulation</button>
        </div>
        
        <div class="container">
            <h1>Pooled Formal</h1>
            <p>Click the button below to start the simulation.</p>
            <button onclick="window.location.href='/src/pooledQueue_fromal.php'">Start Simulation</button>
        </div>
    </div>
</body>
</html>
