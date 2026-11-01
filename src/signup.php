<?php
//get payload from fronted
    $first_name = $_POST['fname'];
    $last_name = $_POST['lname'];
    $ident_number =  $_POST['idnumber'];
    $mobile_phone = $_POST['mphhone'];
    $e_mail = $_POST['email'];
    $password = MD5($_POST['pswd']);

    //MD5 metodo de encriptacion 

   
    echo "<br>" . $first_name; 
    echo "<br>" . $last_name;
    echo "<br>" . $ident_number;
    echo "<br>" . $mobile_phone;
    echo "<br>" . $e_mail;
    echo "<br>" . $password;