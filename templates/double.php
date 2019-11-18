<?php
//
// Description
// -----------
// This function will output a pdf document as a series of thumbnails.
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_tutorials_templates_double($ciniki, $tnid, $categories, $args) {

    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheOriginal');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');

    //
    // Load tenant details
    //
    $rc = ciniki_tenants_tenantDetails($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {   
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
    }

    //
    // Load INTL settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Create a custom class for this document
    //
    class MYPDF extends TCPDF {
        public $tenant_name = '';
        public $title = '';
        public $coverpage = 'no';
        public $toc = 'no';
        public $toc_categories = 'no';
        public $doublesided = 'no';
        public $pagenumbers = 'yes';
        public $footer_height = 0;
        public $header_height = 0;
        public $footer_text = '';
        public $current_side = 'left';
        public $offset = 0;
        public $newpage = 'no';

        public function Header() {
            $this->SetFont('helvetica', 'B', 20);
            $this->SetLineWidth(0.25);
            $this->SetDrawColor(125);
            $this->setCellPaddings(5,1,5,2);
            if( $this->title != '' ) {
                $this->Cell(0, 22, $this->title, 'B', false, 'C', 0, '', 0, false, 'M', 'M');
            }
            $this->setCellPaddings(0,0,0,0);
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            // Set font
            if( $this->pagenumbers == 'yes' ) {
                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 8);
                $this->Cell(0, 8, $this->footer_text . '  --  Page ' . $this->getAliasNumPage().' of '.$this->getAliasNbPages(), 
                    0, false, 'C', 0, '', 0, false, 'T', 'B');
            }
        }

        public function AdvancePage() {
            if( $this->current_side == 'left' ) {
                $this->current_side = 'right';
                $this->offset = $this->middle_margin + ($this->getPageWidth()/2);
            } else {
                $this->AddPage();
                $this->current_side = 'left';
                $this->offset = $this->left_margin;
            }
            $this->SetY($this->header_height);
            $this->newpage = 'yes';
        }

        public function AddMySection($ciniki, $tnid, $category_title, $title, $section) {
            $this->title = $title;

            // Add a table of contents bookmarks
            if( $this->toc == 'yes' && $category_title !== NULL ) {
                if( $this->toc_categories == 'yes' && $category_title != '' ) {
                    $this->Bookmark($category_title, 0, 0, '', '');
                }
                if( $this->toc_categories == 'yes' ) {
                    $this->Bookmark($this->title, 1, 0, '', '');
                } else {
                    $this->Bookmark($this->title, 0, 0, '', '');
                }
            }

            $page_width = ($this->getPageWidth()/2) - $this->middle_margin - $this->right_margin;
            $page_height = $this->getPageHeight() - $this->top_margin - $this->header_height - $this->footer_height;

            //
            // Calculate how much height is required for this step
            //
            $title_content_height = 0;
            if( $section['subtitle'] != '' ) {
                $this->SetFont('', 'B', '16');
                $title_content_height += $this->getStringHeight($page_width, $section['subtitle']);
                $title_content_height += 4;
            }
            if( $section['content'] != '' ) {
                $section['content'] = trim($section['content']) . "\n";
                $this->SetFont('', '', '12');
                $title_content_height += $this->getStringHeight($page_width, $section['content']);
                $section['content'] = preg_replace("/\n/", "<br/>", $section['content']);
            }
            $image = null;
            $img_box_height = 0;
            if( $section['image_id'] > 0 ) {
                $rc = ciniki_images_loadImage($ciniki, $tnid, $section['image_id'], 'original');
                if( $rc['stat'] == 'ok' ) {
                    $image = $rc['image'];
                    $img_height = (($page_width / $image->getImageWidth()) * $image->getImageHeight());
                    //
                    // Check if the image will need as much height as possible, and new page should be started
                    //
                    if( $img_height > ($page_height - $title_content_height) ) {
                        if( $this->newpage == 'no' ) {
                            //
                            // Advance page
                            //
                            $this->AdvancePage();
                        }
                        $img_box_height = $page_height - $title_content_height;
                    }
                    //
                    // Check if there is not room on this page
                    //
                    elseif( $img_height > ($this->getPageHeight() - $this->GetY() - $title_content_height - $this->footer_height) ) {
                        if( $this->newpage == 'no' ) {
                            $this->AdvancePage();
                        }
                        $img_box_height = $this->getPageHeight() - $this->GetY() - $title_content_height - $this->footer_height - 10;
                    } else {
                        $img_box_height = $this->getPageHeight() - $this->GetY() - $title_content_height - $this->footer_height - 20;
                    }
                }
            } 

            if( $image == null ) {
                if( $title_content_height > ($this->getPageHeight() - $this->getY() - $title_content_height - $this->footer_height) ) {
                    if( $this->newpage == 'no' ) {
                        $this->AdvancePage();
                    }
                }
            }

            //
            // Add the image title
            //
            if( $section['subtitle'] != '' ) {
                $this->SetX($this->offset);
                $this->SetFont('', 'B', '16');
                if( $this->newpage == 'no' ) {
                    $this->Ln(4);
                }
                $this->Cell($page_width, 8, $section['subtitle'], 0, 1, 'L', false, '', 0, false, 'T', 'T');
            }
        
            //
            // Add the content
            //
            if( $section['content'] != '' ) {
                $this->SetX($this->offset);
                $this->SetFont('', '', '12');
                $this->MultiCell($page_width, 8, $section['content'], 0, 'L', false, 1, '', '', true, 0, true, true, 0, 'T');
            }

            //
            // Add the image
            //
            if( $image != null ) {
                $this->SetX($this->offset);
                $this->SetLineWidth(0.25);
                $this->SetDrawColor(50);
                $img = $this->Image('@'.$image->getImageBlob(), '', '', $page_width, $img_box_height, 
                    'JPEG', '', '', false, 300, '', false, false, 
                    array('LTRB'=>array('color'=>array(128,128,128))), 'CT');
//                    array('LTRB'=>array('color'=>array(128,128,128))), 'CT');
                $this->Ln();
                $this->Ln(2);
            }
            $this->newpage = 'no';
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('L', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    $pdf->title = $args['title'];

    // Set PDF basics
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->footer_text = $tenant_details['name'];
    $pdf->SetTitle($args['title']);
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->header_height = 25;
    $pdf->footer_height = 10;
    $pdf->top_margin = 10;
    $pdf->left_margin = 12;
    $pdf->right_margin = 12;
    $pdf->middle_margin = 5;
    $pdf->offset = $pdf->left_margin;
    $pdf->SetMargins($pdf->left_margin, $pdf->header_height, $pdf->right_margin);
    $pdf->SetHeaderMargin($pdf->top_margin);
//  $pdf->SetFooterMargin($pdf->footer_height);
    $pdf->setPageOrientation('L', false);
    $pdf->SetFooterMargin(0);

    // Set font
    $pdf->SetFont('times', 'BI', 10);
    $pdf->SetCellPadding(0);

    if( isset($args['doublesided']) ) {
        $pdf->doublesided = $args['doublesided'];
    }

    //
    // Check if coverpage is to be outputed
    //
    if( isset($args['coverpage']) && $args['coverpage'] == 'yes' ) {
        $pdf->coverpage = 'yes';
        $pdf->title = '';
        if( isset($args['title']) && $args['title'] != '' ) {
            $title = $args['title'];
            $pdf->footer_text .= '  --  ' . $args['title'];
        } else {
            $title = "Tutorials";
        }
        $pdf->pagenumbers = 'no';
        $pdf->AddPage('P');
        
        if( isset($args['coverpage-image']) && $args['coverpage-image'] > 0 ) {
            $img_box_width = 180;
            $img_box_height = 150;
            $rc = ciniki_images_loadCacheOriginal($ciniki, $tnid, $args['coverpage-image'], 2000, 2000);
            if( $rc['stat'] == 'ok' ) {
                $image = $rc['image'];
                $pdf->SetLineWidth(0.25);
                $pdf->SetDrawColor(50);
                $img = $pdf->Image('@'.$image, '', '', $img_box_width, $img_box_height, 'JPEG', '', '', false, 300, '', false, false, 0, 'CT');
            }
            $pdf->SetY(-50);
        } else {
            $pdf->SetY(-100);
        }


        $pdf->SetFont('times', 'B', '30');
        $pdf->MultiCell(180, 5, $title, 0, 'C', false, 1, '', '', true, 0, false, true, 0, 'T');
        $pdf->endPage();
        if( $pdf->doublesided == 'yes' ) {
            $pdf->AddPage();
            $pdf->Cell(0, 0, '');
            $pdf->endPage();
        }
    }
    $pdf->pagenumbers = 'yes';

    //
    // Add the tutorials items
    //
    $page_num = 1;
    $pdf->toc_categories = 'no';
    if( count($categories) > 1 ) {
        $pdf->toc_categories = 'yes';
    }
    if( isset($args['toc']) && $args['toc'] == 'yes' ) {
        $pdf->toc = 'yes';
    }

    if( $pdf->toc == 'yes' && $pdf->doublesided == 'yes' ) {
        $pdf->AddPage();
        $pdf->Cell(0, 0, '');
        $pdf->endPage();
    }

    foreach($categories as $cid => $category) {
        $tutorial_num = 1;
        foreach($category['tutorials'] as $tid => $tutorial) {
            //
            // Start a new page for each tutorial
            //
            $pdf->title = $tutorial['title'];
            $pdf->AddPage('L');
            $pdf->SetFillColor(255);
            $pdf->SetTextColor(0);
            $pdf->SetDrawColor(51);
            $pdf->SetLineWidth(0.15);

            if( isset($args['removetext']) && $args['removetext'] != '' ) {
                $tutorial['title'] = preg_replace('/' . $args['removetext'] . '/', '', $tutorial['title']);
            }
            if( isset($tutorial['steps']) ) {
                $pdf->title = $tutorial['title'];

                // 
                // Add introduction to tutorial
                //
                if( $tutorial['synopsis'] != '' ) {
                    $pdf->AddMySection($ciniki, $tutorial['tnid'], 
                        ($tutorial_num==1?$category['name']:NULL), 
                        $tutorial['title'], 
                        array(
                            'image_id' => 0, 
                            'subtitle' => '', 
                            'content' => $tutorial['synopsis'],
                            )
                        ); 
                }
                
                //
                // Count total steps, then setup titles
                //
                $num_steps = 0;
                $step_num = 0;
                $substep_num = 0;
                foreach($tutorial['steps'] as $sid => $step) {
                    if( $step['content_type'] == 10 ) {
                        $num_steps++;
                    }
                }
                foreach($tutorial['steps'] as $sid => $step) {
                    if( $step['content_type'] == 10 ) {
                        $step_num++;
                        $substep_num = 0;
                        $full_title = 'Step ' . $step_num . ' of ' . $num_steps . ' - ' . $step['title'];
                    }
                    elseif( $step['content_type'] == 20 ) {
                        $substep_num++;
                        $full_title = 'Step ' . $step_num . chr(96+$substep_num) . ' of ' . $num_steps . ' - ' . $step['title'];
                    }
                    else {
                        $full_title = $step['title'];
                    }

                    $pdf->AddMySection($ciniki, $tutorial['tnid'], 
                        (($tutorial_num==1&&$step_num<2)?$category['name']:($step_num<2?'':NULL)), 
                        $tutorial['title'], 
                        array(
                            'image_id' => $step['image1_id'], 
                            'subtitle' => $full_title,
                            'content' => $step['content'],
                            )
                        );
                }
            }
            $tutorial_num++;    
        }
    }

    if( isset($args['toc']) && $args['toc'] == 'yes' ) {
        $pdf->title = 'Table of Contents';
        $pdf->addTOCPage('P');
        $pdf->Ln(8);
        $pdf->SetFont('', '', 14);
        $pdf->pagenumbers = 'no';
        $pdf->addTOC((($pdf->coverpage=='yes')?($pdf->doublesided=='yes'?3:2):0), 'courier', '.', 'INDEX', 'B');
        $pdf->pagenumbers = 'yes';
        $pdf->endTOCPage();
    }

    return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
