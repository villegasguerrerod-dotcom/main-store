<?php
// get database conecction 
require('../settings/database.php');

//get payload from fronted
    $first_name = $_POST['fname'];
    $last_name = $_POST['lname'];
    $ident_number =  $_POST['idnumber'];
    $mobile_phone = $_POST['mphone'];
    $e_mail = $_POST['email'];
    $password = MD5($_POST['pswd']);
    $PHOTO_URL = 'photos/photo_default.png';
    //echo "celular: " . $mobile_phone;

    //MD5 metodo de encriptacion 

    $sql_check_email = "SELECT * FROM users WHERE email = '$e_mail' ";
    $res = pg_query($local_conn, $sql_check_email);
    $total =pg_num_rows($res);
    if ($total < 1){
        //prepare query
        $sql = "INSERT INTO users
                (firstname, lastname, ident_number, email, password, url_photo, mobile_phone) 
            values(
                '$first_name' ,
                '$last_name', 
                '$ident_number',
                '$e_mail',
                '$password',
                '$PHOTO_URL',
                '$mobile_phone'
            ) ;
        ";

        //Execute query 
        $res = pg_query($local_conn, $sql);

        if ($res){
            echo "<script> alert('user has been created successfully !!!')</script>";
            header('refresh:0;url=signin.html');
        } 
        else{
            echo"<br>Error: user hasn't been created";
        }
    } else{
         echo "<script> alert('user email al ready exist. Try again !!!')</script>";
            //header('refresh:0;url=signup.html');


    }
        
        