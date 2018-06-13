<link rel="stylesheet" href="./bootstrap.min.css">

<?php

# Remake by Nuna

//SQLSRV Settings
$DB_User = 'sa';
$DB_Pass = 'Password';
$serverName = "127.0.0.1";
$connectionInfo = array("Database"=>"RF_User", "UID"=>$DB_User, "PWD"=>$DB_Pass );
$conn = sqlsrv_connect( $serverName, $connectionInfo) or die ("Cannot connect to Database");

$config["DaysPrem"] = "7"; // How long new players get Premium Service in Days
$config["CashPrem"] = "5000"; // How much new players get Cash Coin
$config["openreg"] = "open"; // Open / Close Registration Page

$username = $_POST['username'];
$password = $_POST['password'];
$re_password = $_POST['re_password'];
$email = $_POST['email'];
$re_email = $_POST['re_email'];
$pin = $_POST['pin'];
$submit = $_POST['submit'];

// ------------------------------------------------
// MSSQL Server Anti-Inject Function
// ------------------------------------------------
function antiject($str)
{
    $escape = "/([\x00\n\r\,\'\"\x1a])/ig";
    //$str = preg_replace($escape,$str);
    $str = stripslashes($str);
    $str = htmlspecialchars($str);
    $str = trim($str);
    $str = preg_replace("/'/", "''", $str);
    $str = preg_replace('/"/', '""', $str);
    $str = str_replace("`", "", $str);
    $str = preg_replace("/;$/", "", $str);
    $str = preg_replace("/\\\\/", "", $str);

    return $str;
}

$username = antiject($username);
$password = antiject($password);
$re_password = antiject($re_password);

if( $submit != "" ) 
{
	if( $exit_stage1 != true ) 
    {
		$idcheck = "SELECT CONVERT(VARCHAR(50),id) FROM RF_User.dbo.tbl_rfaccount WHERE id=CONVERT(binary,(?))";
		$idparams = array($username);
		$idquery = sqlsrv_query($conn, $idcheck, $idparams);
		if(sqlsrv_has_rows($idquery) ) 
		{
			$error = "Sorry, the username you choose has already been taken.";
			$exit_stage2 = true;
		}
		$emailcheck = "SELECT Email FROM RF_User.dbo.tbl_rfaccount WHERE Email=(?)";
		$emailparams = array($email);
		$emailquery = sqlsrv_query($conn, $emailcheck, $emailparams);
		if(sqlsrv_has_rows($emailquery)) {
            $error = "Sorry, your e-mail address has already been used.";
            $exit_stage2 = true;
        }
		$billingcheck = "SELECT id FROM BILLING.dbo.tbl_UserStatus WHERE id = (?)";
		$billingparams = array($username);
		$billingquery = sqlsrv_query($conn, $billingcheck, $billingparams);
		if(sqlsrv_has_rows($emailquery)) {
			$error = "";
			$exit_stage2 = true;
		}
		
	    if( $username == "" ) {
			$exit_stage2 = true;
			$error = "You forgot to fill in a username";
		}
		else {
			if( eregi("[^a-zA-Z0-9]", $username)) {
				$exit_stage2 = true;
				$error = "Invalid username entered. Letters and numbers only!";
			}
			else {
				if( strlen($username) < 4 || 12 < strlen($username)) {
					$exit_stage2 = true;
					$error = "Username must be greater than 4 and less than 12 characters long";
				}
			}
		}

		if( $password == "" ) {
			$exit_stage2 = true;
			$error = "You forgot to fill in your password";
		}
		else {
			if( eregi("[^a-zA-Z0-9]", $password)) {
				$exit_stage2 = true;
				$error = "Invalid password entered. Letters and numbers only!";
			}
			else {
				if( strlen($password) < 4 || 16 < strlen($password)) {
					$exit_stage2 = true;
					$error = "Password must be greater than 4 and less than 16 characters long";
				}
			}
		}
		if( $password != $re_password ) {
			$exit_stage2 = true;
			$error = "Your confirmation password not match";
		}
		if( $email == "") {
			$exit_stage2 = true;
			$error = "You forgot to fill in your email";
		}
		if( $pin == "") {
			$exit_stage2 = true;
			$error = "You forgot to fill in your pin";
		}
	}
	
	if( $exit_stage2 == false )	{
		$registerquery = "INSERT INTO RF_User.dbo.tbl_rfaccount (id,password,email,pin) VALUES ((CONVERT (binary,?)),(CONVERT (binary,?)),?,?)";
		$registerparams = array($username, $password, $email, $pin);
		if( !($register_query = sqlsrv_query($conn, $registerquery, $registerparams))) 	{
			$error = "SQL Error inserting data into the database";
		}
		else {	               
			 $error = "Successfully registered a new account!";
			 $exit_form = true;
		}
		{
			$cashquery = "INSERT INTO BILLING.dbo.tbl_UserStatus (id,Status,DTStartPrem,DTEndPrem,cash) VALUES (?, '2',(CONVERT(datetime,GETDATE())), (CONVERT(datetime,GETDATE()+?)), ?)";
			$cashparams = array($username, $config["DaysPrem"], $config["CashPrem"]);
			if( !($insert_result = sqlsrv_query($conn, $cashquery, $cashparams))) {
				#echo "Congratulations, New player reward: ". $config["DaysPrem"] ." Days Premium Service and ". $config["CashPrem"] ." Cash Coin!";
				echo "";
			}
		}
	}
}

if( $config["openreg"] == close) {
    echo "Registration Page has been disabled by the admins";
    return 0;
}
else {
	# Input Table in HTML is here
	?>
		<div class="container">
		<?php if ($error != "") {echo "<script> alert('".$error."'); </script>";}	 ?>
		</div>
		<center>
	    <form class="form-signin" method="post" action="register.php">
	    <table border='0'>
	    <tr><td >Username:</td><td ><input type="text" autocomplete="off" minlength="4" maxlength="12" class="form-control" placeholder="Username length must be between 4 to 12 characters only." name="username" size="50" value=""></td>
	    </tr>
	    <tr><td >Password:</td><td ><input type="password" autocomplete="off" minlength="4" maxlength="12" class="form-control" placeholder="Password length must be between 4 to 12 characters only." size="50" name="password" value=""></td>
	    </tr>
	    <tr><td >Retype Password:</td><td ><input type="password" autocomplete="off" class="form-control" placeholder="Re-type your Password." size="50" minlength="4" maxlength="12" name="re_password"></td>
	    </tr>
	    <tr><td >Email Address:</td><td ><input type="email" autocomplete="off" class="form-control" placeholder="Please make sure you enter a valid and working Email."  minlength="4" size="50" maxlength="50" name="email" value=""></td>
	    </tr>
	    <tr><td >Pin:</td><td ><input type="password" autocomplete="off" class="form-control" placeholder="PIN must be only numbers. REMEMBER YOUR PIN!" size="50" pattern="[0-9]{6}" minlength="6" maxlength="6" name="pin" value=""></td>
	    </tr>
	    <tr><p><td  align="center" colspan="2"><input type="submit" class="btn btn-primary" name="submit" value="Register"> <input type="reset" class="btn btn-outline-primary" value="Reset"></td></p>
	    </tr>
	    </table></form></center></div>
	<?php
}

?>

<footer>
<center>
<div>
Copyright 2018, <a href="https://aurosgaming.com/" target="_blank" title="Nuna" alt="Nuna">Nuna</a></p>
</div>
</center>
</footer>
