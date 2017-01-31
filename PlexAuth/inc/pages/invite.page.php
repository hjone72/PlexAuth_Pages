<!DOCTYPE html>
<html lang="en">
	<head>
		<?php
            // Values we will use throughout this page.
            $today = date("Y-m-d");
            $User = unserialize($_SESSION['ytbuser']);
            $uid = $User->getID();

			// PHP Functions
            function generateInviteCode($length = 6) {
                $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
                $charactersLength = strlen($characters);
                $randomString = '';
                for ($i = 0; $i < $length; $i++) {
                    $randomString .= $characters[rand(0, $charactersLength - 1)];
                }
                return $randomString;
            }

            require_once('sql.module.php');

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

            function writeInvite($code, $genDate, $uid){
                $sqlConn = getConnection();
                $query = 'INSERT INTO invites (code, genDate, genUser) VALUES (?, ?, ?)';
                $binder = null;
                $binder[] = $code;
                $binder[] = $genDate;
                $binder[] = $uid;
                $return = sqlTemplateQuery($query, $binder, $sqlConn);
            }

			function authorizeSponsor($User) {
				if ($User->authURI('sponsor')) {
					return true;
				}
				$sqlConn = getConnection();
				$query = 'SELECT * FROM users WHERE id= ?';
				$binder = null;
				$binder[] = $User->getID();
				$return = sqlTemplateQuery($query, $binder, $sqlConn)[0];
				if ($return['invites'] == null) {
					$return['invites'] = 0;
				}
				if ($return['invites'] > 0) {
					return true;
				}
				return false;
			}

		?>
		<!--  Meta  -->
		<?php require_once 'inc/meta.php'; ?>
		<title>Invite to Plex</title>
		<!--  CSS  -->
		<?php require_once 'inc/css.php'; ?>
	</head>
	<body class="grey lighten-5">
		<!--  Nav  -->
		<?php require_once 'inc/nav.php'; ?>
		<main class="valign-wrapper">
			<div class="container valign">
				<div class="section">
					<div class="row">
						<div class="col l10 offset-l1 m12 s12">
							<div class="row">
								<div class="col s12">
									<h1 class="center-align">Invite someone to Plex</h1>
								</div>
								<?php
									if (authorizeSponsor($User)) {
                                        if (isset($_POST['formname']) && $_POST['formname'] == 'gencode') {
                                            // Check when this person last generated a code.
                                            $code = queryInvites($today, $uid);
                                            if ($code == null) {
                                                $code = generateInviteCode();
                                                writeInvite($code, $today, $uid);
                                            } else {
                                                $code = queryInvites($today, $uid)[0]['code'];
                                            }
                                        ?>
					    <p class="center-align">Please navigate to <a href="https://secure.yourdomain.com/?page=join&code=<?php echo $code; ?>">https://secure.yourdomain.com/?page=join&code=<?php echo $code; ?></a>.<br>All invitation codes expire at 11:59PM. Only one code may be generated per day.</p>
                                            <div class="row">
                                                <div class="col s12 center-align">
                                                    <div class="center-align invitation-code">
                                                        <?php echo $code; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php
                                        } else {
                                        ?>
                                            <form method="post">
                                                <div class="row right-align">
                                                    <div class="col m10 s10 offset-m1 offset-s1">
							<div class="row center-align">
								<input type="hidden" name="formname" value="gencode"/>
								<button class="btn waves-effect waves-light plex-orange center-align" type="submit" name="action">Generate invite code
									<i class="material-icons right">send</i>
								</button>
							</div>
                                                </div>
                                            </form>
                                        <?php
                                        }
									} else {
										print '<div class="row center-align">';
											print "Sorry, you don't have any invites remaining. Please contact an admin to invite someone";
										print '</div>';
									}
								?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</main>
		<!--  Footer  -->
		<?php require_once 'inc/footer.php'; ?>
		<!--  Scripts  -->
		<?php require_once 'inc/javascripts.php'; ?>
	</body>
</html>
