<?php
//
// Description
// -----------
// This method will return the list of Tutorials for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Tutorial for.
//
// Returns
// -------
//
function qruqsp_tutorials_tutorialList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'list'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'List Requested'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'contributor_tnid'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Contributor Tenant ID'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'tutorials', 'private', 'checkAccess');
    $rc = qruqsp_tutorials_checkAccess($ciniki, $args['tnid'], 'qruqsp.tutorials.tutorialList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    
    //
    // Setup return array
    //
    $rsp = array('stat'=>'ok');
    
    //
    // Setup library tnid if in config
    //
    $library_tnid = 0;
    if( isset($ciniki['config']['qruqsp.tutorials']['library.tnid'])
        && $ciniki['config']['qruqsp.tutorials']['library.tnid'] > 0
        ) {
        $library_tnid = $ciniki['config']['qruqsp.tutorials']['library.tnid'];
    }

    //
    // Return the most recent additions to the library
    //
    if( $args['list'] == 'latest' ) {
        //
        // Get the list of submitted uncategoried entries
        //
        if( isset($args['tnid']) == $library_tnid ) {
            $strsql = "SELECT DISTINCT tutorials.id, "
                . "tutorials.tnid, "
                . "tutorials.title, "
                . "tutorials.permalink, "
                . "tutorials.synopsis, "
                . "tenants.name AS author, "
                . "library.date_added AS date_published "
                . "FROM qruqsp_tutorial_library AS library, qruqsp_tutorials AS tutorials, ciniki_tenants AS tenants "
                . "WHERE library.tnid = '" . ciniki_core_dbQuote($ciniki, $library_tnid) . "' "
                . "AND library.category = '' "
                . "AND library.tutorial_id = tutorials.id "
                . "AND tutorials.tnid = tenants.id "
                . "GROUP BY library.tutorial_id "
                . "ORDER BY date_published DESC "
                . "LIMIT 25 "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tutorials', array(
                array('container'=>'tutorials', 'fname'=>'id', 
                    'fields'=>array('id', 'tnid', 'title', 'permalink', 'synopsis', 'author', 'date_published'),
                    'utctotz'=>array('date_published'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.15', 'msg'=>'Unable to load tutorials', 'err'=>$rc['err']));
            }
            $rsp['submitted'] = isset($rc['tutorials']) ? $rc['tutorials'] : array();
        }

        $strsql = "SELECT DISTINCT tutorials.id, "
            . "tutorials.tnid, "
            . "tutorials.title, "
            . "tutorials.permalink, "
            . "tutorials.synopsis, "
            . "tenants.name AS author, "
            . "MIN(library.date_added) AS date_published "
            . "FROM qruqsp_tutorial_library AS library, qruqsp_tutorials AS tutorials, ciniki_tenants AS tenants "
            . "WHERE library.tnid = '" . ciniki_core_dbQuote($ciniki, $library_tnid) . "' "
            . "AND library.category <> '' "
            . "AND library.tutorial_id = tutorials.id "
            . "AND tutorials.tnid = tenants.id "
            . "GROUP BY library.tutorial_id "
            . "ORDER BY date_published DESC "
            . "LIMIT 25 "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tutorials', array(
            array('container'=>'tutorials', 'fname'=>'id', 
                'fields'=>array('id', 'tnid', 'title', 'permalink', 'synopsis', 'author', 'date_published'),
                'utctotz'=>array('date_published'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.15', 'msg'=>'Unable to load tutorials', 'err'=>$rc['err']));
        }
        if( isset($rc['tutorials']) ) {
            $rsp['tutorials'] = $rc['tutorials'];
            $rsp['tutorial_ids'] = array();
            foreach($rsp['tutorials'] as $k => $v) {
                $rsp['tutorial_ids'][] = $v['permalink'];
            }
        } else {
            $rsp['tutorials'] = array();
            $rsp['tutorial_ids'] = array();
        }
    }
    //
    // Get the list of categories for the library
    //
    elseif( $args['list'] == 'categories' ) {
        $strsql = "SELECT category, subcategory, COUNT(tutorial_id) AS num_tutorials "
            . "FROM qruqsp_tutorial_library "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $library_tnid) . "' "
            . "AND category <> '' "
            . "GROUP BY category, subcategory "
            . "ORDER BY category, subcategory "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tutorials', array(
            array('container'=>'categories', 'fname'=>'category', 'fields'=>array('category', 'num_tutorials'), 
                'sums'=>array('num_tutorials')),
            array('container'=>'subcategories', 'fname'=>'subcategory', 'fields'=>array('subcategory', 'num_tutorials')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.12', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
        }
        $rsp['categories'] = isset($rc['categories']) ? $rc['categories'] : array();

        //
        // Load the tutorial list if specified
        //
        if( isset($args['category']) && $args['category'] != '' ) {
            $strsql = "SELECT tutorials.id, "
                . "tutorials.tnid, "
                . "tutorials.title, "
                . "tutorials.permalink, "
                . "tutorials.synopsis, "
                . "tenants.name AS author, "
                . "library.date_added AS date_published "
                . "FROM qruqsp_tutorial_library AS library, qruqsp_tutorials AS tutorials, ciniki_tenants AS tenants "
                . "WHERE library.tnid = '" . ciniki_core_dbQuote($ciniki, $library_tnid) . "' "
                . "AND library.category <> '' "
                . "AND library.tutorial_id = tutorials.id "
                . "AND tutorials.tnid = tenants.id "
                . "ORDER BY library.date_added DESC "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tutorials', array(
                array('container'=>'tutorials', 'fname'=>'id', 
                    'fields'=>array('id', 'tnid', 'title', 'permalink', 'synopsis', 'author', 'date_published'),
                    'utctotz'=>array('date_published'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.18', 'msg'=>'Unable to load tutorials', 'err'=>$rc['err']));
            }
            if( isset($rc['tutorials']) ) {
                $rsp['tutorials'] = $rc['tutorials'];
                $rsp['tutorial_ids'] = array();
                foreach($rsp['tutorials'] as $k => $v) {
                    $rsp['tutorial_ids'][] = $v['permalink'];
                }
            } else {
                $rsp['tutorials'] = array();
                $rsp['tutorial_ids'] = array();
            }
        }
        
    }
    //
    // Return the list of contributors (tenants) to the library
    //
    elseif( $args['list'] == 'contributors' ) {
        $strsql = "SELECT tenants.id, "
            . "tenants.name, "
            . "COUNT(tutorials.id) AS num_tutorials "
            . "FROM qruqsp_tutorial_library AS library, qruqsp_tutorials AS tutorials, ciniki_tenants AS tenants "
            . "WHERE library.tnid = '" . ciniki_core_dbQuote($ciniki, $library_tnid) . "' "
            . "AND library.category <> '' "
            . "AND tutorials.tnid = tenants.id "
            . "GROUP BY tenants.id "
            . "ORDER BY tenants.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tutorials', array(
            array('container'=>'contributors', 'fname'=>'id', 'fields'=>array('id', 'name', 'num_tutorials')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.11', 'msg'=>'Unable to load contributors', 'err'=>$rc['err']));
        }
        $rsp['contributors'] = isset($rc['contributors']) ? $rc['contributors'] : array();
       
        //
        // Get the list of tutorials for a contributor if specified
        //
        if( isset($args['contributor_tnid']) && $args['contributor_tnid'] != '' ) {
            $strsql = "SELECT tutorials.id, "
                . "tutorials.tnid, "
                . "tutorials.title, "
                . "tutorials.permalink, "
                . "tutorials.synopsis, "
                . "MIN(library.date_added) AS date_published "
                . "FROM qruqsp_tutorial_library AS library, qruqsp_tutorials AS tutorials "
                . "WHERE library.tnid = '" . ciniki_core_dbQuote($ciniki, $library_tnid) . "' "
                . "AND library.tutorial_id = tutorials.id "
                . "AND tutorials.tnid = '" . ciniki_core_dbQuote($ciniki, $args['contributor_tnid']) . "' "
                . "GROUP BY tutorials.id "
                . "ORDER BY date_published DESC "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tutorials', array(
                array('container'=>'tutorials', 'fname'=>'id', 
                    'fields'=>array('id', 'tnid', 'title', 'permalink', 'synopsis', 'date_published'),
                    'utctotz'=>array('date_published'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.13', 'msg'=>'Unable to load tutorials', 'err'=>$rc['err']));
            }
            if( isset($rc['tutorials']) ) {
                $rsp['tutorials'] = $rc['tutorials'];
                $rsp['tutorials_ids'] = array();
                foreach($rsp['tutorials'] as $k => $v) {
                    $rsp['tutorials_ids'][] = $v['id'];
                }
            } else {
                $rsp['tutorials'] = array();
                $rsp['tutorials_ids'] = array();
            }
        }
    }
    //
    // Return the list of contributors (tenants) to the library
    //
    elseif( $args['list'] == 'bookmarked' ) {
        $strsql = "SELECT tutorials.id, "
            . "tutorials.tnid, "
            . "tutorials.title, "
            . "tutorials.permalink, "
            . "tutorials.synopsis, "
            . "tenants.name AS author, "
            . "bookmarks.date_added "
            . "FROM qruqsp_tutorial_bookmarks AS bookmarks, qruqsp_tutorial_library AS library, qruqsp_tutorials AS tutorials, ciniki_tenants AS tenants "
            . "WHERE bookmarks.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND bookmarks.tutorial_id = library.tutorial_id "
            . "AND library.category <> '' "
            . "AND bookmarks.tutorial_id = tutorials.id "
            . "AND tutorials.tnid = tenants.id "
            . "ORDER BY tutorials.date_added DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tutorials', array(
            array('container'=>'tutorials', 'fname'=>'id', 
                'fields'=>array('id', 'tnid', 'title', 'permalink', 'synopsis', 'author', 'date_added'),
                'utctotz'=>array('date_added'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.14', 'msg'=>'Unable to load tutorials', 'err'=>$rc['err']));
        }
        if( isset($rc['tutorials']) ) {
            $rsp['tutorials'] = $rc['tutorials'];
            $rsp['tutorials_ids'] = array();
            foreach($rsp['tutorials'] as $k => $v) {
                $rsp['tutorials_ids'][] = $v['id'];
            }
        } else {
            $rsp['tutorials'] = array();
            $rsp['tutorials_ids'] = array();
        }
    }
    //
    // My Tutorials
    //
    elseif( $args['list'] == 'mytutorials' ) {
        //
        // Get the list of categories for the tenant
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'qruqsp.tutorials', 0x10) ) {
            $strsql = "SELECT tags.permalink, "
                . "tags.tag_name, "
                . "COUNT(tutorials.id) AS num_tutorials "
                . "FROM qruqsp_tutorial_tags AS tags "
                . "INNER JOIN qruqsp_tutorials AS tutorials ON ("
                    . "tags.tutorial_id = tutorials.id "
                    . "AND tutorials.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE tags.tag_type = 50 "
                . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY permalink "
                . "ORDER BY permalink "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tutorials', array(
                array('container'=>'categories', 'fname'=>'permalink', 
                    'fields'=>array('permalink', 'category'=>'tag_name', 'num_tutorials')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.16', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
            }
            $rsp['categories'] = isset($rc['categories']) ? $rc['categories'] : array();

            //
            // Check for tutorials not in a category
            //
            $strsql = "SELECT IFNULL(tags.tag_name, 'Unknown'), COUNT(tutorials.id) AS num_tutorials "
                . "FROM qruqsp_tutorials AS tutorials "
                . "LEFT JOIN qruqsp_tutorial_tags AS tags ON ("
                    . "tutorials.id = tags.tutorial_id "
                    . "AND tags.tag_type = 50 "
                    . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE tutorials.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "GROUP BY tags.tag_name "
                . "HAVING ISNULL(tags.tag_name) "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.tutorials', 'nocat');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.20', 'msg'=>'Unable to load uncategoried', 'err'=>$rc['err']));
            }
            if( isset($rc['nocat']['num_tutorials']) && $rc['nocat']['num_tutorials'] > 0 ) {
                $rsp['categories'][] = array('permalink'=>'', 'category'=>'Unknown', 'num_tutorials'=>$rc['nocat']['num_tutorials']);
            }

            //
            // Load the tutorial list if specified
            //
            if( isset($args['category']) && $args['category'] == '_latest_' ) {
                $strsql = "SELECT tutorials.id, "
                    . "tutorials.tnid, "
                    . "tutorials.title, "
                    . "tutorials.permalink, "
                    . "tutorials.synopsis, "
                    . "tutorials.flags, "
                    . "tutorials.date_added "
                    . "FROM qruqsp_tutorials AS tutorials "
                    . "WHERE tutorials.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "ORDER BY tutorials.date_added DESC "
                    . "LIMIT 25 "
                    . "";
            } elseif( isset($args['category']) && $args['category'] != '' ) {
                $strsql = "SELECT tutorials.id, "
                    . "tutorials.tnid, "
                    . "tutorials.title, "
                    . "tutorials.permalink, "
                    . "tutorials.synopsis, "
                    . "tutorials.flags, "
                    . "tutorials.date_added "
                    . "FROM qruqsp_tutorial_tags AS tags, qruqsp_tutorials AS tutorials "
                    . "WHERE tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "AND tags.tag_type = 50 "
                    . "AND tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
                    . "AND tags.tutorial_id = tutorials.id "
                    . "AND tags.tnid = tutorials.tnid "
                    . "ORDER BY tutorials.date_added DESC "
                    . "";
            } else {
                $strsql = "SELECT tutorials.id, "
                    . "tutorials.tnid, "
                    . "tutorials.title, "
                    . "tutorials.permalink, "
                    . "tutorials.synopsis, "
                    . "tutorials.flags, "
                    . "tutorials.date_added, "
                    . "tags.tag_name "
                    . "FROM qruqsp_tutorials AS tutorials "
                    . "LEFT JOIN qruqsp_tutorial_tags AS tags ON ("
                        . "tutorials.id = tags.tutorial_id "
                        . "AND tags.tag_type = 50 "
                        . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . ") "
                    . "WHERE tutorials.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "HAVING ISNULL(tags.tag_name) "
                    . "ORDER BY tutorials.date_added DESC "
                    . "";
            }
        } else {
            $strsql = "SELECT tutorials.id, "
                . "tutorials.tnid, "
                . "tutorials.title, "
                . "tutorials.permalink, "
                . "tutorials.synopsis, "
                . "tutorials.flags, "
                . "tutorials.date_added "
                . "FROM qruqsp_tutorials AS tutorials "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY tutorials.date_added DESC "
                . "";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.tutorials', array(
            array('container'=>'tutorials', 'fname'=>'id', 
                'fields'=>array('id', 'tnid', 'title', 'permalink', 'synopsis', 'flags', 'date_added'),
                'utctotz'=>array('date_added'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.tutorials.17', 'msg'=>'Unable to load tutorials', 'err'=>$rc['err']));
        }
        if( isset($rc['tutorials']) ) {
            $rsp['tutorials'] = $rc['tutorials'];
            $rsp['tutorial_ids'] = array();
            foreach($rsp['tutorials'] as $k => $v) {
                $rsp['tutorial_ids'][] = $v['permalink'];
            }
        } else {
            $rsp['tutorials'] = array();
            $rsp['tutorial_ids'] = array();
        }
    }

    return $rsp;
}
?>
