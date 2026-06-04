<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hello World</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
        }

        .card {
            background: white;
            padding: 50px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 90%;
        }

        h1 {
            font-size: 3rem;
            color: #4f46e5;
            margin-bottom: 15px;
        }

        p {
            color: #555;
            font-size: 1.1rem;
            margin-bottom: 25px;
        }

        button {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s ease;
        }

        button:hover {
            background: #3730a3;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <div class="card">
        <h1>Hello World 👋</h1>
        <p>Welcome to your beautifully styled HTML page.</p>
        <button onclick="alert('Hello, World!')">Click Me</button>
    </div>

</body>
</html>