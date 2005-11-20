<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------

 LICENSE

	This file is part of GLPI.

    GLPI is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    GLPI is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with GLPI; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 ------------------------------------------------------------------------
*/

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
// Tracking Classes

class Job {

	var $fields	= array();
	var $updates	= array();
	var $computername	= "";
	var $computerfound	= 0;
	

	function getfromDB ($ID,$purecontent) {

		$this->ID = $ID;

		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM glpi_tracking WHERE (ID = $ID)";

		if ($result = $db->query($query)) 
			if ($db->numrows($result)==1){
			$data = $db->fetch_assoc($result);
			foreach ($data as $key => $val) {
				$this->fields[$key] = $val;
			}
			if (!$purecontent) {
				$this->contents = nl2br($this->fields["contents"]);
			}
			$m= new CommonItem;
			if ($m->getfromDB($this->fields["device_type"],$this->fields["computer"])){
				$this->computername=$m->getName();
			}
			if ($this->computername==""){
				$this->computername = "N/A";
				$this->computerfound=0;				
			} else 	$this->computerfound=1;	

			return true;
		} else {
			return false;
		}
		return false;
	}

	function numberOfFollowups(){
		$db=new DB();

		// Set number of followups
		$query = "SELECT count(*) FROM glpi_followups WHERE (tracking = $this->ID)";
		$result = $db->query($query);
		return $db->result($result,0,0);

	}

	function updateInDB($updates)  {

		$db = new DB;

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE glpi_tracking SET ";
			$query .= $updates[$i];
			$query .= "='";
			$query .= $this->fields[$updates[$i]];
			$query .= "' WHERE ID='";
			$query .= $this->fields["ID"];	
			$query .= "'";
//			echo $query;
			$result=$db->query($query);
		}
	}

	function addToDB() {
		
		$db = new DB;

		// Build query
		$query = "INSERT INTO glpi_tracking (";
		$i=0;
		
		foreach ($this->fields as $key => $val) {
			$fields[$i] = $key;
			$values[$i] = $val;
			$i++;
		}		
		for ($i=0; $i < count($fields); $i++) {
			$query .= $fields[$i];
			if ($i!=count($fields)-1) {
				$query .= ",";
			}
		}
		$query .= ") VALUES (";
		for ($i=0; $i < count($values); $i++) {
			$query .= "'".$values[$i]."'";
			if ($i!=count($values)-1) {
				$query .= ",";
			}
		}
		$query .= ")";
		$result=$db->query($query);
		return $db->insert_id();
	}	

/*
	function updateStatus($status) {
		// update Status of Job
		
		$db = new DB;
		$query = "UPDATE glpi_tracking SET status = '$status' WHERE ID = $this->ID";
		if ($result = $db->query($query)) {
			$this->closedate=date("Y-m-d G:i:s");
			$query = "UPDATE glpi_tracking SET closedate = NOW() WHERE ID = $this->ID";
			if ($result = $db->query($query)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
*/
	function updateRealtime() {
		// update Status of Job
		
		$db = new DB;
		$query = "SELECT SUM(realtime) FROM glpi_followups WHERE tracking = '".$this->ID."'";
		if ($result = $db->query($query)) {
				$query2="UPDATE glpi_tracking SET realtime='".$db->result($result,0,0)."' WHERE ID='".$this->ID."'";
				$db->query($query2);
				return true;
		} else {
			return false;
		}
	}
	
/*
	function assignTo($user,$type) {
		// assign Job to user
		
		$db = new DB;
		$this->assign=$user;
		$this->assign_type=$type;
		$query = "UPDATE glpi_tracking SET assign = '$user',assign_type = '$type' WHERE ID = '$this->ID'";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	function categoryTo($category) {
		// change category
		
		$db = new DB;
		$this->category=$category;
		$query = "UPDATE glpi_tracking SET category = '$category' WHERE ID = '$this->ID'";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}
	function priorityTo($priority) {
		// change priority
		
		$db = new DB;
		$this->priority=$priority;
		$query = "UPDATE glpi_tracking SET priority = '$priority' WHERE ID = '$this->ID'";
//		echo $query;
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	function authorTo($author) {
		// change item
		
		$db = new DB;
		$this->fields["author"] = $author;

		$query = "UPDATE glpi_tracking SET author = '$author' WHERE ID = '$this->ID'";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	function mailAuthorTo($uemail=NULL){
		//change mail

		$db = new DB;
		$this->uemail = $uemail;

		$query = "UPDATE glpi_tracking SET uemail = '$uemail' WHERE ID = '$this->ID'";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}




	function itemTo($type,$comp) {
		// change item
		
		$db = new DB;
		$this->device_type = $type;
		$this->computer = $comp;

		$query = "UPDATE glpi_tracking SET computer = '$comp', device_type='$type' WHERE ID = '$this->ID'";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}
*/

	function textFollowups() {
		// get the last followup for this job and give its contents as
		GLOBAL $lang;
		$db=new DB();
		$query = "SELECT * FROM glpi_followups WHERE tracking = '".$this->ID."' AND private = '0' ORDER by date ASC";
		$result=$db->query($query);
		$nbfollow=$db->numrows($result);
		$message = $lang["mailing"][1]."\n".$lang["mailing"][4]." : $nbfollow\n".$lang["mailing"][1]."\n";
		
		if ($nbfollow>0){
			$fup=new Followup();
			while ($data=$db->fetch_array($result)){
				$fup->getfromDB($data['ID']);
					$message .= "[ ".$fup->fields["date"]." ]\n";
					$message .= $lang["mailing"][2]." ".$fup->getAuthorName()."\n";
					$message .= $lang["mailing"][3]."\n".$fup->fields["contents"]."\n".$lang["mailing"][0]."\n";
			}	
		}
		return $message;
	}
	
	function textDescription(){
		GLOBAL $lang;
		
		$db=new DB;
		$m= new CommonItem;
		$name="N/A";
		if ($m->getfromDB($this->fields["device_type"],$this->fields["computer"])){
			$name=$m->getType()." ".$m->getName();
		}
		
		
		$message = $lang["mailing"][1]."\n*".$lang["mailing"][5]."*\n".$lang["mailing"][1]."\n";
		$message.= $lang["mailing"][2]." ".$this->getAuthorName()."\n";
		$message.= $lang["mailing"][6]." ".$this->fields["date"]."\n";
		$message.= $lang["mailing"][7]." ".$name."\n";
		$message.= $lang["mailing"][8]." ".$this->getAssignName()."\n";
		$message.= $lang["mailing"][16]." ".getPriorityName($this->fields["priority"])."\n";
		$message.= $lang["mailing"][3]."\n".$this->fields["contents"]."\n";	
		$message.="\n\n";
		return $message;
	}
	
	function deleteInDB ($ID) {
		if ($ID!=""){
			$db=new DB;
			$query2="delete from glpi_tracking where ID = '$ID'";
			$query1="delete from glpi_followups where tracking = '$ID'";

			$query="SELECT ID FROM glpi_followups WHERE tracking = '$ID'";
			$result=$db->query($query);
			if ($db->numrows($result)>0)
			while ($data=$db->fetch_array($result)){
				$querydel="DELETE FROM glpi_tracking_planning WHERE id_followup = '".$data['ID']."'";
				$db->query($querydel);				
			}

			$db->query($query1);
			$db->query($query2);
			 return true;
			}
			 return false;		
	}
	
	function getAssignName($link=0){
	global $cfg_install;
	
	if ($this->fields["assign_type"]==USER_TYPE){
		return getUserName($this->fields["assign"],$link);
		
	} else if ($this->fields["assign_type"]==ENTERPRISE_TYPE){
		$ent=new Enterprise();
		$ent->getFromDB($this->fields["assign"]);
		$before="";
		$after="";
		if ($link){
			$before="<a href=\"".$cfg_install["root"]."/enterprises/enterprises-info-form.php?ID=".$this->fields["assign"]."\">";
			$after="</a>";
		}
		
		return $before.$ent->fields["name"].$after;
	}
	
	}
	
	function getAuthorName($link=0){
	
	return getUserName($this->fields["author"],$link);
	}
	
}


class Followup {
	
	var $fields	= array();
	var $updates	= array();
/*
	function getfromDB ($ID,$iteration) {

		$this->fields["ID"] = $ID;

		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM glpi_followups WHERE (tracking = $ID) ORDER BY date ASC";
	
		if ($result = $db->query($query)) {
			$this->fields["tracking"] = $ID;
			$this->fields["date"] = $db->result($result,$iteration,"date");
			$this->fields["author"] = $db->result($result, $iteration, "author");
			$this->fields["private"] = $db->result($result, $iteration, "private");
			$this->fields["contents"] = nl2br($db->result($result, $iteration, "contents"));

			return true;

		} else {
			return false;
		}
	}
*/

	function getfromDB ($ID) {

		$this->ID = $ID;

		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM glpi_followups WHERE (ID = $ID)";

		if ($result = $db->query($query)) 
			if ($db->numrows($result)==1){
			$data = $db->fetch_assoc($result);
			foreach ($data as $key => $val) {
				$this->fields[$key] = $val;
			}
			return true;
		} else {
			return false;
		}
		return false;
	}


	function putInDB () {	
		// prepare variables

		$this->fields["date"] = date("Y-m-d H:i:s");
	
		// dump into database
		$db = new DB;
		$query = "INSERT INTO glpi_followups VALUES (NULL, ".$this->fields["tracking"].", '".$this->fields["date"]."','".$this->fields["author"]."', '".$this->contents."')";

		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}


	function addToDB() {
		
		$db = new DB;

		// Build query
		$query = "INSERT INTO glpi_followups (";
		$i=0;
		
		foreach ($this->fields as $key => $val) {
			$fields[$i] = $key;
			$values[$i] = $val;
			$i++;
		}		
		for ($i=0; $i < count($fields); $i++) {
			$query .= $fields[$i];
			if ($i!=count($fields)-1) {
				$query .= ",";
			}
		}
		$query .= ") VALUES (";
		for ($i=0; $i < count($values); $i++) {
			$query .= "'".$values[$i]."'";
			if ($i!=count($values)-1) {
				$query .= ",";
			}
		}
		$query .= ")";

		$result=$db->query($query);

		if (isset($this->fields["realtime"])&&$this->fields["realtime"]>0) {
			$job=new Job();
			$job->getfromDB($this->fields["tracking"],0);
			$job->updateRealTime();
		}

		return $db->insert_id();
	}	

	function updateInDB($updates)  {

		$db = new DB;
				
		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE glpi_followups SET ";
			$query .= $updates[$i];
			$query .= "='";
			$query .= $this->fields[$updates[$i]];
			$query .= "' WHERE ID='";
			$query .= $this->fields["ID"];	
			$query .= "'";
//			echo $query;
			$result=$db->query($query);
			if ($updates[$i]=="realtime") {
				$job=new Job();
				$job->getfromDB($this->fields["tracking"],0);
				$job->updateRealTime();
			}
		}
	}

	
	// Plus utilis�
	/*
	function logFupUpdate () {
		// log event
		
		$db = new DB;
		$query = "SELECT * FROM glpi_tracking WHERE (ID = $this->tracking)";
		
		if ($result = $db->query($query)) {
			$cID = $db->result($result, 0, "computer");
			$type="";
			switch ($db->result($result, 0, "device_type")){
				case COMPUTER_TYPE :$type="computers";break;
				case NETWORKING_TYPE :$type="networking";break;
				case PRINTER_TYPE :$type="printers";break;
				case MONITOR_TYPE :$type="monitors";break;
				case SOFTWARE_TYPE :$type="software";break;
				case PERIPHERAL_TYPE :$type="peripherals";break;
			}
			logEvent($cID, $type, 4, "tracking", $this->fields["author"]." added followup to job $this->tracking.");
			return true;
		} else {
			return false;
		}
		
	}
	*/
	
	function getAuthorName($link=0){
	return getUserName($this->fields["author"],$link);
	}	
	
	function deleteInDB ($ID) {
		if ($ID!=""){
			$db=new DB;
			$query="delete from glpi_followups where ID = '$ID'";
			$db->query($query);
			$querydel="DELETE FROM glpi_tracking_planning WHERE id_followup = '$ID'";
			$db->query($querydel);				
			 return true;

		}
		return false;		
	}

}



?>
