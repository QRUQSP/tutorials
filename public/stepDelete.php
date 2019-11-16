<?php
//
// Description
// -----------
// This method will delete an step.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the step is attached to.
// step_id:            The ID of the step to be removed.
//
// Returns
// -------
//
function qruqsp_tutorials_stepDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'step_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Step'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tutorials', 'private', 'checkAccess');
    $rc = qruqsp_tutorials_checkAccess($ciniki, $args['tnid'], 'ciniki.tutorials.stepDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the step
    //
    $strsql = "SELECT id, content_id, uuid "
        . "FROM qruqsp_tutorial_steps "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['step_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tutorials', 'step');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['step']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.26', 'msg'=>'Step does not exist.'));
    }
    $step = $rc['step'];

    //
    // Check if the content is used in any other steps
    //
    $strsql = "SELECT COUNT(steps.tutorial_id) AS num "
        . "FROM qruqsp_tutorial_steps AS steps "
        . "WHERE steps.content_id = '" . ciniki_core_dbQuote($ciniki, $step['content_id']) . "' "
        . "AND steps.id <> '" . ciniki_core_dbQuote($ciniki, $args['step_id']) . "' "
        . "AND steps.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
    $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'qruqsp.tutorials', 'num');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.40', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
    }
    $remove_content = 'yes';
    if( isset($rc['num']) && $rc['num'] > 0 ) {
        $remove_content = 'no';
    }

    //
    // Check for any dependencies before deleting
    //

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'qruqsp.tutorials.step', $args['step_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.27', 'msg'=>'Unable to check if the step is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.28', 'msg'=>'The step is still in use. ' . $rc['msg']));
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
    // Check if content should be removed
    //
    if( $step['content_id'] > 0 && $remove_content == 'yes' ) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'qruqsp.tutorials.content',
            $step['content_id'], null, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.tutorials');
            return $rc;
        }
    }

    //
    // Remove the step
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'qruqsp.tutorials.step',
        $args['step_id'], $step['uuid'], 0x04);
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

    return array('stat'=>'ok');
}
?>
