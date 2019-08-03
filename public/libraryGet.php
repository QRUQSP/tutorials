<?php
//
// Description
// ===========
// This method will return all the information about an library.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the library is attached to.
// library_id:          The ID of the library to get the details for.
//
// Returns
// -------
//
function qruqsp_tutorials_libraryGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'library_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Library'),
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
    // Return default for new Library
    //
    if( $args['library_id'] == 0 ) {
        $library = array('id'=>0,
            'tutorial_id'=>'',
            'category'=>'',
            'subcategory'=>'',
        );
    }

    //
    // Get the details for an existing Library
    //
    else {
        $strsql = "SELECT qruqsp_tutorial_library.id, "
            . "qruqsp_tutorial_library.tutorial_id, "
            . "qruqsp_tutorial_library.category, "
            . "qruqsp_tutorial_library.subcategory "
            . "FROM qruqsp_tutorial_library "
            . "WHERE qruqsp_tutorial_library.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND qruqsp_tutorial_library.id = '" . ciniki_core_dbQuote($ciniki, $args['library_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tutorials', array(
            array('container'=>'librarys', 'fname'=>'id', 
                'fields'=>array('tutorial_id', 'category', 'subcategory'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.47', 'msg'=>'Library not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['librarys'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.48', 'msg'=>'Unable to find Library'));
        }
        $library = $rc['librarys'][0];
    }

    return array('stat'=>'ok', 'library'=>$library);
}
?>
