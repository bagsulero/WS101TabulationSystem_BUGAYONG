<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
	<title>LOGIN Tabulation </title>

</head>
<style>
body {
	background-image: url('images/tabu.jpg'); 
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    flex-direction: column;
    margin: 0; 
}

*{
	font-family: sans-serif;
	box-sizing: border-box;
}

form {
	width: 400px;
	border: 2px solid #ccc;
	padding: 30px;
	background: white;
	border-radius: 15px;
}

h2 {
	text-align: center;
	margin-bottom: 40px;
}

input {
	display: block;
	border: 2px solid black;
	width: 95%;
	padding: 10px;
	margin: 10px auto;
	border-radius: 5px;
}
label {
	color:black;
	font-size: 18px;
	padding: 10px;
}

button {
	float: right;
	background:black;
	padding: 10px 15px;
	color: #fff;
	border-radius: 5px;
	margin-right: 10px;
	border: none;
}
button:hover{
	opacity: .7;
}
.error {
   background: white;
   color: #A94442;
   padding: 2px;
   width: 95%;
   border-radius: 5px;
   margin: 20px auto;
}

h1 {
	text-align: center;
	color: red;
}

a {
	float: right;
	background: blue;
	padding: 10px 15px;
	color: white;
	border-radius: 5px;
	margin-right: 10px;
	border: none;
	text-decoration: none;
}
a:hover{
	opacity: .7;
}
	
</style>
<body>
     <form action="login_process.php" method="post">
		<h2>  Tabulation System </h2>
		<?php
		?>
     	<h3>Login</h3>
     	<?php if (isset($_GET['error'])) { ?>
     		<p class="error"><?php echo $_GET['error']; ?></p>
     	<?php } ?>
     	
     	<input type="text" name="uname" placeholder="User Name"><br>

     	
     	<input type="password" name="password" placeholder="Password"><br>
		 <p><a href="forgot_password.php">Sign Up</a></p>
     	<button type="submit">Login</button>
     </form>

</body>
</html>