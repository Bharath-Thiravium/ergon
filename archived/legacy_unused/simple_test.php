<?php
echo "PostgreSQL Test<br>";

if (function_exists('pg_connect')) {
    echo "✅ Extension enabled<br>";
} else {
    echo "❌ Extension missing<br>";
}

echo "Done.";
?>