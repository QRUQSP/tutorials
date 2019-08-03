<?php
//
// Description
// -----------
// This method will add a new library for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Library to.
//
// Returns
// -------
//
function qruqsp_tutorials_libraryAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'tutorial_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tutorial'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Category'),
        'subcategory'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Sub Category'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tutorials', 'private', 'checkAccess');
    $rc = qruqsp_tutorials_checkAccess($ciniki, $args['tnid'], 'qruqsp.tutorials.libraryAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check if library enabled
    //
    if( !isset($ciniki['config']['qruqsp.tutorials']['library.tnid'])
        || $ciniki['config']['qruqsp.tutorials']['library.tnid'] < 1
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.32', 'msg'=>'Library not configured'));
    }
    $library_tnid = $ciniki['config']['qruqsp.tutorials']['library.tnid'];

    //
    // Check if user submitting to shared library
    //
    if( $args['tnid'] != $library_tnid ) {
        $strsql = "SELECT COUNT(*) AS num "
            . "FROM qruqsp_tutorial_library "
            . "WHERE tutorial_id = '" . ciniki_core_dbQuote($ciniki, $args['tutorial_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $library_tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'qruqsp.tutorials', 'num');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.33', 'msg'=>'Unable to check library', 'err'=>$rc['err']));
        }
        if( isset($rc['num']) && $rc['num'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.33', 'msg'=>'Tutorial already published'));
        }
    } else {
        $strsql = "SELECT COUNT(*) AS num "
            . "FROM qruqsp_tutorial_library "
            . "WHERE tutorial_id = '" . ciniki_core_dbQuote($ciniki, $args['tutorial_id']) . "' "
            . "AND category = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
            . "AND subcategory = '" . ciniki_core_dbQuote($ciniki, $args['subcategory']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $library_tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'qruqsp.tutorials', 'num');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.33', 'msg'=>'Unable to check library', 'err'=>$rc['err']));
        }
        if( isset($rc['num']) && $rc['num'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.33', 'msg'=>'Tutorial already published to the category'));
        }
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'qruqsp.tutorials');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the library to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $library_tnid, 'qruqsp.tutorials.library', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.tutorials');
        return $rc;
    }
    $library_id = $rc['id'];

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
    ciniki_tenants_updateModuleChangeDate($ciniki, $library_tnid, 'qruqsp', 'tutorials');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $library_tnid, 'ciniki', 'web', 'indexObject', array('object'=>'qruqsp.tutorials.library', 'object_id'=>$library_id));

    return array('stat'=>'ok', 'id'=>$library_id);
}
?>
