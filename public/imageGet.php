<?php
//
// Description
// -----------
// This function will return the image binary data in jpg format.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the image from.
// image_id:            The ID if the image requested.
// version:             The version of the image (original, thumbnail)
//
//                      *note* the thumbnail is not referring to the size, but to a 
//                      square cropped version, designed for use as a thumbnail.
//                      This allows only a portion of the original image to be used
//                      for thumbnails, as some images are too complex for thumbnails.
//
// maxwidth:            The max width of the longest side should be.  This allows
//                      for generation of thumbnail's, etc.
//
// maxlength:           The max length of the longest side should be.  This allows
//                      for generation of thumbnail's, etc.
//
// Returns
// -------
// Binary image data
//
function qruqsp_tutorials_imageGet($ciniki) {
    //
    // Check args
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'image_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Image'), 
        'tutorial_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tutorial'), 
        'version'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Version'),
        'maxwidth'=>array('required'=>'no', 'default'=>'0', 'blank'=>'no', 'name'=>'Maximum Width'),
        'maxheight'=>array('required'=>'no', 'default'=>'0', 'blank'=>'no', 'name'=>'Maximum Height'),
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
    $rc = qruqsp_tutorials_checkAccess($ciniki, $args['tnid'], 'qruqsp.tutorials.libraryGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the tutorial
    //
    $strsql = "SELECT tutorials.id, tutorials.tnid, tutorials.flags "
        . "FROM qruqsp_tutorials AS tutorials "
        . "WHERE tutorials.id = '" . ciniki_core_dbQuote($ciniki, $args['tutorial_id']) . "' "
        . "AND (flags&0x01) = 0x01 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.tutorials', 'tutorial');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.49', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    if( !isset($rc['tutorial']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.50', 'msg'=>'Unable to find requested item'));
    }
    $tutorial = $rc['tutorial'];


    //ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'getImage');
    //return ciniki_images_getImage($ciniki, $args['tnid'], $args['image_id'], $args['version'], $args['maxwidth'], $args['maxheight']);
    if( $args['version'] == 'thumbnail' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
        $rc = ciniki_images_loadCacheThumbnail($ciniki, $tutorial['tnid'], $args['image_id'], $args['maxwidth'], $args['maxheight']);
    } else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheOriginal');
        $rc = ciniki_images_loadCacheOriginal($ciniki, $tutorial['tnid'], $args['image_id'], $args['maxwidth'], $args['maxheight']);
    }
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $rc['last_updated']) . ' GMT', true, 200);
    if( isset($ciniki['request']['args']['attachment']) && $ciniki['request']['args']['attachment'] == 'yes' ) {
        header('Content-Disposition: attachment; filename="' . $rc['original_filename'] . '"');
    }
    header("Content-type: image/jpeg"); 

    echo $rc['image'];
    exit();
}
?>
