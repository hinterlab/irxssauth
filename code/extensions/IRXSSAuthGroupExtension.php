<?php
class IRXSSAuthGroupExtension extends DataExtension {
	
	/**
	 * Update any requests to hide irx group in admin security page.
	 */
	function augmentSQL(SQLQuery &$query, DataQuery &$dataQuery = null) {
		$memberDO = Member::currentUser ();
		
		// if not irxstaff, then hide irxstaff group
		if ($dataQuery->getQueryParam ( 'RemoveGroupFilter' ) !== true && (! $memberDO || ! $memberDO->ID || ! $memberDO->isInternetrixEmail ())) {
			$query->addWhere ( "\"Group\".\"ParentID\" >= 0" );
		}
	}
	public function requireDefaultRecords() {
		if (! Group::get ()->setDataQueryParam ( 'RemoveGroupFilter', true )->filter ( array (
				'Code' => 'irx-staff' 
		) )->first ()) {
			$this->createIRXadminGroup ();
		}
	}
	protected function createIRXadminGroup() {
		$irxgrop = new Group ();
		$irxgrop->Title = 'Internetrix Staff';
		$irxgrop->Description = 'Internetrix Staff Group. Only @internetrix.com.au email user can see this irx admin group.';
		$irxgrop->Code = 'irx-staff';
		$irxgrop->ParentID = -1;
		$irxgrop->write ();
		
		$permission = new Permission ();
		$permission->Code = 'ADMIN';
		$permission->Arg = 0;
		$permission->Type = 1;
		$permission->GroupID = $irxgrop->ID;
		$permission->write ();
		
		return $irxgrop;
	}
}
