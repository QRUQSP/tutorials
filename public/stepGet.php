<?php
//
// Description
// ===========
// This method will return all the information about an step.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the step is attached to.
// step_id:          The ID of the step to get the details for.
//
// Returns
// -------
//
function qruqsp_tutorials_stepGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'step_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Step'),
        'tutorial_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Tutorial'),
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
    $rc = qruqsp_tutorials_checkAccess($ciniki, $args['tnid'], 'qruqsp.tutorials.stepGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Step
    //
    if( $args['step_id'] == 0 ) {
        //
        // Get the last sequence number for this tutorial
        //
        $step = array('id'=>0,
            'tutorial_id'=>'',
            'content_id'=>'',
            'content_type'=>'10',
            'sequence'=>'1',
            'title'=>'',
            'image1_id'=>0,
            'image2_id'=>0,
            'image3_id'=>0,
            'image4_id'=>0,
            'image5_id'=>0,
            'content'=>'',
        );
        if( isset($args['tutorial_id']) && $args['tutorial_id'] > 0 ) {
            $strsql = "SELECT MAX(sequence) AS seq "
                . "FROM qruqsp_tutorial_steps "
                . "WHERE tutorial_id = '" . ciniki_core_dbQuote($ciniki, $args['tutorial_id']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.tutorials', 'step');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.36', 'msg'=>'Unable to load next sequence number', 'err'=>$rc['err']));
            }
            if( isset($rc['step']['seq']) ) {
                $step['sequence'] = ($rc['step']['seq'] + 1);
            }
        }
    }

    //
    // Get the details for an existing Step
    //
    else {
        $strsql = "SELECT steps.id, "
            . "steps.tutorial_id, "
            . "steps.content_id, "
            . "steps.content_type, "
            . "steps.sequence, "
            . "content.title, "
            . "content.image1_id, "
            . "content.image2_id, "
            . "content.image3_id, "
            . "content.image4_id, "
            . "content.image5_id, "
            . "content.content "
            . "FROM qruqsp_tutorial_steps AS steps "
            . "INNER JOIN qruqsp_tutorial_content AS content ON ("
                . "steps.content_id = content.id "
                . "AND content.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE steps.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND steps.id = '" . ciniki_core_dbQuote($ciniki, $args['step_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tutorials', array(
            array('container'=>'steps', 'fname'=>'id', 
                'fields'=>array('tutorial_id', 'content_id', 'content_type', 'sequence',
                    'title', 'image1_id', 'image2_id', 'image3_id', 'image4_id', 'image5_id', 'content'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.29', 'msg'=>'Step not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['steps'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.30', 'msg'=>'Unable to find Step'));
        }
        $step = $rc['steps'][0];

        //
        // Get the list of other tutorials this step is used in
        //
        $strsql = "SELECT tutorials.id, "
            . "tutorials.title "
            . "FROM qruqsp_tutorial_steps AS steps, qruqsp_tutorials AS tutorials "
            . "WHERE steps.content_id = '" . ciniki_core_dbQuote($ciniki, $step['content_id']) . "' "
            . "AND steps.id <> '" . ciniki_core_dbQuote($ciniki, $args['step_id']) . "' "
            . "AND steps.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND steps.tutorial_id = tutorials.id "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tutorials', array(
            array('container'=>'tutorials', 'fname'=>'id', 
                'fields'=>array('id', 'title'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.37', 'msg'=>'Unable to load tutorials', 'err'=>$rc['err']));
        }
        if( isset($rc['tutorials']) && count($rc['tutorials']) > 0 ) {
            $step['tutorials'] = $rc['tutorials'];
        }
    }

    return array('stat'=>'ok', 'step'=>$step);
}
?>
