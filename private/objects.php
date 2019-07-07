<?php
//
// Description
// -----------
// This function returns the list of objects for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_tutorials_objects(&$ciniki) {
    //
    // Build the objects
    //
    $objects = array();

    $objects['tutorial'] = array(
        'name' => 'Tutorial',
        'sync' => 'yes',
        'o_name' => 'tutorial',
        'o_container' => 'tutorials',
        'table' => 'qruqsp_tutorials',
        'fields' => array(
            'title' => array('name'=>'Title'),
            'permalink' => array('name'=>'Permalink', 'default'=>''),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'synopsis' => array('name'=>'Synopsis', 'default'=>''),
            'content' => array('name'=>'Content', 'default'=>''),
            'date_published' => array('name'=>'Date Published', 'default'=>''),
            ),
        'history_table' => 'qruqsp_tutorials_history',
        );
    $objects['step'] = array(
        'name' => 'Step',
        'sync' => 'yes',
        'o_name' => 'step',
        'o_container' => 'steps',
        'table' => 'qruqsp_tutorial_steps',
        'fields' => array(
            'tutorial_id' => array('name'=>'Tutorial', 'ref'=>'qruqsp.tutorials.tutorial'),
            'content_id' => array('name'=>'Content', 'ref'=>'qruqsp.tutorials.content'),
            'content_type' => array('name'=>'Type', 'default'=>'10'),
            'sequence' => array('name'=>'Order', 'default'=>'1'),
            ),
        'history_table' => 'qruqsp_tutorials_history',
        );
    $objects['content'] = array(
        'name' => 'Content',
        'sync' => 'yes',
        'o_name' => 'content',
        'o_container' => 'content',
        'table' => 'qruqsp_tutorial_content',
        'fields' => array(
            'title' => array('name'=>'Title'),
            'image1_id' => array('name'=>'First Image', 'ref'=>'ciniki.images.image', 'default'=>0),
            'image2_id' => array('name'=>'Second Image', 'ref'=>'ciniki.images.image', 'default'=>0),
            'image3_id' => array('name'=>'Third Image', 'ref'=>'ciniki.images.image', 'default'=>0),
            'image4_id' => array('name'=>'Fourth Image', 'ref'=>'ciniki.images.image', 'default'=>0),
            'image5_id' => array('name'=>'Fifth Image', 'ref'=>'ciniki.images.image', 'default'=>0),
            'content' => array('name'=>'Content', 'default'=>''),
            ),
        'history_table' => 'qruqsp_tutorials_history',
        );
    $objects['tag'] = array(
        'name' => 'Tag',
        'sync' => 'yes',
        'o_name' => 'tag',
        'o_container' => 'tags',
        'table' => 'qruqsp_tutorial_tags',
        'fields' => array(
            'tutorial_id' => array('name'=>'Tutorial', 'ref'=>'qruqsp.tutorials.tutorial'),
            'tag_type' => array('name'=>'Tag Type'),
            'tag_name' => array('name'=>'Tag Name'),
            'permalink' => array('name'=>'Permalink'),
            ),
        'history_table' => 'qruqsp_tutorials_history',
        );
    $objects['bookmark'] = array(
        'name' => 'Bookmark',
        'sync' => 'yes',
        'o_name' => 'bookmark',
        'o_container' => 'bookmarks',
        'table' => 'qruqsp_tutorial_bookmarks',
        'fields' => array(
            'tutorial_id' => array('name'=>'Tutorial', 'ref'=>'qruqsp.tutorials.tutorial'),
            ),
        'history_table' => 'qruqsp_tutorials_history',
        );
    //
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
