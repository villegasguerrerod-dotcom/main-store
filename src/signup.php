<?php
// get database conecction 
require('../settings/database.php');

//get payload from fronted
    $first_name = $_POST['fname'];
    $last_name = $_POST['lname'];
    $ident_number =  $_POST['idnumber'];
    $mobile_phone = $_POST['mphhone'];
    $e_mail = $_POST['email'];
    $password = MD5($_POST['pswd']);

    //MD5 metodo de encriptacion 


    //prepare query
    $sql = "INSERT INTO 
        users(firstname, lastname, ident_number, email, password) 
        values(
            '$first_name' ,
            '$last_name', 
            '$ident_number',
            '$e_mail',
            '$password'
        ) ;
    ";

    //Execute query 
    $res = pg_query($local_conn, $sql);

    if ($res){
        echo "<br>user has been created successfully !!!";

    } 
    else{
        echo"<br>Error: user hasn't been created";
    }
    
        