<?php
//
// Description
// -----------
// This method will return the list of Librarys for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Library for.
//
// Returns
// -------
//
function qruqsp_tutorials_libraryList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tutorials', 'private', 'checkAccess');
    $rc = qruqsp_tutorials_checkAccess($ciniki, $args['tnid'], 'qruqsp.tutorials.libraryList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of librarys
    //
    $strsql = "SELECT qruqsp_tutorial_library.id, "
        . "qruqsp_tutorial_library.tutorial_id, "
        . "qruqsp_tutorial_library.category, "
        . "qruqsp_tutorial_library.subcategory "
        . "FROM qruqsp_tutorial_library "
        . "WHERE qruqsp_tutorial_library.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tutorials', array(
        array('container'=>'librarys', 'fname'=>'id', 
            'fields'=>array('id', 'tutorial_id', 'category', 'subcategory')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['librarys']) ) {
        $librarys = $rc['librarys'];
        $library_ids = array();
        foreach($librarys as $iid => $library) {
            $library_ids[] = $library['id'];
        }
    } else {
        $librarys = array();
        $library_ids = array();
    }

    return array('stat'=>'ok', 'librarys'=>$librarys, 'nplist'=>$library_ids);
}
?>
