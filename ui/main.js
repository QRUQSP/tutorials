//
// This is the main app for the tutorials module
//
function qruqsp_tutorials_main() {
    //
    // The panel to list the tutorial
    //
    this.menu = new M.panel('Tutorial Library', 'qruqsp_tutorials_main', 'menu', 'mc', 'large narrowaside', 'sectioned', 'qruqsp.tutorials.main.menu');
    this.menu.category = '';
    this.menu.mycategory = '_latest_';
    this.menu.contributor_tnid = 0;
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
        'tabs':{'label':'', 'type':'menutabs', 'selected':'categories', 'tabs':{
//            'latest':{'label':'Library', 'fn':'M.qruqsp_tutorials_main.menu.switchTab("latest");'},
            'categories':{'label':'Library', 'fn':'M.qruqsp_tutorials_main.menu.switchTab("categories");'},
//            'contributors':{'label':'Contributors', 'fn':'M.qruqsp_tutorials_main.menu.switchTab("contributors");'},
//            'bookmarked':{'label':'Bookmarked', 'fn':'M.qruqsp_tutorials_main.menu.switchTab("bookmarked");'},
            'mytutorials':{'label':'My Tutorials', 'fn':'M.qruqsp_tutorials_main.menu.switchTab("mytutorials");'},
            }},
        'categories':{'label':'Categories', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.qruqsp_tutorials_main.menu.sections.tabs.selected == 'categories' ? 'yes' : 'no'; },
            'noData':'No Categories',
            },
        'mycategories':{'label':'Website Categories', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.qruqsp_tutorials_main.menu.sections.tabs.selected == 'mytutorials' && M.modFlagOn('qruqsp.tutorials', 0x10) ? 'yes' : 'no'; },
            'noData':'No Categories',
            },
        'contributors':{'label':'Contributors', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.qruqsp_tutorials_main.menu.sections.tabs.selected == 'contributors' ? 'yes' : 'no'; },
            'noData':'No Contributors',
            },
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':2,
            'cellClasses':[''],
            'hint':'Search tutorial',
            'noData':'No tutorial found',
            'headerValues':['Title', 'PDF'],
            'cellClasses':['multiline', '', '', ''],
            },
        'submitted':{'label':'Submissions', 'type':'simplegrid', 'num_cols':4,
            'visible':function() { return M.qruqsp_tutorials_main.menu.sections.tabs.selected == 'latest' && M.qruqsp_tutorials_main.menu.data.submitted != null && M.qruqsp_tutorials_main.menu.data.submitted.length > 0 ? 'yes' : 'no'; },
            'headerValues':['Title', 'Author', 'Date Submitted'],
            'cellClasses':['multiline', '', ''],
            'noData':'No submissions',
            'editFn':function(s, i, d) { 
                if( (M.userPerms&0x01) == 0x01 ) {
                    return 'M.qruqsp_tutorials_main.edit.open(\'M.qruqsp_tutorials_main.menu.open();\',' + d.id + ',null);' 
                }
                return '';
                },
            },
        'tutorials':{'label':'Tutorials', 'type':'simplegrid', 'num_cols':4,
            'visible':function() { return M.qruqsp_tutorials_main.menu.sections.tabs.selected == 'latest' || (M.qruqsp_tutorials_main.menu.sections.tabs.selected == 'categories' && M.qruqsp_tutorials_main.menu.category != '') ? 'yes' : 'no'; },
            'headerValues':['Title', 'Author', 'Date Published', 'PDF'],
            'cellClasses':['multiline', '', '', ''],
            'noData':'No tutorials',
            },
        'contributortutorials':{'label':'Tutorials', 'type':'simplegrid', 'num_cols':4,
            'visible':function() { return M.qruqsp_tutorials_main.menu.sections.tabs.selected == 'contributors' ? 'yes' : 'no'; },
            'headerValues':['Title', 'Date Published', 'PDF'],
            'cellClasses':['multiline', '', '', ''],
            'noData':'No tutorials',
            },
        'bookmarked':{'label':'Tutorials', 'type':'simplegrid', 'num_cols':4,
            'visible':function() { return M.qruqsp_tutorials_main.menu.sections.tabs.selected == 'bookmarked' ? 'yes' : 'no'; },
            'headerValues':['Title', 'Author', 'Date Published', 'PDF'],
            'cellClasses':['multiline', '', '', ''],
            'noData':'No tutorials bookmarked',
            },
        'mytutorials':{'label':'Tutorials', 'type':'simplegrid', 'num_cols':4,
            'visible':function() { return M.qruqsp_tutorials_main.menu.sections.tabs.selected == 'mytutorials' ? 'yes' : 'no'; },
            'headerValues':['Title', 'Website', 'Library', 'PDF'],
            'cellClasses':['multiline', '', '', ''],
            'noData':'No tutorials',
            'addTxt':'Add Tutorial',
            'addFn':'M.qruqsp_tutorials_main.edit.open(\'M.qruqsp_tutorials_main.menu.open();\',0,null);',
            'editFn':function(s, i, d) { 
                if( d.tnid == M.curTenantID ) { 
                    return 'M.qruqsp_tutorials_main.edit.open(\'M.qruqsp_tutorials_main.menu.open();\',' + d.id + ',null);' 
                }
                return '';
                },
            },
    }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('qruqsp.tutorials.tutorialSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.qruqsp_tutorials_main.menu.liveSearchShow('search',null,M.gE(M.qruqsp_tutorials_main.menu.panelUID + '_' + s), rsp.tutorials);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        switch(j) {
            case 0: return '<span class="maintext">' + d.title + '</span><span class="subtext">' + d.synopsis + '</span>';
            case 1: return '<span onclick="event.stopPropagation();M.qruqsp_tutorials_main.menu.downloadPDF(' + d.id + ',\'single\');" class="faicon">&#xf1c1;</span>'
                + '&nbsp;&nbsp;<span onclick="event.stopPropagation();M.qruqsp_tutorials_main.menu.downloadPDF(' + d.id + ',\'double\');" class="faicon">&#xf0db;</span>'
                + '&nbsp;&nbsp;<span onclick="event.stopPropagation();M.qruqsp_tutorials_main.menu.downloadPDF(' + d.id + ',\'triple\');" class="faicon">&#xf00b;</span>';
        }
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.qruqsp_tutorials_main.tutorial.open(\'M.qruqsp_tutorials_main.menu.open();\',\'' + d.id + '\');';
    }
    this.menu.sectionData = function(s) {
        if( s == 'categories' || s == 'mycategories' ) {
            return this.data['categories'];
        }
        if( s == 'tutorials' || s == 'contributortutorials' || s == 'bookmarked' || s == 'mytutorials' ) {
            return this.data['tutorials'];
        }
        return this.data[s];
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'categories' || s == 'mycategories' ) {    
            return d.category + ' <span class="count">' + d.num_tutorials + '</span>';
        }
        if( s == 'contributors' ) {    
            return d.name + ' <span class="count">' + d.num_tutorials + '</span>';
        }
        if( s == 'search' || s == 'submitted' || s == 'tutorials' || s == 'bookmarked' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.title + '</span><span class="subtext">' + d.synopsis + '</span>';
                case 1: return d.author;
                case 2: return d.date_published;
                case 3: return '<span onclick="event.stopPropagation();M.qruqsp_tutorials_main.menu.downloadPDF(' + d.id + ',\'single\');" class="faicon">&#xf1c1;</span>'
                    + '&nbsp;&nbsp;<span onclick="event.stopPropagation();M.qruqsp_tutorials_main.menu.downloadPDF(' + d.id + ',\'double\');" class="faicon">&#xf0db;</span>'
                    + '&nbsp;&nbsp;<span onclick="event.stopPropagation();M.qruqsp_tutorials_main.menu.downloadPDF(' + d.id + ',\'triple\');" class="faicon">&#xf00b;</span>';
            }
        }
        if( s == 'contributortutorials' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.title + '</span><span class="subtext">' + d.synopsis + '</span>';
                case 1: return d.date_published;
                case 2: return '<span onclick="event.stopPropagation();M.qruqsp_tutorials_main.menu.downloadPDF(' + d.id + ',\'single\');" class="faicon">&#xf1c1;</span>'
                    + '&nbsp;&nbsp;<span onclick="event.stopPropagation();M.qruqsp_tutorials_main.menu.downloadPDF(' + d.id + ',\'double\');" class="faicon">&#xf0db;</span>'
                    + '&nbsp;&nbsp;<span onclick="event.stopPropagation();M.qruqsp_tutorials_main.menu.downloadPDF(' + d.id + ',\'triple\');" class="faicon">&#xf00b;</span>';
            }
        }
        if( s == 'mytutorials' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.title + '</span><span class="subtext">' + d.synopsis + '</span>';
                case 1: return (d.flags&0x10) == 0x10 ? 'Visible' : '';
                case 2: return (d.flags&0x01) == 0x01 ? 'Published' : '';
                case 3: return '<span onclick="event.stopPropagation();M.qruqsp_tutorials_main.menu.downloadPDF(' + d.id + ',\'single\');" class="faicon">&#xf1c1;</span>'
                    + '&nbsp;&nbsp;<span onclick="event.stopPropagation();M.qruqsp_tutorials_main.menu.downloadPDF(' + d.id + ',\'double\');" class="faicon">&#xf0db;</span>'
                    + '&nbsp;&nbsp;<span onclick="event.stopPropagation();M.qruqsp_tutorials_main.menu.downloadPDF(' + d.id + ',\'triple\');" class="faicon">&#xf00b;</span>';
            }
        }
    }
    this.menu.rowClass = function(s, i, d) {
        if( s == 'categories' && d.permalink == this.category ) {
            return 'highlight';
        }
        if( s == 'contributors' && d.id == this.contributor_tnid ) {
            return 'highlight';
        }
        if( s == 'mycategories' && d.permalink == this.mycategory ) {
            return 'highlight';
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'categories' ) {
            return 'M.qruqsp_tutorials_main.menu.selectCategory("' + d.permalink + '");';
        }
        if( s == 'mycategories' ) {
            return 'M.qruqsp_tutorials_main.menu.selectMyCategory("' + d.permalink + '");';
        }
        if( s == 'contributors' ) {
            return 'M.qruqsp_tutorials_main.menu.selectContributor("' + d.id + '");';
        }
        if( s == 'submitted' || s == 'tutorials' || s == 'bookmarked' || s == 'contributortutorials' || s == 'mytutorials' ) {
            return 'M.qruqsp_tutorials_main.tutorial.open(\'M.qruqsp_tutorials_main.menu.open();\',\'' + d.id + '\');';
        }
    }
    this.menu.switchTab = function(t) {
        this.sections.tabs.selected = t;
        this.open();
    }
    this.menu.selectCategory = function(c) {
        this.category = c;
        this.open();
    }
    this.menu.selectMyCategory = function(c) {
        this.mycategory = c;
        this.open();
    }
    this.menu.selectContributor = function(c) {
        this.contributor_tnid = c;
        this.open();
    }
    this.menu.downloadPDF = function(id,f) {
        var args = {'tnid':M.curTenantID, 'layout':f, 'output':'pdf', 'tutorials':id};
        M.api.openPDF('qruqsp.tutorials.downloadPDF', args);
    }
    this.menu.open = function(cb) {
        if( this.sections.tabs.selected == 'categories' 
            || this.sections.tabs.selected == 'contributors'
            || (this.sections.tabs.selected == 'mytutorials' && M.modFlagOn('qruqsp.tutorials', 0x10))
            ) {
            this.size = 'large narrowaside';
        } else {
            this.size = 'large';
        }
        var args = {'tnid':M.curTenantID, 'list':this.sections.tabs.selected};
        if( this.sections.tabs.selected == 'categories' ) {
            args['category'] = this.category;
        } else if( this.sections.tabs.selected == 'contributors' ) {
            args['contributor_tnid'] = this.contributor_tnid;
        } else if( this.sections.tabs.selected == 'mytutorials' ) {
            args['category'] = this.mycategory;
        }
        M.api.getJSONCb('qruqsp.tutorials.tutorialList', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_tutorials_main.menu;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');

    //
    // The panel to view a Tutorial
    //
    this.tutorial = new M.panel('Tutorial', 'qruqsp_tutorials_main', 'tutorial', 'mc', 'large mediumaside', 'sectioned', 'qruqsp.tutorials.main.tutorial');
    this.tutorial.data = null;
    this.tutorial.tutorial_id = 0;
    this.tutorial.seq_num = 0;
    this.tutorial.nplist = [];
    this.tutorial.sections = {
        'general':{'label':'', 'aside':'yes', 'fields':{
            'title':{'label':'Title', 'editable':'no', 'type':'text'},
            }},
        'synopsis':{'label':'', 'type':'html', 'aside':'yes',},
        'steps':{'label':'', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'noData':'No steps',
            },
        '_buttons':{'label':'', 'aside':'yes', 'buttons':{
            'unbookmark':{'label':'Remove Bookmark', 
                'visible':function() {return M.qruqsp_tutorials_main.tutorial.tutorial_id > 0 && M.qruqsp_tutorials_main.tutorial.data.bookmarked == 'yes' ? 'yes' : 'no'; },
                'fn':'M.qruqsp_tutorials_main.tutorial.bookmarkRemove();'},
            'bookmark':{'label':'Bookmark', 
                'visible':function() {return M.qruqsp_tutorials_main.tutorial.tutorial_id > 0 && M.qruqsp_tutorials_main.tutorial.data.library != null && M.qruqsp_tutorials_main.tutorial.data.library == 'yes' && M.qruqsp_tutorials_main.tutorial.data.bookmarked != 'yes' ? 'yes' : 'no'; },
                'fn':'M.qruqsp_tutorials_main.tutorial.bookmarkAdd();'},
            }},
//        'step_image_id':{'label':'', 'type':'html'},
        'step_image_id':{'label':'', 'type':'imageform', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'size':'large', 'controls':'no', 'history':'no'},
            }},
        'step_content':{'label':'', 'type':'html'},
        };
    this.tutorial.imageURL = function(s, i, d, img_id) {
        return M.api.getBinaryURL('qruqsp.tutorials.imageGet', {'tnid':M.curTenantID, 'tutorial_id':this.tutorial_id, 'image_id':img_id, 'version':'original'});
    }
    this.tutorial.fieldValue = function(s, i, d) { 
        if( s == 'step_image_id' && i == 'image_id' ) {
            if( this.data.steps[this.seq_num] != null && this.data.steps[this.seq_num].image1_id != null ) {
                return this.data.steps[this.seq_num].image1_id;
            }
        }
        return this.data[i]; 
    }
    this.tutorial.sectionData = function(s) {
        if( s == 'step_content' ) {
            if( this.data.steps[this.seq_num] != null && this.data.steps[this.seq_num].content != null ) {
                return this.data.steps[this.seq_num].html_content;
            } else {
                return '';
            }
        }
        return this.data[s];        
    }
    this.tutorial.cellValue = function(s, i, j, d) {
        if( s == 'steps' ) {
            return d.short_title;
        }
    }
    this.tutorial.rowFn = function(s, i, d) {
        if( s == 'steps' ) {
            return 'M.qruqsp_tutorials_main.tutorial.openStep(' + d.id + ');';
        }
    }
    this.tutorial.openStep = function(s) {
        for(var i in this.data.steps) {
            if( this.data.steps[i].id == s ) {
                this.seq_num = i;
                this.sections.step_content.label = this.data.steps[i].full_title;
                this.refreshSections(['steps', 'step_image_id', 'step_content']);
            }
        }
        this.seq_num = 0;
    }
    this.tutorial.rowClass = function(s, i, d) {
        if( s == 'steps' && d.id == this.data.steps[this.seq_num].id ) {
            return 'highlight';
        }
        return '';
    }
    this.tutorial.bookmarkAdd = function() {
        if( this.tutorial_id > 0 ) {
            M.api.getJSONCb('qruqsp.tutorials.bookmarkAdd', {'tnid':M.curTenantID, 'tutorial_id':this.tutorial_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.qruqsp_tutorials_main.tutorial;
                p.data.bookmarked = 'yes';
                p.refreshSection('_buttons');
            });
        }
    }
    this.tutorial.bookmarkRemove = function() {
        if( this.tutorial_id > 0 ) {
            M.api.getJSONCb('qruqsp.tutorials.bookmarkDelete', {'tnid':M.curTenantID, 'tutorial_id':this.tutorial_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.qruqsp_tutorials_main.tutorial;
                p.data.bookmarked = 'no';
                p.refreshSection('_buttons');
            });
        }
    }
    this.tutorial.open = function(cb, tid, list) {
        if( tid != null ) { this.tutorial_id = tid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('qruqsp.tutorials.tutorialGet', {'tnid':M.curTenantID, 'tutorial_id':this.tutorial_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_tutorials_main.tutorial;
            p.data = rsp.tutorial;
            p.openStep(0);
            p.refresh();
            p.show(cb);
        });
    }
    this.tutorial.save = function(cb) {
        if( cb == null ) { cb = 'M.qruqsp_tutorials_main.tutorial.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.tutorial_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('qruqsp.tutorials.tutorialUpdate', {'tnid':M.curTenantID, 'tutorial_id':this.tutorial_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('qruqsp.tutorials.tutorialAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_tutorials_main.tutorial.tutorial_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.tutorial.remove = function() {
        if( confirm('Are you sure you want to remove tutorial?') ) {
            M.api.getJSONCb('qruqsp.tutorials.tutorialDelete', {'tnid':M.curTenantID, 'tutorial_id':this.tutorial_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_tutorials_main.tutorial.close();
            });
        }
    }
    this.tutorial.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.tutorial_id) < (this.nplist.length - 1) ) {
            return 'M.qruqsp_tutorials_main.tutorial.save(\'M.qruqsp_tutorials_main.tutorial.open(null,' + this.nplist[this.nplist.indexOf('' + this.tutorial_id) + 1] + ');\');';
        }
        return null;
    }
    this.tutorial.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.tutorial_id) > 0 ) {
            return 'M.qruqsp_tutorials_main.tutorial.save(\'M.qruqsp_tutorials_main.tutorial.open(null,' + this.nplist[this.nplist.indexOf('' + this.tutorial_id) - 1] + ');\');';
        }
        return null;
    }
    this.tutorial.addClose('Close');
    this.tutorial.addButton('next', 'Next');
    this.tutorial.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Tutorial
    //
    this.edit = new M.panel('Tutorial', 'qruqsp_tutorials_main', 'edit', 'mc', 'medium mediumaside', 'sectioned', 'qruqsp.tutorials.main.edit');
    this.edit.data = null;
    this.edit.tutorial_id = 0;
    this.edit.nplist = [];
    this.edit.sections = {
        'general':{'label':'', 'aside':'yes', 'fields':{
            'title':{'label':'Title', 'required':'yes', 'type':'text'},
            'flags5':{'label':'Published on Website', 'type':'flagtoggle', 'default':'off', 'field':'flags', 'bit':0x10,
                'active':function() { return M.modOn('ciniki.web') ? 'yes' : 'no';}, 
                },
//            'date_published':{'label':'Date Published', 'type':'date'},
            }},
        '_categories':{'label':'Website Categories', 'aside':'yes', 
            'active':function() { return M.modOn('ciniki.web') && M.modFlagOn('qruqsp.tutorials', 0x10) ? 'yes' : 'no';}, 
            'fields':{
                'mycategories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category:'},
        }},
        '_synopsis':{'label':'Synopsis', 'aside':'yes', 'fields':{
            'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        'librarycategories':{'label':'Library Categories', 'type':'simplegrid', 'aside':'yes', 'num_cols':2,
            'visible':function() { return M.qruqsp_tutorials_main.edit.data.library != null && M.qruqsp_tutorials_main.edit.data.library == 'yes' ? 'yes' : 'no'; },
            'noData':'No Categories',
            'addTxt':'Add to Library Category',
            'addFn':'',
            },
        'steps':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'cellClasses':['multiline'],
            'noData':'No steps added',
            'addTxt':'Add Step',
            'addFn':'M.qruqsp_tutorials_main.edit.save("M.qruqsp_tutorials_main.addstep.open(\'M.qruqsp_tutorials_main.edit.open();\',0,M.qruqsp_tutorials_main.edit.tutorial_id,null);");',
            },
//        '_content':{'label':'Content', 'fields':{
//            'content':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
//            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.qruqsp_tutorials_main.edit.save();'},
            'publish':{'label':'Submit to Library', 
                'visible':function() {return M.qruqsp_tutorials_main.edit.tutorial_id > 0 && M.qruqsp_tutorials_main.edit.data.library == null ? 'yes' : 'no'; },
                'fn':'M.qruqsp_tutorials_main.edit.publish();'},
            'unpublish':{'label':'Remove from Library', 
                'visible':function() {return M.qruqsp_tutorials_main.edit.tutorial_id > 0 && M.qruqsp_tutorials_main.edit.data.library == 'yes' ? 'yes' : 'no'; },
                'fn':'M.qruqsp_tutorials_main.edit.unpublish();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.qruqsp_tutorials_main.edit.tutorial_id > 0 ? 'yes' : 'no'; },
                'fn':'M.qruqsp_tutorials_main.edit.remove();'},
            }},
        };
    this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.tutorials.tutorialHistory', 'args':{'tnid':M.curTenantID, 'tutorial_id':this.tutorial_id, 'field':i}};
    }
    this.edit.cellValue = function(s, i, j, d) {
        if( s == 'librarycategories' ) {
            if( d.category == '' ) {
                switch(j) {
                    case 0: return 'Request submitted';
                    case 1: return '';
                }
            }
            switch(j) {
                case 0: return d.category;
                case 1: return d.subcategory;
            }
        }
        if( s == 'steps' ) {
            return d.full_title;
        }
    }
    this.edit.rowFn = function(s, i, d) {
        if( s == 'steps' ) {
            return 'M.qruqsp_tutorials_main.edit.save("M.qruqsp_tutorials_main.step.open(\'M.qruqsp_tutorials_main.edit.open();\',' + d.id + ',M.qruqsp_tutorials_main.edit.tutorial_id,M.qruqsp_tutorials_main.edit.data.steps_ids);");';
        }
        if( s == 'librarycategories' && (M.userPerms&0x01) == 0x01 ) {
            return 'M.qruqsp_tutorials_main.edit.save("M.qruqsp_tutorials_main.library.open(\'M.qruqsp_tutorials_main.edit.open();\',' + d.id + ',M.qruqsp_tutorials_main.edit.tutorial_id);");';
        }
    }
    this.edit.publish = function() {
        if( this.tutorial_id > 0 && confirm("Are you sure you're ready to submit this tutorial to the library?") ) {
            M.api.getJSONCb('qruqsp.tutorials.libraryAdd', {'tnid':M.curTenantID, 'tutorial_id':this.tutorial_id, 'category':'', 'subscategory':''}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.qruqsp_tutorials_main.edit;
                alert("Thank you for submitted your tutorial, it has been sent for review.");
                M.qruqsp_tutorials_main.edit.open();
            });
        }
    }
    this.edit.open = function(cb, tid, list) {
        if( tid != null ) { this.tutorial_id = tid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('qruqsp.tutorials.tutorialGet', {'tnid':M.curTenantID, 'tutorial_id':this.tutorial_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_tutorials_main.edit;
            p.data = rsp.tutorial;
            p.sections._categories.fields.mycategories.tags = [];
            if( rsp.mycategories != null ) {
                for(var i in rsp.mycategories) {
                    p.sections._categories.fields.mycategories.tags.push(rsp.mycategories[i].category.name);
                }
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.edit.save = function(cb) {
        if( cb == null ) { cb = 'M.qruqsp_tutorials_main.edit.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.tutorial_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('qruqsp.tutorials.tutorialUpdate', {'tnid':M.curTenantID, 'tutorial_id':this.tutorial_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('qruqsp.tutorials.tutorialAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_tutorials_main.edit.tutorial_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.edit.remove = function() {
        if( confirm('Are you sure you want to remove tutorial?') ) {
            M.api.getJSONCb('qruqsp.tutorials.tutorialDelete', {'tnid':M.curTenantID, 'tutorial_id':this.tutorial_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_tutorials_main.edit.close();
            });
        }
    }
    this.edit.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.tutorial_id) < (this.nplist.length - 1) ) {
            return 'M.qruqsp_tutorials_main.edit.save(\'M.qruqsp_tutorials_main.edit.open(null,' + this.nplist[this.nplist.indexOf('' + this.tutorial_id) + 1] + ');\');';
        }
        return null;
    }
    this.edit.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.tutorial_id) > 0 ) {
            return 'M.qruqsp_tutorials_main.edit.save(\'M.qruqsp_tutorials_main.edit.open(null,' + this.nplist[this.nplist.indexOf('' + this.tutorial_id) - 1] + ');\');';
        }
        return null;
    }
    this.edit.addButton('save', 'Save', 'M.qruqsp_tutorials_main.edit.save();');
    this.edit.addClose('Cancel');
    this.edit.addButton('next', 'Next');
    this.edit.addLeftButton('prev', 'Prev');

    //
    // The panel to add a Step
    //
    this.addstep = new M.panel('Add Step', 'qruqsp_tutorials_main', 'addstep', 'mc', 'medium mediumaside', 'sectioned', 'qruqsp.tutorials.main.addstep');
    this.addstep.data = null;
    this.addstep.tutorial_id = 0;
    this.addstep.step_id = 0;
    this.addstep.nplist = [];
    this.addstep.sections = {
        '_tabs':{'label':'', 'type':'menutabs', 'selected':'new', 'tabs':{
            'new':{'label':'New Step', 'fn':'M.qruqsp_tutorials_main.addstep.switchTab("new");'},
            'existing':{'label':'Choose Existing', 'fn':'M.qruqsp_tutorials_main.addstep.switchTab("existing");'},
            }},
        '_image1_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 
            'visible':function() { return M.qruqsp_tutorials_main.addstep.sections._tabs.selected == 'new' ? 'yes' : 'no'; },
            'fields':{
                'image1_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                    'addDropImage':function(iid) {
                        M.qruqsp_tutorials_main.addstep.setFieldValue('image1_id', iid);
                        return true;
                        },
                    'addDropImageRefresh':'',
                 },
            }},
        'general':{'label':'', 'aside':'yes', 
            'visible':function() { return M.qruqsp_tutorials_main.addstep.sections._tabs.selected == 'new' ? 'yes' : 'no'; },
            'fields':{
                'title':{'label':'Title', 'type':'text'},
                'content_type':{'label':'Type', 'type':'toggle', 'toggles':{'10':'Step', '50':'Unnumbered'}},
                'sequence':{'label':'Order', 'type':'text', 'size':'small'},
            }},
        '_content':{'label':'Content', 
            'visible':function() { return M.qruqsp_tutorials_main.addstep.sections._tabs.selected == 'new' ? 'yes' : 'no'; },
            'fields':{
                'content':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_chooser':{'label':'Choose Existing Step', 
            'visible':function() { return M.qruqsp_tutorials_main.addstep.sections._tabs.selected == 'existing' ? 'yes' : 'no';},
            'fields':{
                'content_id':{'label':'', 'hidelabel':'yes', 'type':'select', 'options':[], 'complex_options':{'value':'id', 'name':'title'}},
            }},
        '_order':{'label':'', 'aside':'yes', 
            'visible':function() { return M.qruqsp_tutorials_main.addstep.sections._tabs.selected == 'existing' ? 'yes' : 'no'; },
            'fields':{
                'content_type':{'label':'Type', 'type':'toggle', 'toggles':{'10':'Step', '50':'Unnumbered'}},
                'sequence':{'label':'Order', 'type':'text', 'size':'small'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.qruqsp_tutorials_main.addstep.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.qruqsp_tutorials_main.addstep.step_id > 0 ? 'yes' : 'no'; },
                'fn':'M.qruqsp_tutorials_main.addstep.remove();'},
            }},
        };
    this.addstep.fieldValue = function(s, i, d) { return this.data[i]; }
    this.addstep.switchTab = function(t) {
        this.sections._tabs.selected = t;
        if( this.sections._tabs.selected == 'new' ) {
            this.size = 'medium mediumaside';
        } else {
            this.size = 'medium';
        }
        this.refresh();
        this.show();
    }
    this.addstep.open = function(cb, sid, tid, list) {
        if( sid != null ) { this.step_id = sid; }
        if( tid != null ) { this.tutorial_id = tid; }
        if( list != null ) { this.nplist = list; }
        if( this.sections._tabs.selected == 'new' ) {
            this.size = 'medium mediumaside';
        } else {
            this.size = 'medium';
        }
        var content_id = this.formValue('content_id');
        M.api.getJSONCb('qruqsp.tutorials.stepGet', {'tnid':M.curTenantID, 'tutorial_id':this.tutorial_id, 'step_id':0, 'content':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_tutorials_main.addstep;
            p.data = rsp.step;
            if( content_id != null ) {
                p.data.content_id = content_id;
            }
            p.sections._chooser.fields.content_id.options = {};
            if( rsp.contentlist != null ) {
                p.sections._chooser.fields.content_id.options = rsp.contentlist;
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.addstep.save = function(cb) {
        if( cb == null ) { cb = 'M.qruqsp_tutorials_main.addstep.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.sections._tabs.selected == 'existing' ) {
            var c = this.serializeFormSection('yes', '_chooser')
                + this.serializeFormSection('yes', '_order');
        } else {
            var c = this.serializeFormSection('yes', '_image1_id')
                + this.serializeFormSection('yes', 'general')
                + this.serializeFormSection('yes', '_content');
        }
        M.api.postJSONCb('qruqsp.tutorials.stepAdd', {'tnid':M.curTenantID, 'tutorial_id':this.tutorial_id}, c, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.qruqsp_tutorials_main.addstep.close();
        });
    }
    this.addstep.addButton('save', 'Save', 'M.qruqsp_tutorials_main.addstep.ave();');
    this.addstep.addClose('Cancel');

    //
    // The panel to edit Step
    //
    this.step = new M.panel('Step', 'qruqsp_tutorials_main', 'step', 'mc', 'medium mediumaside', 'sectioned', 'qruqsp.tutorials.main.step');
    this.step.data = null;
    this.step.tutorial_id = 0;
    this.step.step_id = 0;
    this.step.content_id = 0;
    this.step.nplist = [];
    this.step.sections = {
        '_image1_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'image1_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.qruqsp_tutorials_main.step.setFieldValue('image1_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
             },
        }},
        'general':{'label':'', 'aside':'yes', 'fields':{
            'title':{'label':'Title', 'type':'text'},
            'content_type':{'label':'Type', 'type':'toggle', 'toggles':{'10':'Step', '50':'Unnumbered'}},
            'sequence':{'label':'Order', 'type':'text', 'size':'small'},
            }},
        '_content':{'label':'Content', 'fields':{
            'content':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        'tutorials':{'label':'This step also used in', 'type':'simplegrid', 'aside':'yes', 'num_cols':1,
            'visible':function() { return M.qruqsp_tutorials_main.step.data.tutorials != null ? 'yes' : 'no';},
            'noData':'Not currently used in other tutorials',
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.qruqsp_tutorials_main.step.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.qruqsp_tutorials_main.step.step_id > 0 ? 'yes' : 'no'; },
                'fn':'M.qruqsp_tutorials_main.step.remove();'},
            }},
        };
    this.step.fieldValue = function(s, i, d) { return this.data[i]; }
    this.step.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.tutorials.stepHistory', 'args':{'tnid':M.curTenantID, 'step_id':this.step_id, 'field':i}};
    }
    this.step.cellValue = function(s, i, j, d) {
        if( s == 'tutorials' ) {
            switch(j) {
                case 0: return d.title;
            }
        }
    }
    this.step.open = function(cb, sid, tid, list) {
        if( sid != null ) { this.step_id = sid; }
        if( tid != null ) { this.tutorial_id = tid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('qruqsp.tutorials.stepGet', {'tnid':M.curTenantID, 'tutorial_id':this.tutorial_id, 'step_id':this.step_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_tutorials_main.step;
            p.data = rsp.step;
            p.refresh();
            p.show(cb);
        });
    }
    this.step.save = function(cb) {
        if( cb == null ) { cb = 'M.qruqsp_tutorials_main.step.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.step_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('qruqsp.tutorials.stepUpdate', {'tnid':M.curTenantID, 'step_id':this.step_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('qruqsp.tutorials.stepAdd', {'tnid':M.curTenantID, 'tutorial_id':this.tutorial_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_tutorials_main.step.step_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.step.remove = function() {
        if( confirm('Are you sure you want to remove step?') ) {
            M.api.getJSONCb('qruqsp.tutorials.stepDelete', {'tnid':M.curTenantID, 'step_id':this.step_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_tutorials_main.step.close();
            });
        }
    }
    this.step.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.step_id) < (this.nplist.length - 1) ) {
            return 'M.qruqsp_tutorials_main.step.save(\'M.qruqsp_tutorials_main.step.open(null,' + this.nplist[this.nplist.indexOf('' + this.step_id) + 1] + ');\');';
        }
        return null;
    }
    this.step.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.step_id) > 0 ) {
            return 'M.qruqsp_tutorials_main.step.save(\'M.qruqsp_tutorials_main.step.open(null,' + this.nplist[this.nplist.indexOf('' + this.step_id) - 1] + ');\');';
        }
        return null;
    }
    this.step.addButton('save', 'Save', 'M.qruqsp_tutorials_main.step.save();');
    this.step.addClose('Cancel');
    this.step.addButton('next', 'Next');
    this.step.addLeftButton('prev', 'Prev');

    //
    // The panel to edit library entries
    //
    this.library = new M.panel('Library Category', 'qruqsp_tutorials_main', 'library', 'mc', 'medium', 'sectioned', 'qruqsp.tutorials.main.library');
    this.library.data = null;
    this.library.library_id = 0;
    this.library.tutorial_id = 0;
    this.library.nplist = [];
    this.library.sections = {
        'entry':{'label':'', 'aside':'yes', 'fields':{
            'category':{'label':'Category', 'type':'text'},
            'subcategory':{'label':'Sub Category', 'type':'text'},
            'sequence':{'label':'Order', 'type':'text', 'size':'small'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.qruqsp_tutorials_main.library.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.qruqsp_tutorials_main.library.library_id > 0 ? 'yes' : 'no'; },
                'fn':'M.qruqsp_tutorials_main.library.remove();'},
            }},
        };
    this.library.fieldValue = function(s, i, d) { return this.data[i]; }
    this.library.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.tutorials.libraryHistory', 'args':{'tnid':M.curTenantID, 'library_id':this.library_id, 'field':i}};
    }
    this.library.open = function(cb, lid, tid, list) {
        if( lid != null ) { this.library_id = lid; }
        if( tid != null ) { this.tutorial_id = tid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('qruqsp.tutorials.libraryGet', {'tnid':M.curTenantID, 'library_id':this.library_id, 'tutorial_id':this.tutorial_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_tutorials_main.library;
            p.data = rsp.library;
            p.refresh();
            p.show(cb);
        });
    }
    this.library.save = function(cb) {
        if( cb == null ) { cb = 'M.qruqsp_tutorials_main.library.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.library_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('qruqsp.tutorials.libraryUpdate', {'tnid':M.curTenantID, 'library_id':this.library_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('qruqsp.tutorials.libraryAdd', {'tnid':M.curTenantID, 'tutorial_id':this.tutorial_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_tutorials_main.library.library_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.library.remove = function() {
        if( confirm('Are you sure you want to remove tutorial category?') ) {
            M.api.getJSONCb('qruqsp.tutorials.libraryDelete', {'tnid':M.curTenantID, 'library_id':this.library_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_tutorials_main.library.close();
            });
        }
    }
    this.library.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.library_id) < (this.nplist.length - 1) ) {
            return 'M.qruqsp_tutorials_main.library.save(\'M.qruqsp_tutorials_main.library.open(null,' + this.nplist[this.nplist.indexOf('' + this.library_id) + 1] + ');\');';
        }
        return null;
    }
    this.library.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.library_id) > 0 ) {
            return 'M.qruqsp_tutorials_main.library.save(\'M.qruqsp_tutorials_main.library.open(null,' + this.nplist[this.nplist.indexOf('' + this.library_id) - 1] + ');\');';
        }
        return null;
    }
    this.library.addButton('save', 'Save', 'M.qruqsp_tutorials_main.library.save();');
    this.library.addClose('Cancel');
    this.library.addButton('next', 'Next');
    this.library.addLeftButton('prev', 'Prev');

    //
    // Start the app
    // cb - The callback to run when the user leaves the main panel in the app.
    // ap - The application prefix.
    // ag - The app arguments.
    //
    this.start = function(cb, ap, ag) {
        args = {};
        if( ag != null ) {
            args = eval(ag);
        }
        
        //
        // Create the app container
        //
        var ac = M.createContainer(ap, 'qruqsp_tutorials_main', 'yes');
        if( ac == null ) {
            alert('App Error');
            return false;
        }
        if( (M.userPerms&0x01) == 0x01 ) {
            this.edit.sections.librarycategories.addFn = 'M.qruqsp_tutorials_main.edit.save("M.qruqsp_tutorials_main.library.open(\'M.qruqsp_tutorials_main.edit.open();\',0,M.qruqsp_tutorials_main.edit.tutorial_id);");';
        } else {
            this.edit.sections.librarycategories.addFn = '';
        }
        this.menu.open(cb);
    }
}
