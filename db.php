<?php

$conn = mysqli_connect("localhost","root","root","listahan") or die("nope");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>