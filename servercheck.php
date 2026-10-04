<?php

echo "<h2>RPL SERVER CHECK</h2>";

echo "<p><strong>Apache is serving:</strong></p>";
echo "<p>" . __DIR__ . "</p>";

echo "<hr>";

echo "<p>style.css: ";
echo file_exists(__DIR__ . "/style.css") ? "YES" : "NO";
echo "</p>";

echo "<p>script.js: ";
echo file_exists(__DIR__ . "/script.js") ? "YES" : "NO";
echo "</p>";

?>