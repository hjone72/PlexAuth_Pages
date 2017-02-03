<!DOCTYPE html>
<html lang="en">
    <?php
        ////////////////// PHP FUNCTIONS ////////////////////
        function GoogleValidation($post) {
            // Do the google stuff here.
            if (isset($post['g-recaptcha-response'])) {
                $gsecret = ""; //gsecret code.
                $captcha = filter_var($post['g-recaptcha-response'], FILTER_SANITIZE_STRING); // Sanitize the string before making call to Google.
                $response=json_decode(file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . $gsecret . "&response=" . $captcha . "&remoteip=" . $_SERVER['REMOTE_ADDR']), true);
                
                if ($response['success'] == true) {
                    return true;
                } else {
                    return false;
                }
            }
            return false;
        }
    
        function getConnection() {
            $sqlConn = connectSQL(array('invites','invitesP@ssw0rd','mysql:host=localhost;dbname=invites')); //Connect to the SQL database.
            return $sqlConn;
        }
        
        function queryInvites($date = null, $username = null) {
            $sqlConn = getConnection();

            if ($date != null && $username != null) {
                $value = 'genDate = "' . $date . '" AND genUser = "' . $username .'"';
            } elseif ($date != null) {
                $value = 'genDate = "' . $date . '"';
            } elseif ($username != null) {
                $value = 'genUser = "' . $username . '"';
            } else {
                return null;
            }
            $query = 'SELECT * FROM invites WHERE ' . $value;
            $return = sqlTemplateQuery($query, null, $sqlConn);
            $sqlConn = null;
            return $return;
        }
    
        function InviteValidation($icode) {
            // Check the users invitation code.
            
            //DO:
            // Static MySQL query that returns all non claimed codes.
            // iterate through each code and check for match.
            
            //force icode to uppercase.
            $icode = strtoupper($icode);
            
            // Do we claim the invite code yet?
            $today = date("Y-m-d");
            if (!preg_match("/^[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{6}$/",$icode)) {
                //invalid code.
				$_SESSION['alert'] = "Invalid invite code.";
                return false;
            }
			$inviteToday = queryInvites($today);
			$inviteAlways = queryInvites("NEVER");
			$return = array_merge($inviteToday, $inviteAlways);
            foreach ($return as $invite) {
                if ($invite['claimedBy'] != null) {
                    continue; //This is needed because of the never expire code.
                }
                if ($invite['code'] == $icode) {
                    //Match found.
                    $_SESSION['inviteCode'] = $invite['code'];
                    $_SESSION['sponsor'] = $invite['genUser'];
                    return true;
                }
            }
			$_SESSION['alert'] = "Invalid invite code.";
            return false;
        }
    
        function joinPlex($username, $email, $password) {
            require_once('PlexUser.class.php'); //Ensure that the PlexUser class has been loaded.
            $ticketData = array(
                'user[email]'=> $email,
                'user[username]'=> $username,
                'user[password]'=> $password);

            $ch = curl_init('https://plex.tv/users.json');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                    
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($ticketData));                                                        
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'X-Plex-Client-Identifier: 5aea70da-7c8d-4866-ba78-f5925df92b40',
                'Content-Type: application/x-www-form-urlencoded'));
            $output = curl_exec($ch);
            
            $result = json_decode($output, true);
            if (array_key_exists("error",$result)){
                print 'There was an error creating the account.';
                print_r($result); // Haven't done any real error capturing here. Print the unformated error to the user. I don't suspect this will happen often anyway.
            } elseif (array_key_exists("user",$result)) {
                return true;
            } else {
                print 'There was an error creating the account.';
                print_r($result);
            }
			return false;
        }
    
        function reloadPage($post = false) {
            if ($post) {
                $_POST = null;
                unset($_POST);
            }
            header('Location: '.$_SERVER['REQUEST_URI']);
        }
    
        function test_input($data) {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data);
            return $data;
        }
    
        include_once('sql.module.php');
        //CURL POST Function
        function httpPost($url,$params,$header = null){
            $ch = curl_init();  

            curl_setopt($ch,CURLOPT_URL,$url);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
            curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
            curl_setopt($ch,CURLOPT_HEADER, false); 
            curl_setopt($ch,CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch,CURLOPT_TIMEOUT, 30);
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$params);    

            $output=curl_exec($ch);

            curl_close($ch);
            return $output;
        }
    
        function alert($alert) {
            // Alert the user of something using javascript.
            echo '<script language="javascript">';
            echo 'alert("'.$alert.'")';
            echo '</script>';
        }
    ?>
	<head>
		<!--  Meta  -->
		<?php require_once 'inc/meta.php'; ?>
		<title>Join my Plex</title>
		<!--  CSS  -->
		<?php require_once 'inc/css.php'; ?>
        <script src='https://www.google.com/recaptcha/api.js'></script>
        <script>
            function create() {
                var elements = document.getElementsByClassName("createAccount");
                for (i = 0; i < elements.length; i++) {
                    if (document.getElementById("plexCreate").checked) {
                        elements[i].classList.remove("hide");
                        document.getElementById("submit").innerHTML = 'Create Account<i class="material-icons right">send</i>';
                    } else {
                        elements[i].className += " hide";
                        document.getElementById("submit").innerHTML = 'Load Details<i class="material-icons right">send</i>';
                    }
                }
            }
        </script>
	</head>
	<body class="plex-black">
		<main class="valign-wrapper">
			<div class="container valign">
				<div class="section">
					<div class="row">
						<div class="col col l6 offset-l3 m8 offset-m2 s12 z-depth-4 holding-box grey lighten-5">
							<div class="row">
								<div class="col s12">
									<h1 class="center-align">Join my Plex</h1>
								<?php
                                // Check GET variable for invite code. If code exists then preload it for the user.
                                if (isset($_GET['code'])) {
                                    $inviteCode = $_GET['code'];
                                } else {
                                    $inviteCode = ""; // No invite code was found. 
                                }
                                
                                // Check if there are any alerts from the previous page load.
                                if (isset($_SESSION['alert'])) {
                                    alert($_SESSION['alert']); // Create the alert.
                                    unset($_SESSION['alert']); // Remove the alert so it can be reused.
                                }
                                
                                // Check our progress indicators.
                                if (!isset($_SESSION['invited'])) {
                                    $_SESSION['invited'] = false; // Reset it to false.
                                }
                                if (!isset($_SESSION['PlexLogin'])) {
                                    $_SESSION['PlexLogin'] = false; // Reset the Plex login status.
                                }
                                if (!isset($_SESSION['JoinComplete'])) {
                                    $_SESSION['JoinComplete'] = false; // Reset the join completion status.
                                }
                                if (!isset($_SESSION['accountCreated'])) {
                                    $_SESSION['accountCreated'] = false;
                                }
                                
                                // Create a form for the user to enter their invite code into. ReCapture too!
                                if (!$_SESSION['invited']) {
                                    // Before displaying the form. Check if this is a post submission.
                                    if (isset($_POST) && $_POST['formname'] == 'invite') {
                                        // This is a post. Does the information validate?
                                        $validation = true; // Validation switch. Start true, triger false on any invalidation.
                                        if (!GoogleValidation($_POST) || !InviteValidation($_POST['icode'])) {
                                            $validation = false;
					    reloadPage(true);
                                        }
                                    }
                                    if ($validation == true) {
                                        // You made it! You're now fully validated! :D
                                        // Now let's get you all signed up!
                                        $_SESSION['invited'] = true; // flip the switch. The user can now proceed.
                                        reloadPage(true);
                                    } else {
                                ?>
                                    <div class="row">
                                        <div class="col s10 offset-s1">
                                            <p class="center-align">Enter your 6-character invitation code</p>
                                        </div>
                                    </div>
                                    <form method="post">
										<div class="row">
                                            <div class="invitation center-align">
                                                <i class="material-icons prefix active">lock_outline</i>
                                                <input id="icode" name="icode" maxlength="6" type="text" value="<?php echo $inviteCode; ?>" maxlength="6" class="tooltipped" data-position="top" data-delay="50" data-tooltip="Enter your invitation code." data-tooltip-id="e49cafed-7cd8-eaac-b138-d6c33cf073c2">
                                            </div>
										</div>
                                        <div class="row">
                                            <div class="col s12 center-align">
                                                <div class="g-recaptcha" data-sitekey="6LdEAw4UAABBAPCLJjavlf0w4DkHFXf2w2q816zr" style="display:inline-block;"></div>
                                                <input type="hidden" name="formname" value="invite">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="row center-align">
                                                <button class="btn-large waves-effect waves-light plex-orange" type="submit" name="action">Join
                                                </button>
                                            </div>
                                        </div>
									</form>
                                <?php
                                    }
                                } elseif (!$_SESSION['PlexLogin']) {
                                    // Do some validations here if they logged into Plex or not.
                                    if (isset($_POST) && $_POST['formname'] == 'login') {
                                        if ($_POST['plexCreate']) {
											$join = false;
                                            if (!isset($_POST["PlexEmail"])) {
                                                $emailErr = " Email is required";
                                            } else {
                                                $email = test_input($_POST["PlexEmail"]);
                                                if ($email != $_POST["PlexEmail"]) {
                                                    $emailErr = " Invalid email format";
                                                } else {
                                                    // check if e-mail address is well-formed
                                                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                                        $emailErr = " Invalid email format"; 
                                                    }
                                                }
                                            }
                                            // Username validation.
                                            if (!isset($_POST["PlexUsername"])) {
                                                $usrErr = " Username is required";
                                            } else {
                                                $username = test_input($_POST["PlexUsername"]);
                                                if ($username != $_POST["PlexUsername"]) {
                                                    $usrErr = " Invalid username, please do not use special characters in your username..";
                                                } else {
                                                    if (!filter_var($username, FILTER_SANITIZE_STRING)) {
                                                        $usrErr = " Invalid username, please do not use special characters in your username.";
                                                    }
                                                }
                                            }
                                            // Password validation.
                                            if (!isset($_POST["PlexPassword"])) {
                                                $pwdErr = " Password is required.";
                                            } else {
                                                $pwd = test_input($_POST["PlexPassword"]);
                                                if ($pwd != $_POST["PlexPassword"]) {
                                                    $pwdErr = " There is an error with your password.";
                                                } elseif (!isset($_POST["PlexPasswordConfirm"])) {
                                                    $pwdErr = " Please confirm your password.";
                                                } elseif ($pwd != $_POST["PlexPasswordConfirm"]) {
                                                    $pwdErr = " Passwords do not match.";
                                                }
                                            }
                                            if (isset($emailErr) || isset($usrErr) || isset($pwdErr)) {
                                                $_SESSION['alert'] = "Please ensure you enter all details in the form accurately." . $emailErr . $nameErr . $usrErr . $pwdErr;
                                                reloadPage(true);
                                            } else {
                                                $join = joinPlex($username, $email, $pwd);
                                            }
                                        } else {
											$join = true;
										}
										// This is a post. Does the information validate?
										if ($join) {
											require_once('PlexUser.class.php'); //Ensure that the PlexUser class has been loaded.
											if (isset($User)){
												$User = unserialize($_SESSION['ytbuser']); //Workaround for bad code design for PlexUserClass
											}

											if (!isset($_SESSION['NewUser'])){
												$NewUser = new PlexUser(null, $_POST['PlexEmail'], $_POST['PlexPassword']);
												$_SESSION['NewUser'] = serialize($NewUser);
											} else {
												$NewUser = unserialize($_SESSION['NewUser']);
											}

											if (isset($User)){
												$_SESSION['ytbuser'] = serialize($User); //Workaround for bad code design for PlexUserClass
											} else {
												unset($User);
												unset($_SESSION['ytbuser']);
											}
											if ($NewUser->getUsername() != ''){
												$_SESSION['PlexLogin'] = true;
												reloadPage(true);
											} else {
												$_SESSION['alert'] = "Username and Password were incorrect.";
												unset($_SESSION['NewUser']);
											}
										}
                                    }
                                    // Display the login page.
                                    ?>
                                        <form method="post">
                                            <div class="row">
                                                <div class="input-field col s12">
                                                    <input id="email" name="PlexEmail" type="email" class="validate tooltipped" data-position="top" data-delay="50" data-tooltip="Use your Plex credentials">
                                                    <label for="email">Plex Email</label>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="input-field col s12">
                                                    <input id="password" name="PlexPassword" type="password" class="validate">
                                                    <label for="password">Plex Password</label>
                                                </div>
                                            </div>
                                            <div class="row createAccount hide">
                                                <div class="input-field col s12">
                                                    <input id="passwordConf" name="PlexPasswordConfirm" type="password" class="validate createAccount hide">
                                                    <label for="password" class="createAccount hide">Confirm Plex Password</label>
                                                </div>
                                            </div>
                                            <div class="row createAccount hide">
                                                <div class="input-field col s12">
                                                    <input id="username" name="PlexUsername" type="text" class="validate createAccount hide">
                                                    <label for="username" class="createAccount hide">Plex Username</label>
                                                </div>
                                            </div>
                                            <input type="hidden" name="formname" value="login"/>
                                            <div class="row">
                                                <div class="checkbox col s6">
                                                    <input id="plexCreate" type="checkbox" name="plexCreate" onclick="create();"/>
                                                    <label for="plexCreate">Create a Plex account</label>
                                                </div>
                                            </div>
                                            <div class="form-legal createAccount hide">
                                                This form will create a new account with <a href="https://plex.tv" target="_blank">Plex.tv</a>. By creating an account, you confirm that you accept the Plex <a href="https://www.plex.tv/sign-up/#remodal-terms" target="_blank">Terms and Conditions</a> and are at least 13 years old.
                                            </div>
                                            <div class="row right-align">
                                                <button class="btn waves-effect waves-light plex-orange" id="submit" type="submit" name="action">Load Details
                                                    <i class="material-icons right">send</i>
                                                </button>
                                            </div>
                                        </form>
                                    <?php
                                } elseif (!$_SESSION['JoinComplete']) {
                                    // Display the final page.
                                    if (isset($_POST) && $_POST['formname'] == 'details') {
                                        // This is a form submission. Check the data.
                                        if (!isset($_POST["FName"]) || !isset($_POST["LName"])) {
                                            $nameErr = " Name is required";
                                        } else {
                                            $name = $_POST["FName"] . " " . $_POST["LName"];
                                            $name = test_input($name);
                                            // check if name only contains letters and whitespace
                                            if (!preg_match("/^[a-zA-Z ]*$/",$name)) {
                                                $nameErr = " Only letters and white space allowed";
                                            } else {
                                                //Name validates.
                                                $_SESSION['FName'] = test_input($_POST["FName"]);
                                                $_SESSION['LName'] = test_input($_POST["LName"]);
                                            }
                                        }

                                        if (!isset($_POST["PlexEmail"])) {
                                            $emailErr = " Email is required";
                                        } else {
                                            $email = test_input($_POST["PlexEmail"]);
                                            if (($email != $_POST["PlexEmail"]) || (!filter_var($email, FILTER_VALIDATE_EMAIL))) {
                                            // check if e-mail address is well-formed
                                                $emailErr = " Invalid email format"; 
                                            } else {
                                                // Email validates.
                                                $_SESSION['PlexEmail'] = $email;
                                            }
                                        }
                                        
                                        if (!isset($_POST['priv']) || !isset($_POST['tac'])) {
                                            $agreeErr = "You must read and accept our Terms and Conditions and our Privacy Agreement.";
                                        }

                                        if (!empty($emailErr) || !empty($nameErr)){
                                            $_SESSION['alert'] = "Please ensure you enter all details in the form accurately." . $emailErr . $nameErr;
                                            reloadPage(true);
                                        } elseif (!empty($agreeErr)) {
                                            $_SESSION['alert'] = $agreeErr;
                                            reloadPage(true);
                                        } else {
                                            // Validation successful.
                                            $_SESSION['JoinComplete'] = true;
                                            reloadPage();
                                        }
                                    }
                                        $NewUser = unserialize($_SESSION['NewUser']); // Load the user from the previous step.
                                        ?>
                                            <form method="post">
                                                <p>This information will be used to contact you so please ensure it is accurate.</p>
                                                <div class="row">
                                                    <div class="input-field col s12">
                                                        <input id="FName" name="FName" type="text">
                                                        <label for="FName">First Name</label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="input-field col s12">
                                                        <input id="LName" name="LName" type="text">
                                                        <label for="LName">Last Name</label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="input-field col s12">
                                                        <input id="email" name="PlexEmail" type="email" value="<?php echo $NewUser->getEmail();?>">
                                                        <label for="email">Best contact email address</label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="input-field col s12">
                                                        <input id="username" name="PlexUsername" type="text" disabled value="<?php echo $NewUser->getUsername();?>">
                                                        <label for="username">Plex Username</label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="checkbox col s6">
                                                        <input id="tac" name="tac" type="checkbox">
                                                        <label for="tac"><a href="ENTER A URL">Terms and Conditions</a></label>
                                                    </div>
                                                    <div class="checkbox col s6">
                                                        <input id="priv" name="priv" type="checkbox">
                                                        <label for="priv"><a href="ENTER A URL">Privacy Agreement</a></label>
                                                    </div>
                                                </div>
                                                <div class="row right-align">
                                                    <button class="btn waves-effect waves-light plex-orange" type="submit" name="action">Join
                                                        <i class="material-icons right">send</i>
                                                    </button>
                                                </div>
                                                <input type="hidden" name="formname" value="details"/>
                                            </form>
                                        <?php
                                    } elseif (!$_SESSION['accountCreated']) {
                                        $_SESSION['accountCreated'] = true; //Ensure the following can only be run once.
                                        $NewUser = unserialize($_SESSION['NewUser']); //Grab the NewUser from the session.
                                        $username = $NewUser->getUsername();

                                        $sqlConn = connectSQL(array('invites','invitesP@ssw0rd','mysql:host=localhost;dbname=invites')); //Connect to the SQL database.
                                    
                                        // One final check that the code is still valid before claiming it.
                                        if (InviteValidation($_SESSION['inviteCode'])) {
                                            // Code is still valid. Claiming it now.
                                            $query = 'UPDATE invites SET claimedBy = ? WHERE code = ?';
                                            $binder = null;
                                            $binder[] = $NewUser->getID();
                                            $binder[] = $_SESSION['inviteCode'];
                                            $value = sqlTemplateQuery($query, $binder, $sqlConn)[0];
                                            
											
					// Remove 1 invite from the sponsors pool
					$query = 'UPDATE users SET invites = invites - 1 WHERE id = ?';
					$binder = null;
                                            $binder[] = $_SESSION['sponsor'];
                                            $result = sqlTemplateQuery($query, $binder, $sqlConn)[0]['username'];

                                            // Get the username of the sponsor.
                                            $query = 'SELECT * FROM users WHERE id = ?';
                                            $binder = null;
                                            $binder[] = $_SESSION['sponsor'];
                                            $Sponsor = sqlTemplateQuery($query, $binder, $sqlConn)[0]['username'];
                                            
                                            //User has been sponsored. Invite them to Plex.

                                            //User stuff
                                            //Here we are going to create a new entry into a MySQL database that contains the username and an expiry date.
                                            //To begin with the expiry will be set to 7 days after today. The info column will also be set to trial.
                                            //This will give the user some grace before killing their streams.

                                            $query = 'INSERT INTO users (id, username, end_date, info) VALUES (?, ?, ?, "trial")';
                                            $binder = null;
                                            $binder[] = $NewUser->getID();
                                            $binder[] = $username; //Add username to binder.
                                            $expiry = date_format(date_create(Date('Y-m-d', strtotime("+7 days"))), 'Y-m-d'); //7 days after today.
                                            $binder[] = $expiry; //Add expiry to binder.
                                            $value = sqlTemplateQuery($query, $binder, $sqlConn)[0];

                                            $query = 'Select * From users where username = ?';
                                            $binder = null;
                                            $binder[] = $username;
                                            $value = sqlTemplateQuery($query, $binder, $sqlConn)[0];
                                            if ($value['username'] != $username){
                                                Print 'An error occured creating your account. Please contact an admin. ';
                                            }                                        
                                            $sqlConn = null;
                                            //Plex stuff
                                            //HARD CODED VALUES THAT SHOULD BE MADE VARIABLE//
                                            //Navigate to: https://plex.tv/api/resources.xml?X-Plex-Token=XXXXXXXXXXXXXXXX
											// copy the clientIdentifier from your plex server: "fe3e045267994acdbe7c6d4f19daa105"
                                            $MachineID = ""; //MachineID of PlexServer
                                            $XPCImd5 = md5($username);
                                            $PToken = $GLOBALS['ini_array']['token'];
											
											// All of this stuff can be edited to customize what you want.
											// See here for details: https://github.com/jrudio/go-plex-client
                                            $ContentBody = array(
                                                "server_id" => $MachineID,
                                                "shared_server" => array(
                                                    "library_section_ids" => array(),
                                                    "invited_email" => $username),
                                                "sharing_settings" => "");

                                            $JSONBody = json_encode($ContentBody);

                                            $PlexHeader = array(
                                                'X-Plex-Version: 1.0',
                                                'X-Plex-Platform-Version: 1.0',
                                                'X-Plex-Device-Name: PlexAuth-UserJoin',
                                                'X-Plex-Platform: PlexAuth',
                                                'Content-Type: application/json',
                                                'X-Plex-Product: PlexAuth-UserJoin',
                                                'X-Plex-Device: PlexAuth-UserJoin',
                                                'Host: plex.tv'
                                                );
                                            $ContentLength = strlen($JSONBody);
                                            array_push($PlexHeader, "X-Plex-Client-Identifier: $XPCImd5", "Content-Length: $ContentLength", "X-Plex-Token: $PToken");
                                            httpPost("https://plex.tv/api/servers/$MachineID/shared_servers", $JSONBody, $PlexHeader); //This should have invited the user.
                                            
											//Edit these settings as needed.
											$address = "admin@yourdomain.com"; // Your email address.
                                            $subject = "$username has just joined your Plex";
                                            $header = "FROM: MyDomain <admin@yourdomain.com>\r\n";
                                            if ($Email != $NewUser->getEmail()){
                                                $emessage = "Please be advised that the user has a different contact address to their Plex email address.\r\n";
                                            } else {
                                                $emessage = "";
                                            }
                                            $inviter = "They were invited by " . $Sponsor . "\r\n";
                                            $msg = "Hi,\r\n$FirstName $LastName has just joined your Plex. You can contact them via $Email\r\n" . $emessage . $inviter . "Regards,\r\nMyDomain Team";
                                            mail($address, $subject, $msg, $header);
                                            reloadPage(true);
                                        } else {
                                            echo "Unfortunately your invitation code has already been claimed.";
                                            session_destroy();
									        session_regenerate_id();
                                        }
                                    } elseif ($_SESSION['accountCreated'])  {
                                        echo "You should now have an email in your inbox with an invitation to Plex. Once you have accepted this you will be able to login to PlexAuth. If you are unable to find the email please ensure you are checking the inbox of the email address associated with your Plex account. We would recommend having a quick read through our <a href=\"\">FAQ.</a>";
                                        echo '<br>If you experience any issues with myPlex please log a support ticket at <a href="helpdesk">helpdesk.</a>';
                                    }
                                ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</main>

		<!--  Scripts  -->
		<?php require_once 'inc/javascripts.php'; ?>
	</body>
</html>
