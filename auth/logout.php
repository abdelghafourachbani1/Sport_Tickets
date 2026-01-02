<?php 
    session_start();
    session_destroy();
    header("location: ..logiN.php");
    exit();