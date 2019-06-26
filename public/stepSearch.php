<?php
//
// Description
// -----------
// This method searchs for a Steps for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Step for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function qruqsp_tutorials_stepSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tutorials', 'private', 'checkAccess');
    $rc = qruqsp_tutorials_checkAccess($ciniki, $args['tnid'], 'qruqsp.tutorials.stepSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of steps
    //
    $strsql = "SELECT qruqsp_tutorials_steps.id, "
        . "qruqsp_tutorials_steps.tutorial_id, "
        . "qruqsp_tutorials_steps.content_id, "
        . "qruqsp_tutorials_steps.content_type, "
        . "qruqsp_tutorials_steps.sequence "
        . "FROM qruqsp_tutorials_steps "
        . "WHERE qruqsp_tutorials_steps.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tutorials', array(
        array('container'=>'steps', 'fname'=>'id', 
            'fields'=>array('id', 'tutorial_id', 'content_id', 'content_type', 'sequence')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['steps']) ) {
        $steps = $rc['steps'];
        $step_ids = array();
        foreach($steps as $iid => $step) {
            $step_ids[] = $step['id'];
        }
    } else {
        $steps = array();
        $step_ids = array();
    }

    return array('stat'=>'ok', 'steps'=>$steps, 'nplist'=>$step_ids);
}
?>
