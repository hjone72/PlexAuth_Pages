<?php
	//MYSQL functions.
	function connectSQL ($info = null) {
		//This function connects to MySQL server and returns the connection.
		
		//SQL server details.
        $user = $info[0];
        $password = $info[1];
        $database = $info[2];
		
		//Attempt connection. Catch on error.
		try {
			//Create connection using PDO
			//PDO allows for greater portability across different databases eg MySQL MSSQL
			$conn = new PDO($database, $user, $password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
            print "<p>Error connecting to database: " . $e->getMessage() . "</p>"; //This should be disabled unless debugging.
            die();
		}
		return $conn;
	}
	
	function sqlTemplateQuery($query, $binding = null, $conn = null) {
		//This executes a prepared query and returns the results as an array.
		if ($conn == null) {
			//There is no existing connection to SQL.
			//Create one.
			$conn = connectSQL();
			$closeconn = true;
		}
        //Prepare the tempate query
        $sqlquery = $conn->prepare($query);
        try {
            $queryresult = $sqlquery->execute($binding);
            $results = $sqlquery->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            //return $e;
            $results = $queryresult;
        }
        if (isset($closeconn)) {
            //This function opened the connection. This function should close the connection.
            $conn = null;
        }
        //Return array of results.
        //$results[0]['rowname']
		return $results;
	}
?>