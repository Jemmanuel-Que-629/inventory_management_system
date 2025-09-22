<?php
    session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Inventory Management System</title>
        <link href='https://fonts.googleapis.com/css?family=Poppins' rel='stylesheet'>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.12.2/sweetalert2.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.12.2/sweetalert2.min.js"></script>
    </head>
    <style>
        body {
            font-family: 'Poppins';
            background-color: #ffffff;
        }
    </style>
<body>
      <?php require '../../layout/sidebar.php'; ?>
      <?php
            if(!empty($_SESSION['login_messages'])){
                $error = $_SESSION['login_messages'];
                echo "<script>
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: '{$error['type']}',
                        title: '{$error['message']}',
                        showConfirmButton: false,
                        timer: 3000
            });
            </script>";
            unset($_SESSION['toast']);
        }
        ?>
</body>
</html>