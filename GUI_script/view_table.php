<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="style.css">
</head>

<body>
<?php 


	session_start();

	if(empty($_SESSION['loginID']))
		header("Location: login.php");

	require('DBconnect.php');
	$SponsID=$_SESSION['loginID']; //get SponsID from previos session

	$UnauthorizedMessage = '<div align="center"><h3 align="center" style="padding: 40px; font-size:28px; line-height:50px;"  class="invalid_message">Sorry, you are not permitted to run this query.</h3> </div>';
	$FieldEmptyMessage='<div align=center><h3 align=center style="padding: 40px; font-size:28px; line-height:50px;"  class="invalid_message">Error<br>You have not filled all the required fields.</h3> </div>';

	$SponsRepBackButton="<h2><a href='sponsrep.php' class='back_button'>Go back</a></h2><br>";
	$SectorHeadBackButton="<h2><a href='sectorhead.php' class='back_button'>Go back</a></h2><br>";
	$CSOBackButton="<h2><a href='CSO.php' class='back_button'>Go back</a></h2><br>";
		

	
	function get_person_name($SponsID){
			require('DBconnect.php'); //this is needed in every function that uses mySQL
			$rep_name=mysql_query("SELECT Name FROM CommitteeMember WHERE StudID = $SponsID" );
			$rep_name=mysql_fetch_assoc($rep_name);
			$rep_name = $rep_name["Name"];
			return $rep_name;
		}

	function get_access_level($SponsID){
		require('DBconnect.php'); //this is needed in every function that uses mySQL
		$rep_access_level=mysql_query("SELECT AccessLevel FROM SponsLogin WHERE SponsID = $SponsID" );
		$rep_access_level=mysql_fetch_assoc($rep_access_level);
		$rep_access_level = $rep_access_level["AccessLevel"];
		return $rep_access_level;	
	}

	function get_person_sector($SponsID){
		require('DBconnect.php'); //this is needed in every function that uses mySQL
		$rep_access_level=mysql_query("SELECT Sector FROM SponsRep WHERE SponsID = $SponsID" );
		if(mysql_num_rows($rep_access_level) == 0)//i.e. you don't find the person with that SponsID in the SponsRep table.
			$rep_access_level=mysql_query("SELECT Sector FROM SectorHead WHERE SponsID = $SponsID" ); //look in the SectorHead table.

		$rep_access_level=mysql_fetch_assoc($rep_access_level);
		$rep_access_level = $rep_access_level["Sector"];
		return $rep_access_level;	
	}

	function print_table($result){ //array of arritubtes and corresponding sql result we get from querying the attributes
			echo '<div align="center">';
			echo '<table align="center" style=\"width:100%\" class="output_table">';
			echo "<tr>";
			$i=0;
			//$attributes_info = mysql_fetch_field($result, $i); //gets a lot of data about the attributes...their names, their types etc.
			while($i < mysql_num_fields($result)) {
				$attr=mysql_fetch_field($result, $i); 
				echo "<th>".$attr->name."</th>";
				$i++;
			}

			while($row=mysql_fetch_assoc($result)){
				echo '<tr>';
				foreach ($row as $key => $value) {
					echo '<td>'.$value.'</td>';
				}
				echo "</tr>";
			}
			echo "</table>";
			echo '</div>';
	}
	
	function print_sort($result){ 
		echo '<form action="sort_search_table.php" class="sort_form" method="post" align="center">';
			echo 'Sort by:<select name="order_by">';
			$i=0;

			while($i < mysql_num_fields($result)) {
				$attr=mysql_fetch_field($result, $i); 
				echo "<option>".$attr->name."</option>";
				$i++;
			}
			echo '</select> ';

			echo '<button type="submit" name="submit">Sort</button>';
		echo '</form>';
	}


	function print_search($result){ 
		echo '<form action="sort_search_table.php" class="search_form" method="post" align="center">';
		
			echo 'Search by:<select name="search_by">';
			$i=0;

			while($i < mysql_num_fields($result)) {
				$attr=mysql_fetch_field($result, $i); 
				echo "<option>".$attr->name."</option>";
				$i++;
			}
			echo '</select> ';
			echo '<input type="text" name="search_field">';

			echo '<button type="submit" name="submit">Search</button>';
		echo '</form>';
	}
	

	
	$SponsAccessLevel = get_access_level($SponsID);
	$SponsName = get_person_name($SponsID);

	$SponsSector="";
	if($SponsAccessLevel!="CSO")
		$SponsSector = get_person_sector($SponsID);

	
	$query_type=$_SESSION['query_type'];
	$table_name=$_SESSION['table_name'];


	echo '<header align="center">
			<h1>Sponsorship Department</h1>';

		
	if($SponsAccessLevel=="SectorHead")
			echo $SectorHeadBackButton;

	if($SponsAccessLevel=="SponsRep")
		echo $SponsRepBackButton;

	if($SponsAccessLevel == "CSO")
		echo $CSOBackButton;

	

	echo '</header>';

	echo '<div align="center">';



	$meeting_view_query = "SELECT Name as 'SponsRep Name',CMPName as 'Company Name', CEName as 'Company Executive Name', MeetingType as 'Meeting Type', Date, Time, Address, Outcome 

		FROM ((Select SponsID, Sector from SponsRep) UNION (Select SponsID, Sector from SectorHead)) as SponsOfficer
		natural join Meeting 
		natural join CommitteeMember 

		where SponsOfficer.SponsID = StudID 
		and Sector='$SponsSector'
		";	//meeting_view_query is common for both SponsRep and SectorHead

	$CSOmeeting_view_query = "SELECT Name as 'SponsRep Name',Sector, CMPName as 'Company Name', CEName as 'Company Executive Name', MeetingType as 'Meeting Type', Date, Time, Address, Outcome 

				FROM ((Select SponsID, Sector from SponsRep) UNION (Select SponsID, Sector from SectorHead)) as SponsOfficer
				natural join Meeting 
				natural join CommitteeMember 
                where Meeting.SponsID=StudID and SponsOfficer.SponsID=StudID
				";


	$Company_view_query="SELECT CMPName as 'Company Name', CMPStatus as 'Status', CMPAddress as 'Company Address'  
		FROM Company 
		WHERE Sector = '$SponsSector'"; //Company_view_query is common for both SponsRep and SectorHead

	$CSOCompany_view_query="SELECT CMPName as 'Company Name', Sector, CMPStatus as 'Status', CMPAddress as 'Company Address'  
				FROM Company";

	$CSOCompanyExec_view_query ="SELECT  CMPName as 'Company Name', Sector, CEName as 'Executive Name', CEMobile as 'Mobile', CEEmail as 'Email', CEPosition as 'Position in Company'  
				from Company natural join CompanyExec"; 
 

	$CompanyExec_view_query ="SELECT  CMPName as 'Company Name', CEName as 'Executive Name', CEMobile as 'Mobile', CEEmail as 'Email', CEPosition as 'Position in Company'  
		
		from Company natural join CompanyExec 
		
		where sector = '$SponsSector' "; //CompanyExec_view_query is common for both SponsRep and SectorHead


			

	$FestivalAccount_SponsRep_view_query="SELECT SponsID, Name, Title as 'Company Name', Date, Amount as 'Amount (Rs.)' from AccountLog natural join CommitteeMember natural join SponsRep where sector='$SponsSector' and Studid=SponsId and StudID=$SponsID ";



	$FestivalAccount_SectorHead_view_query="SELECT SponsID, Name, Title as 'Company Name', Date, Amount as 'Amount (Rs.)' from AccountLog natural join CommitteeMember natural join SponsRep where sector='$SponsSector' and Studid=SponsID";


	$FestivalAccount_CSO_view_query="SELECT SponsID, Name, Sector, Title as 'Company Name',Date, Amount as 'Amount (Rs.)' from AccountLog natural join CommitteeMember natural join SponsRep where Studid=SponsID";


	$SponsRep_view_query="SELECT SponsID, Name, DateAssigned, Mobile, Email 
	from SponsRep, CommitteeMember 
	where StudID=SponsID and Sector = '$SponsSector'";


	$CSOSponsRep_view_query="SELECT SponsID, Name, Sector, DateAssigned, Mobile, Email 
					from SponsRep, CommitteeMember 
					where StudID=SponsID ";

	$CSOSectorHead_view_query="SELECT SponsID, Name, Sector as `Head of:`, Mobile, Email 
					from SectorHead, CommitteeMember 
					where StudID=SponsID ";
			

	$result="";
	$main_query="";
	$table_message="";


		if($table_name=="Meeting Log"){ 
			if($SponsAccessLevel!="CSO"){



				if(isset($_POST['submit'])){
					$required = array('Date','Time','CMPName','CEName'); //also require SponsID, but we get that from php

					foreach($required as $field) {
					  if (empty($_POST[$field])){
						exit($FieldEmptyMessage);
					  }
					}
					
					 

					$MeetDate=$_POST['Date'];
					$MeetTime=$_POST['Time']; 
					$MeetCMPName=$_POST['CMPName'];	
					$MeetCEName=$_POST['CEName'];	
					$MeetOutcome="";	


					if($query_type=="Insert"){//outcome is by default (Update after Meeting)
						
						$MeetType=Null; $MeetAddress=Null;
						if(!empty($_POST['MeetingType']))
							$MeetType=$_POST['MeetingType'];
						if(!empty($_POST['Address']))
							$MeetAddress=$_POST['Address'];	

						$query="INSERT INTO `Meeting` (`Date`, `Time`, `SponsID`, `MeetingType`, `CEName`, `CMPName`, `Outcome`, `Address`) 
							VALUES ('$MeetDate', '$MeetTime', $SponsID, '$MeetType', '$MeetCEName', '$MeetCMPName', 
								'(Update after meeting)', '$MeetAddress');";
							if(mysql_query($query ))
								echo "Insertion successful";
							else echo"Insertion not successful";
					}

					else if ($query_type=="Update"){
						if(!empty($_POST['Outcome']))
							$MeetOutcome=$_POST['Outcome'];	
						else exit($FieldEmptyMessage);
						$meeting_update_query="UPDATE Meeting 
						SET Outcome='$MeetOutcome'
						WHERE Date='$MeetDate' and Time='$MeetTime' and CMPName='$MeetCMPName' and CEName='$MeetCEName';";
						if(mysql_query($meeting_update_query ))
							echo "Meeting Update successful";
							else echo"Meeting Update not successful";


					}
					else if($query_type="Delete"){
						
						
						$query = "DELETE FROM Meeting WHERE CMPName = '$MeetCMPName' and SponsID='$SponsID' and CEname = '$MeetCEName' and Date = '$MeetDate' and Time='$MeetTime'";
						if(mysql_query($query ));
						// 	echo "Meeting Delete successful";
						// else echo"Meeting Delete not successful";


					}


				}
				$table_message= "<h2>Meetings in ".$SponsSector." sector:</h2>";
				echo $table_message;
				$result = mysql_query($meeting_view_query );
				
				$main_query=$meeting_view_query;
			}
			else {
				if(isset($_POST['submit'])){
						$required = array('Date','Time','CMPName','CEName'); //also require SponsID, but we get that from php

						foreach($required as $field) 
						{
						  if (empty($_POST[$field]))
						  {
							exit($FieldEmptyMessage);
						  }
						}
						
						 

						$MeetDate=$_POST['Date'];
						$MeetTime=$_POST['Time']; 
						$MeetCMPName=$_POST['CMPName'];	
						$MeetCEName=$_POST['CEName'];	
						$MeetOutcome="";	
						$SponsIDForm=$_POST['SponsID'];

						if($query_type=="Insert")
						{//outcome is by default (Update after Meeting)
							
							$MeetType=Null; $MeetAddress=Null;
							if(!empty($_POST['MeetingType']))
								$MeetType=$_POST['MeetingType'];
							if(!empty($_POST['Address']))
								$MeetAddress=$_POST['Address'];	
							
							$query="INSERT INTO `Meeting` (`Date`, `Time`, `SponsID`, `MeetingType`, `CEName`, `CMPName`, `Outcome`, `Address`) 
								VALUES ('$MeetDate', '$MeetTime', $SponsIDForm, '$MeetType', '$MeetCEName', '$MeetCMPName', 
									'(Update after meeting)', '$MeetAddress');";
								if(mysql_query($query ))
									echo "Insertion successful";
								else echo"Insertion not successful";
						}

						else if ($query_type=="Update")
						{
							if(!empty($_POST['Outcome']))
								$MeetOutcome=$_POST['Outcome'];	
							else exit($FieldEmptyMessage);
							$meeting_update_query="UPDATE Meeting 
							SET Outcome='$MeetOutcome'
							WHERE Date='$MeetDate' and Time='$MeetTime' and CMPName='$MeetCMPName' and CEName='$MeetCEName';";
							if(mysql_query($meeting_update_query ))
								echo "Meeting Update successful";
								else echo"Meeting Update not successful";


						}
						else if($query_type="Delete")
						{
								
							$query = "DELETE FROM Meeting WHERE CMPName = '$MeetCMPName' and SponsID='$SponsIDForm' and CEname = '$MeetCEName' and Date = '$MeetDate' and Time='$MeetTime'";
							if(mysql_query($query ));
								//echo "Meeting Delete successful";
							//else echo"Meeting Delete not successful";


						}


				}
				$table_message= "<h2>Meetings Log:</h2>";
				echo $table_message;
				$result = mysql_query($CSOmeeting_view_query );
				
				$main_query=$CSOmeeting_view_query;
			}

		}



		else if ($table_name=="Company"){
			if($SponsAccessLevel!="CSO"){
				if(isset($_POST['submit'])){

					$required = array('CMPName');

					foreach($required as $field) {
					  if (empty($_POST[$field])){
					  	echo $field;
						exit($FieldEmptyMessage);	  	
					  }
					}

					
					$CMPName=$_POST['CMPName']; 	
					$CMPAddress="null";

					if($query_type=="Insert"){

						if(!empty($_POST['CMPAddress']))
							$CMPAddress=$_POST['CMPAddress'];
						

						$query = "INSERT INTO `Company` (`CMPName`, `CMPStatus`, `Sector`, `CMPAddress`) VALUES
									('$CMPName', 'Not called', '$SponsSector', '$CMPAddress');";
						if(mysql_query($query ))
							echo "Successfully Inserted";
						else echo"Insertion Unsuccessful";
					}


					else if ($query_type=="Update"){
						$CMPAddress="";
						$CMPStatus="";
						if(!empty($_POST['CMPAddress'])){
							$CMPAddress=$_POST['CMPAddress'];
							if(mysql_query("UPDATE Company SET CMPAddress='$CMPAddress' WHERE CMPName='$CMPName'" ))
								echo "Company address update successful";
							else echo "Company address update not successful";
						}

						if(!empty($_POST['CMPStatus'])){
							$CMPStatus=$_POST['CMPStatus'];
							if(mysql_query("UPDATE Company SET CMPStatus='$CMPStatus' WHERE CMPName='$CMPName'" ))
								echo "Company status update successful";
							else echo "Company status update not successful";
						}

						
					}

					else if($query_type="Delete"){
						$CMPName=$_POST['CMPName'];
						
						$query = "DELETE FROM Company WHERE CMPName = '$CMPName' and Sector = '$SponsSector'";
						if(mysql_query($query ));
						// 	echo "Company Deletion successful";
						// else echo"Company Deletion not successful";
					}
				}
			
				$table_message= "<h2>Companies of ".$SponsSector." sector:</h2>";
				echo $table_message;
				$result = mysql_query($Company_view_query );
				
				$main_query=$Company_view_query;
			}
			


			else  {

				if(isset($_POST['submit'])){

					$required = array('CMPName');

					foreach($required as $field) {
					  if (empty($_POST[$field])){
						echo $field;
						exit($FieldEmptyMessage);	  	
					  }
					}

					
					$CMPName=$_POST['CMPName']; 	
					$CMPAddress="null";
					if($query_type=="Insert"){

						if(!empty($_POST['CMPAddress']))
							$CMPAddress=$_POST['CMPAddress'];
						

						$query = "INSERT INTO `Company` (`CMPName`, `CMPStatus`, `Sector`, `CMPAddress`) VALUES
									('$CMPName', 'Not called', '$SponsSector', '$CMPAddress');";
						if(mysql_query($query ))
							echo "Successfully Inserted";
						else echo"Insertion Unsuccessful";
					}


					else if ($query_type=="Update"){
						$CMPAddress="";
						$CMPStatus="";
						if(!empty($_POST['CMPAddress'])){
							$CMPAddress=$_POST['CMPAddress'];
							if(mysql_query("UPDATE Company SET CMPAddress='$CMPAddress' WHERE CMPName='$CMPName'" ))
								echo "Company address update successful";
							else echo "Company address update not successful";
						}

						if(!empty($_POST['CMPStatus'])){
							$CMPStatus=$_POST['CMPStatus'];
							if(mysql_query("UPDATE Company SET CMPStatus='$CMPStatus' WHERE CMPName='$CMPName'" ))
								echo "Company status update successful";
							else echo "Company status update not successful";
						}

						
					}

					else if($query_type="Delete"){
						$CMPName=$_POST['CMPName'];
						
						$query = "DELETE FROM Company WHERE CMPName = '$CMPName' and Sector = '$SponsSector'";
						if(mysql_query($query ));
							//echo "Company Deletion successful";
						//else echo"Company Deletion not successful";
					}
				}
				

				$table_message= "<h2>Companies of".$SponsSector.":</h2>";
				echo $table_message;
				$result = mysql_query($CSOCompany_view_query );
				
				$main_query=$CSOCompany_view_query;
			
			}
		}
		


		else if ($table_name=="Company Executive"){
			if($SponsAccessLevel!="CSO"){	
				if(isset($_POST['submit'])){

					$required = array('CEName','CMPName');

					foreach($required as $field) {
					  if (empty($_POST[$field])){
						exit($FieldEmptyMessage);	  	
					  }
					}


					$CEName=$_POST['CEName']; 
					$CMPName=$_POST['CMPName']; 
					$CEMobile="";
					$CEEmail="";
					$CEPosition="";


					if($query_type=="Insert"){
						if(!empty($_POST['CEMobile']))				
							$CEMobile=$_POST['CEMobile'];
						if(!empty($_POST['CEEmail']))
							$CEEmail=$_POST['CEEmail'];
						if(!empty($_POST['CEPosition']))
							$CEPosition=$_POST['CEPosition'];	
						$CE_insert_query = "INSERT INTO `CompanyExec` (`CEName`,`CMPName`, `CEMobile`, `CEEmail`, `CEPosition`) VALUES
										('$CEName', '$CMPName', '$CEMobile', '$CEEmail','$CEPosition');";
							if(mysql_query($CE_insert_query ))
								echo "Successfully Inserted into CompanyExec";
							else echo"Insertion Unsuccessful into Company Exec";


						
					}

					else if ($query_type=="Update"){
						if(!empty($_POST['CEEmail'])){
							$CEEmail=$_POST['CEEmail'];
							if(mysql_query("UPDATE CompanyExec SET CEEmail='$CEEmail' where  CMPName='$CMPName' and CEName='$CEName'" ))
								echo "Company EXEC Email update successful";
							else echo "Company EXEC Email update not successful";
						}

						if(!empty($_POST['CEMobile'])){
							$CEMobile=$_POST['CEMobile'];
							if(mysql_query("UPDATE CompanyExec SET CEMobile='$CEMobile' where  CMPName='$CMPName' and CEName='$CEName'" ))
								echo "Company EXEC Mobile update successful";
							else echo "Company EXEC Mobile update not successful";
						}


						if(!empty($_POST['CEPosition'])){
							$CEPosition=$_POST['CEPosition'];
							if(mysql_query("UPDATE CompanyExec SET CEPosition='$CEPosition' where  CMPName='$CMPName' and CEName='$CEName'" ))
								echo "Company EXEC Position update successful";
							else echo "Company EXEC Position update not successful";
						}

					}

					else if($query_type="Delete"){
						
						$query = "DELETE FROM CompanyExec WHERE CMPName = '$CMPName' and CEName = '$CEName' ";
						if(mysql_query($query ));
						// 	echo "Query successful";
						// else echo"Query not successful";
					}
					
				}

				$table_message= "<h2>Company Executives of ".$SponsSector." sector:</h2>";
				echo $table_message;
				$result = mysql_query($CompanyExec_view_query );
				
				$main_query=$CompanyExec_view_query;
			}

			else{
				if(isset($_POST['submit'])){

					$required = array('CEName','CMPName');

					foreach($required as $field) {
					  if (empty($_POST[$field])){
						exit($FieldEmptyMessage);	  	
					  }
					}


					$CEName=$_POST['CEName']; 
					$CMPName=$_POST['CMPName']; 
					$CEMobile="";
					$CEEmail="";
					$CEPosition="";


					if($query_type=="Insert"){
						if(!empty($_POST['CEMobile']))				
							$CEMobile=$_POST['CEMobile'];
						if(!empty($_POST['CEEmail']))
							$CEEmail=$_POST['CEEmail'];
						if(!empty($_POST['CEPosition']))
							$CEPosition=$_POST['CEPosition'];	
						$CE_insert_query = "INSERT INTO `CompanyExec` (`CEName`,`CMPName`, `CEMobile`, `CEEmail`, `CEPosition`) VALUES
										('$CEName', '$CMPName', '$CEMobile', '$CEEmail','$CEPosition');";
							if(mysql_query($CE_insert_query ))
								echo "Successfully Inserted into CompanyExec";
							else echo"Insertion Unsuccessful into Company Exec";


						
					}

					else if ($query_type=="Update"){
						if(!empty($_POST['CEEmail'])){
							$CEEmail=$_POST['CEEmail'];
							if(mysql_query("UPDATE CompanyExec SET CEEmail='$CEEmail' where  CMPName='$CMPName' and CEName='$CEName'" ))
								echo "Company EXEC Email update successful";
							else echo "Company EXEC Email update not successful";
						}

						if(!empty($_POST['CEMobile'])){
							$CEMobile=$_POST['CEMobile'];
							if(mysql_query("UPDATE CompanyExec SET CEMobile='$CEMobile' where  CMPName='$CMPName' and CEName='$CEName'" ))
								echo "Company EXEC Mobile update successful";
							else echo "Company EXEC Mobile update not successful";
						}


						if(!empty($_POST['CEPosition'])){
							$CEPosition=$_POST['CEPosition'];
							if(mysql_query("UPDATE CompanyExec SET CEPosition='$CEPosition' where  CMPName='$CMPName' and CEName='$CEName'" ))
								echo "Company EXEC Position update successful";
							else echo "Company EXEC Position update not successful";
						}

					}

					else if($query_type="Delete"){
						
						$query = "DELETE FROM CompanyExec WHERE CMPName = '$CMPName' and CEName = '$CEName' ";
						if(mysql_query($query ));
						// 	echo "Query successful";
						// else echo"Query not successful";
					}
					
				}

				$table_message= "<h2>Company Executives:</h2>";
				echo $table_message;
				$result = mysql_query($CSOCompanyExec_view_query);
				
				$main_query=$CSOCompanyExec_view_query;
				
			}	

		}



		else if ($table_name=="Festival Account"){
			if($SponsAccessLevel!="CSO"){
			if($SponsAccessLevel == "SponsRep"){
				if(isset($_POST['submit'])){

					$required = array('Title','Amount', 'Date');

					foreach($required as $field) {
					  if (empty($_POST[$field])){
						exit($FieldEmptyMessage);	  	
					  }
					}

					
					$AccountTitle=$_POST['Title']; 
					$AccountDate=$_POST['Date'];
					$AccountAmount=$_POST['Amount'];	

					if($query_type=="Insert"){
						$query = "INSERT INTO `AccountLog` (`Title`,`SponsID`, `Amount`, `TransType`, `Date`) VALUES
										('$AccountTitle', '$SponsID', '$AccountAmount', 'Deposit','$AccountDate');";
						if(mysql_query($query ))
							echo "Successfully inserted account entry";
						else echo"Unsuccessful account entry insertion";
						
					}

				}
				
				$table_message= "<h2>Account of ".$SponsName." from ".$SponsSector." sector:</h2>";
				echo $table_message;
				$result = mysql_query($FestivalAccount_SponsRep_view_query );
				
				$main_query=$FestivalAccount_SponsRep_view_query;
			}


			else if ($SponsAccessLevel == "SectorHead"){
				if(isset($_POST['submit'])){

					if($query_type=="Insert"){

						$required = array('Title','Amount', 'Date');

						foreach($required as $field) {
						  if (empty($_POST[$field])){
							exit($FieldEmptyMessage);	  	
						  }
						}

						
						$AccountTitle=$_POST['Title']; 
						$AccountDate=$_POST['Date'];
						$AccountAmount=$_POST['Amount'];	

						$query = "INSERT INTO `AccountLog` (`Title`,`SponsID`, `Amount`, `TransType`, `Date`) VALUES
										('$AccountTitle', '$SponsID', '$AccountAmount', 'Deposit','$AccountDate');";
						if(mysql_query( $query ))
							echo "Successfully inserted account entry";
						else echo"Unsuccessful account entry insertion";
						
					}
					else if($query_type=="Delete"){
						$required = array('Title','SponsID');

						foreach($required as $field) {
						  if (empty($_POST[$field])){
							exit($FieldEmptyMessage);	  	
						  }
						}

						$AccountTitle=$_POST['Title']; 
						$SponsIDForm=$_POST['SponsID']; 

						$query = "DELETE FROM AccountLog WHERE Title = '$AccountTitle' and SponsID = '$SponsIDForm' ";
						if(mysql_query($query ));
						// 	echo "Account entry deletion successful";
						// else echo"Account entry deletion not successful";
					}
				}

				$table_message= "<h2>Account of ".$SponsSector." sector:</h2>";
				echo $table_message;
				$result = mysql_query($FestivalAccount_SectorHead_view_query );
				
				$main_query=$FestivalAccount_SectorHead_view_query;
				
			}
			}
			
			else{
				if(isset($_POST['submit'])){

						if($query_type=="Insert"){

							$required = array('Title','Amount', 'Date');

							foreach($required as $field) {
							  if (empty($_POST[$field])){
								exit($FieldEmptyMessage);	  	
							  }
							}

							
							$AccountTitle=$_POST['Title']; 
							$AccountDate=$_POST['Date'];
							$AccountAmount=$_POST['Amount'];	

							$query = "INSERT INTO `AccountLog` (`Title`,`SponsID`, `Amount`, `TransType`, `Date`) VALUES
											('$AccountTitle', '$SponsID', '$AccountAmount', 'Deposit','$AccountDate');";
							if(mysql_query( $query ))
								echo "Successfully inserted account entry";
							else echo"Unsuccessful account entry insertion";
							
						}
						else if($query_type=="Delete"){
							$required = array('Title','SponsID');

							foreach($required as $field) {
							  if (empty($_POST[$field])){
								exit($FieldEmptyMessage);	  	
							  }
							}

							$AccountTitle=$_POST['Title']; 
							$SponsIDForm=$_POST['SponsID']; 

							$query = "DELETE FROM AccountLog WHERE Title = '$AccountTitle' and SponsID = '$SponsIDForm' ";
							if(mysql_query($query ));
							// 	echo "Account entry deletion successful";
							// else echo"Account entry deletion not successful";
						}
				}

				$table_message= "<h2>Account Log:</h2>";
				echo $table_message;
				$result = mysql_query($FestivalAccount_CSO_view_query );
				
				$main_query=$FestivalAccount_CSO_view_query;
			}

		}

		
		else if($table_name=="Sponsorship Representative"){
			if($SponsAccessLevel=="SectorHead"){

				if(isset($_POST['submit'])){

				$required = array('SponsID');

				foreach($required as $field) {
				  if (empty($_POST[$field])){
					exit($FieldEmptyMessage);	  	
				  }
				}

				$SponsIDForm=$_POST['SponsID']; 
				
				/*


				if($query_type=="Insert"){
					
					$query = "INSERT INTO `SponsRep` (`SponsID`,`Sector`, DateAssigned) VALUES
									('$SponsIDForm', '$SponsSector', CURDATE());";
						if(mysql_query($query ))
							echo "Successfully added SponsRep";
						else echo"Insertion of SponsRep Unsuccessful";
				}*/


				if($query_type=="Update"){
					$required = array('Sector');

					foreach($required as $field) {
					  if (empty($_POST[$field])){
						exit($FieldEmptyMessage);	  	
					  }
					}
					$SponsSectorForm=$_POST['Sector'];
					
					
					if(!mysql_query("UPDATE SponsRep SET Sector='$SponsSectorForm', DateAssigned=CURDATE() where SponsID='$SponsIDForm' and Sector='$SponsSector'"))
					// 	echo "SponsRep update successful";
						echo $UnauthorizedMessage;
					
					
				}
				

				else if($query_type="Delete"){
					if(!mysql_query("DELETE FROM SponsRep WHERE SponsID = '$SponsIDForm' and Sector = '$SponsSector'"))
						// 	echo "Successfully Deleted SponsRep";
				  		echo $UnauthorizedMessage;

				}
				
				}

			
				$table_message= "<h2>Details of SponsReps from ".$SponsSector." sector:</h2>";
				echo $table_message;
				$result = mysql_query($SponsRep_view_query );
				
				$main_query=$SponsRep_view_query;
			}

			else if($SponsAccessLevel=="CSO"){
					
						if(isset($_POST['submit'])){


							if($query_type=="Insert"){
							
								$required = array('SponsIDForm', 'SponsName', 'SponsPasswordForm', 'SponsSectorForm');

								foreach($required as $field)
								{
								  if (empty($_POST[$field]))
								  {
									exit($FieldEmptyMessage);	  	
								  }
								}

								$SponsIDForm=$_POST['SponsIDForm'];
								$SponsName=$_POST['SponsName'];
								$SponsPasswordForm =$_POST['SponsPasswordForm'];
								$SponsSectorForm=$_POST['SponsSectorForm'];
									$SponsEmail="";
									$SponsMobile="";
									$SponsYear="";
									$SponsBranch="";
									if(!empty($_POST['Email']))
										$SponsEmail=$_POST['Email'];
									if(!empty($_POST['Mobile']))
										$SponsMobile=$_POST['Mobile'];
									if(!empty($_POST['Year']))
										$SponsYear=$_POST['Year'];
									if(!empty($_POST['Branch']))
										$SponsBranch=$_POST['Branch'];
									
									$query = "INSERT INTO `SponsRep` (`SponsID`,`Sector`, `DateAssigned`) VALUES
													($SponsIDForm, '$SponsSectorForm', CURDATE());";
										if(mysql_query($query ));
										// {echo "Successfully added SponsRep";}
										// else echo"Insertion of SponsRep Unsuccessful";
									$query = "INSERT INTO `CommitteeMember` (`StudID`,`Name`,`Dept`,`Role`,`Mobile`,`Email`,`Year`,`Branch`) VALUES
													($SponsIDForm, '$SponsName', 'Sponsorship', 'SponsRep', $SponsMobile, '$SponsEmail', '$SponsYear', '$SponsBranch');";
										if(mysql_query($query ));
									$query = "INSERT INTO `SponsLogin` (`SponsID`,`Password`, `AccessLevel`) VALUES
													($SponsIDForm, '$SponsPasswordForm', 'SponsRep');";
										if(mysql_query($query));


							}


							else if($query_type=="Update"){
								$required = array('SponsIDForm');

								foreach($required as $field) {
								  if (empty($_POST[$field])){
									exit($FieldEmptyMessage);	  	
								  }
								}
								$SponsIDForm=$_POST['SponsIDForm']; 
								$SponsSectorForm="";
								$SponsPasswordForm="";
								if(!empty($_POST['SponsSectorForm'])){
									$SponsSectorForm=$_POST['SponsSectorForm'];
								if(mysql_query("UPDATE SponsRep SET Sector='$SponsSectorForm', DateAssigned=CURDATE() where SponsID='$SponsIDForm' "));
									
								}
								if(!empty($_POST['SponsPasswordForm'])){
									$SponsPasswordForm=$_POST['SponsPasswordForm'];
									if(mysql_query("UPDATE SponsLogin SET Password='$SponsPasswordForm' where SponsID='$SponsIDForm' "));
									
								}
								
								 	//echo "SponsRep update successful";
									//echo $UnauthorizedMessage;
								
								
							}
							

							else if($query_type="Delete")
							{
								$required = array('SponsIDForm');

								foreach($required as $field) {
								  if (empty($_POST[$field])){
									exit($FieldEmptyMessage);	  	
								  }
								}
								$SponsIDForm=$_POST['SponsIDForm']; 
								if(mysql_query("DELETE FROM SponsRep WHERE SponsID = '$SponsIDForm'"));
								//{echo "Successfully Deleted SponsRep";}
									//echo $UnauthorizedMessage;

							}
						}
										
						$table_message= "<h2>Details of all  Spons Reps:</h2>";
						echo $table_message;
						$result = mysql_query($CSOSponsRep_view_query);
						
						$main_query=$CSOSponsRep_view_query;
							
						
			}	

		}

		else if($table_name=="Sector Head"){
			if($SponsAccessLevel=="CSO"){
					
						if(isset($_POST['submit'])){


							if($query_type=="Insert"){
							
								$required = array('SponsIDForm', 'SponsName', 'SponsPasswordForm', 'SponsSectorForm');

								foreach($required as $field)
								{
								  if (empty($_POST[$field]))
								  {
									exit($FieldEmptyMessage);	  	
								  }
								}

								$SponsIDForm=$_POST['SponsIDForm'];
								$SponsName=$_POST['SponsName'];
								$SponsPasswordForm =$_POST['SponsPasswordForm'];
								$SponsSectorForm=$_POST['SponsSectorForm'];
									$SponsEmail="";
									$SponsMobile="";
									$SponsYear="";
									$SponsBranch="";
									if(!empty($_POST['Email']))
										$SponsEmail=$_POST['Email'];
									if(!empty($_POST['Mobile']))
										$SponsMobile=$_POST['Mobile'];
									if(!empty($_POST['Year']))
										$SponsYear=$_POST['Year'];
									if(!empty($_POST['Branch']))
										$SponsBranch=$_POST['Branch'];
									
									$query = "INSERT INTO `SectorHead` (`SponsID`,`Sector`, `DateAssigned`) VALUES
													($SponsIDForm, '$SponsSectorForm', CURDATE());";
										if(mysql_query($query ));
										// {echo "Successfully added SectorHead";}
										// else echo"Insertion of SectorHead Unsuccessful";
									$query = "INSERT INTO `CommitteeMember` (`StudID`,`Name`,`Dept`,`Role`,`Mobile`,`Email`,`Year`,`Branch`) VALUES ($SponsIDForm, '$SponsName', 'Sponsorship', 'SectorHead', $SponsMobile, '$SponsEmail', '$SponsYear', '$SponsBranch');";
										if(mysql_query($query));

									$query = "INSERT INTO `SponsLogin` (`SponsID`,`Password`, `AccessLevel`) VALUES
												($SponsIDForm, '$SponsPasswordForm', 'SectorHead');";
										if(mysql_query($query ));


							}


							else if($query_type=="Update"){
								$required = array('SponsIDForm');

								foreach($required as $field) {
								  if (empty($_POST[$field])){
									exit($FieldEmptyMessage);	  	
								  }
								}
								$SponsIDForm=$_POST['SponsIDForm']; 
								$SponsSectorForm="";
								$SponsPasswordForm="";
								if(!empty($_POST['SponsSectorForm'])){
									$SponsSectorForm=$_POST['SponsSectorForm'];
								if(mysql_query("UPDATE SectorHead SET Sector='$SponsSectorForm' where SponsID='$SponsIDForm' "));
									
								}
								if(!empty($_POST['SponsPasswordForm'])){
									$SponsPasswordForm=$_POST['SponsPasswordForm'];
									if(mysql_query("UPDATE SponsLogin SET Password='$SponsPasswordForm' where SponsID='$SponsIDForm' "));
									
								}
								
								 	//echo "SectorHead update successful";
									//echo $UnauthorizedMessage;
								
								
							}
							

							else if($query_type="Delete")
							{
								$required = array('SponsIDForm');

								foreach($required as $field) {
								  if (empty($_POST[$field])){
									exit($FieldEmptyMessage);	  	
								  }
								}
								$SponsIDForm=$_POST['SponsIDForm']; 
								if(mysql_query("DELETE FROM SectorHead WHERE SponsID = '$SponsIDForm'"));
								//{echo "Successfully Deleted SectorHead";}
									//echo $UnauthorizedMessage;

							}
						}
										
						$table_message= "<h2>Details of all Sector Heads:</h2>";
						echo $table_message;
						$result = mysql_query($CSOSectorHead_view_query);
						
						$main_query=$CSOSectorHead_view_query;
							
						
			}	



		}


		echo '<script type="text/javascript">
function printpage() {
document.getElementById("printButton").style.visibility="hidden";
window.print();
document.getElementById("printButton").style.visibility="visible";  
}
</script>';
echo '<button name="print" type="button" value="Print" id="printButton" onClick="printpage()">Print</button>';
		print_sort($result);
		print_search($result);
		
		print_table($result);

		$_SESSION['main_query']=$main_query;
		$_SESSION['table_message']=$table_message;

?>
	</div>
</body>
</html>