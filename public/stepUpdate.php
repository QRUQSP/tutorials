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
function qruqsp_tutorials_stepUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'step_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Step'),
        'content_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Content'),
        'content_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'),
        'title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Title'),
        'image1_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'First Image'),
        'image2_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Second Image'),
        'image3_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Third Image'),
        'image4_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Fourth Image'),
        'image5_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Fifth Image'),
        'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Content'),
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
    $rc = qruqsp_tutorials_checkAccess($ciniki, $args['tnid'], 'qruqsp.tutorials.stepUpdate');
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
    // Get the existing step
    //
    $strsql = "SELECT id, tutorial_id, content_id, sequence "
        . "FROM qruqsp_tutorial_steps "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['step_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.tutorials', 'step');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.38', 'msg'=>'Unable to load step', 'err'=>$rc['err']));
    }
    if( !isset($rc['step']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.39', 'msg'=>'Unable to find requested step'));
    }
    $step = $rc['step'];

    //
    // Check if content was to be updated
    //
    if( isset($args['title']) || isset($args['image1_id']) || isset($args['image2_id']) 
        || isset($args['image3_id']) || isset($args['image4_id']) || isset($args['image5_id']) 
        || isset($args['content']) 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'qruqsp.tutorials.content', $step['content_id'], $args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.tutorials');
            return $rc;
        }
    }

    //
    // Update the Step in the database
    //
    if( isset($args['content_type']) || isset($args['content_id']) || isset($args['sequence']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'qruqsp.tutorials.step', $args['step_id'], $args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.tutorials');
            return $rc;
        }
        
        //
        // Check if sequences need to be updated
        //
        if( isset($args['sequence']) && $args['sequence'] != $step['sequence'] ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sequencesUpdate');
            $rc = ciniki_core_sequencesUpdate($ciniki, $args['tnid'], 'qruqsp.tutorials.step', 
                'tutorial_id', $step['tutorial_id'], $args['sequence'], $step['sequence']);
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'qruqsp.tutorials.step', 'object_id'=>$args['step_id']));

    return array('stat'=>'ok');
}
?>
