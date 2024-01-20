<?php
session_start();

if(isset($_SESSION["email"])){
    header("Location: Login.php");
    return;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>REGISTER</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous">
    </script>
</head>
<body>
<div class="vh-100 d-flex justify-content-center align-items-center bg-secondary">
    <div class="container">
        <div class="row d-flex justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card bg-white">
                    <div class="card-body p-5">


                        <div class="card-body">
                            <p class=" mb-5">Pro přihlášení vyplňte svůj e-mail a heslo</p>
                            <form action="RegisterC.php" method="post">
                                <div class="mb-3">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" class="form-control" name="email" />
                                </div>
                                <div class="mb-3">
                                    <label for="password">Password</label>
                                    <input type="password" id="password" class="form-control" name="password" />
                                    <br>
                                    <label for="password">Again Password</label>
                                    <input type="password" id="password1" class="form-control" name="password1" />
                                </div>
                                <input type="submit" class="btn btn-primary w-100" value="Register" name="">
                            </form>
                        </div>
                        <p class="text-center">Jsi již členem? <a data-toggle="tab" href="Login.php">Log In</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>