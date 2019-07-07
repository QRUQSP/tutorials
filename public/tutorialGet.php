<?php
//
// Description
// ===========
// This method will return all the information about an tutorial.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the tutorial is attached to.
// tutorial_id:          The ID of the tutorial to get the details for.
//
// Returns
// -------
//
function qruqsp_tutorials_tutorialGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'tutorial_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tutorial'),
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
    $rc = qruqsp_tutorials_checkAccess($ciniki, $args['tnid'], 'qruqsp.tutorials.tutorialGet');
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
    // Return default for new Tutorial
    //
    if( $args['tutorial_id'] == 0 ) {
        $tutorial = array('id'=>0,
            'title'=>'',
            'permalink'=>'',
            'flags'=>'0',
            'synopsis'=>'',
            'content'=>'',
            'bookmarked'=>'no',
        );
    }

    //
    // Get the details for an existing Tutorial
    //
    else {
        //
        // Load the tutorial
        //
        ciniki_core_loadMethod($ciniki, 'qruqsp', 'tutorials', 'private', 'tutorialLoad');
        $rc = qruqsp_tutorials_tutorialLoad($ciniki, $args['tnid'], $args['tutorial_id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.19', 'msg'=>'Unable to load tutorial', 'err'=>$rc['err']));
        }
        if( !isset($rc['tutorial']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.22', 'msg'=>'Unable to find Tutorial'));
        }
        $tutorial = $rc['tutorial'];
        $tutorial['bookmarked'] = 'no';

        //
        // Check if tutorial is bookmarked
        //
        $strsql = "SELECT tutorial_id "
            . "FROM qruqsp_tutorial_bookmarks "
            . "WHERE tutorial_id = '" . ciniki_core_dbQuote($ciniki, $args['tutorial_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.tutorials', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.31', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
        }
        if( isset($rc['item']['tutorial_id']) ) {
            $tutorial['bookmarked'] = 'yes';
        }
    }

    $rsp = array('stat'=>'ok', 'tutorial'=>$tutorial);

    //
    // Get the list of my categories
    //
    $strsql = "SELECT tags.tag_name, "
        . "tags.permalink, "
        . "COUNT(tutorials.id) AS num_tutorials "
        . "FROM qruqsp_tutorial_tags AS tags "
        . "LEFT JOIN qruqsp_tutorials AS tutorials ON ("
            . "tags.tutorial_id = tutorials.id "
            . "AND tutorials.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND tags.tag_type = 50 "
        . "GROUP BY tag_name "
        . "ORDER BY tag_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'qruqsp.tutorials', array(
        array('container'=>'categories', 'fname'=>'tag_name', 'name'=>'category',
            'fields'=>array('name'=>'tag_name', 'permalink', 'num_tutorials')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['categories']) ) {
        $rsp['mycategories'] = array();
    } else {
        $rsp['mycategories'] = $rc['categories'];
    }

    return $rsp;
}
?>
