<?php
include_once('./class/bookMulticolumnClass.php');
include_once('./class/pimClass.php');
include_once('./class/fpdf.php');
include_once('./class/logsClass.php');

set_time_limit(600);
ini_set('memory_limit','1000M');

$pim = new pim;

class PDF extends FPDF
{
 var $currentY;
 var $Xoffset;
 
    function newPageWithHeader()
    {
        $this->AddPage();
        $this->Xoffset=0;
        if($this->PageNo()%2==0){$this->Xoffset=15;}

        
        $this->SetFont('Arial','',10);
        $this->SetXY(5+$this->Xoffset, 5);
        $this->SetDrawColor(0,0,0);
        $this->SetFillColor(210,210,210);
        $this->SetTextColor(0,0,0);
        $this->SetLineWidth(.3);        
        $this->Cell(30,7,'Vehicle',1,1,'C',true);
        $this->SetXY(35+$this->Xoffset, 5);
        $this->Cell(136,7,'Notes',1,1,'C',true);
        $this->SetXY(165+$this->Xoffset, 5);
        $this->Cell(30,7,'Parts',1,1,'C',true);
        // Save ordinate
        $this->currentY=10;
    }

    function renderMakeName($make)
    {
        $this->SetFont('Arial','B',22);
        $this->SetXY(5+$this->Xoffset, $this->currentY+10);        
        $this->Cell(0,0,$make);
        $this->currentY+=10;
    }   

    function renderModelName($model,$continued)
    {
        $this->SetFont('Arial','B',16);
        $this->SetXY(5+$this->Xoffset, $this->currentY+8);        
        $this->Cell(0,0,$model);
        if($continued)
        {
         $w=$this->GetStringWidth($model);
         $this->SetFont('Arial','',10);
         $this->SetXY(7+$w+$this->Xoffset, $this->currentY+8);        
         $this->Cell(0,0,"(continued)");
        }
        $this->currentY+=12;
    }   

    function drawLastYearblockBorder()
    {
        $this->Line(5+$this->Xoffset, $this->currentY, 35+$this->Xoffset, $this->currentY);
    }


    
    function renderContinuedOnNextPage($model)
    {
        $this->SetFont('Arial','',9);
        $this->SetXY(7+$this->Xoffset, $this->currentY+3);
        $this->Cell(0,0,$model.' continued on next page...');
    }
    
    function renderRow($years,$notes,$columns,$columnsettings,$renderyeartop, $renderyearbottom, $renderyears)
    {
        // xoffset of 0 will center the content with 10mm of left and right margin
        
     $notewidth=$columnsettings['widths'][0];
     $notesandpartcolumnswidth=0;
     
     foreach($columnsettings['widths'] as $width)
     {
        $notesandpartcolumnswidth+=$width;
     }     
     
     // find the tallest stack of parts
     $biggestpartscount=0; foreach($columns as $columnkey=>$parts){if($biggestpartscount>count($parts)){$biggestpartscount=count($parts);}}
     $maxheight=(4*$biggestpartscount)+4;

     $this->Line(35+$this->Xoffset, $this->currentY, ($notewidth)+$this->Xoffset, $this->currentY); // draw top boder
     if($renderyears)
     {
        $this->SetFont('Arial','',12);
        $w=$this->GetStringWidth($years);
        $this->SetXY((19-($w/2))+$this->Xoffset, $this->currentY+4);// center the text in the cell
        $this->Cell(0,0,$years);
     }

     $notesheight=0;
     if($notes!='')
     {
        $this->SetFont('Arial','',10);
        $this->SetXY(36+$this->Xoffset, $this->currentY+1);
        $tempy=$this->GetY();
        $this->MultiCell($notewidth,4,$notes,0,'L',false );
        $notesheight=4+$this->GetY()-$tempy;
     }
     if($notesheight>$maxheight){$maxheight=$notesheight;}

     
     $columnindex=1;
     $columnsx=36+$this->Xoffset+$notewidth;
             
     foreach($columnsettings['keys'] as $columnkey)
     {
        $columnwidth=$columnsettings['widths'][$columnindex];
        $columnsx+=$columnwidth;
        
        if(array_key_exists($columnkey, $columns))
        {
            
            if(count($columns[$columnkey]))
            {
               $this->SetFont('Arial','',11);
               foreach($columns[$columnkey] as $i=>$part)
               {
                  $w=$this->GetStringWidth($part);
                  $this->SetXY(($columnsx-($w/2))+$this->Xoffset, ($this->currentY+(($i+1)*4)));// center the text in the cell
                  $this->Cell(0,0,$part);
               }
            }        
        }        
        $this->Line($columnsx+$this->Xoffset-($columnwidth/2), $this->currentY, $columnsx+$this->Xoffset-($columnwidth/2), $this->currentY+$maxheight);// vertical line between notes and parts
        $columnindex++;
     }
     // total avail width (qualiriers and parts): 160mm (6.3in)
     
     
     
     
     $this->Line(5+$this->Xoffset, $this->currentY, 5+$this->Xoffset, $this->currentY+$maxheight); // left vertical border of yearblock
//     $this->Line(195+$this->Xoffset, $this->currentY, 195+$this->Xoffset, $this->currentY+$maxheight); //right vertical border of ending part column
        
     $this->Line(35+$this->Xoffset, $this->currentY, 35+$this->Xoffset, $this->currentY+$maxheight); // vertical line between yearblock and notes
//     $this->Line(165+$this->Xoffset, $this->currentY, 165+$this->Xoffset, $this->currentY+$maxheight);// vertical line between notes and parts
        
     if($renderyeartop){$this->Line(5+$this->Xoffset, $this->currentY, 35+$this->Xoffset, $this->currentY);}
     if($renderyearbottom){$this->Line(5+$this->Xoffset, $this->currentY+$maxheight, 35+$this->Xoffset, $this->currentY+$maxheight);}
     $this->Line(35+$this->Xoffset, $this->currentY+$maxheight, 35+$notesandpartcolumnswidth+$this->Xoffset, $this->currentY+$maxheight); //horizontal line closing bottom of notes and parts
     
     $this->currentY=$this->currentY+$maxheight;
    }
}





//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'exportForPrintProcess.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$book = new book;


$receiverprofileid=intval($_GET['receiverprofile']);
$profile=$pim->getReceiverprofileById($receiverprofileid);
$profiledata=$profile['data'];


$profileelements=explode(';',$profiledata);
$keyedprofile=array(); foreach($profileelements as $profileelement){$bits=explode(':',$profileelement);if(count($bits)==2){$keyedprofile[$bits[0]]=$bits[1];}}

$filename='catalog.pdf';
if(array_key_exists('PrintedDocumentFilename', $keyedprofile)){$filename=$keyedprofile['DocumentTitle'].'_'.random_int(100000, 999999).'.pdf';}

$categories=$pim->getReceiverprofilePartcategories($receiverprofileid);
//$categories=array(114,115);
$content=$book->getContent($categories);
//print_r($content['Honda']);

//extract a dictinct list of all column keys
$columnkeys=array();
foreach($content as $make => $models)
{
 foreach($models as $model=>$blocks)
 {
  foreach($blocks as $block)
  {
   foreach($block['qualifierblocks'] as $columns)
   {
    foreach($columns as $columnkey=>$column)
    {
     if(!in_array($columnkey,$columnkeys)){$columnkeys[]=$columnkey;}     
    }       
   }
  }
 }
}

//print_r($columnkeys);

$limitY=250;
$pdf = new PDF('P','mm','Letter');
$pdf->Xoffset=0;
$pdf->currentY=0;
$pdf->SetTitle('AirQualitee Cabin Air Filters');
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(false, 0);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAuthor('AutoPartSource');
$pdf->newPageWithHeader();
$columnsettings=array('keys'=>$columnkeys,'widths'=>array(60,20,20,20,20));



foreach($content as $make => $models)
{
 //if($make!='Honda'){continue;}
 if($pdf->currentY>($limitY-30)){$pdf->newPageWithHeader();}
 $pdf->renderMakeName($make);

  
 foreach($models as $model=>$blocks)
 {
      
   //need to render a new model name - see if we're close enough to the page end to break early
   // so that the model name and the table content are on the next page together
  if($pdf->currentY>$limitY-15){$pdf->newPageWithHeader(); $pdf->renderMakeName($make);}  
  $pdf->renderModelName($model, false);
     
  foreach($blocks as $block)
  {
   $rownumber=0;
   foreach($block['qualifierblocks'] as $qualifiers=>$columns)
   {
    $freshlybroken=false;
    if($pdf->currentY>$limitY)
    {
         // render "continued on next page" message at
     $pdf->renderContinuedOnNextPage($model);
     $pdf->drawLastYearblockBorder();
     $pdf->newPageWithHeader();
     $pdf->renderMakeName($make);
     $pdf->renderModelName($model, true);
     $freshlybroken=true;
    }
     
    switch(count($block['qualifierblocks']))
    {
        case 1:
           $drawtopborder=true; $drawbottomborder=true; $renderyears=true; break;
         
        case 2:
           if($rownumber==0)
           {// on the first row of a merged pair
               $drawtopborder=true; $drawbottomborder=false; $renderyears=true;
           }
           else
           {// second row of a merged pair
               $drawtopborder=false; $drawbottomborder=true; $renderyears=false;
           } 
           break;

        case 3:
            // three row block
            if($rownumber==0)
            {//on first row
               $drawtopborder=true; $drawbottomborder=false; $renderyears=true;
            }
            else
            {// not on first row 
               $renderyears=false;
               if($rownumber==count($block['qualifierblocks'])-1)
               {//on last row
                   $drawtopborder=false; $drawbottomborder=true;                 
               }
               else
               {// not on last row (on the middle one)
                   $drawtopborder=false; $drawbottomborder=false;
               }
            }
            
            break;

            default :
            //4 or more rows in this block
            
            if($rownumber==0)
            {//on first row
               $drawtopborder=true; $drawbottomborder=false; $renderyears=true;
            }
            else
            {// not on first row 
               $renderyears=false;
               if($rownumber==count($block['qualifierblocks'])-1)
               {//on last row
                   $drawtopborder=false; $drawbottomborder=true;                 
               }
               else
               {// not on last row (on the middle one)
                   $drawtopborder=false; $drawbottomborder=false;
               }
            }
                
            break;
    }
     
    if($freshlybroken){$drawtopborder=true;$renderyears=true;}

    $niceyears=$block['startyear'].' - '.$block['endyear']; if($block['startyear']==$block['endyear']){$niceyears=$block['startyear'];}
     
    $pdf->renderRow($niceyears, $qualifiers, $columns, $columnsettings, $drawtopborder, $drawbottomborder,$renderyears);
     
    $rownumber++;
   }
  }
 }
}


$pdf->Output('D',$filename);


?>