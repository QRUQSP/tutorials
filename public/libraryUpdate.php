<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_tutorials_libraryUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'library_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Library'),
        'tutorial_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Tutorial'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'subcategory'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sub Category'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tutorials', 'private', 'checkAccess');
    $rc = qruqsp_tutorials_checkAccess($ciniki, $args['tnid'], 'qruqsp.tutorials.libraryUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the library item
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
    // Update the Library in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'qruqsp.tutorials.library', $args['library_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.tutorials');
        return $rc;
    }

    //
    // Check if flags should be updated
    //
    if( isset($args['category']) ) {
        if( $args['category'] == '' && ($item['flags']&0x01) == 0x01 && $item['num_listings'] == 0 ) {
            //
            // Remove the publish flag
            //
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'qruqsp.tutorials.tutorial', $item['id'], array(
                'flags' => ($item['flags'] & 0xFFFE),
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.tutorials');
                return $rc;
            }
        } elseif( $args['category'] != '' && ($item['flags']&0x01) == 0 ) {
            //
            // Add the publish flag
            //
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'qruqsp.tutorials.tutorial', $item['id'], array(
                'flags' => ($item['flags'] | 0x01),
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.tutorials');
                return $rc;
            }
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

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'qruqsp.tutorials.library', 'object_id'=>$args['library_id']));

    return array('stat'=>'ok');
}
?>
