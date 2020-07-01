<?php
namespace Internetrix\IRXSSAuth\Extensions;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use function get_class;

class IRXSSAuthGroupExtension extends DataExtension {

	/**
	 * Update any requests to hide irx group in admin security page.
	 */
	function augmentSQL(SQLSelect $query, DataQuery $dataQuery = null) {
		$memberDO = Member::currentUser ();

		// if not irxstaff, then hide irxstaff group
		if ($dataQuery->getQueryParam ( 'RemoveGroupFilter' ) !== true && (! $memberDO || ! $memberDO->ID || ! $memberDO->isInternetrixEmail ())) {
		    //we also need to make sure we are logging in. If we are coming from the security controller we should not augment the sql
		    if(Controller::has_curr() && Controller::curr() instanceof Security){
		        return;
            }
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
