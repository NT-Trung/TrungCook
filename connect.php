<?php
    $servername="localhost";
    $username="root";
    $password="";
    $datsbase="thoitrangtrungcook";

    $conn = mysqli_connect($servername,$username,$password,$datsbase);
    if(!$conn){
        echo ("Kết nối không thành công!");
    }else{
        echo ("Kết nối thành công");
    }
    
?>