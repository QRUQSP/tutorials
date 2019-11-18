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
function qruqsp_tutorials_tutorialLoad($ciniki, $tnid, $tutorial_id) {
    //
    // Load tenant settings
    //
/*    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];
*/
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load the tutorial
    //
    $strsql = "SELECT tutorials.id, "
        . "tutorials.tnid, "
        . "tutorials.title, "
        . "tutorials.permalink, "
        . "tutorials.flags, "
        . "tutorials.synopsis, "
        . "tutorials.content "
        . "FROM qruqsp_tutorials AS tutorials "
        . "WHERE (tutorials.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "OR (tutorials.flags&0x01) = 0x01 "
            . ") "
        . "AND tutorials.id = '" . ciniki_core_dbQuote($ciniki, $tutorial_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tutorials', array(
        array('container'=>'tutorials', 'fname'=>'id', 
            'fields'=>array('tnid', 'title', 'permalink', 'flags', 'synopsis', 'content'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.8', 'msg'=>'Tutorial not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['tutorials'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.9', 'msg'=>'Unable to find Tutorial'));
    }
    $tutorial = $rc['tutorials'][0];

    //
    // Get the list of tutorial steps
    //
    $strsql = "SELECT steps.id, "
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
            . "AND content.tnid = '" . ciniki_core_dbQuote($ciniki, $tutorial['tnid']) . "' "
            . ") "
        . "WHERE steps.tutorial_id = '" . ciniki_core_dbQuote($ciniki, $tutorial_id) . "' "
        . "AND steps.tnid = '" . ciniki_core_dbQuote($ciniki, $tutorial['tnid']) . "' "
        . "ORDER BY steps.sequence "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tutorials', array(
        array('container'=>'steps', 'fname'=>'id', 
            'fields'=>array('id', 'content_type', 'sequence', 'title', 
                'image1_id', 'image2_id', 'image3_id', 'image4_id', 'image5_id', 'content'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.21', 'msg'=>'Unable to load steps', 'err'=>$rc['err']));
    }
    $num_steps = 0;
    $step_number = 0;
    $substep_number = 0;
    if( isset($rc['steps']) ) {
        $tutorial['steps'] = $rc['steps'];
        $tutorial['steps_ids'] = array();
        // Get the number of steps
        foreach($tutorial['steps'] as $k => $v) {
            if( $v['content_type'] == 10 ) {
                $num_steps++;
            }
        }
        foreach($tutorial['steps'] as $k => $v) {
            $tutorial['steps_ids'][] = $v['id'];
            if( $v['content_type'] == 10 ) {
                $substep_number = 0;
                $step_number++;
                $tutorial['steps'][$k]['extended_title'] = 'Step ' . $step_number . ' of ' . $num_steps . ' - ' . $v['title'];
                $tutorial['steps'][$k]['full_title'] = 'Step ' . $step_number . '. ' . $v['title'];
                $tutorial['steps'][$k]['short_title'] = $step_number . '. ' . $v['title'];
            } elseif( $v['content_type'] == 20 ) {
                $substep_number++;
                $tutorial['steps'][$k]['extended_title'] = 'Step ' . $step_number . chr(65+$substep_number) . ' of ' . $num_steps . ' - ' . $v['title'];
                $tutorial['steps'][$k]['full_title'] = 'Step ' . $step_number . chr(65+$substep_number) . '. ' . $v['title'];
                $tutorial['steps'][$k]['short_title'] = $step_number . chr(65+$substep_number) . '. ' . $v['title'];
            } else {
                $tutorial['steps'][$k]['extended_title'] = $v['title'];
                $tutorial['steps'][$k]['full_title'] = $v['title'];
                $tutorial['steps'][$k]['short_title'] = $v['title'];
            }
            $tutorial['steps'][$k]['html_content'] = preg_replace("/\n/", '<br/>', trim($v['content']));
        }
    } else {
        $tutorial['steps'] = array();
        $tutorial['steps_ids'] = array();
    }

    //
    // Get any my categories
    //
    $strsql = "SELECT tag_type, tag_name AS lists "
        . "FROM qruqsp_tutorial_tags "
        . "WHERE tutorial_id = '" . ciniki_core_dbQuote($ciniki, $tutorial_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tutorial['tnid']) . "' "
        . "ORDER BY tag_type, tag_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'qruqsp.tutorials', array(
        array('container'=>'tags', 'fname'=>'tag_type', 'name'=>'tags',
            'fields'=>array('tag_type', 'lists'), 'dlists'=>array('lists'=>'::')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['tags']) ) {
        foreach($rc['tags'] as $tags) {
            if( $tags['tags']['tag_type'] == 50 ) {
                $tutorial['mycategories'] = $tags['tags']['lists'];
            }
        }
    }

    return array('stat'=>'ok', 'tutorial'=>$tutorial);
}
?>
