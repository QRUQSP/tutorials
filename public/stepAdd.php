<?php
//
// Description
// -----------
// This method will add a new step for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Step to.
//
// Returns
// -------
//
function qruqsp_tutorials_stepAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'tutorial_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tutorial'),
        'content_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Content'),
        'content_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'),
        'sequence'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Order'),
        'title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Title'),
        'image1_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'First Image'),
        'image2_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Second Image'),
        'image3_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Third Image'),
        'image4_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Fourth Image'),
        'image5_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Fifth Image'),
        'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Content'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tutorials', 'private', 'checkAccess');
    $rc = qruqsp_tutorials_checkAccess($ciniki, $args['tnid'], 'qruqsp.tutorials.stepAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
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
    // Check if content needs to be added, or existing content was used
    //
    if( !isset($args['content_id']) || $args['content_id'] == 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'qruqsp.tutorials.content', $args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.tutorials');
            return $rc;
        }
        $args['content_id'] = $rc['id'];
    }

    //
    // Add the step to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'qruqsp.tutorials.step', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.tutorials');
        return $rc;
    }
    $step_id = $rc['id'];

    //
    // Update the sequences for the tutorial
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sequencesUpdate');
    $rc = ciniki_core_sequencesUpdate($ciniki, $args['tnid'], 'qruqsp.tutorials.step', 
        'tutorial_id', $step['tutorial_id'], $args['sequence'], -1);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.tutorials');
        return $rc;
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'qruqsp.tutorials.step', 'object_id'=>$step_id));

    return array('stat'=>'ok', 'id'=>$step_id);
}
?>
