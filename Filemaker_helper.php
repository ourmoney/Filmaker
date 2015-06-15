<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Filemaker_helper
 * 
 * @author    Ashish Tailor ashishgtailor@hotmail.com
 * Create a FileMaker Database object and 
 * provide auxillary functions to work with the object.
*/

class Filemaker_helper {
	/**
	 * FileMaker PHP API Object
	 */
	public $fm;
	
	/**
	 * Reference to CodeIgniter
	 */
	private $CI;
	
	/**
	 * Config filename that holds validation rules
	 *
	 * will be used to auto-load the config values only when they are needed
	 */
	private $validation_config_file;
	
	/**
	 * Validation rules
	 *
	 * will be auto-load from config file only when needed
	 * 
	 * NULL = have not been loaded
	 * FALSE = failed to load
	 * is_array() = loaded
	 */
	private $validation_rules = NULL;
	
	/*
	 * map Table Occurrences in FileMaker to Table Key's
	 * 
	 * $tables format:
	 *	array(
	 *		'tableKey1' => array('TableOccurence1', etc...),
	 *		'tableKey2' => array('TableOccurence2', 'TableOccurence3', etc...),
	 *		etc...
	 *	)
	 */
	private $tables = array();
	
	/**
	 * map Fields in FileMaker to Table Key's, and Field Key's
	 * 
	 * $tables format:
	 *	array(
	 *		'tableKey1' => array(
	 *			'fieldKey1' => array(
	 *				'name' => 'fieldName1',
	 *				'label' => 'Field Label 1',
	 *			),
	 *			all fields...
	 *		),
	 *		all table keys...
	 *	)
	 */
	private $fields = array();
	
	public $jobcontainer;
	
	/*********************************************************************/
	
	/**
	 * @param	string	name of config file to retrieve connection settings from
	 */
    function __construct($dbconfig='fmdb')
	{
		// provide access to CodeIgniter Resources
		$this->CI =& get_instance();
		
		// create FM Database object
		require_once 'Filemaker/FileMaker.php';
		$this->CI->config->load($dbconfig, TRUE);
		$this->fm = new FileMaker(
			$this->CI->config->item('database', $dbconfig),
			$this->CI->config->item('hostname', $dbconfig),
			$this->CI->config->item('username', $dbconfig),
			$this->CI->config->item('password', $dbconfig)
		);
		$this->validation_config_file = $this->CI->config->item('validation_config_file', $dbconfig);
		$this->tables = $this->CI->config->item('tables', $dbconfig);
		$this->fields = $this->CI->config->item('fields', $dbconfig);
		
		//$this->fillJobsContainer();
		
    }
	
	/*********************************************************************/
	public function showLayout()
	{
		$layouts = $this->fm->listLayouts();
		if (FileMaker::isError($layouts)) { 
			//can't connect to database generally returns a 401 for invalid or unauthorized login
			echo $layouts->getMessage();
			exit;
		}
		
		return $layouts;
		
	}
	/*********************************************************************/
	public function showJobs($clientid)
	{
		$command = $this->fm->newFindCommand('web-jobs');
		$command->addFindCriterion('JobStatus', "Open");
		$command->addFindCriterion('Addresses::URN', $clientid);
		$command->addSortRule('JobNumber' ,1,FILEMAKER_SORT_DESCEND);
				
		$result = $command->execute();
		
		return $result;
		
	}
	/*********************************************************************/
	public function record_count($layouts,$clientid)
	{
		$command = $this->fm->newFindCommand($layouts);
		$command->addFindCriterion('JobStatus', "Open");
		$command->addFindCriterion('Addresses::URN', $clientid);
				
		$result = $command->execute();
		
		if (FileMaker::isError($result))
		{
			$recordcount=0;
			return $recordcount;
			exit;
		}
		
		$recordcount=$result->getFoundSetCount();
		
		return $recordcount;
		
	}
	/*********************************************************************/
	public function showJobList($max,$page,$clientid)
	{
		if(!isset($page)) { $page = 0; }



		$command = $this->fm->newFindCommand('web-jobs');
		$command->addFindCriterion('JobStatus', "Open");
		$command->addFindCriterion('Addresses::URN', $clientid);
		$command->addSortRule('Addresses::URN' ,1,FILEMAKER_SORT_ASCEND);
		$command->addSortRule('JobNumber' ,1,FILEMAKER_SORT_DESCEND);
		$command->setRange($page,$max);
		
		$result = $command->execute();
		
		if (FileMaker::isError($result))
		{
			return NULL;
			exit;
		}
									   
							   
		return $result;
		/*foreach ($result->getRecords() as $record) {
				   
				// Get the title of the record
					$data['job_no'][] = $record->getField('JobNumber');
					$data['job_title'][] = $record->getField('JobDescription');
					$data['client'][] = $record->getField('Client');
					
				
				}
		return $data;*/
	}
	/*********************************************************************/
	public function showJobTitle($jobnumber)
	{
		$JobDescription = "none";
		
		if($jobnumber != 0 )
		{
			$command = $this->fm->newFindCommand('web-jobs');
			$command->addFindCriterion('JobStatus', "Open");
			$command->addFindCriterion('JobNumber', $jobnumber);
					
			$result = $command->execute();
			$records=$result->getRecords();
			$JobDescription=$records[0]->getField('JobDescription');
			
			/*foreach ($result->getRecords() as $record) {
				   
				// Get the title of the record
					$JobDescription = $record->getField('JobDescription');
				
				}*/
		}
		return $JobDescription;
		
	}
	/*
	public function showJobsContainer()
	{
		
			$command = $this->fm->newFindCommand('web-jobs');
			//$command->addFindCriterion('JobStatus', "Open");
			
					
			$result = $command->execute();
								
			foreach ($result->getRecords() as $record) {
				   
				// Get the title of the record
					$jobs[$record->getField('JobNumber')] = $record->getField('JobDescription');
				
			}
		
			return $jobs;
		
	}*/
	public function fillJobsContainer($status)
	{
		
			$command = $this->fm->newFindCommand('web-jobs');
			if($status != '')
				$command->addFindCriterion('JobStatus', "Open");
			
					
			$result = $command->execute();
								
			foreach ($result->getRecords() as $record) {
				   
				// Get the title of the record
					$this->jobcontainer[$record->getField('JobNumber')] = $record->getField('JobDescription');
				
			}
		
			//return $jobs;
			
	}
	
	public function getJobTitle($jobnumber)
	{
			if($jobnumber != 0)
			{
				$jobtitle=$this->jobcontainer[$jobnumber];
				
			}
			else
				$jobtitle="Not available";
				
			return $jobtitle;
				
	}
	public function getClients()
	{
		$command = $this->fm->newFindCommand('web-jobs');
		$command->addFindCriterion('JobStatus', "Open");
		$command->addSortRule('Client' ,1,FILEMAKER_SORT_ASCEND);
		
		$result = $command->execute();
								
			/*foreach ($result->getRecords() as $record) {
				   
				// Get the title of the record
					//$clients[$record->getField('Addresses::URN')] = $record->getField('Client');
					
					$clients['client_id']=$record->getField('Addresses::URN');
					$clients['client']=$record->getField('Client');
			}*/
		
			return $result;	
				
	}
	public function uploadTime($data,$userid)
	{
		$newPerformScript = $this->fm->newPerformScriptCommand('Time_UI','Open Time Input',$userid);
		$result = $newPerformScript->execute();
			
		//$userid=$userid;
		$jobnumber=$data['job_no'];
		$hours=$data['hours'].'.'.$data['min'];
		$activity=$data['activity'];
		$date=$this->convertdate($data['tdate']);
		/*
		$jobnumber='3534';
		$hours='9.50';
		$activity='Admin';
		$date=$this->convertdate('2015-04-05');*/
		
		$rec = $this->fm->createRecord('Time_List_UI');

		$rec->setField('User',$userid);
		$rec->setField('JobNumber',$jobnumber);
		$rec->setField('NumberOfHours',$hours);
		$rec->setField('Activity',$activity);
		$rec->setField('Date',$date);
		
		$result = $rec->commit();
		
		return $result;
	}
	public function convertdate($date)
	{
		$date=explode('-',$date);
		$newdate=$date[1]."/".$date[2]."/".$date[0];
		return $newdate;
	}
	
	
}
// END Filemaker_helper Class

/* End of file FileMaker_helper.php */
/* Location: ./application/libraries/FileMaker_helper.php */ 