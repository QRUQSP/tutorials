<?php
//
// Description
// -----------
// This method will delete an library.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the library is attached to.
// library_id:            The ID of the library to be removed.
//
// Returns
// -------
//
function qruqsp_tutorials_libraryDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'library_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Library'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tutorials', 'private', 'checkAccess');
    $rc = qruqsp_tutorials_checkAccess($ciniki, $args['tnid'], 'ciniki.tutorials.libraryDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the library
    //
    $strsql = "SELECT id, uuid, tnid, tutorial_id "
        . "FROM qruqsp_tutorial_library "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['library_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tutorials', 'library');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['library']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.44', 'msg'=>'Library does not exist.'));
    }
    $library = $rc['library'];

    //
    // Load the tutorial details, and number of other listings
    //
    $strsql = "SELECT tutorials.id, tutorials.flags, COUNT(library.id) AS num_listings "
        . "FROM qruqsp_tutorials AS tutorials "
        . "LEFT JOIN qruqsp_tutorial_library AS library ON ( "
            . "tutorials.id = library.tutorial_id "
            . "AND library.id <> '" . ciniki_core_dbQuote($ciniki, $library['tutorial_id']) . "' "
            . ") "
        . "WHERE tutorials.id = '" . ciniki_core_dbQuote($ciniki, $library['tutorial_id']) . "' "
        . "AND tutorials.tnid = '" . ciniki_core_dbQuote($ciniki, $library['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.tutorials', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.49', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.50', 'msg'=>'Unable to find requested item'));
    }
    $item = $rc['item'];
    
    //
    // Check for any dependencies before deleting
    //

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'qruqsp.tutorials.library', $args['library_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.45', 'msg'=>'Unable to check if the library is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.46', 'msg'=>'The library is still in use. ' . $rc['msg']));
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'qruqsp.tutorials');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the library
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'qruqsp.tutorials.library',
        $args['library_id'], $library['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.tutorials');
        return $rc;
    }

    //
    // Check if flags should be updated
    //
    if( ($item['flags']&0x01) == 0x01 && $item['num_listings'] == 0 ) {
        //
        // Remove the publish flag
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'qruqsp.tutorials.tutorial', $item['id'], array(
            'flags' => ($item['flags'] & 0xFFFE),
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.tutorials');
            return $rc;
        }
    } 

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'qruqsp.tutorials');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'qruqsp', 'tutorials');

    return array('stat'=>'ok');
}
?>
