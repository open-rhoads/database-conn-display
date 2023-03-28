<?php
ini_set('display_errors', 1); // Let me learn from my mistakes!
error_reporting(E_ALL); // Show all possible problems!
// variable for form error message, declare now to avoid errors when using later
// Will remain empty unless they submit without making a selection
$formmsg = "";

// variable for semester, year & resulting row count
$semester = "";
$year = "";
$rowcount = "";

$dsn      = "mysql:host=localhost;dbname=college_rhoads";  //data source host and db name
$username = "root";
$password = "";


// Create connection to database and store in variable
$conn = new PDO($dsn, $username, $password); // creates PDO object

// Check connection using try/catch statement

try  {
     $conn = new PDO($dsn, $username, $password);
     echo "Connection is successful<br><br>";
}

catch (PDOException $e) {
       $error_message = $e->getMessage();
       echo "An error occurred: $error_message" ;
}


// sql statement set up WHEN PAGE LOADS
//selects 5 columns from 3 inner joined tables
//uses table aliases to distinguish where to find all columns and simplify code
//orders results at the end
$sql_table =    "SELECT
                CONCAT(mems.lname, ', ', mems.fname) AS 'Student Name',
                sch.name AS 'Scholarship',
                sch.amount AS 'Amount', 
                sch.semester AS 'Semester', 
                sch.year AS 'Year' 
                FROM
                members mems
                JOIN scholarships_students schst ON (schst.student_id = mems.student_id) 
                JOIN scholarships sch ON (schst.scholarship_id = sch.scholarship_id)
                ORDER BY year DESC, semester, amount DESC, lname "; 

//prepare the above initial sql for the table
$statement = $conn->prepare($sql_table);

// execute (create) the result set
$statement->execute();

// row count of initial table rows returned (should be 21 initially)
$rowcount = $statement->rowCount();

// just to test results for initial table
echo "Our initial count of members is " . $rowcount . "<br>";

// sql statement set up FOR DROP DOWN LIST
//selects all distinct years from the scholarships table
$sql_select = "SELECT DISTINCT year FROM scholarships";

//prepare the above sql for the year dropdown list
$statement2 = $conn->prepare($sql_select);

// execute (create) the result set
$statement2->execute();

// row count for the select dropdown
$rowcount2 = $statement2->rowCount();

// just to test results for dropdown
echo "Row count for Select is " . $rowcount2 . "<br>";


// ******************  FORM POSTBACK ***********************
//IF form has been submitted by accessing the request method within $_SERVER object
// user not allowed to make the first selection of none - if logic check and give error message
if (($_SERVER['REQUEST_METHOD'] == 'POST') && ($_POST["year"] == "none")) {
    //add message to form error message variable, to be displayed on page
    $formmsg .= "Please select a year. <br>";

} //end if to see if no selection for year after submission

//IF form has been submitted by accessing the request method within $_SERVER object
// user not allowed to make the first selection of none - if logic check and give error message
if (($_SERVER['REQUEST_METHOD'] == 'POST') && ($_POST["semester"] == "none")) {
    //add message to form error message variable, to be displayed on page
    $formmsg .= "Please select a semester. <br>";

} //end if to see if no selection for year after submission

// retrieve form values & rerun sql based on user selection
if (($_SERVER['REQUEST_METHOD'] == 'POST') && ($_POST["year"] != "none") && ($_POST["semester"] != "none")) {
    // store value from year and semester dropdowns in variable using $_POST   
    $year =  $_POST["year"];
    $semester = $_POST["semester"];

    // this SQL code will override the initial SQL code and this one will be used to fill in the table
    $sql_table =    "SELECT
                    CONCAT(mems.lname, ', ', mems.fname) AS 'Student Name',
                    sch.name AS 'Scholarship',
                    sch.amount AS 'Amount' 
                    FROM
                    members mems
                    JOIN scholarships_students schst ON (schst.student_id = mems.student_id) 
                    JOIN scholarships sch ON (schst.scholarship_id = sch.scholarship_id)
                    WHERE semester = :sem and year = :yr
                    ORDER BY year DESC, semester, amount DESC, lname "; 

    //prepare the new sql statement for the table based on user query
    $statement = $conn->prepare($sql_table);

    // execute (create) the result set
    $statement->execute([":yr" => "$year" , ":sem" => "$semester"]);

    // new row count for table
    $rowcount = $statement->rowCount();

    // just to test
    echo "Row count for new members table is " . $rowcount;        

} //end if to repopulate table sql
// & for server request/form submission check

?>
    <!DOCTYPE html>
    <!-- Mikaela Rhoads -->

    <html lang="en">

    <head>
        <meta charset="utf-8">
        <title>Display, Populate and Query the Database - 3</title>

        <style>
            body {
                font-family: arial, sans-serif;
                font-size: 100%;
            }

            h1 {
                text-align: center;
                font-size: 1.5em;
                margin-bottom: 20px;
            }

            .red {
                color: red;
            }

            td {
                border: 1px solid #000;
                padding: 10px;
                vertical-align: top;
                width: 33%;
            }

            th {
                background: #000;
                color: #fff;
                height: 20px;
                padding: 10px;
                font-size: 1.2em;
                width: 33%;
            }

            table {
                border-collapse: collapse;
                border: 2px solid #000;
                width: 600px;
                margin: 10px auto 50px auto;
            }

            tbody tr:nth-of-type(odd) {
                background: #eee;
            }

            #btn {
                text-decoration: none;
                color: #000;            
            }        

            [type=submit], #btn {
                margin-top: 25px;
                padding: 10px;
                width: 200px;
                border: none;
                border-radius: 5px;
                background-color: #d9d9d9;
                color: #000;
                font-size: 1.3em;
            }

            select,
            label,
            input {
                display: block;
            }


            select {
                background-color: #d9d9d9;
                color: #000;
                font-size: 1em;
                padding: 10px;
                width: 200px;
                margin-top: 10px;
                border: none;
            }

            option {
                background-color: #f5f5f5;
                color: #000;
                font-size: 1em;
                padding: 10px;
                border-bottom: 1px solid #000;
            }

            caption {
                margin-bottom: 20px;
            }

        </style>
   </head>

    <body>

        <header>
            <h1>Final Project: Displaying Data from the Database, Populating 'select' element, Query the Database</h1>
            <p></p>
        </header>

       <!-- place holder for messages -->
        <p class="red"><?php echo $formmsg ?></p>

  
<!-- GENERATE FORM AND TABLE USING PHP -->
<?php
      
    // check to make sure we have records returned for year DROP DOWN LIST (values from database)
    if ($rowcount2 != 0){
        
        // begin form
        echo "<form action='". $_SERVER['PHP_SELF'] . "' method='post'>\n\r";
        echo "<label for='year'>Select a Year:</label>\n\r";
        echo "<select name='year' id='year' required>\n\r";
        echo "<option value='none'>Make a Selection</option>\n\r";
        
        // output data of each row as associative array in result set
        $rows = $statement2->fetchAll();
    
        // foreach loop to create <option> elements by looping through the returned associative array
        // - note the positioning of the quotations
        foreach($rows as $row) {
            echo"<option value='" . $row["year"] . "'>" . $row["year"] . "</option>\n\r";         
        } // end foreach
        
        // end select
        echo "</select>\n\r";

        //code semester select to output with PHP (values are hard coded)
        echo "<label for='semester'>Select a Semester:</label>\n\r";
        echo "<select name='semester' id='semester' required>\n\r";
        echo "<option value='none'>Make a Selection</option>\n\r";
        echo "<option value='fall'>Fall</option>\n\r";
        echo "<option value='spring'>Spring</option>\n\r";
        // end select
        echo "</select>\n\r";
        //end form
        echo "<input type='submit' value='Display Scholarships'>\n\r";
        echo "</form>\n\r<br>\n\r\n\r";
        echo "<a id='btn' href=" . $_SERVER['PHP_SELF'] . ">Show All Scholarships</a>\n\r\n\r";  
        
    }  // end if for rowcount check for year drop down list      
    else {
        // message for no results
        echo "Sorry, there were no results";
    } // end else
                    

    // BEGINNING TABLE FOR QUERY

    //  CHECK TO SEE IF FORM IS NOT SUBMITTED, IF SO, DISPLAY BEGINNING TABLE
    if($_SERVER['REQUEST_METHOD'] != 'POST') {      
    // check to make sure we have records returned for TABLE
        if ($rowcount != 0){
            
            // hard code header row of table
            echo "<table>\n\r";  
            echo "<tr>\n\r"; 
            echo "<th>Student Name</th>\n\r"; 
            echo "<th>Scholarship</th>\n\r"; 
            echo "<th>Amount</th>\n\r"; 
            echo "<th>Semester</th>\n\r";  
            echo "<th>Year</th>\n\r"; 
            echo "</tr>\n\r\n\r"; 
            
            // output data of each row as associative array in result set
            $rows = $statement->fetchAll();

            // body of table 
            foreach($rows as $row) {
                echo "<tr>\n\r";
                echo "<td>" . $row["Student Name"] . "</td>\n\r";
                echo "<td>" . $row["Scholarship"] . "</td>\n\r";
                echo "<td>" . $row["Amount"] . "</td>\n\r";
                echo "<td>" . $row["Semester"] . "</td>\n\r";
                echo "<td>" . $row["Year"] . "</td>\n\r";
                echo "</tr>\n\r\n\r";         
            } // end foreach
                
            // end table
            echo "</table>\n\r\n\r";
        
        }  // end if for rowcount check for data table
        
        else {
            // message for no results
            echo "Sorry, there were no results\n\r";
        } // end else
    } // END CHECK FOR $_SERVER['REQUEST_METHOD']

                
    // NEW TABLE FOR QUERY
    //  CHECK TO SEE IF FORM IS SUBMITTED, IF SO, DISPLAY NEW TABLE BASED ON SELECTION
                
    if($_SERVER['REQUEST_METHOD'] == 'POST') {             
                    
    // check to make sure we have records returned for TABLE
        if ($rowcount != 0){
            
            // hard code header row of table 
            //add caption and use the semester and year variables to display the results dynamically, 
            //those columns are removed
            echo "<table>\n\r";  
            echo "<caption>Displaying " . $rowcount . " scholarships for " . $semester . " " . $year . "</caption>";
            echo "<tr>\n\r"; 
            echo "<th>Student Name</th>\n\r"; 
            echo "<th>Scholarship</th>\n\r"; 
            echo "<th>Amount</th>\n\r"; 
            echo "</tr>\n\r\n\r"; 
            
            // output data of each row as associative array in result set
            $rows = $statement->fetchAll();
        
            // body of table 
            foreach($rows as $row) {
                echo "<tr>\n\r";
                echo "<td>" . $row["Student Name"] . "</td>\n\r";
                echo "<td>" . $row["Scholarship"] . "</td>\n\r";
                echo "<td>" . $row["Amount"] . "</td>\n\r";
                echo "</tr>\n\r\n\r";           
            } // end foreach
                
            // end table
            echo "</table>\n\r\n\r";
            
        }  // end if for rowcount check for data table
        
        else {
            // message for no results
            echo "Sorry, there were no results for " . $semester . " " . $year . "\n\r";
        } // end else
        
    } // END CHECK FOR $_SERVER['REQUEST_METHOD']
                
        
    // close the connection
    $conn = null;        

?>

</body>

</html>
