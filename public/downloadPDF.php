<?php
//
// Description
// ===========
// This method will list the art catalog items sorted by category.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get the list from.
// section:         (optional) How the list should be sorted and organized.
//
//                  - category
//                  - media
//                  - location
//                  - year
//                  - list
//
// name:            (optional) The name of the section to get restrict the list.  This
//                  can only be specified if the section is also specified.  If the section
//                  is category, then the name will restrict the results to the cateogry of
//                  this name.
//
// type:            (optional) Only list items of a specific type. Valid types are:
//
//                  - painting
//                  - photograph
//                  - jewelry
//                  - sculpture
//                  - fibreart
//                  - clothing
//
// limit:           (optional) Limit the number of results.
// 
// Returns
// -------
//
function qruqsp_tutorials_downloadPDF($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        // PDF options
        'layout'=>array('required'=>'no', 'blank'=>'no', 'default'=>'list', 'name'=>'Layout',
            'validlist'=>array('single', 'double', 'triple')), 
        'coverpage'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Cover Page'), 
        'toc'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Table of Contents'), 
        'doublesided'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Double Sided'), 
        'title'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Title'), 
        'removetext'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Text to Remove'), 
        'tutorials'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'idlist', 'name'=>'Tutorials'), // List of tutorials to include
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
    $rc = qruqsp_tutorials_checkAccess($ciniki, $args['tnid'], 'qruqsp.tutorials.downloadPDF'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    //
    // Load the list of tutorials organized by category
    // FIXME: Needs to be updated to decide if library categories or personal categories
    //
    if( count($args['tutorials']) > 1 ) {

/*    $strsql = "SELECT qruqsp_tutorials.id, "
        . "IFNULL(qruqsp_tutorial_tags.tag_name, '') AS category, "
        . "IFNULL(qruqsp_tutorial_settings.detail_value, 99) AS catsequence, "
        . "qruqsp_tutorials.title, "
        . "qruqsp_tutorials.sequence, "
        . "qruqsp_tutorials.primary_image_id, "
        . "qruqsp_tutorials.content "
        . "FROM qruqsp_tutorials "
        . "LEFT JOIN qruqsp_tutorial_tags ON ("
            . "qruqsp_tutorials.id = qruqsp_tutorial_tags.tutorial_id "
            . "AND qruqsp_tutorial_tags.tag_type = 10 "
            . "AND qruqsp_tutorial_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN qruqsp_tutorial_settings ON ("
            . "CONCAT_WS('-', 'category', 'sequence', qruqsp_tutorial_tags.permalink) = qruqsp_tutorial_settings.detail_key "
            . "AND qruqsp_tutorial_settings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE qruqsp_tutorials.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND qruqsp_tutorials.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['tutorials']) . ") "
        . "ORDER BY catsequence, category, qruqsp_tutorials.sequence, title, qruqsp_tutorials.id "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'qruqsp.tutorials', array(
        array('container'=>'categories', 'fname'=>'category',
            'fields'=>array('name'=>'category')),
        array('container'=>'tutorials', 'fname'=>'id',
            'fields'=>array('id', 'title', 'sequence', 'image_id'=>'primary_image_id', 'content')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['categories']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.23', 'msg'=>'Unable to find tutorials'));
    } else {
        $categories = $rc['categories'];
    } */
    } else {
        $categories = array(
            array('name' => '', 'tutorials' => array()),
            );
    }

    //
    // Load the list of tutorials, and their steps
    //
    $strsql = "SELECT tutorials.id, "
        . "tutorials.tnid, "
        . "tutorials.title AS tutorial_title, "
        . "tutorials.synopsis AS tutorial_synopsis, "
        . "steps.id AS step_id, "
        . "steps.content_type, "
        . "steps.sequence, "
        . "content.title AS step_title, "
        . "content.image1_id, "
        . "content.image2_id, "
        . "content.image3_id, "
        . "content.image4_id, "
        . "content.image5_id, "
        . "content.content "
        . "FROM qruqsp_tutorials AS tutorials "
        . "LEFT JOIN qruqsp_tutorial_steps AS steps ON ("
            . "tutorials.id = steps.tutorial_id "
            . ") "
        . "LEFT JOIN qruqsp_tutorial_content AS content ON ("
            . "steps.content_id = content.id "
            . ") "
        . "WHERE tutorials.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['tutorials']) . ") "
        // Make sure published in library or they own it
        . "AND ((tutorials.flags&0x01) = 0x01 " 
            . "OR tutorials.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "ORDER BY tutorials.id, steps.sequence, step_title "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'qruqsp.tutorials', array(
        array('container'=>'tutorials', 'fname'=>'id',
            'fields'=>array('id', 'tnid', 'title'=>'tutorial_title', 'synopsis'=>'tutorial_synopsis')),
        array('container'=>'steps', 'fname'=>'step_id',
            'fields'=>array('id'=>'step_id', 'content_type', 'sequence', 'title'=>'step_title', 
                'image1_id', 'image2_id', 'image3_id', 'image4_id', 'image5_id', 'content')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tutorials']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.24', 'msg'=>'Unable to find tutorials'));
    } else {
        if( count($args['tutorials']) > 1 ) {
            foreach($categories as $cid => $cat) {
                foreach($cat['tutorials'] as $tid => $tutorial) {
                    if( isset($rc['tutorials'][$tid]['steps']) ) {
                        $categories[$cid]['tutorials'][$tid]['steps'] = $rc['tutorials'][$tid]['steps'];
                    }
                }
            }
        } else {
            $categories[0]['tutorials'] = $rc['tutorials'];
        }
    }

    if( count($categories) < 1 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.25', 'msg'=>'Unable to find tutorials'));
    }
    
/*    //
    // Check for coverpage settings
    //
    if( isset($args['coverpage']) && $args['coverpage'] == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
        $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'qruqsp_tutorial_settings', 'tnid', $args['tnid'], 'qruqsp.tutorials', 'settings', 'coverpage');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['settings']['coverpage-image']) ) {
            $args['coverpage-image'] = $rc['settings']['coverpage-image'];
        }
    }
*/
    //
    // Generate PDF
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tutorials', 'templates', $args['layout']);
    $function = 'qruqsp_tutorials_templates_' . $args['layout'];
    $rc = $function($ciniki, $args['tnid'], $categories, $args);
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    if( isset($args['title']) && $args['title'] != '' ) {
        $filename = preg_replace('/[^a-zA-Z0-9_]/', '', preg_replace('/ /', '_', $args['title']));
    } else {
        foreach($categories as $cat) {
            foreach($cat['tutorials'] as $tutorial) {
                $filename = preg_replace('/[^a-zA-Z0-9_]/', '', preg_replace('/ /', '_', $tutorial['title']));
                break;
            }
            break;
        }
    }
    if( isset($rc['pdf']) ) {
        $rc['pdf']->Output($filename . '.pdf', 'D');
    }

    return array('stat'=>'exit');
}
?>
