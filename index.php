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
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-3">
                <form method="post" action="login_process.php" class="p-4 border rounded shadow">
                    <h1 class="mt-3 text-center">Login Form</h1>
                    <div class="mb-3">
                        <label class="form-label">Email:</label>
                        <input type="text" name="email" id="email" class="form-control custom-width" placeholder="email@gmail.com" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password:</label>
                        <input type="password" name="password" id="password" class="form-control custom-width" />
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-success mb-3" name="login">Login</button>
                    </div>
                </form>
        </div>

        <?php
            if(!empty($_SESSION['toast'])){
                $toast = $_SESSION['toast'];
                echo "<script>
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: '{$toast['type']}',
                        title: '{$toast['message']}',
                        showConfirmButton: false,
                        timer: 3000
            });
            </script>";
            unset($_SESSION['toast']);
        }
        ?>

</body>