<?php 

$con = mysqli_connect("localhost", "root","","rp habana");

if($con -> connect_error) {
    die("connection failed: " . $con -> connect_error);
} 
// else{
//     echo("Connected succesfully");
// }
?>