<?php
App::uses('AppModel', 'Model');
App::import('Vendor', 'Git');
App::import('Vendor', 'Prosty');
/**
 * Project Model
 *
 * @property Commit $Commit
 */
class Project extends AppModel {
/**
 * Validation rules
 *
 * @var array
 */
 
 	var $Prosty;
 	
 	function getProsty(){
 		if(!$this->Prosty){
 			$this->Prosty = new Prosty();
		}	
		return $this->Prosty;
 	}
 		 	
	public $validate = array(
		'project_alias' => array(
			'notempty' => array(
				'rule' => array('notempty'),
				'message' => 'Cannot be empty',
				'on' => 'create', // Limit validation to 'create' operations				
			),		
			'unique' => array(
				'rule' => 'isUnique',
				'message' => 'The name must be unique',
				'on' => 'create', // Limit validation to 'create' operations				
			),
			'folder' => array(
				'rule' => array('validateProjectAliasFolder'),
				'message' => 'Folder already exists',
				'on' => 'create', // Limit validation to 'create' operations				
			),
			'github' => array(
				'rule' => array('checkGithub'),
				'message' => 'GitHub account doesn\'t exists or repository is not empty',
				'on' => 'create', // Limit validation to 'create' operations
			)	
		),
		'title' => array(
			'notempty' => array(
				'rule' => array('notempty'),
			),
		),
		'primary_domain' => array(
			'alphanumeric' => array(
				'rule' => '/^[\w\.]+$/',
				'message' => 'Cannot contain http:// or www - only the base hostname!',
			),
		),
		'dev_domain' => array(
			'notempty' => array(
				'rule' => array('notempty'),
			),
		),
		'use_cache' => array(
			'boolean' => array(
				'rule' => array('boolean'),
			),
		),
		'current_version' => array(
			'folder' => array(
				'rule' => array('validateVersionFolder'),
				'message' => 'Invalid version chosen - it seems it does not exist',				
				'required' => false,				
				'on' => 'update', // Limit validation 'update' operations
			),
		),
		'screenshot' => array(
			'boolean' => array(
				'rule' => array('boolean'),
			),
		),
		'exclude' => array(
			'boolean' => array(
				'rule' => array('boolean'),
			),
		),
		'errors' => array(
			'numeric' => array(
				'rule' => array('numeric'),
			),
		),
		'created_by' => array(
			'numeric' => array(
				'rule' => array('numeric'),
			),
		),
		'modified_by' => array(
			'numeric' => array(
				'rule' => array('numeric'),
			),
		),
	);

    /**
     * Validation: check if a Github account has been created
     *************************************************/
	function checkGithub($check) {
        return Git::git_check_repository('ls-remote -h '.Prosty::getGitRemote($check["project_alias"]).'.git' );        
	}
	
	/**
	 * Check if a folder of the name "$project_alias" exists
	 * Validation: will fail if the folder exists
	 ***************************************/
    function validateProjectAliasFolder($check){
		$Prosty = $this->getProsty();      					
	    $path = $Prosty->web_root.$check["project_alias"];
	    
        return !file_exists($path); //return false if it exists
    }       	    
    
	/**
	 * Check whether the chosen version's folder exists
	 * Validation: will fail if the folder does NOT exist
	 ***************************************/
    function validateVersionFolder($check){

		// get project alias from db	    
		$old_data = $this->find('first', array(
		    'conditions' => array('id' => $this->id), //array of conditions
			'fields' => array('project_alias'),
			'recursive' => -1
		));
	    $project_alias = $old_data["Project"]["project_alias"];
	    	    
	    // get webroot from Prosty class
		$Prosty = $this->getProsty();      					
	    $path = $Prosty->web_root.$project_alias.'/prod/'.$check["current_version"];
        return file_exists($path); //return true if it exists

    }       
    		
    /**
     * beforeSave: executes after valdiations and before save
     *************************************************/
	function beforeSave($model){
	
		$Prosty = $this->getProsty();      					
		
		// create new project
		if(!isset($this->data["Project"]["id"])) {
			$Prosty->createNewProject($this->data["Project"]);
			
			// create prod database
			$this->query("CREATE DATABASE IF NOT EXISTS `".$this->data["Project"]["project_alias"]."-prod`");
	
			// create dev database
			$this->query("CREATE DATABASE IF NOT EXISTS `".$this->data["Project"]["project_alias"]."-dev`");			
			
		// update project
		} else{
						
			$old_data = $this->findById($this->id);
			$Prosty->updateProject($this->data["Project"], $old_data["Project"]);
		
		}
	
		// success
		if(count($Prosty->errors) == 0){
			return true;
			
		// error
		}else{
			debug($Prosty->errors);
			return false;
		}
		
			
	}

/**
 * hasMany associations
 *
 * @var array
 */
	public $hasMany = array(
		'Commit' => array(
			'className' => 'Commit',
			'foreignKey' => 'project_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		)
	);

}