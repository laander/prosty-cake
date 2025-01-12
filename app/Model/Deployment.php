<?php
App::uses('AppModel', 'Model');
App::import('Vendor', 'Git');

class Deployment extends AppModel {
	public $actsAs = array('Prosty');
	public $validate = array(
		'project_id' => array(
			'numeric' => array(
				'rule' => array('numeric'),
				'message' => 'Project id must be numeric',
			),
			'currentVersion' => array(
				'rule' => array('validateCurrentVersion'),
				'message' => 'The current version could not be found'
			),			
			'nextVersion' => array(
				'rule' => array('validateNextVersion'),
				'message' => 'The next version already exists'
			),						
		)
	);
		
	/*******************
	* beforeSave: deploy project, clone folders and run git
	*******************/			
	function beforeSave(){
	
		$project_id = $this->data["Deployment"]["project_id"];
		$versions = $this->getVersionPaths($project_id);
			
		if($this->validates()){	
						
			// create new version
			if(isset($this->data["Deployment"]["create_next_version"]) && $this->data["Deployment"]["create_next_version"] == true){
			
				// clone current version
				$this->recursive_copy($versions["current"], $versions["next"]);         
				   												
				// git pull
				$repo = Git::open($versions['next']);			
				$this->GitPull($repo);									
				
				// set deployed version
				$this->data["Deployment"]["deployed_version"] = basename($versions["next"]);				
		
			// update current version
			}else{
			
				// git pull
				$repo = Git::open($versions['current']);
				$this->GitPull($repo);					
				
				// set deployed version
				$this->data["Deployment"]["deployed_version"] = basename($versions["current"]);								
			}		
	
			// clear cache for current project (only if cache is enabled!)			
			$project = $this->Project->findById($project_id);
			if($project["Project"]["use_cache"] === 1){
				$this->clearCache($project_id);
			}

			// set error status - errors might have occured during git
			$this->data["Deployment"]["status"] = count($this->getErrors()) === 0 ? true : false;		
		}else{
			// remove invalid fields from array
			foreach($this->invalidFields() as $errorName => $error){		
				unset($this->data["Deployment"][$errorName]);
			}		
		
			// set error status
			$this->data["Deployment"]["status"]	= false;			
		}				
		
		return true;
	}
	
	/*******************
	* afterSave: log errors
	*******************/	
	function afterSave(){
	
		$project_id = $this->data["Deployment"]["project_id"];
		$versions = $this->getVersionPaths($project_id);		
				
		// errors occured during deployment
		if($this->data["Deployment"]["status"]	== false){

			// log Prosty errors
			foreach($this->getErrors() as $error){		
				// set values
				$this->data["DeploymentError"]["deployment_id"] = $this->data["Deployment"]["id"];			
				$this->data["DeploymentError"]["message"] = $error["message"];
				$this->data["DeploymentError"]["calling_function"] = $error["calling_function"];
	
				// save errors
				$this->DeploymentError->create();
				$this->DeploymentError->save($this->data);		
			}				
	
			// log cake validation errors
			foreach($this->invalidFields() as $errorName => $error){		
				// set values
				$this->data["DeploymentError"]["deployment_id"] = $this->data["Deployment"]["id"];			
				$this->data["DeploymentError"]["message"] = $error[0];
				$this->data["DeploymentError"]["calling_function"] = $errorName;
	
				// save errors
				$this->DeploymentError->create();
				$this->DeploymentError->save($this->data);		
			}		
			
		// success: no errors occured during deployment
		}else{
		
			// update version in db	and symlink (done through relational)
			$this->Project->id = $project_id;	
			$this->Project->saveField('current_version', $this->data["Deployment"]["deployed_version"]);
							
		}		
	}
	
/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'Project' => array(
			'className' => 'Project',
			'foreignKey' => 'project_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'User' => array(
			'className' => 'User',
			'foreignKey' => 'created_by',
		),
		'ModifiedBy' => array(
			'className' => 'User',
			'foreignKey' => 'modified_by',
		)	
	);
	
	public $hasMany = array(
		'DeploymentError' => array(
			'className' => 'DeploymentError',
			'foreignKey' => 'deployment_id',
			'dependent' => false,
		)		
	);		
		
	// verify that the current version exists
	function validateCurrentVersion($check){
		$versions = $this->getVersionPaths($check["project_id"]);
						
		// success if dir exists		
		return is_dir($versions["current"]) ? true : false; 
	}

	// verify that the version "to-be" does not exist
	function validateNextVersion($check){	
		$versions = $this->getVersionPaths($check["project_id"]);		
		
		// error if dir exists			
		return is_dir($versions["next"]) ? false : true; 
	}		
}
