<?php
    // First things first. Kill the page if user isn't an admin.
    if (!$User->authURI('admin')) {
        header("Location: index.php");
        die();
    }

    $xmlData = file_get_contents("https://plex.tv/pms/friends/all?X-Plex-Token=" . $GLOBALS['ini_array']['token']); // Download all friends in XML format.
    $data = simplexml_load_string($xmlData);    // Convert XML to useful object.

    $RAWPerm = file_get_contents($GLOBALS['ini_array']['JSON']); // Load JSON file with permissions.
    $JSONPerm = json_decode($RAWPerm, true);    // Convert RAW JSON into object.
    $Filemtime = filemtime($GLOBALS['ini_array']['JSON']); // Use this to ensure two people don't overwrite eachothers changes.

    if ($User->authURI('admin') && !empty($_POST)) {
        if ($_POST['timestamp'] != $Filemtime) {
            // File has been modified. Do not continue.
            print "ERROR: Permissions have been modified since submission. Please retry";
            die();
        }
        $newPerm = Array();
        foreach ($_POST as $item => $bool) {
            $items = explode("-", $item);
            if ($items[0] == "timestamp") {
                continue;
            }
            if (!isset($newPerm[$items[0]])) {
                $newPerm[$items[0]] = Array("permissions" => []);
            }
            array_push($newPerm[$items[0]]["permissions"],$items[1]);
            // print_r($newPerm);
            // print "<br>";
        }
        $newPerm["template"] = $JSONPerm["template"];
        print_r(json_encode($newPerm));
        file_put_contents($GLOBALS['ini_array']['JSON'],json_encode($newPerm));
        $_POST = array();
        print "<script>alert('Permissions have been saved!'); window.location.href = window.location.href;</script>";
        die();
    }

    function plexIDToUsername($id, $data) {
        // Function that will convert an input PlexID to their Username.
	    foreach ($data->User as $usr){
		if ($usr->attributes()['id'] == $id) {
			return $usr->attributes()['username'];
			break;
		}
	    }
    }

    function getPermissions($userID, $JSONPerm) {
        // Get the permissions for a user.
        if (isset($JSONPerm[$userID])) {
            if (isset($JSONPerm[$userID]['permissions'])) {
                return $JSONPerm[$userID]['permissions'];
            }
        }
        return Array();
        
        foreach ($JSONPerm as $user => $options) {
            $username = plexIDToUsername($user, $data)? plexIDToUsername($user, $data): $user;
            print "<label for='$user'>$username</label><input type='text' placeholder='Permissions' value='";
            $perms = json_encode($options["permissions"]);
            print "$perms'/>";
        }
        print '</form>';
    }
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<!--  Meta  -->
		<?php require_once 'inc/meta.php'; ?>
		<title>Permissions - Your site</title>
		<!--  CSS  -->
		<?php require_once 'inc/css.php'; ?>
        <script lang="javascript">
            var permissions = JSON.parse(`<?php print_r($RAWPerm); ?>`);
            var prevUser = "";
            function selectUser (selectObject) {
                var id = selectObject.value;
                document.getElementById(id).classList.remove("hide");
                document.getElementById("saveall").classList.remove("hide");
                if (prevUser != "") {
                    document.getElementById(prevUser).classList.add("hide");
                }
                prevUser = id;
            }
            function updateCurrentPerms() {
                for (var user in permissions) {
                        if (user === "template") {
                                continue;
                        }
                        var usrperm = permissions[user];
                        for (var perm in usrperm['permissions']) {
                                console.log(user + "-" + usrperm['permissions'][perm]);
                                document.getElementById(user + "-" + usrperm['permissions'][perm]).checked = true;
                        }
                }
            }
            document.addEventListener('DOMContentLoaded', function() {
                updateCurrentPerms();
            });
        </script>
	</head>
	<body class="grey lighten-5">
		<!--  Nav  -->
		<?php require_once 'inc/nav.php'; ?>
		<main class="valign-wrapper">
			<div class="container valign">
				<div class="section">
					<div class="row">
						<div class="col s12">
                            <div class="row">
                              <div class="input-field col s12 m6">
                                <select class="icons" onchange="selectUser(this);">
                                  <option value="" disabled selected>Select a user</option>
                                  <?php
                                    foreach ($data->User as $usr){
                                        $usr = $usr->attributes();
					print '<option value="'. $usr['id'] .'" data-icon="'. $usr['thumb'] .'" class="left circle">'. $usr['username'] .'</option>';
                                    }
                                  ?>
                                </select>
                                <label>Select user to change permissions.</label>
                              </div>
                            </div>
                            <!-- /////////////////////////////////////////////////////////////////////////// -->
                            <!-- THE IMPORTANT BIT START -->
                            <!-- Page Title -->
                            <form method="post">
                            <?php 
                            foreach ($data->User as $usr){
                                $usr = $usr->attributes(); ?>
                                <div id="<?php print $usr['id']; ?>" class="hide">
                                    <div class="row">
                                        <div class="col s12">
                                            <h2><?php print $usr['username']; ?>'s Permissions</h2>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col s8">
                                            <?php
                                                // Build the different permissions based on the template.
                                                if (isset($JSONPerm['template']['permissions'])) {
                                                    foreach ($JSONPerm['template']['permissions'] as $permission) {
                                                        ?>
                                                        <div class="row">
                                                            <div class="col s9">
                                                                <?php print $permission; ?>
                                                            </div>
                                                            <div class="col s3">
                                                                <div class="switch">
                                                                    <label>
                                                                        Off
                                                                            <input id="<?php print $usr['id']; print '-'.$permission; ?>" name="<?php print $usr['id']; print '-'.$permission; ?>" type="checkbox">
                                                                            <span class="lever"></span>
                                                                        On
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php
                                                    }
                                                } else {
                                                    print '<p>Please ensure you have a permission template in your permissions.json file.</p>';
                                                    die();
                                                }

                                            ?>
                                        </div>
                                        <div class="col s4">Side Bar or something??<br>Account Details??</div>
                                    </div>
                                </div>
                            <?php } ?>
                                <input type="hidden" value="<?php print $Filemtime; ?>" name="timestamp"/>
                                <input id="saveall" type="submit" value="Save all" class="hide"/>
                            </form>
                            <!-- THE IMPORTANT BIT END -->
                            <!-- \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\ -->
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

