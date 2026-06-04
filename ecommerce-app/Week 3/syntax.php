<?php
echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<title>PHP Test</title>";
echo "<style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            text-align: center;
            padding-top: 100px;
        }
        .card {
            background: white;
            width: 400px;
            margin: auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
      </style>";
echo "</head>";
echo "<body>";

echo "<div class='card'>";
echo "<h1>Hello World from PHP!</h1>";
echo "<p>PHP is running successfully.</p>";
echo "<p>Today's date: " . date("Y-m-d H:i:s") . "</p>";
echo "</div>";

echo "</body>";
echo "</html>";
?>